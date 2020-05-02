<?php

class Filter_m extends MY_Model
{
    protected $table = 'filters';
    protected $primary_key = 'id';
    protected $primary_filter = 'trim';


    public function __construct()
    {
        parent::__construct();
        $this->load->model('Yandex/filter_values_m');
    }

    /**
     * @param array $filter
     * @return bool
     */
    public function create(stdClass $filter = null)
    {
        if (! $filter) {
            $this->save_error('Не передан фильтр.');
            return false;
        }

        // проверяем фильтр на дубликат
        if ($this->count($filter->id)) return true;

        // добавляем значения фильтра
        if (isset($filter->values)) {
            foreach ($filter->values as $value) {
                $value->filter_id = $filter->id;

                // дубликаты
                if ($this->filter_values_m->count($filter->id)) {
                    continue;
                }

                $result = $this->filter_values_m->save(null, (array) $value);
                if (! $result) {
                    $this->save_error('Не удалось добавить значение фильтра.');
                    return false;
                }
            }
            unset($filter->values);
        }

        return $this->save(null, (array) $filter);
    }

    /**
     * Создаст фильтры из группы
     * @param array $filters
     * @return bool
     */
    public function createGroup(array $filters = [])
    {
        if (! $filters) return false;

        $result = true;
        foreach ($filters as $item) {
            if (!$this->create($item)) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}