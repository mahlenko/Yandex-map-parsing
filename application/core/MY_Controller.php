<?php

class MY_Controller extends CI_Controller
{
    /**
     * @var string
     */
    protected $server = 'https://yandex.ru/maps/api/search';

    /**
     * @var array
     */
    private $data = [];

    /**
     * Load parent constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $key
     * @param string $value
     * @return array|mixed
     */
    protected function data(string $key = '', $value = '')
    {
        if ($value == '') {
            if (! empty($key)) {
                if (array_key_exists($key, $this->data) !== false) {
                    return $this->data[$key];
                } else {
                    return [];
                }
            }

            return $this->data;
        }

        $this->data[$key] = $value;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function decode(string $data = '') {
        return json_decode($data);
    }
}
