<?php

use \Curl\Curl;

/**
 * Яндекс.Карты
 * Получает информацию по API для задачи
 */
class Map_requests extends MY_Model
{
    /**
     * Случайный прокси из базы
     * @var stdClass
     */
    public $proxy;

    /**
     * Адрес запроса к API яндекс карт
     * @var string
     */
    private $server = 'https://yandex.ru/maps/api/search';

    /**
     * Задача которую выполняет демон
     * @var stdClass
     */
    private $task;

    /**
     * Map_requests constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model(['Yandex/proxy_m']);
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
    }


    /**
     * Получает компании из Яндекса
     * @param $task
     * @return bool|stdClass
     */
    public function load($task)
    {
        // сохраняем задачу
        $this->task = $task;

        // поиск прокси сервера задачи
        $proxy = $this->proxy_m->get($task->proxy_id);
        if (! $proxy) {
            $this->save_error('Не найден прокси сервер.');
            return false;
        }

        $this->proxy = $proxy[0];

        // подгружаем библиотеку запросов
        $this->load->library('requests', [ 'proxy' => $this->proxy ]);

        // готовим запрос
        $query = $this->query();
        if (! $query) {
            $this->save_error('Не смог сформировать запрос на выбранный участок.');
            return false;
        }

        // получаем компании
        $response = $this->requests->get($this->server, $query);

        // отметим выбранный участок, о количестве найденных записей
        if (isset($response->response->data->totalResultCount)) {
            // позиция на которой остановились
            $container = $this->task->geography->containers[$this->task->position];

            // количество полученных
            $fount_company = intval($container->found_company) + $response->response->data->totalResultCount;

            $this->db->where('lon', $container->lon);
            $this->db->where('lat', $container->lat);
            $this->db->where('spn_lat', $container->spn_lat);
            $this->db->where('spn_lat', $container->spn_lat);
            $this->geography_containers_m->save($container->geo_id, [
                'found_company' => $fount_company,
                'found_count' => $container->found_count + 1
            ]);
        }

        return isset($response->response->data) ? $response->response->data : new stdClass();
    }


    /**
     * Формирует данные для запроса к Яндексу
     * на основе выбранной задачи.
     * @return array
     */
    private function query()
    {
        if (! isset($this->task->geography->containers[$this->task->position])) {
            return false;
        }

        // позиция на которой остановились
        $container = $this->task->geography->containers[$this->task->position];

        // запрос к Яндексу
        $query = [
            'ajax' => 1,
            'output' => 'json',
            'lang' => 'ru_RU',
            'results' => 25,
            'skip' => intval($this->task->skip),
            'll' => implode(',', [$container->lon, $container->lat]),
            'spn' => implode(',', [$container->spn_lon, $container->spn_lat]),
            'origin' => 'maps-form',
            'snippets' => 'businessrating/1.x',
            'geocoder_sco' => 'latlong',
            'csrfToken' => $this->proxy->token,
            'yandex_gid' => $container->geo_id,
            'perm' => '',
            'source' => 'form',
            'parent_reqid' => '',
            'serpid' => '',
            // новые параметры для тестов
            'sessionid' => '1576528628503_901589',
            'search_experimental_rearr[0]' => 'scheme_Local/Geoupper/Adverts/AdvMachine/SendRequest=true',
            'search_experimental_rearr[1]' => 'scheme_Local/Geo/Adverts/AdvMachine/PrepareRequest=true'
        ];

        // если выбрана рубрика
        if ($this->task->rubric_id) {
            $text = ! empty($this->task->text) ? $this->task->text : $this->task->rubric->seoname;
            $query['text'] = json_encode([ // поиск по рубрикам (категориям)
                'text' => $text,
                'what' => [
                    [
                        'attr_name' => 'rubric',
                        'attr_values' => [$this->task->rubric->seoname]
                    ]
                ]
            ]);
        } else {
            // если не назначен текст поиска отменяем
            if (empty($this->task->text)) {
                return false;
            }

            $query['text'] = $this->task->text;
        }

        return $query;
    }
}
