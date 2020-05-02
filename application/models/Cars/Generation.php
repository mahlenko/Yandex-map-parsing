<?php

class Generation extends MY_Model
{
    /**
     * @var string
     */
    protected $table = 'car_generations';

    /**
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * @var string
     */
    private $cron_key = 'generations';

    /**
     * @var array
     */
    private $settings = [];

    /**
     * Адрес по которому получим данные
     * @var string
     */
    private $server_api = 'https://auto.ru/-/ajax/desktop/getBreadcrumbsWithFilters/';


    public function __construct()
    {
        parent::__construct();

        // $this->load->library('RequestsApi', ['use_proxy' => false]);
        $this->load->library('RequestsApi');
        $this->load->model(['Cars/cron', 'Cars/connect']);

        $this->settings = $this->cron->get($this->cron_key);
    }

    /**
     * @return bool
     */
    public function run()
    {
        /* Получаем модель автомобиля */
        $model = $this->getModel();
        if (! $model) {
            dump('Не найдена модель автомобиля.');
            return false;
        }

        /* Марка автомобиля, выбранная по модели */
        $mark = $this->marks->get($model->id_car_mark);
        if (! $mark) {
            dd('Не найдена марка автомобиля в БД.');
            return false;
        }

        /* фильтр выбора */
        $catalog_filter = $this->filter($mark[0], $model);

        /* ------------------------------------------------------------------ */

        $result = $this->requestsapi->post($this->server_api, [
            'section' => 'all',
            'category' => 'cars',
            'catalog_filter' => $catalog_filter
        ], ['x-csrf-token' => '68348d41000652e6cc2e269a8e646af3cf0e2e50b411374f']);

        if (! $result) {
            /* сообщаем о возможных ошибках */
            if ($this->requestsapi->curl->getHttpStatusCode() === 503) {
                dd('Возможно нехватает `_csrf_token` в куках, и такого же `x-csrf-token` в headers.');
            }

            /* подозреваем что это капча, проверим что она и попробуем решить */
            $captcha_solve = $this->requestsapi->captchaSolve();

            /* проверяем решение капчи */
            if ($captcha_solve) {
                $captcha_check = $this->requestsapi->captchaCheck($captcha_solve);

                if ($captcha_check) {
                    /**
                     * Успешное прохождение каптчи, остановим работу скрипта
                     * и попросим обновить страницу
                     */
                    dump('Капча успешно решена. Следующий запрос получит нужные данные.');
                    return false;
                }
            } elseif ($this->requestsapi->errors()) {
                dd($this->requestsapi->errors());
                return false;
            }
        }

        /* ------------------------------------------------------------------ */

        if ($result) {
            $generations = $this->requestsapi->response();
            if (! $generations || ! isset($generations[0]->level)) {
                dump('Непредвиденная ошибка', $generations);
                return false;
            }

            /* Проверим, что запрос был правильным, и сервис вернул нам поколения автомобилей */
            if ($generations[0]->level != 'GENERATION_LEVEL') {
                dd('Получен не верный уровень данных. Ожидается GENERATION_LEVEL, получен '. $generations[0]->level);
                return false;
            }

            if (isset($generations[0]->entities) && count($generations[0]->entities)) {
                /* Сохраняем или обновляем данные поколения */
                foreach ($generations[0]->entities as $generation) {
                    $this->addGeneration($model->id, $generation);
                }

                /* Сохраним позицию проверки, для автоматической работы дальше */
                $this->cron->save($this->cron_key, ['value' => $model->id]);

                /* Вывод резальтата :) */
                dump('Автомобиль: '. $mark[0]->name .' '. $model->name .' (ID: '. $model->id .')', 'Поколений: '. count($generations[0]->entities));
            } else {
                dump('Запрос выполнен успешно, но никаких данных не найдено.');
            }

            return true;
        }

        dump('Не получили ответ. Можно попробовать отследить.');
        return false;
    }

    /**
     * @return bool
     */
    private function getModel()
    {
        $this->load->model('Cars/models');
        $model_id = $this->settings ? $this->settings[0]->value + 1 : 1;
        $model = $this->models->get($model_id);

        if (! $this->settings) {
            $this->cron->save(null, [
                'key'   => $this->cron_key,
                'value' => $model_id
            ]);
        }

        return $model ? $model[0] : false;
    }

    /**
     * @param int $model_id
     * @param $generation
     * @return bool
     */
    private function addGeneration(int $model_id, $generation)
    {
        $data = new stdClass();

        $data->name         = $generation->name;
        $data->begin        = $generation->yearFrom;
        $data->end          = $generation->yearTo;
        $data->id_car_model = $model_id;
        $data->value        = $generation->itemFilterParams->super_gen;

        $double = $this->get(null, [
            'id_car_model' => $data->id_car_model,
            'value'        => $data->value
        ]);

        if ($double) {
            return $this->save($double[0]->id, $data);
        }

        return $this->save(null, $data);
    }

    /**
     * @param $mark
     * @param $model
     * @return array
     */
    private function filter($mark, $model)
    {
        /* Формируем данные для запроса */
        $filter = [
            'mark'  => $mark->value,
            'model' => $model->value
        ];

        /* Если выбранная модель, является дочерней, немного меняем запрос */
        if (intval($model->parent_id)) {
            /* @todo лучше проверять, найден ли реально родительская модель авто */
            $model_parent             = $this->models->get($model->parent_id);

            $filter['model']          = $model_parent[0]->value;
            $filter['nameplate_name'] = $model->value;
        }

        return $filter;
    }

}
