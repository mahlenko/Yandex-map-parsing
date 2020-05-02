<?php

class Migration_proxy extends CI_Migration {

    /**
     * Создаст таблицу для хранения прокси
     */
    public function up()
    {
        $this->dbforge->add_field([
            'proxy_id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'lock' => [
                'type' => 'SMALLINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Блокировка за задачей'
            ],
            'active' => [
                'type' => 'SMALLINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => 'Активный?'
            ],
            'used_tasks' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Количество назначенных задач'
            ],
            'captcha_detect' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'comment' => 'Количество сработанных капч'
            ],
            'tariff_id' => [
                'type' => 'SMALLINT',
                'constraint' => 1,
                'default' => null,
                'comment' => 'Тариф'
            ],
            'login' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
            ],
            'password' => [
                'type' =>'VARCHAR',
                'constraint' => 16,
                'default' => null,
            ],
            'server' => [
                'type' =>'VARCHAR',
                'constraint' => 16,
                'default' => null,
                'unique' => true,
            ],
            'port' => [
                'type' =>'INT',
                'constraint' => 5,
                'default' => null,
            ],
            'socks_port' => [
                'type' =>'INT',
                'constraint' => 5,
                'default' => null,
            ],
            'comment' => [
                'type' =>'VARCHAR',
                'constraint' => 255,
                'default' => null,
                'unique' => true,
                'comment' => ''
            ],
            'token' => [
                'type' =>'VARCHAR',
                'constraint' => 100,
                'default' => null,
                'unique' => true,
                'comment' => 'Яндекс токен'
            ],
            'cookies' => [
                'type' =>'TEXT',
                'default' => '',
                'comment' => 'Куки отправляемые в запросе Яндексу, там же и капча'
            ],
        ]);

        $this->dbforge->add_field("`expired`  timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('proxy_id', true);
        $this->dbforge->add_key('lock');
        $this->dbforge->create_table('proxies');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('proxies');
    }

}