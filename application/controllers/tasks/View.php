<?php

class View extends MY_Controller
{
    public function __construct() {
        parent::__construct();

        $this->load->model('Tasks/task_m');
    }

    public function index($task_id = null)
    {
        if (! $task_id) {
            redirect('tasks/task');
        }

        $task = $this->task_m->find($task_id);
        if (! $task) {
            redirect('tasks/task');
        }

        return $this->twig->display('tasks/view', $this->data());
    }
}