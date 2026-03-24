<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use App\Libraries\ApiResponse;
use App\Models\ApiTokenModel;
use App\Models\UsuarioModel;
use App\Models\PermissionsModel;
use App\Models\RolesModel;


class UserManagement extends BaseController
{
    protected $api_response;
    protected $email;
    protected $usuario;
    protected $permission;
    protected $roles;
    protected $apiTokenModel;

    public function __construct()
    {
        $this->api_response =  new ApiResponse();
        $this->email = \Config\Services::email();
        $this->usuario = model(UsuarioModel::class);
        $this->permission = model(PermissionsModel::class);
        $this->roles = model(RolesModel::class);
        $this->apiTokenModel = model(ApiTokenModel::class);
    }


    // create user
    public function create()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_create(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $array_data = [
                'email'         => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'nome_usuario'          => $data['nome'],
                'sobrenome_usuario'     => $data['sobrenome'],
                'id_sexo'               => $data['id_sexo'],
                'data_nascimento'       => $data['data_nascimento'],
                'nome_pai'       => $data['nome_pai'],
                'nome_mae'       => $data['nome_mae'],
                'nif'            => $data['nif'],
                'id_documento'   => $data['id_documento'],
                'numero_doc'     => $data['numero_doc'],
                'data_emisao_doc'    => $data['data_emisao_doc'],
                'local_emisaao_doc'  => $data['local_emisaao_doc'],
                'id_municipio'       => $data['id_municipio'],
                'id_instituicao'     => $data['id_instituicao'],
                'id_endereco'        => $data['id_endereco'],
                'telefone_fixo'      => $data['telefone_fixo'],
                'telefone_movel'     => $data['telefone_movel'],
                'url_foto'           => $data['url_foto'],
                'email_alternativo'  => $data['email_alternativo'],
                'is_active'          => 1,
                'created_at'            => date('Y-m-d H:i:s')
            ];



            $id = $this->usuario->insert($array_data, true);

            if ($id > 0) {
                $id_data = ['id' => hash('sha256', $id)];
                return $this->api_response->set_success($id_data, 'Dados inseridos com sucesso!');
            } else {
                return $this->api_response->set_error('Ocorreu um erro ao adicionar dados!', 401);
            }
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // update user
    public function update()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_update(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $decoded = $this->usuario->where('id_usuario', $data['id'])->first();

            if (!$decoded) {
                return $this->api_response->set_error('Usuário não encontrado!', 404);
            }

            $array_data = [
                'nome_usuario'          => $data['nome'],
                'sobrenome_usuario'     => $data['sobrenome'],
                'id_sexo'               => $data['id_sexo'],
                'data_nascimento'       => $data['data_nascimento'],
                'nome_pai'              => $data['nome_pai'],
                'nome_mae'              => $data['nome_mae'],
                'nif'                   => $data['nif'],
                'id_documento'          => $data['id_documento'],
                'numero_doc'            => $data['numero_doc'],
                'data_emisao_doc'       => $data['data_emisao_doc'],
                'local_emisaao_doc'     => $data['local_emisaao_doc'],
                'id_municipio'          => $data['id_municipio'],
                'id_endereco'           => $data['id_endereco'],
                'telefone_fixo'         => $data['telefone_fixo'],
                'telefone_movel'        => $data['telefone_movel'],
                'email_alternativo'     => $data['email_alternativo'],
                'updated_at'            => date('Y-m-d H:i:s')
            ];


            $updated = $this->usuario->update($decoded->id_usuario, $array_data);

            if ($updated) {
                return $this->api_response->set_success([], 'Dados atualizados com sucesso!');
            } else {
                return $this->api_response->set_error('Ocorreu um erro ao atualizar dados!', 401);
            }
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // delete user
    public function delete()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario->where('id_usuario', $data['id'])->first();

            if (!$usuario) {
                return $this->api_response->set_error('Usuário não encontrado!', 404);
            }

            $deleted = $this->usuario->delete($usuario->id_usuario);

            if ($deleted) {
                return $this->api_response->set_success([], 'Usuário removido com sucesso!');
            } else {
                return $this->api_response->set_error('Ocorreu um erro ao remover o usuário!', 401);
            }
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // list users
    public function list()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 10;
            $offset   = ($page - 1) * $per_page;

            $builder = $this->usuario->builder();

            // Filtro: Nome ou Sobrenome
            if (!empty($data['nome'])) {
                $builder->groupStart()
                    ->like('usuario.nome_usuario', $data['nome'])
                    ->orLike('usuario.sobrenome_usuario', $data['nome'])
                    ->groupEnd();
            }

            // Filtro: NIF
            if (!empty($data['nif'])) {
                $builder->where('nif', $data['nif']);
            }

            // Filtro: Número do documento
            if (!empty($data['numero_doc'])) {
                $builder->where('usuario.numero_doc', $data['numero_doc']);
            }

            // Filtro: Telefone (fixo ou móvel)
            if (!empty($data['telefone'])) {
                $builder->groupStart()
                    ->like('usuario.telefone_fixo', $data['telefone'])
                    ->orLike('usuario.telefone_movel', $data['telefone'])
                    ->groupEnd();
            }

            // Filtro: Email (principal ou alternativo)
            if (!empty($data['email'])) {
                $builder->groupStart()
                    ->like('usuario.email', $data['email'])
                    ->orLike('usuario.email_alternativo', $data['email'])
                    ->groupEnd();
            }

            //  Fitro instituição: obrigatório
            if (!empty($data['id_instituicao'])) {
                $builder->where('usuario.id_instituicao', $data['id_instituicao']);
            } else {
                return $this->api_response->set_error('Informações da instituição não encontradas', 403);
            }



            $total   = $builder->countAllResults(false);
            $usuarios = $builder
                ->select('
                        usuario.id_usuario,
                        usuario.nome_usuario,
                        usuario.sobrenome_usuario,
                        usuario.email,
                        usuario.email_alternativo,
                        usuario.nif,
                        usuario.numero_doc,
                        usuario.telefone_fixo,
                        usuario.telefone_movel,
                        
                        usuario.id_sexo,
                        s.sexo_sigla,
                        s.designacao_sexo,
                        
                        usuario.id_municipio,
                        m.nome_municipio,
                        
                        usuario.id_instituicao,
                        i.nome_instituicao,

                        d.id_documento,
                        d.documento,

                        e.id_endereco,
                        e.casa,
                        e.rua,
                        e.bairro,
                        e.distrito,
                        
                        usuario.created_at,
                        usuario.updated_at
                        ')
                ->join('sexo AS s', '(usuario.id_sexo = s.id_sexo)')
                ->join('municipio AS m', '(usuario.id_municipio = m.id_municipio)')
                ->join('instituicao i', '(usuario.id_instituicao = i.id_instituicao)')
                ->join('documento AS d', '(usuario.id_documento = d.id_documento)')
                ->join('endereco AS e', 'usuario.id_endereco = e.id_endereco')
                ->where('usuario.deleted_at', null)
                //->where('usuario.is_active', 1)
                ->orderBy('usuario.nome_usuario', 'ASC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            $resultado = [
                'users'         => $usuarios,
                'pagination'   => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($total / $per_page),
                    'from'         => $offset + 1,
                    'to'           => min($offset + $per_page, $total),
                ]
            ];

            return $this->api_response->set_success($resultado, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // deactivate user
    public function deactivate()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario->where('id_usuario', $data['id'])
                ->where('deleted_at', null)
                ->where('is_active', 1)
                ->first();

            if (!$usuario) {
                return $this->api_response->set_error('Usuário não encontrado!', 404);
            }

            if ($usuario->is_active == 0) {
                return $this->api_response->set_error('Usuário já se encontra desativado!', 401);
            }

            $updated = $this->usuario->update($usuario->id_usuario, [
                'is_active'  => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($updated) {
                return $this->api_response->set_success([], 'Usuário desativado com sucesso!');
            } else {
                return $this->api_response->set_error('Ocorreu um erro ao desativar o usuário!', 401);
            }
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // deactivate user
    public function activate()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario->where('id_usuario', $data['id'])
                ->where('deleted_at', null)
                ->where('is_active', 0)
                ->first();

            if (!$usuario) {
                return $this->api_response->set_error('Usuário não encontrado!', 404);
            }

            if ($usuario->is_active == 1) {
                return $this->api_response->set_error('Usuário já se encontra ativado!', 401);
            }

            $updated = $this->usuario->update($usuario->id_usuario, [
                'is_active'  => 1,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($updated) {
                return $this->api_response->set_success([], 'Usuário ativado com sucesso!');
            } else {
                return $this->api_response->set_error('Ocorreu um erro ao desativar o usuário!', 401);
            }
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // get user by id
    public function show()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario
                ->select('
                        usuario.id_usuario,
                        usuario.nome_usuario,
                        usuario.sobrenome_usuario,
                        usuario.email,
                        usuario.email_alternativo,
                        usuario.nif,
                        usuario.numero_doc,
                        usuario.telefone_fixo,
                        usuario.telefone_movel,
                        
                        usuario.id_sexo,
                        s.sexo_sigla,
                        s.designacao_sexo,
                        
                        usuario.id_municipio,
                        m.nome_municipio,
                        
                        usuario.id_instituicao,
                        i.nome_instituicao,

                        d.id_documento,
                        d.documento,

                        e.id_endereco,
                        e.casa,
                        e.rua,
                        e.bairro,
                        e.distrito,
                        
                        usuario.created_at,
                        usuario.updated_at
                ')
                ->join('sexo AS s', '(usuario.id_sexo = s.id_sexo)')
                ->join('municipio AS m', '(usuario.id_municipio = m.id_municipio)')
                ->join('instituicao i', '(usuario.id_instituicao = i.id_instituicao)')
                ->join('documento AS d', '(usuario.id_documento = d.id_documento)')
                ->join('endereco AS e', 'usuario.id_endereco = e.id_endereco')
                ->where('usuario.deleted_at', null)
                ->where('usuario.id_usuario', $data['id'])
                ->first();

            if (!$usuario) {
                return $this->api_response->set_error('Usuário não encontrado!', 404);
            }

            return $this->api_response->set_success($usuario, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }






    /**=================================================================
     *====================forms validation==============================
     ===================================================================*/

    private function _form_validate_create()
    {
        return [
            'email' => [
                'label'     => 'E-mail de acesso',
                'rules'     => 'required|valid_email|min_length[3]|max_length[50]|is_unique[usuario.email]',
            ],
            'password' => [
                'label'     => 'Palavra-passe',
                'rules'     => 'required|min_length[6]|max_length[255]|alpha_numeric_punct'
            ],
            'conf_password' => [
                'label'     => 'Confirmar palavra-passe',
                'rules'     => 'required|min_length[6]|max_length[255]|matches[password]'
            ],
            'nome' => [
                'label'     => 'Nome',
                'rules'     => 'required|min_length[3]|max_length[50]'
            ],
            'sobrenome' => [
                'label'     => 'Sobrenome',
                'rules'     => 'required|min_length[3]|max_length[50]'
            ],
            'id_sexo' => [
                'label'     => 'Sexo',
                'rules'     => 'required|is_natural_no_zero',
            ],
            'data_nascimento' => [
                'label'     => 'Data de nascimento',
                'rules'     => 'permit_empty',
            ],
            'nome_pai' => [
                'label'     => 'Nome do pai',
                'rules'     => 'permit_empty|min_length[3]|max_length[50]'
            ],
            'nome_mae' => [
                'label'     => 'Nome da mãe',
                'rules'     => 'permit_empty|min_length[3]|max_length[50]'
            ],
            'nif' => [
                'label'     => 'NIF',
                'rules'     => 'permit_empty|min_length[14]|max_length[15]'
            ],
            'id_documento' => [
                'label'     => 'Documento',
                'rules'     => 'required|is_natural_no_zero',
            ],
            'numero_doc' => [
                'label'     => 'N° doc.',
                'rules'     => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'data_emisao_doc' => [
                'label'     => 'Data de emissão',
                'rules'     => 'permit_empty',
            ],
            'local_emisaao_doc' => [
                'label'     => 'Local de emissão',
                'rules'     => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'id_municipio' => [
                'label'     => 'Município',
                'rules'     => 'required|is_natural_no_zero',
            ],
            'id_instituicao' => [
                'label'     => 'Instituição',
                'rules'     => 'required|is_natural_no_zero',
            ],
            'telefone_fixo' => [
                'label'     => 'Telefone fixo',
                'rules'     => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'telefone_movel' => [
                'label'     => 'Telemóvel',
                'rules'     => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'url_foto' => [
                'label'     => 'Foto',
                'rules'     => 'permit_empty|min_length[3]|max_length[255]'
            ],
            'email_alternativo' => [
                'label'     => 'Email alternativo',
                'rules'     => 'permit_empty|min_length[3]|max_length[255]'
            ],
        ];
    }


    private function _form_validate_update()
    {
        return [
            'nome' => [
                'label' => 'Nome',
                'rules' => 'required|min_length[3]|max_length[50]'
            ],
            'sobrenome' => [
                'label' => 'Sobrenome',
                'rules' => 'required|min_length[3]|max_length[50]'
            ],
            'id_sexo' => [
                'label' => 'Sexo',
                'rules' => 'required|is_natural_no_zero',
            ],
            'data_nascimento' => [
                'label' => 'Data de nascimento',
                'rules' => 'permit_empty',
            ],
            'nome_pai' => [
                'label' => 'Nome do pai',
                'rules' => 'permit_empty|min_length[3]|max_length[50]'
            ],
            'nome_mae' => [
                'label' => 'Nome da mãe',
                'rules' => 'permit_empty|min_length[3]|max_length[50]'
            ],
            'nif' => [
                'label' => 'NIF',
                'rules' => 'permit_empty|min_length[14]|max_length[15]'
            ],
            'id_documento' => [
                'label' => 'Documento',
                'rules' => 'required|is_natural_no_zero',
            ],
            'numero_doc' => [
                'label' => 'N° doc.',
                'rules' => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'data_emisao_doc' => [
                'label' => 'Data de emissão',
                'rules' => 'permit_empty',
            ],
            'local_emisaao_doc' => [
                'label' => 'Local de emissão',
                'rules' => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'id_municipio' => [
                'label' => 'Município',
                'rules' => 'required|is_natural_no_zero',
            ],
            'telefone_fixo' => [
                'label' => 'Telefone fixo',
                'rules' => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'telefone_movel' => [
                'label' => 'Telemóvel',
                'rules' => 'permit_empty|min_length[3]|max_length[15]'
            ],
            'email_alternativo' => [
                'label' => 'Email alternativo',
                'rules' => 'permit_empty|min_length[3]|max_length[255]'
            ],
        ];
    }
}
