<?php

class Migration_geography extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'geo_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'unique' => true
            ],
            'name' => [
                'type' =>'VARCHAR',
                'constraint' => 50,
                'default' => null
            ],
            'seoname' => [
                'type' =>'VARCHAR',
                'constraint' => 50,
                'default' => null
            ],
            'description' => [
                'type' =>'VARCHAR',
                'constraint' => 50,
                'default' => null
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Полное имя'
            ],
            'lon' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Метка на карте',
            ],
            'lat' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Метка'
            ],
            'bounds_lon' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
                'comment' => 'Границы региона'
            ],
            'bounds_lat' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
            ],
            'bounds_lon_end' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
            ],
            'bounds_lat_end' => [
                'type' => 'DOUBLE',
                'constraint' => [9, 6],
            ],
            'zoom' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 9
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('geo_id', true);
        $this->dbforge->create_table('geography');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('geography');
    }

}