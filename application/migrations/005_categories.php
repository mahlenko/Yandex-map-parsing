<?php

class Migration_categories extends CI_Migration
{
    public function up()
    {
        $this->dbforge->add_field([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ],
            'pluralName' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ],
            'seoname' => [
                'type' =>'VARCHAR',
                'constraint' => 50,
                'default' => null,
            ],
            'class' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => ''
            ]
        ]);

        $this->dbforge->add_field("`modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $this->dbforge->add_field("`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP");

        $this->dbforge->add_key('id', true);
        $this->dbforge->create_table('categories');

        // восстанавливаем данные базы данных
        $this->restore_data('categories');
    }

    /**
     * Восстановит данные
     */
    function restore_data($table_name)
    {
        $sql_filename = APPPATH . '/migrations/backups/'.$table_name.'.sql';
        if (! file_exists($sql_filename)) {
            return false;
        }

        $sql_contents = explode(";", file_get_contents($sql_filename));

//        dd($sql_contents);
        foreach($sql_contents as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            $this->db->query($query);
        }
    }

    /**
     * Удалит таблицу для хранения прокси
     */
    public function down() {
        $this->dbforge->drop_table('categories');
    }

}