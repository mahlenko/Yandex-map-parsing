<?php

class Task_m extends MY_Model
{
    protected $table = 'tasks';
    protected $primary_key = 'task_id';

    private $task_status = [
        'CREATED'   => 'Создан',
        'RUN'       => 'Работает',
        // 'RESTART'   => 'Перезапуск',
        'STOPPED'   => 'Остановлен',
        'PAUSED'    => 'Пауза',
        'COMPLETE'  => 'Завершен',
    ];

    /**
     * @var string
     */
    private $email_admin;

    /**
     * Task_m constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->config('email');
        $this->email_admin = $this->config->item('email_admin');
    }

    /**
     * Поиск задачи со всей информацией
     * @param $task_id
     * @param array $wheres
     * @param array $order
     * @return array|bool
     */
    public function find($task_id = null, array $wheres = [], $order = [])
    {
        $this->load->model([
            'Yandex/geography_m',
            'Company/company_m',
            'Yandex/category_m',
        ]);

        $tasks = $this->get($task_id, $wheres, $order);
        if (! $tasks) {
            $this->save_error('Выбранные задачи не найдена');
            return false;
        }

        //
        $geo_id = array_column($tasks, 'geo_id');

        // поиск регионов
        $regions = $this->geography_m->find($geo_id, [], ['found_company', 'DESC']);
        if ($regions) {
            $regions = $this->geography_m->id2key($regions);
        }

        // Рубрики поиска
        $rubric_id = array_column($tasks, 'rubric_id');
        $rubrics = $this->category_m->get($rubric_id);
        if ($rubrics) {
            $rubrics = $this->category_m->id2key($rubrics);
        }

        // количество собранных организаций
        $tasks_id = array_column($tasks, 'task_id');
        $company_counts = [];
        foreach ($tasks_id as $id) {
            $company_counts[$id] = $this->company_m->count(null, ['task_id' => $id]);
        }

        // собираем все данные по задаче
        foreach ($tasks as $index => $task) {
            // собираем задачи с регионами
            if (array_key_exists($task->geo_id, $regions)) {
                $task->geography = $regions[$task->geo_id];
            }

            // рубрики
            if (array_key_exists($task->rubric_id, $rubrics)) {
                $task->rubric = $rubrics[$task->rubric_id];
            }

            // количество полученных компаний
            if (array_key_exists($task->task_id, $company_counts)) {
                $task->companies_count = $company_counts[$task->task_id];
            }

            // расчет времени
            if (! empty($task->end_process)) {
                $today = strtotime(date('Y-m-d 00:00:00'));
                $diff = strtotime($task->end_process) - strtotime($task->start_process);

                $task->execution_time = date('H:i:s', $today + $diff);
            }

            $tasks[$index] = $task;
        }

        return $tasks;
    }


    /**
     * Доступные статусы задач
     * @param null $status_key
     * @return array|mixed
     */
    public function getStatus($status_key = null)
    {
        if ($status_key) {
            if (isset($this->task_status[mb_strtoupper($status_key)])) {
                return $this->task_status[mb_strtoupper($status_key)];
            }

            return false;
        }

        return $this->task_status;
    }


    /**
     * Смена статуса задачи
     * @param int $task_id
     * @param string $status
     * @return bool
     */
    public function setStatus(int $task_id, string $status)
    {
        $task = $this->find($task_id);
        if (! $task) {
            $this->save_error('Вы не можете менять статус выбранной задаче.');
            return false;
        }

        // нельзя изменить статус для завершенной задачи
        if (! empty($task[0]->end_process)) {
            $this->save_error('Задача уже завершена, вы не можете менять ей статус.');
            return false;
        }

        // проверяем что есть новый статус в списке доступных
        if (! $this->getStatus($status)) {
            $this->save_error('Нельзя выбрать данный статус для этой задачи.');
            return false;
        }

        // для созданной задачи, доступно только запустить
        if ($task[0]->status == 'CREATED' && mb_strtoupper($status) != 'RUN') {
            $this->save_error('Для этой задачи можно выбрать, только статус "'.$this->getStatus('RUN').'".');
            return false;
        }

        $status = mb_strtoupper($status);

        // меняем статус задачи
        $data = ['status' => $status];

        // дополнительные параметры в зависимости от статуса
        switch ($status) {
            case 'RUN':
                $data['start_process'] = date('Y-m-d H:i:s');
                break;

            case 'STOPPED':
                $data['skip'] = 0;
                $data['position'] = 0;
                $data['start_process'] = null;
                $data['end_process'] = null;
                break;

            case 'COMPLETE':
            $data['skip'] = 0;
            $data['end_process'] = date('Y-m-d H:i:s');
            break;
        }

        // сохраним статус и отправим уведомления
        if ($result = $this->save($task_id, $data)) {
            $this->sendNotification($status, $task[0]);
        }

        return $result;
    }


    /**
     * Отправка письма на почту
     * @param string $email
     * @param string $subject
     * @param string $message
     */
    public function sendEmail(string $email = '', string $subject = '', string $message = '')
    {
        $this->load->library('email');

        if (empty($email)) $email = $this->email_admin;

        $this->email->from($this->config->item('smtp_user'), 'Robot DBase');
        $this->email->to($email);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->send(false);
    }


    /**
     * Отправка уведомлений при смене статуса
     * @param string $status
     * @param stdClass $task
     * @return bool
     */
    private function sendNotification(string $status, stdClass $task)
    {
        // отправим письмо о начале и завершении задачи
        if (array_search($status, ['RUN', 'COMPLETE']) !== false)
        {
            // тема сообщения
            $subject = 'Смена статуса задачи';

            // поиск имени рубрики
            $rubric_name = isset($task->rubric->pluralName) ? $task->rubric->pluralName : '';

            switch ($status) {
                case 'RUN':
                    $subject = 'Запуск задачи';

                    $message = [
                        'Задача №: ' . $task->task_id,
                        'Фраза поиска: ' . $task->text,
                        'Рубрика: ' . $rubric_name,
                        'Время запуска: ' . date('d.m.Y H:i:s'),
                        '-----',
                        'По завершению сбора заявок мы отправим вам еще одно сообщение.'
                    ];
                    break;

                case 'COMPLETE':
                    // тема письма
                    $subject = 'Задача завершена';

                    // прайс
                    $price = ceil(($task->companies_count * 0.5) / 10) * 10;
                    $price = number_format($price, 0, ' ', ' ');

                    // текст письма
                    $message = [
                        'Задача №: ' . $task->task_id,
                        'Фраза поиска: ' . $task->text,
                        'Рубрика: ' . $rubric_name,
                        'Собрано: ' . $task->companies_count,
                        'Стоимость: ' . $price . ' руб.',
                        'Дата и время: ' . date('d.m.Y H:i:s'),
                        '-----',
                        'Ссылка на скачивание: <a href="'.site_url('export/excel/index/' . $task->task_id).'">Скачать</a>',
                        'Ссылка на демо: <a href="'.site_url('export/excel/index/' . $task->task_id .'/1').'">Скачать демо</a>'
                    ];
                    break;
            }

            // отправка письма
            return $this->sendEmail($this->email_admin, $subject, implode('<br>', $message));
        }

        return false;
    }


}
