<?php if (!defined('BASEPATH')) exit('No direct script access allowed.');

/**
 * Характеристики автомобиля
 *
 * @package     Sputnik
 * @subpackage  CodeIgniter
 * @category    Core
 * @author      Mahlenko Sergey <sm@weblive.by>
 */
class Marks extends MY_Model
{
    protected $table = 'car_marks';
    protected $primary_key = 'id';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cars/connect');
    }

    /**
     * Добавляем в базу марки автомобилей
     */
    public function run()
    {
        $marks = $this->connect->run(['section' => 'all', 'category' => 'cars']);
        if (!$marks || !isset($marks[0]->entities)) {
            dd('Не получены данные', $marks);
            return false;
        }

        /* Добавляем в базу */
        foreach ($marks[0]->entities as $mark)
        {
            $data = new stdClass();

            $data->name          = $mark->name;
            $data->cyrillic_name = $mark->cyrillic_name;
            $data->begin         = $mark->year_from;
            $data->end           = $mark->year_to;
            $data->popular       = intval($mark->popular);
            $data->value         = strtoupper($mark->itemFilterParams->mark);
            $data->type          = 1;

            // не добавляем гоночный болид в БД
            if ($data->value == 'PROMO_AUTO') {
                continue;
            }

            $double = $this->get(null, [
                'value' => $data->value
            ]);

            /* сохраним или обновим данные */
            $save_result = $this->save((array) $data, $double ? $double[0]['id'] : null);

            if ($save_result) {
                $this->downloadLogotypes($data->value, $mark->logo, $mark->{'big-logo'});
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @param string $logotype
     * @param string $big_logotype
     */
    private function downloadLogotypes(string $name, string $logotype = '', string $big_logotype = '')
    {
        $directory_path = str_replace('/', DIRECTORY_SEPARATOR,
            realpath(APPPATH .'../') .'/logotypes');

        $directory = '';
        foreach (explode(DIRECTORY_SEPARATOR, $directory_path) as $path) {
            if (empty($path)) continue;

            $directory .= DIRECTORY_SEPARATOR . $path;
            if (! file_exists($directory) || ! is_dir($directory)) {
                mkdir($directory);
            }
        }

        /* ------------------------------------------------------------------ */

        $name = strtolower($name);

        $filename = $directory . DIRECTORY_SEPARATOR . $name . '.png';
        if (! empty($logotype) && ! file_exists($filename)) {
            @file_put_contents($filename, file_get_contents('https:' . $logotype));
        }

        $filename_big = $directory . DIRECTORY_SEPARATOR . $name . '-big.png';
        if (! empty($big_logotype) && ! file_exists($filename_big)) {
            @file_put_contents($filename_big, file_get_contents('https:' . $big_logotype));
        }
    }

}