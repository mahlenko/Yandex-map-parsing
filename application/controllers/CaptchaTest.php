<?php

class CaptchaTest extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('AntiCaptcha/captcha');
    }

    public function index()
    {
        $image = 'captcha-yandex.gif';

        // отправляем капчу на решение
        $send_captcha = $this->captcha->image($image);
//        dump($send_captcha);
//        dd($send_captcha);

        sleep(5);

        // вернем ответ
        $code = $this->captcha->getToken($send_captcha['task_id']);
//        $code = $this->captcha->getToken(62869819862);
        dd($code);

//         $this->captcha->goodCaptcha($code['token']);
//        dd($this->captcha->goodCaptcha(62869771129));
//        dd($this->captcha->badCaptcha(62869747127));
    }
}
