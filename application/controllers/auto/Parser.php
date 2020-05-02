<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Parser extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            //'Cars/marks',
            //'Cars/models',
            //'Cars/generation',
        ]);
    }

    /**
     * Получить марки автомобилей
     */
    public function marks() {
        $this->load->model('Cars/marks');
        $this->marks->run();
    }

    /**
     *
     */
    public function models() {
        $this->load->model(['Cars/marks', 'Cars/models']);
        $this->models->run();
        //echo '<script>setTimeout(function() {window.location.reload(true);}, '.mt_rand(1000, 5000).')</script>';
    }

    /**
     *
     */
    public function generations()
    {
        $this->load->model(['Cars/marks', 'Cars/models', 'Cars/generation']);
        $this->generation->run();

        /**
         * Задержка перед следующим этапом
         */
//        $timer = mt_rand(4000, 10000);
        $timer = mt_rand(2000, 5000);
        $url = site_url('auto/parser/generations/');

        echo '<script>setTimeout(function() {window.location.href = "'.$url.'"}, '.$timer.')</script>';
    }
}