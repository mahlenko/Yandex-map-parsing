<?php

/**
 * Сервис антикапчи 2captcha.com
 */
class Captcha_anticaptcha
{
    private $requestUrl = 'https://api.anti-captcha.com/';
    private $clientKey = '068a609377a4f895dbb71949f3d49bc4';
    private $errors = [];
    private $lang = [];


    public function __construct()
    {
        $this->lang = include_once 'AntiCaptcha_lang_ru.php';
    }

    /**
     * Google reCaptcha v2
     *
     * @param string $google_key
     * @param string $url
     * @return mixed
     */
    public function reCaptcha_v2($google_key = '', $url = '')
    {
        $query = [
            'clientKey'  => $this->clientKey,
            'languagePool' => 'ru',
            'task' => [
                'type'         => 'NoCaptchaTaskProxyless',
                'websiteURL'   => $url,
                'websiteKey'   => $google_key,
            ]
        ];

        $result = $this->send('createTask', $query);
        if ($result->errorId) {
            return [
                'status' => 'error',
                'text'   => $this->lang['ERRORS'][$result->errorCode]
            ];
        }

        return [
            'result'  => $result->taskId ? true : false,
            'task_id' => $result->taskId
        ];
    }

    /**
     *
     * @param string $google_key
     * @param string $url
     * @return array
     */
    public function reCaptcha_v3($google_key = '', $action = '', $url = '')
    {
        $query = [
            'clientKey'  => $this->clientKey,
            'languagePool' => 'ru',
            'task' => [
                'type'         => 'RecaptchaV3TaskProxyless',
                'websiteURL'   => $url,
                'websiteKey'   => $google_key,
                'pageAction'   => $action,
                'minScore'     => 0.3
            ]
        ];

        $result = $this->send('createTask', $query);
        if ($result->errorId) {
            return [
                'status' => 'error',
                'text'   => $this->lang['ERRORS'][$result->errorCode]
            ];
        }

        return [
            'result'  => $result->taskId ? true : false,
            'task_id' => $result->taskId
        ];
    }


    /**
     * Получение результата обхода капчи
     * @param string $task_id
     * @return array
     */
    public function getToken($task_id = '')
    {
        $task_id = intval($task_id);
        if (!$task_id) {
            return [
                'status' => 'error',
                'text'   => 'Не задана ID задания'
            ];
        }

        /* Создаем запрос */
        $request = $this->send('getTaskResult', [
            'clientKey'  => $this->clientKey,
            'taskId' => $task_id,
        ]);

        /* Обработка ошибок */
        if ($request->errorId) {
            return [
                'status' => 'error',
                'text'   => $this->lang['ERRORS'][$request->errorCode]
            ];
        }

        /* Полученный токен */
        $token = null;
        if (isset($request->solution->gRecaptchaResponse)) {
            $token = $request->solution->gRecaptchaResponse;
        }

        return [
            'status' => $request->status == 'processing' ? 'processing' : 'ok',
            'token'  => $token
        ];
    }


    public function badCaptcha($id = '') {}
    public function goodCaptcha($id = '') {}

    /**
     * Отправка запроса
     * @param $method
     * @param $data
     * @param $only_body
     * @return bool|string
     */
    public function send($method, $data, $only_body = true)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->requestUrl . $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($ch, CURLOPT_ENCODING,"gzip,deflate");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_NOBODY, $only_body ? false : true);
        curl_setopt($ch, CURLOPT_HEADER, $only_body ? false : true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);

        /* Отключаем проверку SSL если запрос на защищенный адрес */
        $parse_url = parse_url($this->requestUrl);
        if ($parse_url['scheme'] == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        $output = curl_exec($ch);
        if ( $output === false ) {
            $this->errors = curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        return json_decode($output);
    }

}