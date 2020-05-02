<?php

class Migration_filters extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ],
            'disabled' => [
                'type' => 'SMALLINT',
                'constraint' => 1
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'isTop' => [
                'type' => 'SMALLINT',
                'constraint' => 1
            ],
            'selected' => [
                'type' => 'SMALLINT',
                'constraint' => 1
            ]
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('filters');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('filters');
    }

}