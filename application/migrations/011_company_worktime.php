<?php

class Migration_company_worktime extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'constraint' => 20
            ],
            'dayId' => [
                'type' => 'SMALLINT',
                'constraint' => 1
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['from', 'to']
            ],
            'hours' => [
                'type' => 'SMALLINT',
                'constraint' => 2,
                'default' => 0
            ],
            'minutes' => [
                'type' => 'SMALLINT',
                'constraint' => 2,
                'default' => 0
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->create_table('company_worktime');
    }


    public function down() {
        $this->dbforge->drop_table('company_worktime');
    }

}