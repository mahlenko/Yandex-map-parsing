<?php

/**
 * Сервис антикапчи 2captcha.com
 */
class Captcha_2captcha {

    /* Отправляем запрос на прохождение капчи */
    private $requestUrl = 'http://2captcha.com/in.php';

    /* Проверка и получение результата */
    private $resultUrl  = 'http://2captcha.com/res.php';

    /* Токен аккаунта 2captcha */
    private $token = 'da541f85e10daaf313e9593e4a9b6bb6';

    /* Возвращаем JSON */
    private $is_json = true;

    private $lang = [
        'RESULT_ERROR' => [
            'CAPCHA_NOT_READY' => 'Ваша капча еще не решена.',
            'ERROR_CAPTCHA_UNSOLVABLE' => 'Мы не можем решить вашу капчу-три наших работника не смогли решить ее или мы не получили ответ в течение 90 секунд (300 секунд для ReCaptcha V2).',
            'ERROR_WRONG_USER_KEY' => 'Вы указали пользовательский ключ в неправильном формате, оно должно содержать 32 символа.',
            'ERROR_KEY_DOES_NOT_EXIST' => 'Ключ, который вы предоставили, не существует.',
            'ERROR_WRONG_ID_FORMAT' => 'Вы предоставили Captcha ID в неправильном формате. Идентификатор может содержать только цифры.',
            'ERROR_WRONG_CAPTCHA_ID' => 'Недействительный Captcha ID.',
            'ERROR_BAD_DUPLICATES' => 'Не удалось распознать каптчу со 100% точностью.',
            'REPORT_NOT_RECORDED' => 'Ошибка возвращается в ваш запрос жалобы, если вы уже жаловались на множество правильно решенных каптчей.',
            'ERROR_IP_ADDRES' => 'Ваш IP-адрес не соответствует IP-адресу вашего pingback IP или домена.',
            'ERROR_TOKEN_EXPIRED' => 'Предоставленное вами значение вызова истекло'
            ]
    ];


    /**
     * @param string $image_url
     * @return array
     */
    public function image($image_url = '')
    {
        $image_body = file_get_contents($image_url);

        $query = [
            'key'       => $this->token,
            'method'    => 'base64',
            'regsense'  => 1,
            'body'      => base64_encode($image_body),
            'json'      => intval($this->is_json)
        ];

        $response = @file_get_contents(
            $this->requestUrl.'?'.http_build_query($query),
            false,
            stream_context_create(
                [
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/x-www-form-urlencoded'
                    ],
                ]
            )
        );

        if (! $response) {
            return ['result' => false];
        }

        $request = json_decode($response);

        return [
            'result'  => isset($request->request) ? true : false,
            'task_id' => isset($request->request) ? $request->request : null
        ];
    }

    /**
     * Google reCaptcha v2
     *
     * @param string $google_key
     * @param string $url
     * @return mixed
     */
    public function reCaptcha_v2($google_key, $url)
    {
        $query = [
            'version'   => 'v2',
            'pageurl'   => $url,
            'googlekey' => $google_key,
            'key'       => $this->token,
            'method'    => 'userrecaptcha',
            'json'      => intval($this->is_json)
        ];

        $request = json_decode(
            file_get_contents($this->requestUrl.'?'.http_build_query($query))
        );

        return [
            'result'  => $request->status ? true : false,
            'task_id' => $request->request
        ];
    }


    /**
     * Google reCaptcha v3
     * @param string $action
     * @param string $url     Страница с которой читаем капчу
     * @return array
     */
    public function reCaptcha_v3($google_key, $action, $url)
    {
        $query = [
            'min_score' => 0.3,
            'version'   => 'v3',
            'pageurl'   => $url,
            'action'    => $action,
            'googlekey' => $google_key,
            'key'       => $this->token,
            'method'    => 'userrecaptcha',
            'json'      => intval($this->is_json),
//            'proxy'     => '3XMbNx:J2IWqkBMjI@45.89.19.32:16912',
//            'proxytype' => 'http'
        ];

        $request = json_decode(
            file_get_contents($this->requestUrl.'?'.http_build_query($query))
        );

        return [
            'result'  => $request->status ? true : false,
            'task_id' => $request->status ? intval($request->request) : $request->request
        ];
    }


    /**
     * Получить результат прохождения капчи
     * @param string $id
     * @return array
     */
    public function getToken($id = '')
    {
        $query = [
            'id'      => $id,
            'action'  => 'get',
            'key'     => $this->token,
            'json'    => intval($this->is_json)
        ];

        if (! $response = file_get_contents($this->resultUrl.'?'.http_build_query($query)))
        {
            return false;
        }

        $request = json_decode($response);

        /* Обработка ошибок */
        if ($request->status == 0) {
            return [
                'status' => $request->request == 'CAPCHA_NOT_READY' ? 'processing' : 'error',
                'text' => $this->lang['RESULT_ERROR'][$request->request]
            ];
        }

        if ($request->status) {
            return [
                'status' => 'ok',
                'token' => $request->request
            ];
        }
    }


    /**
     * Сообщить что капча не подошла
     * @param string $id
     * @return array
     */
    public function badCaptcha($id = '') {
        $query = [
            'id'      => $id,
            'action'  => 'reportbad',
            'key'     => $this->token,
            'json'    => intval($this->is_json)
        ];

        $request = json_decode(
            file_get_contents($this->resultUrl.'?'.http_build_query($query))
        );

        return $request->status == 1 ? true : false;
    }

    /**
     * Сообщить что капча не подошла
     * @param string $id
     * @return array
     */
    public function goodCaptcha($id = '') {
        $query = [
            'id'      => $id,
            'action'  => 'reportgood',
            'key'     => $this->token,
            'json'    => intval($this->is_json)
        ];

        $request = json_decode(
            file_get_contents($this->resultUrl.'?'.http_build_query($query))
        );

        return $request->status == 1 ? true : false;
    }

}