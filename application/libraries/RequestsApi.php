<?php

use Curl\Curl;
use DiDom\Document;

/**
 * Класс для выполнения Curl запросов.
 * С проверкой и решением каптчи.
 *
 * $this->load->library('RequestsApi', []);
 */
class RequestsApi
{
    /**
     * @var Curl
     */
    public $curl;

    /**
     * @var CI_Controller
     */
    private $ci;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $proxy = [];

    /**
     * Будем использовать прокси
     * @var bool
     */
    private $use_proxy = true;

    /**
     * RequestsApi
     * $options => [ // параметры по-умолчанию, можно переопределить в запросе
     *      'headers' => [],
     *      'cookies' => []
     * ]
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->ci =& get_instance();
        $this->ci->load->model('Yandex/proxy_m');
        $this->proxy = $this->ci->proxy_m->getFree();
        if (! $this->proxy) {
            dd('Нет свободного прокси сервера.');
        }

        if (array_key_exists('use_proxy', $options)) {
            $this->use_proxy = $options['use_proxy'];
        }

        try {
            $this->curl = $this->defaultCurl($options);
        } catch (ErrorException $e) {
            dd($e->getMessage());
        }
    }

    /**
     * Выполнит GET запрос
     * @param string $url
     * @param array $data
     * @param array $header
     * @param array $cookie
     * @return bool
     */
    public function get(string $url, array $data = [], $header = [], $cookie = [])
    {
        return $this->request('get', $url, $data, $header, $cookie);
    }

    /**
     * Выполнит POST запрос
     * @param string $url
     * @param array $data
     * @param array $header
     * @param array $cookie
     * @return bool
     */
    public function post(string $url, array $data = [], $header = [], $cookie = [])
    {
        return $this->request('post', $url, $data, $header, $cookie);
    }

    /**
     * Возвращает результат запроса
     * @return null
     */
    public function response()
    {
        return $this->curl->response;
    }

    /**
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $header
     * @param array $cookie
     * @return bool
     */
    private function request(string $method, string $url, array $data = [], $header = [], $cookie = [])
    {
        if (! in_array($method, ['get', 'post'])) {
            return false;
        }

        $this->setHeadersCurl($this->curl, $header);
        $this->setCookiesCurl($this->curl, $cookie);
        $this->curl->$method($url, $data);

        return $this->curl->getHttpStatusCode() === 200 ? true : false;
    }

    /* ---------------------------------------------------------------------- */

    /**
     * Попытка расшифровать каптчу если есть для этого все данные
     * @return array|bool
     */
    public function captchaSolve()
    {
        if (empty($this->curl->responseHeaders['location'])) {
            $this->errors[] = 'Найденный `location` пустой. Не возможно определить результат.';
            return false;
        }

        if (strpos($this->curl->responseHeaders['location'], 'captcha') === false) {
            $this->errors[] = 'Запрос не переадресовывает на прохождение каптчи.';
            return false;
        }

        $location = $this->curl->responseHeaders['location'];

        /* мы знаем что Auto.ru выдает капчу без ajax */
        $captcha = $this->captchaHtmlData($location);
        if (! $captcha) {
            $this->errors[] = 'Не удалось получить данные для решения каптчи.';
            return false;
        }

        /* отправляем запрос на решение капчи, из url с изображением */
        $this->ci->load->library('CaptchaLib', [], 'captchalib');
        $captcha_solve = $this->ci->captchalib->solve($captcha['captcha_image_url']);
        if ($captcha_solve && array_key_exists('captcha_text', $captcha_solve)) {
            $captcha['solve'] = $captcha_solve;
            return $captcha;
        }

        return false;
    }

    /**
     * Проверка капчи.
     * В случае успеха обновит используемые куки для следующего запроса.
     * @param array $captcha_solve
     * @return bool
     */
    public function captchaCheck(array $captcha_solve)
    {
        /* отправляем запрос сервису на проверку каптчи */
        $this->get($captcha_solve['check_url'], [
            'key'     => $captcha_solve['key'],
            'rep'     => $captcha_solve['solve']['captcha_text'],
            'retpath' => $captcha_solve['retpath'],
        ]);

        if ($this->curl->httpStatusCode == 302) {
            /**
             * Если переадресация яндекса ведет на страницу нашего запроса
             * значит каптча решена верно, нужно сохранить куки (spravka) для
             * последующих запросов на карты (инф. из API YandexXML).
             * Таким образом Яндекс будет понимать что мы прошли тест капчей.
             */
            $location_array = parse_url($this->curl->responseHeaders['location']);
            $ret_path = parse_url($captcha_solve['retpath']);

            if ($location_array['path'] == $ret_path['path'])
            {
                /* сообщим что каптча успешно пройдена */
                $this->ci->captchalib->niceCaptcha($captcha_solve['solve']['task_id']);

                $spravka_cookie = $this->curl->getCookie('spravka');
                /* изменим cookie для следущих запросов */
                if ($spravka_cookie) {
                    $this->ci->proxy_m->setCookie($this->proxy->proxy_id, 'spravka', $spravka_cookie);
                    return true;
                }
            } else {
                /* сообщим что каптча решена не верно */
                $this->ci->captchalib->badCaptcha($captcha_solve['solve']['task_id']);
            }
        }

        dump('Ошибка при проверки капчи.');
        return false;
    }

    /**
     * @param string $url
     * @return array
     */
    private function captchaHtmlData(string $url)
    {
        $result = $this->get($url, [], ['content-type' => 'text/html']);

        if (! $result) {
            /* ... не удалось ... может в следующий запрос */
            return [];
        }

        /* Создаем DOM документ и помещаем в него html страницы, для парсинга */
        $document = new Document();
        $document->loadHtml($this->response());

        /* Сформируем ссылку для проверки капчи */
        $parse_url = parse_url($url);
        parse_str($parse_url['query'], $query);

        return [
            'key'               => $document->find('.form__key')[0]->attr('value'),
            'check_url'         => $parse_url['scheme'] .'://'. $parse_url['host'] .'/checkcaptcha',
            'retpath'           => $query['retpath'],
            'captcha_image_url' => $document->find('.captcha__image img')[0]->attr('src')
        ];
    }

    /* ---------------------------------------------------------------------- */

    /**
     * @param array $options
     * @return Curl
     * @throws ErrorException
     */
    private function defaultCurl(array $options = [])
    {
        $curl = new Curl();

        // если есть прокси, работаем через него
        if ($this->proxy && $this->use_proxy) {
            $curl->setProxy(
                $this->proxy->server,
                $this->proxy->port,
                $this->proxy->login,
                $this->proxy->password);
        }

        if (array_key_exists('headers', $options)) $options['headers'] = [];
        $this->setHeadersCurl($curl, $options);

        if (! array_key_exists('captcha', $options)) $options['captcha'] = [];
        $this->setCookiesCurl($curl, $options['captcha']);

        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 YaBrowser/20.2.0.1145 Yowser/2.5 Safari/537.36');

        return $curl;
    }

    /**
     * Установит в запросы Headers
     * @param Curl $curl
     * @param array $headers
     */
    private function setHeadersCurl(Curl &$curl, array $headers = [])
    {
        $defaults = [
            'content-type' => 'application/json'
        ];

        if ($headers) {
            $defaults = array_merge($defaults, $headers);
        }

        foreach ($defaults as $key => $value) {
            $curl->setHeader($key, $value);
        }
    }

    /**
     * Установит в запросы Cookies
     * @param Curl $curl
     * @param array $cookies
     */
    private function setCookiesCurl(Curl &$curl, array $cookies = [])
    {
        $defaults = [];
        if (! empty($this->proxy->cookies)) {
            $proxy_cookies = json_decode($this->proxy->cookies, true);
            if ($proxy_cookies) {
                $defaults = array_merge($defaults, $proxy_cookies);
            }
        }

        if ($cookies) {
            $defaults = array_merge($defaults, $cookies);
        }

        foreach ($defaults as $key => $value) {
            $curl->setCookie($key, $value);
        }
    }

}
