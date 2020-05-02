<?php

/**
 * Класс для получения компаний из яндекс.карт.
 */
class Run extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Tasks/task_m',
            'Yandex/proxy_m',
            'Company/company_m',
            'Requests/map_requests',
        ]);
    }

    /**
     * Запускает и останавливает задачи
     * @return bool
     */
    public function index()
    {
        // получим задачи которые нужно обработать
        $tasks = $this->task_m->find(null, [
            'status'   => 'RUN',
            'end_process' => null,
        ], ['modified', 'ASC']);

        if ($tasks) {
            // задачи в работе
            foreach ($tasks as $task)
            {
                // назначаем прокси
                if (! $task->proxy_id) {
                    // проверяем наличе свободного прокси
                    $free_proxy = $this->proxy_m->getFree();

                    // если нет свободного прокси, откладываем на потом
                    if (! $free_proxy) {
                        if (is_cli()) {
                            dump('Нет свободного прокси для задачи.');
                        }

                        continue;
                    }

                    // назначаем задаче прокси сервер и устанавливаем
                    // начальную позицию парсинга
                    $result = $this->task_m->save($task->task_id, [
                        'proxy_id' => $free_proxy->proxy_id,
                        'position' => 1
                    ]);

                    if ($result) {
                        // блокируем прокси
                        $this->proxy_m->lock($free_proxy->proxy_id);

                        if (is_cli()) {
                            dump('Стартуем задачу: '
                                . $task->geography->address . '. Секций: '
                                . count($task->geography->containers));
                        }
                    }

                    // увеличиваем количество полученных задач для прокси
                    $this->proxy_m->lock($free_proxy->proxy_id);
                }

                // завершаем задачу если всё собрали
                if ($task->skip == 0 && $task->position >= count($task->geography->containers)) {
                    $result = $this->task_m->setStatus($task->task_id, 'COMPLETE');

                    if ($result && is_cli()) {
                        dump('Завершаем задачу. Собрано:' . $task->companies_count);
                    }

                    // освобождаем прокси сервер
                    $result = $this->proxy_m->unlock($task->proxy_id);
                    if ($result && is_cli()) {
                        dump('Освобождаем прокси сервер.');
                    }

                    // отправляем сообщение пользователю

                    // ----------------------------------

                    continue;
                }

                // запускаем задачу
                $this->id($task->task_id);
            }
        }

        return true;
    }

    /**
     * Запуск задачи по ID
     * @param int $task_id
     * @return array
     */
    public function id($task_id = 0)
    {
        if (! is_cli() && ! $task_id) {
            return [
                'result'  => false,
                'message' => 'Не указан номер задачи.'
            ];
        }

        // поиск задачи
        $task = $this->task_m->find($task_id, ['status' => 'RUN']);

        // если задача не найдена
        if (! $task) {
            // сообщение об ошибке
            $message = 'Задача с ID '. $task_id .' не найдена или ещё не запущена.';

            // задача запущена в браузере
            if (! is_cli()) {
                return [
                    'return'  => false,
                    'message' => $message
                ];
            } else {
                // отправляем сообщение в демон
                dump($message);
            }
        };

        return $this->run($task[0]);
    }

    /**
     * Получает и сохраняет компании
     * @param $task
     * @return array|bool
     */
    private function run($task)
    {
        //if (is_cli()) dump('Отправляем запрос в Яндекс');

        // загружаем компании
        $data = $this->map_requests->load($task);

        // не найдено компаний, или произошла ошибка
        if (! $data || ! isset($data->totalResultCount)) {
            // сообщение в браузер
            if (! is_cli()) {
                return [
                    'result'  => false,
                    'message' => 'Не найдено данных или произошла ошибка. Подробное сообщение в демоне.'
                ];
            }

            return false;
        }

        // сообщения о записях
        //if (isset($data->totalResultCount)) {
            // if (is_cli()) {
            //    dump(date('H:i:s') . ' Найдено: ' . $data->totalResultCount);
            //}
        //}

        // сохраняем полученные данные о кампаниях
        $task = $this->company_m->add($data, $task);

        // зацикливаем в один запрос если есть что проверить
        if (! is_bool($task)) {
            return $this->run($task);
        }

        return $task;
    }

}