<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ApiKeySeeder extends Seeder
{
    public function run()
    {

        $api_client = [
            [
                'project_id'    => 2026001,
                'api_key_hash'       => hash('sha256', '9344c37d384bc089e9714eefd470cbb1dc49a8689ee914ab2cc9ff6253f4be47'),
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s')
            ],
            [
                'project_id'    => 2026002,
                'api_key_hash'       => hash('sha256', '077b1e992162b27d332ca26f39a53e19369fbc9811256b945d7fb3ebd8e3f0f0'),
                'is_active'     => 1,
                'created_at'    => date('Y-m-d H:i:s')
            ]
        ];

        $this->db->table('api_keys')->insertBatch($api_client);
        echo PHP_EOL . 'Seeding: api_clients with ' . count($api_client) . ' records' . PHP_EOL;
    }
}
