<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * Характеристики автомобиля
 *
 * @package     Sputnik
 * @subpackage  CodeIgniter
 * @category    Core
 * @author      Mahlenko Sergey <sm@weblive.by>
 */
class Cron extends MY_Model
{
    protected $table = 'auto_ru_cron';
    protected $primary_key = 'key';
    protected $primary_filter = 'trim';
}