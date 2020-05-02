<?php

class Company_category_m extends MY_Model
{
    protected $table = 'company_category';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'name'          => 'Название',
        'seoname'       => 'Ссылка',
        'pluralName'    => 'Название множественное число',
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
