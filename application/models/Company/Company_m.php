<?php

use TrueBV\Punycode;

class Company_m extends MY_Model
{
    protected $table = 'companies';
    protected $primary_key = 'id';

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'id'            => 'ID',
        'type'          => 'Тип компании',
        'geoId'         => 'ID региона',
        'title'         => 'Название',
        'seoname'       => 'Название для URL',
        'address'       => 'Адрес',
        'locality'      => 'Местность',
        'postalCode'    => 'Индекс',
        'shortTitle'    => 'Сокр. название',
        'fullAddress'   => 'Полный адрес',
        'description'   => 'Описание',
        'tzOffset'      => 'Отступ времени от UTC+0',
        'workingTimeText'   => 'Рабочее время',
        'additionalAddress' => 'Дополнительный адрес'
    ];

    /**
     * @param $response
     * @param $task
     * @return bool
     * @throws ErrorException
     */
    public function add($response, $task)
    {
        $company_add_count = 0;

        if ($response->totalResultCount) {
            $regions_find = explode(' и ', $task->geography->name);

            foreach ($response->items as $company)
            {
                // проверяем что адрес входит в проверяемые
                if (! $this->check_address($company, $task)) {
                    continue;
                }

                // проверяем чтобы адрес содержал регион поиска
//                if (! preg_match('/('.implode('|', $regions_find).')/', $company->fullAddress)) {
//                    if (is_cli()) {
//                         dump("Адрес не соответствует: " . $company->fullAddress);
//                    }
//                    continue;
//                }=

                // если компания уже есть в бд
                if ($this->count($company->id, ['task_id' => $task->task_id, 'fullAddress' => $company->fullAddress])) {
                    continue;
                }

                // сохранить компанию
                if (!$this->company($company, $task->task_id)) {
                    return false;
                }

                // номера телефонов
                if (isset($company->phones) && !$this->phones($company->phones, $company->id)) {
                    return false;
                }

                // категории
                if (isset($company->categories) && !$this->category($company->categories, $company->id)) {
                    return false;
                }

                // рабочее время
                if (isset($company->workingTime) && !$this->workingTime($company->workingTime, $company->id)) {
                    return false;
                }

                // социальные сети
                if (isset($company->socialLinks) && !$this->social($company->socialLinks, $company->id)) {
                    return false;
                }

                // официальные сайты, и ссылки
                if (isset($company->urls) && !$this->urls($company->urls, $company->id)) {
                    return false;
                }

                // параметры компании
                if (isset($company->businessProperties) && !$this->properties($company->businessProperties, $company->id)) {
                    return false;
                }

                // рейтинг компании
                if (isset($company->ratingData) && !$this->rating($company->ratingData, $company->id)) {
                    return false;
                }

                // источники
                if (isset($company->sources) && !$this->sources($company->sources, $company->id)) {
                    return false;
                }

                // собрать email с сайтов
                if (isset($company->urls) && !$this->emails($company->urls, $company->id)) {
                    return false;
                }

                $company_add_count++;
            }
        }

        if (is_cli() && $company_add_count) {
            dump_success("-- Добавлено компаний: " . $company_add_count);
        }

        // обновляем задачу
        $data = [
            'task_id' => $task->task_id,
            'skip' => $task->skip + $response->requestResults,
            'position' => $task->position
            ];

        // переходим к следующей секции
        if ($response->totalResultCount < $task->skip) {
            $data['skip'] = 0;
            $data['position'] = $task->position + 1;
        }

        // обновляем данные задачи
        $result = $this->task_m->save($task->task_id, $data);

        // зацикливаем дальнейшую проверку под одним прокси
        if ($result && $data['skip'] > 0) {
            $task->skip = $data['skip'];
            $task->position = $data['position'];
            return $task;
        }

        return true;
    }

    /**
     * @param null $company_id
     * @param array $wheres
     * @return bool
     */
    public function find($company_id = null, $wheres = [])
    {
        $companies = $this->get($company_id, $wheres);
        if (!$companies) {
            return false;
        }

        // id компаний
        $companies_id = array_column($companies, 'id');

        $this->load->model([
            'Company/company_phone_m',
            'Company/company_emails_m',
            'Company/company_urls_m',
            'Company/company_social_m',
            'Company/company_category_m',
            'Company/company_rating_m',
            'Company/company_sources_m',
        ]);

        // телефоны
        $phones = $this->company_phone_m->find($companies_id);

        // email
        $emails = $this->company_emails_m->find($companies_id);

        // ссылки
        $urls = $this->company_urls_m->find($companies_id);

        // социальные сети
        $socials = $this->company_social_m->find($companies_id);

        // категории
        $categories = $this->company_category_m->find($companies_id);

        // рейтинг
        $rating = $this->company_rating_m->find($companies_id);

        // источники
        $sources = $this->company_sources_m->find($companies_id);

        foreach ($companies as $index => $company)
        {
            // пустые по-умолчанию
            $company->PHONES    = [];
            $company->EMAILS    = [];
            $company->URLS      = [];
            $company->SOCIALS  = [];
            $company->CATEGORIES = [];
            $company->RATING    = [];
            $company->SOURCES   = [];

            // номера телефонов
            if (array_key_exists($company->id, $phones)) {
                $company->PHONES = $phones[$company->id];
            }

            // email адреса
            if (array_key_exists($company->id, $emails)) {
                $company->EMAILS = $emails[$company->id];
            }

            // сайты
            if (array_key_exists($company->id, $urls)) {
                $company->URLS = $urls[$company->id];
            }

            // соц. сети
            if (array_key_exists($company->id, $socials)) {
                $company->SOCIALS = $socials[$company->id];
            }

            // категории
            if (array_key_exists($company->id, $categories)) {
                $company->CATEGORIES = $categories[$company->id];
            }

            // рейтинг
            if (array_key_exists($company->id, $rating)) {
                $company->RATING = $rating[$company->id];
            }

            // источники
            if (array_key_exists($company->id, $sources)) {
                $company->SOURCES = $sources[$company->id];
            }

            $companies[$index] = $company;
        }

        return $companies;
    }

    /**
     * @param $company
     * @param $taks
     * @return bool
     */
    private function check_address($company, $taks)
    {
        return (array_search($taks->geography->geo_id, $company->region->hierarchy) === false) ? false : true;
    }

    /**
     * @param $data
     * @param int $task_id
     * @return bool
     */
    private function company($data, int $task_id)
    {
        $company = ['task_id' => $task_id];

        foreach ($this->keys as $key => $value) {
            if (isset($data->$key)) {
                $company[$key] = trim($data->$key);
            }

            if ($key == 'locality') {
                if (isset($data->addressDetails->locality)) {
                    $company[$key] = $data->addressDetails->locality;
                }
            }
        }

        // сохраняем компанию
        if (! $this->count($company['id'], ['task_id' => $task_id])) {
            return $this->save(null, $company);
        }

        return $this->save($company['id'], $company);
    }

    /**
     * @param $phones
     * @param int $company_id
     * @return bool
     */
    private function phones($phones, int $company_id)
    {
        $this->load->model('Company/company_phone_m');

        foreach ($phones as $phone) {

            $data = [
                'type'          => $phone->type,
                'info'          => $phone->info ?? '',
                'number'        => $phone->number,
                'company_id'    => $company_id,
                'extraNumber'   => $phone->extraNumber ?? '',
            ];

            if (! $this->company_phone_m->count(null, ['number' => $phone->number, 'company_id' => $company_id])) {
                if (!$this->company_phone_m->save(null, $data)) {
                    foreach ($this->company_phone_m->errors()['error_text'] as $error) {
                        $this->save_error($error);
                    }

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $categories
     * @param int $company_id
     * @return bool
     */
    private function category($categories, int $company_id)
    {
        $this->load->model('Company/company_category_m');

        foreach ($categories as $category)
        {
            $data = [
                'name'          => $category->name,
                'seoname'       => $category->seoname,
                'pluralName'    => $category->pluralName,
                'company_id'    => $company_id,
            ];

            if ($this->company_category_m->count($company_id, ['name' => $category->name])) {
                continue;
            }

            if (! $this->company_category_m->save(null, $data)) {
                foreach ($this->company_category_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $working_time
     * @param int $company_id
     * @return bool
     */
    private function workingTime($working_time, int $company_id)
    {
        $this->load->model('Company/company_working_time_m');

        if ($working_time) {
            foreach ($working_time as $day_id => $day) {
                if (! $day) continue;

                foreach ($day as $item) {
                    foreach ($item as $type => $time) {
                        $data = [
                            'dayId' => $day_id,
                            'type' => $type,
                            'hours' => $time->hours,
                            'minutes' => $time->minutes,
                            'company_id' => $company_id,
                        ];

                        if ($this->company_working_time_m->count(null, $data)) {
                            continue;
                        }

                        if (!$this->company_working_time_m->save(null, $data)) {
                            foreach ($this->company_working_time_m->errors()['error_text'] as $error) {
                                $this->save_error($error);
                            }

                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * @param $social_links
     * @param int $company_id
     * @return bool
     */
    private function social($social_links, int $company_id)
    {
        $this->load->model('Company/company_social_m');

        foreach ($social_links as $link) {
            $data = [
                'name'       => $link->name ?? null,
                'type'       => $link->type,
                'href'       => $link->href,
                'company_id' => $company_id,
            ];

            dump_success('-- Добавлен сайт компании: '. $link->href);

            if ($this->company_social_m->count(null, $data)) {
                continue;
            }

            if (! $this->company_social_m->save(null, $data)) {
                foreach ($this->company_social_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $urls
     * @param int $company_id
     * @return bool
     */
    private function urls($urls, int $company_id)
    {
        $this->load->model('Company/company_urls_m');

        foreach ($urls as $url) {
            $data = [
                'url'        => $url,
                'company_id' => $company_id,
            ];

            if ($this->company_urls_m->count(null, $data)) {
                continue;
            }

            if (! $this->company_urls_m->save(null, $data)) {
                foreach ($this->company_urls_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $business_properties
     * @param int $company_id
     * @return bool
     */
    private function properties($business_properties, int $company_id)
    {
        $this->load->model('Company/company_properties_m');

        foreach ($business_properties as $name => $value) {

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $data = [
                'name'       => $name,
                'value'      => $value,
                'company_id' => $company_id,
            ];

            if ($this->company_properties_m->count(null, $data)) {
                continue;
            }

            if (! $this->company_properties_m->save(null, $data)) {
                foreach ($this->company_properties_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $rating
     * @param int $company_id
     * @return bool
     */
    private function rating($rating, int $company_id)
    {
        $this->load->model('Company/company_rating_m');

        foreach ($rating as $name => $value) {
            $data = [
                'name'       => $name,
                'value'      => $value,
                'company_id' => $company_id,
            ];

            if ($this->company_rating_m->count($company_id, $data)) {
                continue;
            }

            if (! $this->company_rating_m->save(null, $data)) {
                foreach ($this->company_rating_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $sources
     * @param int $company_id
     * @return bool
     */
    private function sources($sources, int $company_id)
    {
        $this->load->model('Company/company_sources_m');

        foreach ($sources as $source)
        {
            if (! $source) continue;

            $id = empty($source->id) ? null : $source->id;
            $data = [
                'id'         => $id,
                'name'       => $source->name,
                'href'       => $source->href,
                'company_id' => $company_id,
            ];

            if ($this->company_sources_m->count(null, $data)) {
                continue;
            }

            if (! $this->company_sources_m->save(null, $data)) {
                foreach ($this->company_sources_m->errors()['error_text'] as $error) {
                    $this->save_error($error);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Найден и сохранит email адреса
     * @param $urls
     * @param int $company_id
     * @return mixed
     */
    private function emails($urls, int $company_id)
    {
        $this->load->model('Company/company_emails_m');
        dump_info('-- Поиск email адресов: ' . implode(', ', $urls));
        return $this->company_emails_m->findAndSave($urls, $company_id);
    }

}
