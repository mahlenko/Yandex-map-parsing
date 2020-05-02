<?php

class Company_sources_m extends MY_Model
{
    protected $table = 'company_sources';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'name' => 'Название',
        'href' => 'Ссылка'
    ];

    /**
     * @param null $primary_key
     * @param array $wheres
     * @return array
     */
    public function find($primary_key = null, $wheres = [])
    {
        $items = $this->get($primary_key, $wheres);
        if (! $items) {
            return [];
        }

        $data = [];
        foreach ($items as $item) {
            $data[$item->{$this->primary_key}][] = $item;
        }

        return $data;
    }
}