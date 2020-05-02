<?php

class Migration_company extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'uid' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true
            ],
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20
            ],
            'task_id' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'shortTitle' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'additionalAddress' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'fullAddress' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'postalCode' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'locality' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => null
            ],
            'workingTimeText' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => null
            ],
            'seoname' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'geoId' => [
                'type' => 'INT',
                'constraint' => 11
            ],
            'tzOffset' => [
                'type' => 'INT',
                'constraint' => 11
            ]
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('uid', true);
        $this->dbforge->create_table('companies');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('companies');
    }

}