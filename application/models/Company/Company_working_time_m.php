<?php

class Company_working_time_m extends MY_Model
{
    protected $table = 'company_worktime';
    protected $primary_key = 'company_id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'dayId'     => 'День недели',
        'type'      => 'Тип',
        'hours'     => 'часы',
        'minutes'   => 'минуты',
        'days'      => ['пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'],
    ];
}
