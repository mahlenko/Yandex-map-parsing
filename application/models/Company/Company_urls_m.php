<?php

class Company_urls_m extends MY_Model
{
    protected $table = 'company_urls';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'url' => 'Ссылка'
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
