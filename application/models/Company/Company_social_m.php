<?php

class Company_social_m extends MY_Model
{
    protected $table = 'company_social';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'type' => 'Тип',
        'name' => 'Название',
        'href' => 'Ссылка'
    ];

    /**
     * @param null $company_id
     * @param array $wheres
     * @return array
     */
    public function find($company_id = null, $wheres = [])
    {
        $items = $this->get($company_id, $wheres);
        if (! $items) {
            return [];
        }

        $data = [];
        foreach ($items as $item) {
            $data[$item->company_id][] = $item;
        }

        return $data;
    }
}