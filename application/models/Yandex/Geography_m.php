<?php

use FastSimpleHTMLDom\Document;

/**
 * Модель для работы со справочником
 */
class Geography_m extends MY_Model
{
    protected $table = 'geography';
    protected $primary_key = 'geo_id';

    // из кук яндекс справочника
    private $session_id;
    private $yandex_uid;
    private $csrf;

    public function __construct()
    {
        parent::__construct();

        $this->load->config('yandex');

        // сохраняем из настроек session_id
        $this->session_id = $this->config->item('geography')['session_id'];
        $this->yandex_uid = $this->config->item('geography')['yandex_uid'];
        $this->csrf = $this->config->item('geography')['csrf'];
    }


    /**
     * Вернет регион со всеми координатами
     * @param array $geo_id
     * @param array $wheres
     * @return array|bool
     */
    public function find(array $geo_id = null, $wheres = [])
    {
        $regions = $this->get($geo_id, $wheres);
        if (! $regions) return $regions;

        $this->load->model([
            'Yandex/geography_polygons_m',
            'Yandex/geography_containers_m'
        ]);

        $geo_id = array_column($regions, 'geo_id');

        $polygons = $this->geography_polygons_m->find($geo_id);
        $containers = $this->geography_containers_m->find($geo_id, [
//            'found_count' => 0,
//            'found_company' => 0,
        ]);

        foreach ($regions as $index => $region)
        {
            // многоугольник - форма региона
            if (array_key_exists($region->geo_id, $polygons)) {
                $regions[$index]->polygons = $polygons[$region->geo_id];
            }

            // контейнеры парсинга региона
            if (array_key_exists($region->geo_id, $containers)) {
                $regions[$index]->containers = $containers[$region->geo_id];
            }
        }

        return $regions;
    }

    /**
     * Создать регион
     * @param $yandex_geography_address
     * @return bool
     */
//    public function create($yandex_geography_address)
//    {
//        $this->load->model('yandex/geography_position_m');
//
//        // создаём регион
//        $create_status = $this->save(null, [
//            'geo_id'        => $yandex_geography_address->geo_id,
//            'region_code'   => $yandex_geography_address->region_code,
//            'name'          => $yandex_geography_address->components[count($yandex_geography_address->components)-1]->name->value,
//            'fullname'      => $yandex_geography_address->formatted->value,
//            'address_id'    => $yandex_geography_address->address_id,
//            'precision'     => $yandex_geography_address->precision
//        ]);
//
//        // вернет причину ошибки создания записи
//        if ( !$create_status) return false;
//
//        // сохраним центральную точку
//        if (isset($yandex_geography_address->pos)) {
//            $create_status = $this->geography_position_m->save(null, [
//                'geo_id'    => $yandex_geography_address->geo_id,
//                'type'      => strtolower($yandex_geography_address->pos->type),
//                'lat'       => $yandex_geography_address->pos->coordinates[1],
//                'lon'       => $yandex_geography_address->pos->coordinates[0]
//            ]);
//
//            if (! $create_status) return false;
//        }
//
//        // сохраним координаты ограничевающего прямоугольника
//        $bounding_boxes = $this->geography_position_m->boundingBoxes($yandex_geography_address->bounding_box);
//        // if (count($bounding_boxes) > 1200) {
//            // если слишком много секций, уменьшаем их количество,
//            // увеличивая его размер.
//            // $bounding_boxes = $this->geography_position_m->boundingBoxes(
//            //    $yandex_geography_address->bounding_box,
//            //    [0.1865404687, 0.0696417750]);
//        // }
//
//        foreach ($bounding_boxes as $index => $bounding) {
//            $create_status = $this->geography_position_m->save(null, array_merge($bounding, [
//                'geo_id'    => $yandex_geography_address->geo_id,
//                'type'      => 'bounding',
//                'bounding'  => $index + 1
//            ]));
//
//            if (! $create_status) return false;
//        }
//
//        return $yandex_geography_address->geo_id;
//    }

    /**
     * Получит географические координаты по адресу
     * @param string $address
     * @param stdClass $proxy
     * @return bool
     * @throws ErrorException
     */
//    public function getYandexGeography(string $address, stdClass $proxy)
//    {
//        // отправляемые данные
//        $data = json_encode([
//            'attribute' => [
//                'name' => 'address',
//                'value' => [
//                    'formatted' => [
//                        'value' => $address,
//                        'locale' => 'ru'
//                    ]
//                ]
//            ]
//        ], JSON_UNESCAPED_UNICODE);
//
//
//        $curl = new Curl();
//        $curl->setProxy($proxy->server, $proxy->port, $proxy->username, $proxy->password);
//        $curl->setProxyTunnel();
//        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
//        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
//        $curl->setHeader('x-csrf-token', $this->csrf);
//        $curl->setHeader('Content-Type', 'application/json; charset=utf-8');
//        $curl->setCookie('Session_id', $this->session_id);
//        $curl->setCookie('yandexuid', $this->yandex_uid);
//        $curl->setOpt(CURLOPT_POSTFIELDS, $data);
//        $curl->post('https://yandex.ru/sprav/api/unify', $data);
//
//        // не получен ответ или пришло не то, что ожидали получить
//        if (! isset($curl->response->validation_status)) {
////            $this->save_error('Не удалось получить информацию от Яндекс.Справочника.', false);
////            $this->save_error('Полученный результат: '.json_encode($this->response), false);
////            $this->save_error('Ошибка запроса: '.$curl->getCurlErrorMessage(), false);
//
//            $messages = [
//                '1) Откройте сайт <a href="https://yandex.ru/sprav/" target="_blank">Яндекс.Справочник</a>, возмите значение <strong>Session_id</strong> из кук.',
//                '2) Откройте сайт <a href="https://yandex.ru/sprav/" target="_blank">Яндекс.Справочник</a>, возмите значение <strong>yandexuid</strong> из кук.',
//                '3) Откройте код страницы <a href="view-source:https://yandex.ru/sprav/" target="_blank">Яндекс.Справочник</a>, найдите <strong>csrf</strong> token.',
//                '4) Заполните значениями конфиг config/yandex.php'
//            ];
//            die(implode('<br>', $messages));
//
//            return false;
//        }
//
//        // ошибка поиска региона
//        if ($curl->response->validation_status != 'ok' || ! isset($curl->response->address->value) ) {
//            $this->save_error('Яндекс.Справочник вернул результат, но запрошенный регион не был найден.', false);
//            return false;
//        }
//
//        return $curl->response->address->value;
//    }
}