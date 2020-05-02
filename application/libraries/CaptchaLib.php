<?php

class CaptchaLib
{
    /**
     * Таймаут между проверками
     * @var int
     */
    public $timeout = 5;

    /**
     * Количество попыток
     * @var int
     */
    public $attempt = 10;

    /**
     * @var CI_Controller
     */
    private $ci;

    public function __construct() {
        $this->ci =& get_instance();

        // подключаем библиотеку антикапчи
        $this->ci->load->library('AntiCaptcha/captcha');
    }

    /**
     * @param string $url_image
     * @return array|bool
     */
    public function solve(string $url_image)
    {
        /* отправляем капчу на решение */
        $request = $this->ci->captcha->image($url_image);

        /* ждем решения задачи */
        sleep($this->timeout);

        /* ожидаем решение каптчи */
        $captcha_text = $this->waitResult($request['task_id']);
        if (! $captcha_text) return false;

        return [
            'captcha_text' => $captcha_text,
            'task_id' => $request['task_id']
        ];
    }

    /**
     * Отправим успешный результат обработки каптчи
     * @param int $task_id
     * @return mixed
     */
    public function niceCaptcha(int $task_id) {
        return $this->ci->captcha->goodCaptcha($task_id);
    }

    /**
     * Отправим плохой результат решения каптчи
     * @param int $task_id
     * @return mixed
     */
    public function badCaptcha(int $task_id) {
        return $this->ci->captcha->badCaptcha($task_id);
    }

    /**
     * Ожидаем решение капчи
     * @param int $task_id
     * @return string|bool
     */
    private function waitResult(int $task_id)
    {
        $response = [];

        while (true) {
            /* получаем решение капчи */
            $response = $this->ci->captcha->getToken($task_id);

            /* если каптча решена, остановим цикл */
            if ($response['status'] == 'ok') {
                break;
            }

            /* каптча еще не решена */
            if ($this->attempt <= 0) {
                return false;
            }
            $this->attempt--;

            sleep($this->timeout);
        }

        return $response['status'] == 'ok' ? $response['token'] : false;
    }
}