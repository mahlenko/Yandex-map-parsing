<?php
if (!defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * Характеристики автомобиля
 *
 * @package     Sputnik
 * @subpackage  CodeIgniter
 * @category    Core
 * @author      Mahlenko Sergey <sm@weblive.by>
 */
use \Curl\Curl;
use DiDom\Document;

class Connect extends MY_Model
{
    /**
     * @var string
     */
    private $server = 'https://auto.ru/-/ajax/desktop/getBreadcrumbsWithFilters/';

    /**
     * @var string
     */
    private $captcha_result_url = 'https://auto.ru/checkcaptcha';

    /**
     * @var Curl
     */
    private $curl;

    /* Подключение через прокси */
    private $proxy;

    public function __construct()
    {
        $this->load->model('Yandex/proxy_m');
        $this->load->library('CaptchaLib');

        $proxy = $this->proxy_m->get();

        /* Случайный прокси */
        $proxy = $proxy[mt_rand(0, count($proxy)-1)];
        $this->proxy = $proxy;

        /*  */
        $curl = new Curl();

        /* Настройки */
        $curl->setProxy($proxy->server, $proxy->port, $proxy->login, $proxy->password);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 YaBrowser/20.2.0.1145 Yowser/2.5 Safari/537.36');
        foreach (json_decode($this->proxy->cookies) as $name => $value) {
            $curl->setCookie($name, $value);
        }
        $this->curl = $curl;
    }

    /**
     * @param $data
     * @return |null
     * @throws ErrorException
     */
    public function run($data)
    {
        /* Запрос */
        $this->curl->setHeader('Content-Type', 'application/json');
        $this->curl->post($this->server, $data);

        /* успешное получение */
        if ($this->curl->getHttpStatusCode() === 200) {
            return $this->curl->response;
        }

        /* Возможно получена капча, проверим это и попробуем её решить */
        $this->httpRequestCaptcha($this->server);

        /* Возвращаем результат */
        return $this->curl->error ? $this->curl->errorMessage : $this->curl->response;
    }

    /**
     * @param string $url
     * @return bool
     */
    private function httpRequestCaptcha(string $url)
    {
        /* */
        $location = $this->curl->responseHeaders['location'] ?? '';

        /* */
        $cookie = $this->curl->responseHeaders['set-cookie'] ?? '';
        if (!empty($cookie)) {
            foreach (explode(';', $cookie) as $str_cookie) {
                list($key, $value) = explode('=', trim($str_cookie));
                $this->proxy_m->setCookie($this->proxy->proxy_id, $key, $value);
            }
        }

        if (strpos($location, 'captcha')) {
            /* сохраним информацию, что на ip была выдана каптча */
            $this->proxy_m->save($this->proxy->proxy_id, [
                'captcha_detect' => $this->proxy->captcha_detect + 1
            ]);

            /* попробуем решить капчу */
            return $this->resolveCaptcha($location, $url);
        }
    }

    /**
     * Решение капчи
     * @param string $captcha_page
     * @param string $redirect
     * @return bool
     */
    private function resolveCaptcha(string $captcha_page, string $redirect)
    {
        $document = new Document();

        /* ------------------------------------------------------------------ */
        /* откроем страницу каптчи и получим данные из контента               */
        /* ------------------------------------------------------------------ */
        $this->curl->setHeader('content-type', 'text/html');
        $this->curl->get($captcha_page);

        $document->loadHtml($this->curl->response);

        $key = $document->find('.form__key')[0]->attr('value');

        $search_img = $document->find('.captcha__image img');
        $captcha_image = $search_img[0]->attr('src');

        /* ------------------------------------------------------------------ */
        $parse_url = parse_url($captcha_page);
        parse_str($parse_url['query'], $query_parse);

        /* ------------------------------------------------------------------ */
        $captcha = $this->captchalib->solve($captcha_image);
        if (! $captcha) {
            dump('Не удалось решить или дождаться решения каптчи.');
            return false;
        }

        /* ------------------------------------------------------------------ */
        /* Получив решение капчи, отправляем ответ на сервер                  */
        /* ------------------------------------------------------------------ */
        $query = [
            'key'     => $key,
            'rep'     => $captcha['captcha_text'],
            'retpath' => $query_parse['retpath'],
        ];

        $this->curl->get($this->captcha_result_url, $query);

        /* предварительное успешное прохождение капчи */
        if ($this->curl->httpStatusCode == 302) {
            /**
             * Если переадресация яндекса ведет на страницу нашего запроса
             * значит капча решена верно, нужно сохранить куки (spravka) для
             * последующих запросов на карты (инф. из API YandexXML).
             * Таким образом Яндекс будет понимать что мы прошли тест капчей.
             */
            $location_array = parse_url($this->curl->responseHeaders['location']);
            $ret_path = parse_url($query['retpath']);

            if ($location_array['path'] == $ret_path['path']) {
                /* сообщим капча успешно пройдена */
                $this->captchalib->niceCaptcha($captcha['task_id']);

                if ($spravka_cookie = $this->curl->getCookie('spravka')) {
                    /* изменим cookie для следущих запросов */
                    $this->proxy_m->setCookie($this->proxy->proxy_id, 'spravka', $spravka_cookie);

                    return true;
                }
            }

            /* сообщим что капча не прошла проверку */
            //$this->captchalib->badCaptcha($captcha['task_id']);
        }

        return false;
    }

}