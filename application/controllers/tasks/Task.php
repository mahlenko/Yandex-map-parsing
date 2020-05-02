<?php

class Task extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Tasks/task_m'
        ]);
    }

    /**
     * @return mixed
     */
    public function index()
    {
        // список задач
        $tasks = $this->task_m->find(null, [], ['modified', 'DESC']);
        $this->data('TASKS', $tasks);

        if ($tasks) {
            // поиск запущеных задач
            $run_tasks_id = [];
            foreach ($tasks as $task) {
                if ($task->status == 'RUN') {
                    $run_tasks_id[] = $task->task_id;
                }
            }
            $this->data('RUN_TASKS_ID', $run_tasks_id);

            // регионы из задач
            $regions = [];
            $regions_list = $this->geography_m->find(array_column($tasks, 'geo_id'));
            if ($regions_list) {
                foreach ($regions_list as $region)
                {
                    $regions[$region->geo_id] = $region;
                }
                $this->data('GEOGRAPHY', $regions);
            }
        }

        // статусы задач
        $status_list = $this->task_m->getStatus();
        $this->data('STATUS_LIST', $status_list);

        return $this->twig->display('tasks/index', $this->data());
    }

    /**
     * Текущее состояние задач
     * @todo Создать отдельное API для задач
     */
    public function run_status()
    {
        $tasks_id = $this->input->post('tasks_id', true);
        if (! $tasks_id) {
            return ['result' => false];
        }

        if ($tasks_id) {
            $tasks = $this->task_m->find($tasks_id, ['status' => 'RUN']);
            return [
                'result' => 'ok',
                'tasks' => $tasks
            ];
        }

        return false;
    }
}
