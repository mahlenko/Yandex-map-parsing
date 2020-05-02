<?php

class Migration_geography_position extends CI_Migration
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
            'area_id' => [
                'type' => 'INT',
                'constraint' => 3,
                'comment' => 'Участок региона'
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
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('geo_id');
        $this->dbforge->create_table('geography_polygons');
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('geography_polygons');
    }

}