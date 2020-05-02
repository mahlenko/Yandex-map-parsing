<?php

class Migration_company_sources extends CI_Migration
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
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'constraint' => 20
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'href' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('uid', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->create_table('company_sources');
    }


    public function down() {
        $this->dbforge->drop_table('company_sources');
    }

}