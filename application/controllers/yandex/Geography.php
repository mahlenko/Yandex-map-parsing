<?php

/**
 * Справочник географических объектов
 */
class Geography extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        // подгружаем модели...
        $this->load->model([
            'Requests/region_requests',
            'Yandex/geography_m',
            'Yandex/geography_polygons_m',
            'Yandex/geography_containers_m',
            'Yandex/proxy_m'
        ]);
    }

    public function index()
    {
        $geography = $this->geography_m->get();
        $this->data('GEOGRAPHY', $geography);

        return $this->twig->display('yandex/geography/index', $this->data());
    }

    /**
     * Просмотреть регион
     * @param int $geo_id
     * @return mixed
     */
    public function view(int $geo_id)
    {
        $region = $this->geography_m->find([$geo_id]);
        if ( !$region) {
            redirect('yandex/geography');
        }

        $this->data('REGION', $region[0]);
        return $this->twig->display('yandex/geography/view', $this->data());
    }

    /**
     * Создать новый регион
     */
    public function create()
    {
        // создание нового региона
        $this->form_validation->set_rules('region', 'регион', 'trim|required|callback_rule_region');
        if ($this->form_validation->run()) {

            // при добавлении большого региона, может быть долгая обработка
            ini_set('max_execution_time', 0);

            // название региона
            $region_name = $this->input->post('region', true);

            // поиск региона в яндекс
            $request = $this->region_requests->getRegion($region_name);

            if (! isset($request->response->data->exactResult))
                die('Обновите страницу и подтвердите отправку формы.');

            //
            $region = $request->response->data->exactResult;

            // проверим что такого региона еще нет
            if ($this->geography_m->count($region->region->id)) {
                redirect('yandex/geography');
            }

            // сохраняем регион
            $result = $this->geography_m->save(null, [
                'geo_id' => $region->region->id,
                'name' => $region->region->names->nominative,
                'seoname' => $region->region->name,
                'description' => $region->description,
                'address' => $region->address,
                'lon' => $region->coordinates[0],
                'lat' => $region->coordinates[1],
                'bounds_lon' => $region->region->bounds[0][0],
                'bounds_lat' => $region->region->bounds[0][1],
                'bounds_lon_end' => $region->region->bounds[1][0],
                'bounds_lat_end' => $region->region->bounds[1][1],
                'zoom' => $region->region->zoom,
            ]);

            if (!$result) {
                // ошибка добавления

            }

            // добавим координаты точек региона
            if ($region->displayGeometry)
            {
                $geometries = $region->displayGeometry->geometries;

                foreach ($geometries as $area_id => $geometry)
                {
                    foreach ($geometry->coordinates[0] as $coordinate)
                    {
                        $result = $this->geography_polygons_m->save(null, [
                            'geo_id' => $region->region->id,
                            'area_id' => $area_id,
                            'lon' => $coordinate[0],
                            'lat' => $coordinate[1]
                        ]);

                        if (!$result) {
                            // сообщение об ошибке
                            break;
                        }
                    }
                }

                // создаст контейнеры парсинга
                $this->geography_containers_m->create($region->region->id, $geometries);
            }

            redirect('yandex/geography');
        }

        // покажем форму
        return $this->twig->display('yandex/geography/create');
    }

    /**
     * Проверка введенного региона
     * @param string $region_string
     * @return bool
     */
    public function rule_region($region_string = '')
    {
        $region_arr = explode(',', $region_string);
        if (count($region_arr) > 1) {
            return true;
        }

        $this->form_validation->set_message('rule_region', 'Регион задан не верно. Введите сначала страну, через запятую город и/или область.');
        return false;
    }
}
