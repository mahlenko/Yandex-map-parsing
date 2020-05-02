<?php

/**
 * Используем сервис антикапчи
 */
class Captcha
{
    private $service = '2captcha';
//    private $service = 'anticaptcha';
    private $captcha_service;

    public function __construct() {
        if (!file_exists(realpath(__DIR__) . '/Captcha_'. $this->service .'.php')) {
            exit('Файл с антикапчей: ' . '/Captcha_'. $this->service .'.php' . ' не найден.');
        }

        $nameClass = 'Captcha_'. $this->service;
        include_once realpath(__DIR__) . '/Captcha_'. $this->service .'.php';
        $this->captcha_service = new $nameClass();
    }

    /**
     * Выбрать другой сервис для проверки каптчи
     * @param string $service
     */
    public function setService($service = 'anticaptcha') {
        $this->service = $service;
    }

    /**
     * Получить результат обхода капчи
     * @param string $task_id
     * @return mixed
     */
    public function getToken($task_id = '') {
        return $this->captcha_service->getToken($task_id);
    }

    /**
     * @param $file_image
     * @return mixed
     */
    public function image($file_image)
    {
        return $this->captcha_service->image($file_image);
    }

    /**
     * reCaptcha v2
     * @param string $key
     * @param string $page_url
     * @return array
     */
    public function reCaptcha_v2($key = '', $page_url = '') {
        return $this->captcha_service->reCaptcha_v2($key, $page_url);
    }

    /**
     * reCaptcha v3
     * @param string $key
     * @param string $action
     * @param string $page_url
     * @return array
     */
    public function reCaptcha_v3($key, $action, $page_url) {
        return $this->captcha_service->reCaptcha_v3($key, $action, $page_url);
    }

    /**
     * Если каптча не подошла, сообщаем об этом сервису.
     * @param $task_id
     * @return mixed
     */
    public function badCaptcha($task_id) {
        return $this->captcha_service->badCaptcha($task_id);
    }

    /**
     * Получен хороший ответ с рабочим кодом капчи
     * @param $task_id
     * @return mixed
     */
    public function goodCaptcha($task_id) {
        return $this->captcha_service->goodCaptcha($task_id);
    }


}
