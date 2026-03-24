<?php

namespace App\Database\Seeds;

use App\Models\UsuarioModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Test\Fabricator;
use Faker\Factory;


class UsuarioSeeder extends Seeder
{
    public function run()
    { 
        $faker = Factory::create('pt_PT'); // Localização para dados mais realistas
       
        $data = [];

        for ($i = 0; $i < 50; $i++) {
            $data[] = [
                'nome_usuario'             => $faker->firstName,
                'sobrenome_usuario'        => $faker->lastName,
                'id_sexo'                  => $faker->numberBetween(1, 2),
                'data_nascimento'          => $faker->date('Y-m-d', '2005-01-01'),
                'nome_pai'                 => $faker->name('male'),
                'nome_mae'                 => $faker->name('female'),
                'nif'                      => $faker->unique()->numerify('#########'),
                'id_documento'             => $faker->numberBetween(1, 4),
                'numero_doc'               => $faker->unique()->bothify('??######'),
                'data_emisao_doc'          => $faker->date(),
                'local_emisaao_doc'        => $faker->city,
                'id_municipio'             => $faker->numberBetween(1, 160), 
                'id_instituicao'           => $faker->numberBetween(1, 2), 
                'id_endereco'              => $faker->numberBetween(1, 2), 
                'telefone_fixo'            => $faker->phoneNumber,
                'telefone_movel'           => $faker->phoneNumber,
                'url_foto'                 => '/public/uploads/usuarios/default.png',
                'email'                    => $faker->unique()->safeEmail,
                'email_alternativo'        => $faker->safeEmail,
                'password_hash'            => password_hash('123456', PASSWORD_BCRYPT),
                'is_active'                => 1,
                'last_login_at'            => date('Y-m-d H:i:s'),
                'failed_attempts'          => 0,
                'created_at'               => date('Y-m-d H:i:s'),
                'updated_at'               => date('Y-m-d H:i:s'),
            ];
        }

        // Insere todos os registros de uma vez
        $this->db->table('usuario')->insertBatch($data);
    }
}