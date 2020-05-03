<?php

use Curl\Curl;

class Company_emails_m extends MY_Model
{
    protected $table = 'company_emails';
    protected $primary_key = 'company_id';

    private $pages_name = ['kontakt', 'kontakty', 'contact', 'contacts', 'feedback'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Company/company_urls_m');
    }

    /**
     * Названия полей на русском
     * @var array
     */
    public $keys = [
        'email' => 'Email'
    ];

    /**
     * @param null $company_id
     * @param array $wheres
     * @return array
     */
    public function find($company_id = null, $wheres = [])
    {
        $items = $this->get($company_id, $wheres);
        if (! $items) {
            return [];
        }

        $data = [];
        foreach ($items as $item) {
            $data[$item->company_id][] = $item;
        }

        return $data;
    }

    /**
     * Поиск и сохранение email адресов
     * @param array $urls
     * @param $company_id
     * @return bool
     * @throws ErrorException
     */
    public function findAndSave($urls = [], $company_id)
    {
        $urls = array_unique($urls);

        foreach ($urls as $url) {
            $punycode = $this->punycodeEncode($url);

            // поиск страниц контактов по sitemap.xml
            $pages = $this->findContactPages($punycode, $company_id);
            if ($pages) {
                dump_warning('Страниц поиска email: ' . count($pages));
            }

            if (! $pages) {
                continue;
            }

            // парсинг найденных страниц
            foreach ($pages as $page) {

                dump_info('Просмотр страницы: '. $page);

                $curl = new Curl();
                $curl->setTimeout(1);
                $curl->get($page);

                if ($curl->httpStatusCode === 200) {
                    // успешная загрузка страницы, ищем email адреса
                    preg_match_all('/([a-zA-Z0-9\._%+\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,6})/ui', $curl->response, $_emails, PREG_SET_ORDER);

                    if ($_emails) {
                        $emails_arr = [];
                        foreach ($_emails as $arr) {
                            foreach ($arr as $email) {
                                if (filter_var($email,FILTER_VALIDATE_EMAIL)) {
                                    $emails_arr[] = trim($email);
                                }
                            }
                        }

                        $emails_arr = array_unique($emails_arr);

                        if (isset($emails_arr) && count($emails_arr)) {
                            foreach ($emails_arr as $email) {
                                $data = [
                                    'company_id' => $company_id,
                                    'email' => $email,
                                ];

                                // проверяем что такого ящика еще нет у компании
                                if ($this->count($company_id, $data)) {
                                    continue;
                                }

                                // добавляем почтовый ящик к компании
                                if (! $this->save(null, $data)) {
                                    foreach ($this->errors()['error_text'] as $error) {
                                        $this->save_error($error);
                                    }

                                    return false;
                                }
                            }
                        }
                    }
                }

                // сохраним статус проверки адреса
                $this->db->where('url', $url);
                $this->db->where('company_id', $company_id);
                $this->company_urls_m->save(null, [
                    'check_status_code' => $curl->httpStatusCode
                ]);
            }
        }

        return true;
    }

    /**
     * Поиск страниц с контактами
     * @param string $url
     * @param int $company_id
     * @return array|bool
     * @throws ErrorException
     */
    private function findContactPages($url, $company_id)
    {
        $url = trim($url, '/') . '/';
        $robots_txt = trim($url, '/') .'/robots.txt';

        // не проверяем не доступные адреса
        if (! $this->company_urls_m->count(null, ['url' => $url, 'company_id' => $company_id, 'check_status_code' => 200]))
        {
            dump_error('Адрес ' . $url . ' проверялся ранее.');
            return false;
        }

        // получим файл robots.txt
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setTimeout(1);
        $curl->get($robots_txt);

        if ($curl->httpStatusCode === 0) {
            return false;
        }

        if ($curl->httpStatusCode == 200) {
            // поиск файла sitemap
            $sitemap = $this->searchSitemap($curl->response);

            if ($sitemap) {
                // если найден файл sitemap.xml парсим его в
                // поисках страниц с контактами
                $contact_pages = $this->searchContactPagesSitemap($sitemap);
            }
        }

        if (! isset($contact_pages)) {
            $contact_pages = [];
            foreach ($this->pages_name as $page) {
                $contact_pages[] = $url . $page;
            }
        }

        // добавляем проверку главной страницы
        $contact_pages[] = $url;

        return $contact_pages;
    }

    /**
     * Переводит доменное имя в punycode.
     * @param $url
     * @return string
     */
    private function punycodeEncode($url)
    {
        // конвертируем имя домена в punycode
        $url = rtrim($url, '/');
        $parse_url = parse_url($url);
        $uri = mb_substr($url, strlen($parse_url['scheme'])+3);

        // домен в punycode
        return $parse_url['scheme'].'://' . idn_to_ascii($uri);
    }

    /**
     * Поиск строки со ссылкой на sitemap.xml
     * @param string $robots_txt
     * @return mixed
     */
    private function searchSitemap($robots_txt = '')
    {
        if (empty($robots_txt)) {
            return false;
        }

        foreach(explode("\n", $robots_txt) as $row) {
            $row = trim($row);
            // поиск строки начинающейся на sitemap
            if (preg_match('/^sitemap\:/ui', $row)) {
                return trim(mb_substr($row, 8));
            }
        }

        return false;
    }

    /**
     * @param $sitemap
     * @return bool
     * @throws ErrorException
     */
    private function searchContactPagesSitemap($sitemap)
    {
        $curl = new Curl();
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, 0);
        $curl->setTimeout(1);
        $curl->get($sitemap);

        if ($curl->httpStatusCode == 200) {
            if (isset($curl->response->url)) {
                foreach ($curl->response->url as $item) {
                    if (preg_match('/(' . implode('|', $this->pages_name) . ')/ui', $item->loc)) {
                        return [(string)$item->loc];
                    }
                }
            }
        }

        return false;
    }

}
