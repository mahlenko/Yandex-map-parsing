<?php

class Company_phone_m extends MY_Model
{
    protected $table = 'company_phones';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'number'        => 'Телефон',
        'type'          => 'Тип',
        'extraNumber'   => 'Доп. номер',
        'info'          => 'Информация',
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
