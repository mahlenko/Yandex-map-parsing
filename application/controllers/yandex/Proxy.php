<?php

use \Curl\Curl;
use DOMWrap\Document;

/**
 * Управление списком прокси
 */
class Proxy extends MY_Controller
{
    /**
     * Proxy constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Yandex/proxy_m');
    }


    /**
     * Список прокси
     * @return mixed
     */
    public function index()
    {
        $proxy_list = $this->proxy_m->get();
        $this->data('proxy_list', $proxy_list);

        return $this->twig->display('yandex/proxy/index', $this->data());
    }


    /**
     * Редактировать прокси
     * @param int|null $proxy_id
     * @return mixed
     */
    public function edit(int $proxy_id = null)
    {
        $this->form_validation->set_rules('server', 'Адрес сервера', 'trim|required');
        $this->form_validation->set_rules('port', 'Порт сервера', 'trim|is_natural_no_zero|required');
        $this->form_validation->set_rules('login', '', 'trim|required');
        $this->form_validation->set_rules('password', '', 'trim');
        if ($this->form_validation->run()) {
            // данные из формы
            $proxy_data = $this->input->post(['server', 'port', 'login', 'password'], true);
            if (empty($proxy_data['password'])) {
                unset($proxy_data['password']);
            }

            $result = $this->proxy_m->save($proxy_id, $proxy_data);
            if ($result) {
                redirect('yandex/proxy');
            }
        }

        if ($proxy_id) {
            $proxy = $this->proxy_m->get($proxy_id);
            if ($proxy) $this->data('PROXY', $proxy[0]);
        }

        return $this->twig->display('yandex/proxy/edit', $this->data());
    }


    /**
     * Загрузить прокси через API
     */
    public function load()
    {
        $server = 'https://www.proxy.house/api/open/v1/';
        $token = '9355_1518010466_7bc2f3db23aaad5b0ece3696a41ca1f8757f50ad';

        $curl = new Curl();
        $curl->setHeader('Auth-Token', $token);
        $curl->get($server . 'proxy/list', ['tariff_id' => 3]);

        if ($curl->response->successful)
        {
            foreach($curl->response->data->proxies as $proxy_response)
            {
                $proxy = [
                    'lock'          => 0,
                    'active'        => $proxy_response->active,
                    'tariff_id'     => $proxy_response->tariff_id,
                    'login'         => $proxy_response->login,
                    'password'      => $proxy_response->password,
                    'server'        => $proxy_response->ip,
                    'port'          => $proxy_response->http_port,
                    'socks_port'    => $proxy_response->socks_port,
                    'comment'       => $proxy_response->comment,
                    'token'         => null,
                    'cookies'       => json_encode(['yandexuid' => '3791945321575831998']),
                    'expired'       => $proxy_response->expired_at
                ];

                //
                $proxy_db = $this->proxy_m->get(null, [
                    'server' => $proxy['server'],
                    'login' => $proxy['login']
                ]);

                // обновляем запись
                if ($proxy_db) {
                    unset($proxy['lock'], $proxy['cookie']);

                    $result = $this->proxy_m->save($proxy_db[0]->proxy_id, $proxy);
                    if (! $result) {
                        dd($this->db->errors());
                    }

                    continue;
                }

                // создаем новую запись
                $result = $this->proxy_m->save(null, $proxy);
                if (! $result)
                {}
            }
        }

        // переадресовываем на обновление токенов
        // redirect('yandex/proxy');
        redirect('yandex/proxy/getTokens');
    }


    /**
     * Получит токен для прокси
     * @param int|null $proxy_id
     * @throws ErrorException
     */
    public function getTokens(int $proxy_id = null)
    {
        $proxy_list = $this->proxy_m->get($proxy_id);

        foreach ($proxy_list as $proxy)
        {
            $curl = new Curl();
            $curl->setProxy($proxy->server, $proxy->port, $proxy->login, $proxy->password);
            $curl->setProxyTunnel();
            $curl->get('https://yandex.ru/maps/10733/klin');

            if (! $curl->isError() && ! empty($curl->response))
            {
                // парсим html
                $doc = new Document();
                $doc->html($curl->response);
                $result = $doc->find('script.config-view')->html();

                if ($result) {
                    // сохраняем токен
                    $result = json_decode($result);
                    if (isset($result->csrfToken)) {
                        // сохраним yandex uid в куках
                        if ($curl->getCookie('yandexuid')) {
                            $this->proxy_m->setCookie($proxy->proxy_id, 'yandexuid', $curl->getCookie('yandexuid'));
                        }

                        // сохраним токен
                        if (!$this->proxy_m->save($proxy->proxy_id, ['token' => $result->csrfToken])) {
                            die($this->proxy_m->error());
                        }
                    }
                }
            }
        }

        redirect('yandex/proxy');
    }
}