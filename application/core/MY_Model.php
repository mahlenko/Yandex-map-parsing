<?php

/**
 * Модель для работы с другими моделями
 * и базой данных.
 */
class MY_Model extends CI_Model
{
    /**
     * Таблица базы данных
     * @var string
     */
    protected $table = '';

    /**
     * Первичный ключ
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * Фильтр которым обрабатываем ключ
     * @var string
     */
    protected $primary_filter = 'intval';

    /**
     * Сохраняет время создания и время обновления.
     * Укажите false если не нужно.
     * @var array
     */
    protected $timestamp = [
        'update_when_create' => true,
        'update' => 'modified',
        'create' => 'created'
    ];

    /**
     * Тип возвращаемых данных.
     * @var string result/result_array
     */
    protected $result_type = 'result';

    /**
     * Сохраняет ошибки произошедшие в моделях
     * @var array
     */
    private $errors = [];

    /**
     * Разбивает массив where на части
     * @var int
     */
    private $chunk_items = 1000;

    /**
     * MY_Model constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Получает записи
     * @param mixed $primary_key_id
     * @param array $where_queries
     * @param array $order_by key => order
     * @param int $limit
     * @return array
     */
    public function get($primary_key_id = null, $where_queries = [], $order_by = [], $limit = null) : array
    {
        $result_type = $this->result_type;

        if ($primary_key_id) {
            $primary_key_id = $this->filter_primary_key($primary_key_id);
            $where_queries[$this->primary_key] = $primary_key_id;
        }

        if ($order_by) {
            list($order_key, $order_type) = $order_by;
            $this->db->order_by($this->table . '.' . $order_key, $order_type);
        }

        if (intval($limit)) {
            $this->db->limit($limit);
        }

        /* Устанавливаем правила выборки и выбираем записи */
        $this->where_queries($where_queries);
        $query = $this->db->get($this->table);

        return $query->num_rows() ? $query->$result_type() : [];
    }

    /**
     * Сохраняет записи
     * @param mixed $primary_key_id
     * @param array $data
     * @param array $where_queries
     * @param array $escape
     * @return bool
     * @todo Привязать текст ошибки к файлу локализации
     */
    public function save($primary_key_id = null, $data = [], $where_queries = [], $escape = []) : bool
    {
        if ($primary_key_id) {
            $primary_key_id = $this->filter_primary_key($primary_key_id);
            $where_queries[$this->primary_key] = $primary_key_id;
        }

        $data = (array) $data;

        /* Время обновления/создания записи */
        $date_update = date('Y-m-d H:i:s');

        /* Обновляем записи */
        if ($primary_key_id)
        {
            /* Добавим время обновления */
            if ($this->timestamp !== false) {
                $data[$this->timestamp['update']] = $date_update;
            }

            $this->set_save($data, $escape);
            $this->where_queries($where_queries);
            $result = $this->db->update($this->table);
            if (!$result) $this->save_error($this->db->error()['message']);
            return $result;
        } else {
            /* Добавим время создания записи */
            if ($this->timestamp !== false) {
                if ($this->timestamp['update_when_create']) {
                    /* Добавим время обновления таким же как и время создания */
                    $data[$this->timestamp['update']] = $date_update;
                }
                $data[$this->timestamp['create']] = $date_update;
            }


            /* Создаем новую запись */
            if (! empty($primary_key_id)) {
                $data[$this->primary_key] = $primary_key_id;
            }
            $this->set_save($data, $escape);
            $result = $this->db->insert($this->table);

            if (!$result) $this->save_error($this->db->error()['message']);
            return $result;
        }
    }

    /**
     * Сохранит в базе сразу группу объектов
     * @param null $primary_key_id
     * @param array $data Массив объектов
     * @param array $where_queries
     * @param array $escape
     * @return bool
     */
    public function saveGroup($primary_key_id = null, $data = [], $where_queries = [], $escape = [])
    {
        if (! $data) return false;

        foreach ($data as $item) {
            if (is_array($item) || is_object($item)) {
                $this->save($primary_key_id, (array) $item, $where_queries, $escape);
            }
        }
    }

    /**
     * Возвращает количество записей
     * @param mixed $primary_key_id
     * @param array $where_queries
     * @return int
     */
    public function count($primary_key_id = null, $where_queries = []) : int
    {
        if ($primary_key_id) {
            $primary_key_id = $this->filter_primary_key($primary_key_id);
            $where_queries[$this->primary_key] = $primary_key_id;
        }

        /* Устанавливаем правила выборки */
        $this->where_queries($where_queries);

        return $this->db->count_all_results($this->table);
    }

    /**
     * Удаляет записи из базы,
     * может удалить сразу из нескольких таблиц
     * @param mixed $primary_key_id
     * @param array $where_queries
     * @param int $limit
     * @param array $more_tables
     * @return bool
     */
    public function delete($primary_key_id = null, $where_queries = [], $limit = null, $more_tables = []) : bool
    {
        $tables = $this->table;
        if ($more_tables) {
            if (!is_array($more_tables)) $more_tables = [$more_tables];
            $tables = array_merge($tables, $more_tables);
        }

        if ($primary_key_id) {
            $primary_key_id = $this->filter_primary_key($primary_key_id);
            $where_queries[$this->primary_key] = $primary_key_id;
        }

        $this->where_queries($where_queries);
        if ($limit) $this->db->limit(intval($limit));

        return $this->db->delete($tables);
    }

    /**
     * Выводит все ошибки в моделях
     * @return array
     */
    public function errors()
    {
        /* Добавим сюда ошибку mysql, если она была */
        if ($error_db = $this->db->error() && !empty($error_db['message'])) {
            $this->save_error($error_db['message']);
        }

        $error_text = [];
        foreach ($this->errors as $error) {
            $error_text[] = $error['text'];
        }

        return $error_text ? [
            'errors' => $this->errors,
            'error_text' => $error_text] : false;
    }

    /**
     * Создаст массив для добавления выпадающего списка
     * @param array $data
     * @param string $value
     * @param string $key
     * @param string $first_text
     * @return array
     */
    public function dropdown(array $data, string $value, string $key = '', $first_text = '-- выберите --')
    {
        if (empty($key)) $key = $this->primary_key;

        $result = [0 => $first_text];
        foreach ($data as $item) {
            if (is_array($item) && array_key_exists($key, $item)) {
                $result[$item[$key]] = $item[$value];
            }

            if (is_object($item) && isset($item->{$key})) {
                $result[$item->{$key}] = $item->{$value};
            }
        }

        return $result;
    }

    /**
     * Вернет массив где ключем будет равен $primary_key
     * @param array $data
     * @param string|null $primary_key
     * @return array
     */
    public function id2key(array $data, string $primary_key = null)
    {
        if (! $primary_key || empty($primary_key)) $primary_key = $this->primary_key;

        $result = [];
        foreach ($data as $item) {
            if (is_array($item) && array_key_exists($primary_key, $item)) {
                $result[$item[$primary_key]] = $item;
            }

            if (is_object($item) && isset($item->{$primary_key})) {
                $result[$item->{$primary_key}] = $item;
            }
        }

        return $result;
    }

    /**
     * Сохраняет последнюю ошибку
     * @param string $text
     * @param bool $add_db_error
     */
    protected function save_error(string $text, $add_db_error = true) {

        $error = ['text' => trim($text)];

        if ($add_db_error === true) {
            $error['last_query'] = $this->db->last_query();
        }

        $this->errors[] = $error;

    }

    /**
     * Обработка фильтром первичного ключа
     * @param $primary_keys
     * @return mixed
     */
    private function filter_primary_key($primary_keys)
    {
        $filter = $this->primary_filter;
        if (is_array($primary_keys)) {
            foreach ($primary_keys as $index => $value) {
                $primary_keys[$index] = $filter($value);
            }
        } else {
            $primary_keys = $filter($primary_keys);
        }

        return $primary_keys;
    }

    /**
     * Добавит правила выбора
     * @param array $where_queries
     */
    private function where_queries($where_queries = [])
    {
        if (!$where_queries) return;

        foreach ($where_queries as $key => $values) {
            if (is_array($values))
            {
                // большое количество параметров разбиваем на несколько
                if (count($values) > $this->chunk_items) {
                    // разбиваем на более мелкие части
                    $chunk_wheres = array_chunk($values, $this->chunk_items);

                    // отправляем в запрос каждую часть отдельно
                    $this->db->group_start();
                        foreach ($chunk_wheres as $chunk_where) {
                            $this->db->or_where_in($this->table.'.'.$key, $chunk_where);
                        }
                    $this->db->group_end();
                } else {
                    // не большое количество данных
                    $this->db->where_in($this->table . '.' . $key, $values);
                }
            } else {
                $this->db->where($this->table.'.'.$key, $values);
            }
        }
    }

    /**
     * Обновление данных с настройками экранирования
     * @param array $data
     * @param array $escape
     */
    private function set_save($data = [], $escape = [])
    {
        foreach ($data as $key => $values) {
            // по-умолчанию в CI включено экранирование
            $escape_key = true;
            if (array_key_exists($key, $escape)) {
                $escape_key = boolval($escape[$key]);
            }

            $this->db->set($this->table.'.'.$key, $values, $escape_key);
        }
    }

}