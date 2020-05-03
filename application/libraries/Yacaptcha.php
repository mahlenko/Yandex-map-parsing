<?php

class Yacaptcha
{
    /**
     * Количество попыток ожидания решения капчи
     * @var int
     */
    private $limit = 10;

    /**
     * Время ожидания перед следующей попыткой проверки решения капчи
     * @var int
     */
    private $timeout = 5;

    /**
     * Ссылка на отправку в Яндекс решённой капчи
     * @var string
     */
    private $server = 'https://yandex.ru/checkcaptcha';

    /**
     * @var CI_Controller
     */
    private $ci;

    public function __construct()
    {
        $this->ci =& get_instance();

        // подключаем библиотеку антикапчи
        $this->ci->load->library('AntiCaptcha/captcha');

        // модель для обновления прокси
        $this->ci->load->model('Yandex/proxy_m');
    }

    /**
     * Решить капчу Яндекса
     * @param $captcha
     * @param $curl
     * @return bool
     * @throws ErrorException
     */
    public function solve($captcha, Requests $requests)
    {
        // формируем запрос для решения капчи
        $anticaptcha = $this->getSolve($captcha);
        if (! $anticaptcha) {
            if (is_cli()) {
                dump_error('Капча не решена.');
            }
            return false;
        }

        dump_info('Получили решение капчи. '. $anticaptcha['query']['rep']);

        // отправим запрос с ответом капчи в Яндекс
        $curl = $requests->get($this->server, $anticaptcha['query']);

        /**
         * Проверяем результат, должен вернуть переадресацию.
         */
        if ($curl->httpStatusCode == 302)
        {
            // разбираем результат перенаправления
            $location_array = parse_url($curl->responseHeaders['location']);
            $ret_path = parse_url($anticaptcha['query']['retpath']);

            //dump($curl);

            /**
             * Если переадресация яндекса ведет на страницу нашего запроса
             * значит капча решена верно, нужно сохранить куки (spravka) для
             * последующих запросов на карты (инф. из API YandexXML).
             * Таким образом Яндекс будет понимать что мы прошли тест капчей.
             */
            if ($location_array['path'] == $ret_path['path']) {
                // отправляем уведомление, что капча правильная
                $this->ci->captcha->goodCaptcha($anticaptcha['token']);

                // сохраняем в куки, чтобы больше не спрашивал
                $spravka = $curl->getCookie('spravka');
                if ($spravka) {
                    $this->ci->proxy_m->setCookie($requests->proxy->proxy_id, 'spravka', $spravka);
                    if ($i = $curl->getCookie('i')) {
                        $this->ci->proxy_m->setCookie($requests->proxy->proxy_id, 'i', $i);
                    }

                    // отметим выдачу капчи
                    $this->ci->proxy_m->save($requests->proxy->proxy_id, [
                        'captcha_detect' => $requests->proxy->captcha_detect + 1
                    ]);

                    if (is_cli()) {
                        dump_success('Капча успешно прошла проверку.');
                        //dump('Капча успешно прошла проверку.');
                    }

                    return true;
                }
                return false;
            } else {
                // отправляем уведомление, что капча НЕ правильная
                $this->ci->captcha->badCaptcha($anticaptcha['token']);

                if (is_cli()) {
                    dump_error('Капча не прошла проверку проверку Яндексом.');
                }

                return false;
            }
        }
    }


    /**
     * Решит капчу и вернет массив для отправки в Яндекс
     * @param $captcha
     * @return array|bool
     */
    private function getSolve($captcha)
    {
        // количество попыток ожидания
        $limit = $this->limit;

        // время ожидания запроса
        $timeout = $this->timeout;

        if (is_cli()) {
            dump_warning('Отправка запроса на решение капчи.');
        }

        // отправляем капчу на распознание
        $send_captcha = $this->ci->captcha->image($captcha->{'img-url'});

        // даем время на решение капчи
        sleep($timeout);

        // проверяем решена ли наша капча
        while (true) {
            // пытаемся получить ответ
            $code = $this->ci->captcha->getToken($send_captcha['task_id']);

            // если капча решена, останавливаем
            if ($code['status'] == 'ok') {
                // dump('Результат капчи: ' . $code['token']);
                break;
            }

            // останавливаем
            if ($limit <= 0) {
                return false;
            }

            $limit--;
            sleep($timeout);
        }

        // укажем страницу для переадресации (та же от куда парсим компании)
        $parse_page = parse_url($captcha->{'captcha-page'});
        parse_str($parse_page['query'], $query);
        $retpath = $query['retpath'];

        if (! array_key_exists('token', $code)) {
            return false;
        }

        // готовим данные для запроса
        return [
            'query' => [
                'rep' => $code['token'],
                'key' => $captcha->key,
                'retpath' => $retpath
            ],
            'token' => $code['token']
        ];
    }

}