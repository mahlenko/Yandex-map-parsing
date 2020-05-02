<?php

class Company_rating_m extends MY_Model
{
    protected $table = 'company_rating';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'ratingCount' => 'Общий рейтинг',
        'ratingValue' => 'Рейтинг',
        'reviewCount' => 'Отзывов'
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