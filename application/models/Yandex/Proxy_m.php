<?php

class Proxy_m extends MY_Model
{
    protected $table = 'proxies';
    protected $primary_key = 'proxy_id';

    /**
     * Получит прокси для задачи
     * @return bool|mixed
     */
    public function getFree() {
        $this->db->limit(1);
        $free_proxy = $this->get(null, [
            // свободный прокси
            'lock'      => 0,
            'active'    => 1,
            'expired >' => date('Y-m-d H:i:s')
        ],
            // выберет прокси, который меньше всего без работы
            ['used_tasks', 'ASC']
        );

        return $free_proxy ? $free_proxy[0] : false;
    }

    /**
     * Заблокирует прокси
     * @param int $proxy_id
     * @return bool
     */
    public function lock(int $proxy_id) {
        // блокируем и увеличиваем количество задач
        return $this->save($proxy_id, ['lock' => 1, 'used_tasks' => 'used_tasks+1'], [], ['used_tasks' => false]);
    }

    /**
     * Разблокирует прокси
     * @param int $proxy_id
     * @return bool
     */
    public function unlock(int $proxy_id) {
        return $this->save($proxy_id, ['lock' => 0]);
    }

    /**
     * Добавит или изменит куки для запросов
     * @param int $proxy_id
     * @param string $name
     * @param string $value
     * @return bool
     */
    public function setCookie(int $proxy_id, string $name, string $value)
    {
        $proxy = $this->get($proxy_id);
        if (! $proxy) {
            $this->save_error('Не найден указанный прокси.');
            return false;
        }

        $cookies = json_decode($proxy[0]->cookies);
        $cookies->{$name} = $value;

        if (is_null($value)) {
            unset($cookies->{$name});
        }

        return $this->save($proxy[0]->proxy_id, ['cookies' => json_encode($cookies)]);
    }

    /**
     * Очистит куки для прокси
     * @param int $proxy_id
     * @return bool
     */
    public function clearCookie(int $proxy_id)
    {
        $proxy = $this->get($proxy_id);
        if (! $proxy) {
            $this->save_error('Не найден указанный прокси.');
            return false;
        }

        return $this->save($proxy[0]->proxy_id, ['cookies' => json_decode([])]);
    }

}