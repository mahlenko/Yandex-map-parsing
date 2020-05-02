<?php

class Edit extends MY_Controller
{
    const STATUS_CREATE = 'CREATED';

    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Yandex/category_m',
            'Yandex/geography_m',
            'Tasks/task_m']);
    }

    /**
     * @param int|null $task_id
     * @return mixed
     */
    public function index(int $task_id = null)
    {
        // проверка формы
        $this->form_validation->set_rules('geo_id', 'регион', 'trim|required|is_natural_no_zero', [
            'is_natural_no_zero' => 'Выберите регион поиска или добавьте новый'
        ]);
        $this->form_validation->set_rules('rubric_id', 'рубрика', 'trim|callback_required_text');
        $this->form_validation->set_rules('text', 'ключевая фраза', 'trim');
        if ($this->form_validation->run())
        {
            // можно редактировать только задачи со статусом "создан"
            if ($task_id) {
                $result = $this->task_m->count($task_id, ['status' => 'CREATED']);
                if (!$result) {
                    // @todo сообщение об ошибки
                    redirect('tasks/task');
                }
            }

            $data = $this->input->post(['geo_id', 'text', 'rubric_id'], true);

            // сохраняем
            $task = $this->task_m->save($task_id, array_merge($data, [
                'status' => self::STATUS_CREATE
            ]));

            if ($task) {
                redirect('tasks/task');
            }
        }

        // получаем все регионы
        $geography = $this->geography_m->get();
        if ($geography) {
            $this->data('GEOGRAPHY', $this->geography_m->dropdown($geography, 'name'));
        }

        // получим категории
        $categories = $this->category_m->get(null, [], ['pluralName', 'ASC']);
        if ($categories) {
            $this->data('RUBRICS', $this->category_m->dropdown($categories, 'pluralName'));
        }

        // получаем задачу
        if ($task_id) {
            $task = $this->task_m->get($task_id, ['status' => 'CREATED']);
            if (! $task) redirect('/tasks/task');

            $this->data('TASK', $task[0]);
        }

        return $this->twig->display('tasks/edit', $this->data());
    }


    /**
     * Проверит заполнили ли ключевую фразу
     * или рубрику
     * @param string $text
     * @return bool
     */
    public function required_text(string $rubric_id = '')
    {
        $text = $this->input->post('text', true);
        if (! empty($text) || intval($rubric_id)) {
            return true;
        }

        $this->form_validation->set_message('required_text', 'Заполните имя рубрики или ключевую фразу');
        return false;
    }


    /**
     * Изменить статус задачи
     * @param int $task_id
     * @param string $status
     */
    public function status(int $task_id, string $status)
    {
        // нельзя "завершить задачу" по ссылке,
        // завершить задачу может только скрипт обработки
        if (mb_strtoupper($status) == 'COMPLETE') {
            redirect('tasks/task');
        }

        // меняем статус
        $result = $this->task_m->setStatus($task_id, $status);
        if (! $result) {
            // $this->task_m->errors();
            redirect('tasks/task');
        }

        // успешно сменили статус
        // @todo добавить сообщение
        redirect('tasks/task');
    }

}
