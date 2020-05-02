<?php

class Migration_tasks extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'task_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'rubric_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'comment' => 'Рубрика поиска'
            ],
            'text' => [
                'type' =>'VARCHAR',
                'constraint' => 50,
                'default' => null,
                'comment' => 'Ключевая фраза'
            ],
            'geo_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'skip' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0
            ],
            'position' => [
                'type' => 'INT',
                'construct' => 11,
                'default' => 0
            ],
            'proxy_id' => [
                'type' => 'INT',
                'construct' => 11,
                'default' => 0
            ],
            'start_process' => [
                'type' => 'TIMESTAMP',
                'default' => null
            ],
            'end_process' => [
                'type' => 'TIMESTAMP',
                'default' => null
            ]
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('task_id', true);
        $this->dbforge->create_table('tasks');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('tasks');
    }

}