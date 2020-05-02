<?php

class Company_properties_m extends MY_Model
{
    protected $table = 'company_properties';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'name'  => 'Название',
        'value' => 'Значение'
    ];
}