<?php

/**
 * Яндекс.Карты
 * Получает информацию по API для задачи
 */
class Region_requests extends MY_Model
{
    /**
     * Адрес запроса к API яндекс карт
     * @var string
     */
    private $server = 'https://yandex.ru/maps/api/search';

    /**
     * Используемый прокси
     * @var
     */
    private $proxy;

    public function __construct()
    {
        parent::__construct();

        $this->load->model('Yandex/proxy_m');
        $this->proxy = $this->proxy_m->getFree();

        // подгружаем библиотеку запросов
        $this->load->library('requests', [ 'proxy' => $this->proxy ]);
    }

    /**
     * Получит регион с яндекс карт
     * @param string $text
     * @return mixed
     */
    public function getRegion(string $text)
    {
        return $this->requests->get($this->server, [
            'ajax'      => 1,
            'lang'      => 'ru_RU',
            'origin'    => 'maps-form',
            'text'      => trim($text),
            'csrfToken' => $this->proxy->token,
        ]);
    }


}