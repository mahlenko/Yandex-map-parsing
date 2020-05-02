<?php

use Curl\Curl;

/**
 * Отправляет запросы с прокси и через cURL
 */
class Requests
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * User Agent
     * @var string
     */
    private $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 YaBrowser/19.9.0.1768 Yowser/2.5 Safari/537.36';

    /**
     * @var CI_Controller
     */
    private $ci;

    /**
     * Proxy
     * @var
     */
    public $proxy;

    /**
     * Requests constructor.
     * @param array $params
     * @throws ErrorException
     */
    public function __construct(array $params)
    {
        if (! array_key_exists('proxy', $params))
            die('Укажите для работы прокси.');

        // CI_Controller
        $this->ci =& get_instance();
        $this->ci->load->model('Yandex/proxy_m');
        $this->ci->load->library('Yacaptcha');

        // прокси сервер
        $this->proxy = $params['proxy'];

        $this->curl = new Curl();
        $this->setProxy($this->proxy);
        $this->setCookie();
        // X-Real-Ip требуется того, чтобы яндекс запомнил, что мы решили капчу.
        $this->curl->setHeader('X-Real-Ip', $this->proxy->server);
        $this->curl->setUserAgent($this->user_agent);
    }


    /**
     * Отправить GET запрос
     * @param $server
     * @param $request
     * @param bool $json
     * @return null
     */
    public function get($server, $request, $json = true)
    {
        if ($json) $this->curl->setHeader('Content-type', 'application/json; utf-8');
        $this->curl->get($server, $request);

        return $this->checkResponse('get', $server, $request, $json);
    }


    /**
     * Отправить POST запрос
     * @param $server
     * @param $request
     * @param bool $json
     * @return null
     */
    public function post($server, $request, $json = true)
    {
        if ($json) $this->curl->setHeader('Content-type', 'application/json; utf-8');
        $this->curl->post($server, $request);

        return $this->checkResponse('post', $server, $request, $json);
    }

    /**
     * Установит данные прокси
     * @param stdClass $proxy
     */
    private function setProxy() {
        $this->curl->setProxy($this->proxy->server, $this->proxy->port, $this->proxy->login, $this->proxy->password);
        $this->curl->setProxyTunnel();
    }

    /**
     * Установит куки в запрос
     */
    private function setCookie() {
        if (! empty($this->proxy->cookies)) {
            foreach (json_decode($this->proxy->cookies) as $name => $value) {
                $this->curl->setCookie($name, $value);
            }
        }
    }


    /**
     * Проверка результата.
     * Обработка капчи и csrfToken'a
     * @param string $method
     * @param $server
     * @param $request
     * @param $json
     * @return Curl
     */
    private function checkResponse($method = 'get', $server, $request, $json)
    {
        // обработка полученной капчи
        if (isset($this->curl->response->captcha)) {
            $this->ci->yacaptcha->solve($this->curl->response->captcha, $this);
            $this->proxy = $this->ci->proxy_m->get($this->proxy->proxy_id)[0];

            $this->setProxy();
            $this->setCookie();

            return $this->{$method}($server, $request, $json);
        }

        // яндекс вернул новый токен
        if (isset($this->curl->response->csrfToken)) {
            $result = $this->ci->proxy_m->save($this->proxy->proxy_id, [
                'token' => $this->curl->response->csrfToken
            ]);

            if (! $result) {
                return false;
            }

            // установим новый токен и перенастроим прокси
            $this->proxy->token = $this->curl->response->csrfToken;
            $this->setProxy();
            $this->setCookie();

            return $this->{$method}($server, $request, $json);
        }

        return $this->curl;
    }
}
