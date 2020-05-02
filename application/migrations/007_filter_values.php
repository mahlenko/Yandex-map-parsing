<?php

class Migration_filter_values extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20
            ],
            'filter_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ],
            'selected' => [
                'type' => 'SMALLINT',
                'constraint' => 1
            ],
            'imageUrlTemplate' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'default' => ''
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('filter_values');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('filter_values');
    }

}