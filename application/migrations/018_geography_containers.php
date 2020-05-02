<?php

class Migration_geography_containers extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 6,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'geo_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'lon' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Долгота'
            ],
            'lat' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Широта'
            ],
            'spn_lon' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Долгота',
                'default' => null,
                'null' => true
            ],
            'spn_lat' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Широта',
                'default' => null,
                'null' => true
            ],
            'found_company' => [
                'type' => 'INT',
                'constraint' => 5,
                'comment' => 'Среднее количество найденных компаний',
                'default' => 0,
                'null' => false
            ],
            'found_count' => [
                'type' => 'INT',
                'constraint' => 5,
                'comment' => 'Количество проверок этого участка',
                'default' => 0,
                'null' => false
            ]
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('geo_id');
        $this->dbforge->create_table('geography_containers');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('geography_containers');
    }

}