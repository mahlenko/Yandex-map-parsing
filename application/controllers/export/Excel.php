<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Экспортирует компании из задачи
 * в файл Excel XLSX
 */
class Excel extends MY_Controller
{
    /**
     * Документ XLSX
     * @var
     */
    private $spreadsheet;

    /**
     * Лист документа
     * @var
     */
    private $sheet;

    /**
     * Буквы для столбцов
     * @var array
     */
    private $abc = [
        'A','B','C','D','E','F','G','H','I',
        'J','K','L','M','N','O','P','Q','R',
        'S','T','U','V','W','X','Y','Z'];

    /**
     * Названия столбцов документа
     * @var array
     */
    private $keys_document = [
        'title'         => 'Название',
        'locality'      => 'Населенный пункт',
        'fullAddress'   => 'Адрес',
        'category'      => 'Категория',
        'phones'        => 'Телефоны',
        'emails'        => 'E-mail адреса',
        'urls'          => 'Сайты',
        'social'        => 'Соц. сети',
        'rating'        => 'Рейтинг',
        'workingTimeText' => 'Рабочее время',
        'sources'       => 'Источники'
    ];

    /**
     * Задача
     * @var
     */
    private $task;

    /**
     * Компании задачи
     * @var array
     */
    private $companies = [];

    /**
     * @var bool
     */
    private $use_demo = false;

    /**
     * Расширенная информация по номерам телефона
     * добавит тип (факс, тел. факс) и доб. номер
     * вместе с комментарием.
     * @var bool
     */
    private $extend_phone = false;


    /**
     * Export constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'Company/company_m',
            'Tasks/task_m'
        ]);
    }

    /**
     * @param int $task_id
     * @param int $demo
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function index(int $task_id, int $demo = 0)
    {
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 0);

        // отмечаем демо данные
        $this->use_demo = boolval($demo);

        // поиск задачи
        $task = $this->task_m->find($task_id, ['status' => 'COMPLETE']);
        if (!$task) {
            redirect('tasks/task');
        }

        // задача
        $this->task = $task[0];

        // поиск компаний
        $this->companies = $this->company_m->find(null, ['task_id' => $task_id]);
        if (!$this->companies) {
            redirect('tasks/task');
        }

        // создаём документ
        $this->spreadsheet = new Spreadsheet();

        // создаём лист с названием запроса
        $this->sheet = $this->spreadsheet->getActiveSheet();

        // начальная позиция
        $row = 1;

        // добавляем в документ заголовки столбцов
        // название, телефоны, сайты ....
        $this->setTitle($row);

        // добавляем информацию по компаниям
        foreach ($this->companies as $company) {
            // готовим чистый массив с ключами документа
            $data_company = array_fill_keys(array_keys($this->keys_document), '');

            // информация о компании
            $this->company($company, $data_company);

            // категории
            $this->categories($company, $data_company);

            // номера телефонов
            $this->phones($company, $data_company);

            // ссылки на сайты
            $this->urls($company, $data_company);

            // email адреса
            $this->emails($company, $data_company);

            // социальные сети
            $this->socials($company, $data_company);

            // рейтинг
            $this->rating($company, $data_company);

            // источники
            $this->sources($company, $data_company);

            // добавляем данные компании в таблицу
            $index = 0;
            foreach ($data_company as $key => $value) {
                $column = $this->abc[$index] . $row;

                $this->sheet->getCell($column)->setValue($value);
                $this->sheet->getStyle($column)->getAlignment()->setWrapText(true)->setVertical('top');
                $index++;
            }

            $row++;
        }

        // ключевая фраза и/или рубрика поиска
        $keywords = [$this->task->text];
        if (isset($this->task->rubric->pluralName)) {
            $keywords[] = $this->task->rubric->pluralName;
        }
        $keywords = array_filter($keywords);

        // имя файла
        $filename = [
            implode('-', $keywords),
            $this->task->geography->name,
            date('d-m-Y-H-i', strtotime($this->task->end_process . '+3 hours'))
        ];

        // вернем файл в бразуер, без создания его на диске
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . implode('-', $filename) . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        $writer->save('php://output');

        // очищаем память
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
    }

    /**
     * Установит заголовки таблицы
     * @param $row
     * @param int $column
     */
    private function setTitle(&$row, $column = 0)
    {
        // размер колонки
        $this->spreadsheet->getActiveSheet()->getColumnDimension($this->abc[$column])->setWidth(25);

        // время завершения
        $this->sheet->getCell($this->abc[$column].$row)->setValue('База собрана: ' . date('d.m в H:i:s', strtotime($this->task->end_process .' +3 hours')));
        $column++;

        // количество собранных компаний
        $this->sheet->getCell($this->abc[$column].$row)->setValue('Собрано компаний: ' . count($this->companies));
        $column++;

        // ссылка на сервис баз данных
        // $this->sheet->getCell($this->abc[$column].$row)->setValue('Сервис баз данных: https://kupidb.ru');

        // сообщение о том что это демо версия
        if ($this->use_demo) {
            $this->sheet->getCell($this->abc[$column].$row)->setValue('Выгружена демо версия, со скрытыми данными компаний.');

            // выделяем красным
            $this->sheet->getStyle($this->abc[$column].$row)
                ->getFont()
                ->getColor()
                ->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        }

        // сбрасываем колонки, увеличиваем строки
        $column = 0; $row++;

        // информация о сборке
        $this->sheet->getCell($this->abc[$column].$row)
            ->setValue('База данных предоставляется как есть. Информация о компаниях собранны автоматически или заполнены представителем компании.');

        $row++;
        $row++;

        // заполняем документ названиями столбцов
        foreach ($this->keys_document as $column_name_ru) {
            // название колонки А1
            $position_name = $this->abc[$column].$row;

            // размер колонки
            $this->spreadsheet->getActiveSheet()->getColumnDimension($this->abc[$column])->setWidth(25);

            // записываем значение колонки
            $this->sheet->getCell($position_name)->setValue($column_name_ru);

            // устанавливаем стили
            $this->sheet->getStyle($position_name)
                ->getAlignment()
                ->setWrapText(true)
                ->setVertical('top');

            // увеличиваем номер столбца
            $column++;
        }

        // добавим фильтрацию
//        $this->spreadsheet->getActiveSheet()
//            ->setAutoFilter($this->spreadsheet->getActiveSheet()->calculateWorksheetDimension());

        // сделаем фон светло-серым
        $this->spreadsheet
            ->getActiveSheet()
            ->getStyle('A'.$row.':'.$this->abc[count($this->keys_document)-1].$row)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE3E3E3');

        // переходим к следующей строке
        $row++;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function company($company, &$data)
    {
        $data_keys = ['title', 'locality', 'workingTimeText', 'fullAddress'];

        foreach ($data_keys as $key) {
            if (isset($company->{$key}) && ! empty($company->{$key})) {
                $data[$key] = trim($company->{$key});

                // дополнительная информация по адресу
                if ($key == 'fullAddress') {
                    if (isset($company->additionalAddress) && !empty($company->additionalAddress)) {
                        $data[$key] .= ' (' . $company->additionalAddress . ')';
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function categories($company, &$data)
    {
        if (! isset($company->CATEGORIES) || ! $company->CATEGORIES) {
            return true;
        }

        $category = [];
        foreach ($company->CATEGORIES as $item) {
            $category[] = $item->name;
        }

        $data['category'] = implode("\n", $category);

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function phones($company, &$data)
    {
        $types = [
            'phone' => 'тел.:',
            'fax' => 'факс: ',
            'phone-fax' => 'тел./факс: '
        ];

        if (! isset($company->PHONES) || ! $company->PHONES) {
            return true;
        }

        $phones = [];
        foreach($company->PHONES as $phone) {
            $_phone = '';

            if ($this->extend_phone && $phone->type != 'phone') {
                $_phone .= $types[$phone->type];
            }

            if ($this->use_demo) {
                $_phone .= $this->hide($phone->number);
            } else {
                $_phone .= $phone->number;
            }

            if ($this->extend_phone && ! empty($phone->extraNumber)) {
                $_phone .= ' доп. '.$phone->extraNumber;
            }

            if ($this->extend_phone && ! empty($phone->info)) {
                $_phone .= ' ('.$phone->info.')';
            }

            $phones[] = trim($_phone);
        }

        $data['phones'] = implode(", ", array_unique($phones));
        unset($phones);

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function urls($company, &$data)
    {
        if (! isset($company->URLS) || ! $company->URLS) {
            return true;
        }

        $urls = array_column($company->URLS, 'url');

        // скрываем для демо доступа
        if ($this->use_demo) {
            foreach ($urls as $index => $url) {
                $urls[$index] = $this->hide($url, '*', 40);
            }
        }

        $data['urls'] = implode("\n", array_unique($urls));

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function emails($company, &$data)
    {
        if (! isset($company->EMAILS) && !$company->EMAILS) {
            return true;
        }

        $emails = array_column($company->EMAILS, 'email');

        // скрываем для демо доступа
        if ($this->use_demo) {
            foreach ($emails as $index => $email) {
                $emails[$index] = $this->hide($email);
            }
        }

        $data['emails'] = implode(", ", array_unique($emails));

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function socials($company, &$data)
    {
        if (! isset($company->SOCIALS) && ! $company->SOCIALS) {
            return true;
        }

        $socials = array_column($company->SOCIALS, 'href');

        // скрываем для демо доступа
        if ($this->use_demo) {
            foreach ($socials as $index => $social) {
                $socials[$index] = $this->hide($social, '*', 40);
            }
        }

        $data['social'] = implode("\n", array_unique($socials));

        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function rating($company, &$data)
    {
        if (! isset($company->RATING) && ! $company->RATING) {
            return true;
        }

        $_rating = [];
        foreach ($company->RATING as $item) {
            $key_ru = $item->name;
            if (isset($this->company_rating_m->keys[$item->name])) {
                $key_ru = $this->company_rating_m->keys[$item->name];
            }

            $_rating[] = $key_ru .': '. doubleval($item->value);
        }

        $data['rating'] = implode("\n", array_unique($_rating));
        return true;
    }

    /**
     * @param $company
     * @param $data
     * @return bool
     */
    private function sources($company, &$data)
    {
        if (! isset($company->SOURCES) || ! $company->SOURCES) {
            return true;
        }

        $sources = array_column($company->SOURCES, 'name');
        $data['sources'] = implode(', ', array_unique($sources));
    }


    /**
     * Cкрыть текст за символами
     * @param string $string
     * @param string $char
     * @param int $percent
     * @return string
     */
    private function hide($string = '', $char = '*', $percent = 50)
    {
        if (empty($string)) return $string;

        $count_char = strlen($string);
        $count_char_hide = ceil(($count_char / 100) * $percent);

        $max_char_number = $count_char - $count_char_hide;
        $string_slice = mb_substr($string, 0, $max_char_number);

        for ($i = 0; $i < $count_char_hide; $i++) {
            $num_skip = intval($max_char_number + $i);

            if (preg_match('/[\s\-.@]/', $string[$num_skip], $match))
            {
                $string_slice .= $match[0];
                continue;
            }

            $string_slice .= $char;
        }

        return $string_slice;
    }

}
