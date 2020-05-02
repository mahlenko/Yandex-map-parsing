<?php

class Migration_company_rating extends CI_Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50
            ],

            'value' => [
                'type' => 'DOUBLE',
                'constraint' => [6,4]
            ],
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->add_key('company_id');
        $this->dbforge->create_table('company_rating');
    }


    public function down() {
        $this->dbforge->drop_table('company_rating');
    }

}