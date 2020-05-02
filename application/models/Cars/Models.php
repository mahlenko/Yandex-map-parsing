<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * Характеристики автомобиля
 *
 * @package     Sputnik
 * @subpackage  CodeIgniter
 * @category    Core
 * @author      Mahlenko Sergey <sm@weblive.by>
 */
class Models extends MY_Model
{
    protected $table = 'car_models';
    protected $primary_key = 'id';

    private $cron_key = 'models';
    private $settings = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Cars/cron', 'Cars/connect']);
        $this->settings = $this->cron->get($this->cron_key);
    }

    /**
     * Добавляем в базу модели автомобилей
     */
    public function run()
    {
        $mark = $this->getMark();
        if (!$mark) {
            dump('Марка не определена или все марки получены.');
            return false;
        }

        $models = $this->connect->run([
            'section' => 'all',
            'category' => 'cars',
            'catalog_filter' => [
                'mark' => $mark['value']
            ]
        ]);

        /* Если не получены данные или не данные не те, возвращаем ошибку */
        if(! @$models[0]->level || @$models[0]->level != 'MODEL_LEVEL') {
            dump('Не получены модели, попробуйте позже.', $models);
            return false;
        }

        /* Добавляем в базу */
        dump('Марка ID: '. $mark['id']);
        dump('Марка: '. $mark['name']);
        dump('Моделей: '. count($models[0]->entities));
        foreach($models[0]->entities as $model)
        {

            /* Сохраним модель */
            $model_id = $this->saveModel($model, $mark['id']);
            if (! $model_id) {
                dd('Ошибка при сохранении', $this->db->errors());
            }

            /* Добавим серию моделей */
            if (isset($model->nameplates) && $model->nameplates) {
                dump('Серий: ' . count($model->nameplates));
                $this->saveSeries($model_id, $model->nameplates);
            }
        }

        /* Сохраняем что получили данные этой модели и выбираем следующую */
        $this->cron->save(['value' => $mark['id']], $this->cron_key);

        return true;
    }

    /**
     * Поиск марки автомобиля, модели которой будем получать
     * @return bool
     */
    private function getMark()
    {
        $this->load->model('Cars/marks');
        $cron = $this->cron->get($this->cron_key);
        $marks = $this->marks->get();

        if (!$cron) {
            $this->cron->save(['key' => $this->cron_key, 'value' => null]);
            return $marks[0];
        }

        if (empty($cron[0]['value'])) return $marks[0];

        $find_prev = false;
        foreach($marks as $mark) {
            if ($find_prev) return $mark;
            if ($mark['id'] == $cron[0]['value']) {
                $find_prev = true;
            }
        }

        return false;
    }

    /**
     * @param $model
     * @param int $id_car_mark
     * @return bool|mixed|null
     */
    private function saveModel($model, int $id_car_mark)
    {
        $data = new stdClass();

        $data->id_car_mark   = $id_car_mark;
        $data->name          = $model->name;
        $data->cyrillic_name = $model->cyrillic_name ?? null;
        $data->begin         = $model->year_from;
        $data->end           = $model->year_to;
        $data->value         = strtoupper($model->itemFilterParams->model);

        /* проверка есть ли дубликат в БД */
        $double = $this->get(null, [
            'id_car_mark' => $data->id_car_mark,
            'name'        => $data->name
        ]);

        /*  */
        $model_id = $double ? $double[0]['id'] : null;
        $result = $this->save((array) $data, $model_id);
        if (! $model_id && $result) $model_id = $this->db->insert_id();

        return $result ? $model_id : false;
    }

    /**
     * @param int $model_id
     * @param $nameplates
     */
    private function saveSeries(int $model_id, array $nameplates)
    {
        $model = $this->get($model_id);

        foreach ($nameplates as $serie) {
            $data = new stdClass();

            $data->id_car_mark = $model[0]['id_car_mark'];
            $data->name = $serie->name;
            $data->parent_id = $model[0]['id'];
            $data->value = strtoupper($serie->semantic_url);

            /* проверка есть ли дубликат в БД */
            $double = $this->get(null, [
                'id_car_mark' => $data->id_car_mark,
                'parent_id'   => $data->parent_id,
                'name'        => $data->name
            ]);

            /*  */
            $model_id = $double ? $double[0]['id'] : null;
            $this->save((array) $data, $model_id);
        }
    }

}