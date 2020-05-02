<?php

class Rubrics extends CI_Controller
{
    public function index()
    {
        $this->load->model([
            'Company/company_category_m',
            'Yandex/category_m'
        ]);

        // получаем категории из компаний
        $this->db->group_by('id');
        $categories = $this->company_category_m->get();

        if (! $categories && ! is_cli()) {
            return [
                'result'  => false,
                'message' => 'Категорий еще не найдено.'
            ];
        }

        $new_category_name = [];
        foreach ($categories as $category)
        {
            // добавляем новые категории в бд
            if (! $this->category_m->count($category->id)) {
                $result = $this->category_m->save(null, [
                    'id'            => $category->id,
                    'name'          => $category->name,
                    'pluralName'    => $category->pluralName,
                    'seoname'       => $category->seoname,
                ]);

                if (! $result && ! is_cli()) {
                    return [
                        'result' => false,
                        'message' => 'Не удалось добавить категорию с id ' . $category->id .'.'
                    ];
                }

                $new_category_name[] = $category->pluralName;
            }
        }

        // бекапим базу данных
        $this->backup();

        if (! is_cli()) {
            return [
                'result' => true,
                'data' => ['categories' => $new_category_name],
                'message' => 'Добавлено: ' . count($new_category_name) . ' кат.'
            ];
        }
    }


    /**
     * Бекапим таблицу с категориями
     */
    private function backup()
    {
        $this->load->dbutil();

        $filename = 'categories.sql';

        // Резервное копирование всей вашей базы и присвоение переменной
        $backup = $this->dbutil->backup([
            'tables'        => array('categories'),   // Массив таблиц для резервного копирования.
            // 'ignore'        => array(),               // Список таблиц, которые следует исключить из резервной копии
            'format'        => 'txt',                 // gzip, zip, txt
            'add_drop'      => true,                  // Нужно ли добавлять DROP TABLE заявление
            'add_insert'    => true,                  // Нужно ли добавлять INSERT заявление
            'newline'       => "\n"                   // Символ новой строки, используемый в файл резервной копии
        ]);

        // Загрузить файловый помощник и записать файл на ваш сервер
        $this->load->helper('file');
        write_file(APPPATH .'/migrations/backups/'.$filename, $backup);
    }
}
