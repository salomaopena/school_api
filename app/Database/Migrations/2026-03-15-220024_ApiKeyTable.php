<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ApiKeyTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'api_id' => [
                'type' => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'api_key_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1
            ],
            'rate_limit' => [
                'type' => 'INT',
                'default' => 1000
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ]
        ]);

        $this->forge->addKey("api_id", true);
        $this->forge->createTable('api_keys');
    }

    public function down()
    {
        $this->forge->dropTable("api_keys");
    }
}
