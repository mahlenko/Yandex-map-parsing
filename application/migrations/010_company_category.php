<?php

class Migration_company_category extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'cid' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'auto_increment' => true
            ],
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
            ],
            'company_id' => [
                'type' => 'BIGINT',
                'constraint' => 20
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'seoname' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ],
            'pluralName' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],
            'info' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('cid', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->create_table('company_category');
    }


    public function down() {
        $this->dbforge->drop_table('company_category');
    }

}