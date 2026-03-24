<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
//use App\Libraries\Rbac;
use App\Models\ApiTokenModel;
use App\Models\UsuarioModel;
use App\Models\PermissionsModel;
use App\Models\RolesModel;
use App\Services\JwtService;

class AuthController extends BaseController
{

    protected $api_response;
    protected $email;
    protected $usuario;
    protected $permission;
    protected $roles;
    protected $apiTokenModel;
    protected $jwt;
    //protected $rbac;

    public function __construct()
    {
        $this->api_response =  new ApiResponse();
        $this->email = \Config\Services::email();
        $this->usuario = model(UsuarioModel::class);
        $this->permission = model(PermissionsModel::class);
        $this->roles = model(RolesModel::class);
        $this->apiTokenModel = model(ApiTokenModel::class);
        //$this->rbac = new Rbac();
        $this->jwt  = new JwtService();
    }

    /**
     * create login endpoint
     */
    public function login()
    {
        $this->api_response->validade_request('POST');


        if (!$this->validate($this->_form_validation(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $user = $this->usuario->get_user_by_email($data['email']);

            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                return $this->api_response->set_error('Usuário e/ou Senha inválidos', 401);
            }

            if ($user['is_active'] == 0) {
                return $this->api_response->set_error('Utilizador desativado! Entre em contacto com o suporte.', 401);
            }


            $roles       = $user['roles']       ? explode(',', $user['roles'])       : [];
            $permissions = $user['permissions'] ? explode(',', $user['permissions']) : [];


            $can = [];
            foreach ($permissions as $perm) {
                $can[$perm] = true; // indica que o usuário tem essa permissão
            }

            // Agrupar permissões por módulo
            $modeles = [];
            foreach ($permissions as $perm) {
                $parts = explode('.', $perm); // separa regras "alunos.ler" em ["alunos", "ler"]
                $module = $parts[0];
                $action = $parts[1] ?? '*'; // se não tiver ação, marca como '*'

                // Cria a hierarquia
                if (!isset($modeles[$module])) {
                    $modeles[$module] = [];
                }
                $modeles[$module][$action] = true;
            }


            // Gera access token
            $access_token = $this->jwt->generate_access_token([
                'id_usuario'        => $user['id_usuario'],
                'id_instituicao'    => $user['id_instituicao'],
                'email'             => $user['email'],
                'roles'             => $roles,
                'permissions'       => $permissions,
            ]);

            // Gera refresh token
            $refresh_token = $this->jwt->generate_refresh_token();


            $this->apiTokenModel->insert([
                'id_usuario' => $user['id_usuario'],
                'token' => hash('sha256', $refresh_token),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'ip_address' => $this->request->getIPAddress(),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]);

            // Atualiza ultimo_login
            $id = intval('id_usuario', $user['id_usuario']);
            $this->usuario->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);

            $data = [
                'access_token'  => $access_token,
                'refresh_token' => $refresh_token,
                'token_type'    => 'Bearer',
                'expires_in'    => 10800,
                'usuario'  => [
                    'id_usuario'    => $user['id_usuario'],
                    'nome'      => $user['nome_usuario'],
                    'sobrenome' => $user['sobrenome_usuario'],
                    'email'     => $user['email'],
                    'url_foto'          => $user['url_foto'],
                    'id_instituicao'    => $user['id_instituicao'],
                    'telefone_fixo'     => $user['telefone_fixo'],
                    'roles'         => $roles,
                    'permissions'   => $permissions,
                    'can'           => $can,
                    'modules'       => $modeles
                ],

            ];

            return $this->api_response->set_success($data, 'Login realizado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    /**
     * logout method
     */
    public function logout()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');

        try {

            if (!$authHeader) {
                return $this->api_response->set_error('Token não informado', 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);
            $hash = hash('sha256', $token);


            $token_data = $this->apiTokenModel
                ->where('token', $hash)
                ->where('revoked_at', null)
                ->first();

            if (!$token_data) {
                return $this->api_response->set_error('Token inválido', 401);
            }

            // Revoga o token
            $this->apiTokenModel->update($token_data->id_api_tokens, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'revoked_at' => date('Y-m-d H:i:s')
            ]);
            return $this->api_response->set_success([], 'Logout realizado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // forgot password - solicita recuperação
    public function forgot_password()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'email' => [
                'label' => 'E-mail',
                'rules' => 'required|valid_email'
            ]
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario->where('email', $data['email'])->first();

            // Retorna sucesso mesmo se email não existir (segurança contra enumeração)
            if (!$usuario) {
                return $this->api_response->set_success([], 'Se o e-mail existir, você receberá as instruções em breve!');
            }

            if ($usuario->is_active == 0) {
                return $this->api_response->set_error('Usuário desativado! Entre em contacto com o suporte.', 401);
            }

            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->usuario->forgot_password($usuario->id_usuario, $token, $expires_at);

            $link = base_url("auth/reset-password?token={$token}&email={$usuario->email}");
            $nome_usuario = $usuario->nome_usuario . ' ' . $usuario->sobrenome_usuario;

            $email_service = new \App\Services\EmailService();
            // TODO: descomentar as linas a baixo só após a configuração dos serviços de emails em: EmailService
            // $enviado       = $email_service->send_reset_password($usuario->email, $nome_usuario, $link);

            // if (!$enviado) {
            //     return $this->api_response->set_error('Erro ao enviar o email! Tente novamente.', 500);
            // }

            // Não descomentar. De preferência remover
            // $data = [
            //     'id'            => $usuario->id_usuario,
            //     'email'         => $usuario->email,
            //     'token'         => $token,
            //     'expires_at'    => $expires_at,
            //     'link'          => $link
            // ];

            return $this->api_response->set_success(/*$data ? $data :*/[], 'Se o e-mail existir, você receberá as instruções em breve!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // reset password - redefine a senha com o token
    public function reset_password()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate(
            $this->_validate_reset_form_fields(),
            ['message' => 'Preencha corretamente os campos']
        )) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $usuario = $this->usuario
                ->where('email', $data['email'])
                ->where('password_reset_token', hash('sha256', $data['token']))
                ->first();

            if (!$usuario) {
                return $this->api_response->set_error('E-mail e/ou token inválidos!', 401);
            }

            if (strtotime($usuario->password_reset_expires) < strtotime('now')) {
                return $this->api_response->set_error('Token expirado! Solicite uma nova recuperação de senha.', 401);
            }

            $this->usuario->reset_password($usuario->id_usuario, $data['password']);

            return $this->api_response->set_success([], 'Senha redefinida com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Refresh token ────────────────────────────────────────────────────────
    public function refresh_token()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            if (empty($data['refresh_token'])) {
                return $this->api_response->set_error('Refresh token não informado!', 401);
            }

            $token_hash = hash('sha256', $data['refresh_token']);

            $registro = $this->apiTokenModel->get_valid_token($token_hash);

            if (!$registro) {
                return $this->api_response->set_error('Refresh token inválido ou expirado!', 401);
            }

            $usuario = $this->usuario->get_user_for_generate_token($registro['id_usuario']);

            $roles       = $usuario['roles']       ? explode(',', $usuario['roles'])       : [];
            $permissions = $usuario['permissions'] ? explode(',', $usuario['permissions']) : [];

            // Revoga token antigo
            $this->apiTokenModel->delete_token($token_hash);

            // Gera novos tokens
            $new_access_token  = $this->jwt->generate_access_token([
                'id_usuario'        => $usuario['id_usuario'],
                'id_instituicao'    => $usuario['id_instituicao'],
                'email'             => $usuario['email'],
                'roles'             => $roles,
                'permissions'       => $permissions,
            ]);

            $new_refresh_token = $this->jwt->generate_refresh_token();

            $this->apiTokenModel->insert([
                'id_usuario' => $usuario['id_usuario'],
                'token' => hash('sha256', $new_refresh_token),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'ip_address' => $this->request->getIPAddress(),
                'expires_at' => date('Y-m-d H:i:s', time() + $this->jwt->get_refresh_ttl())
            ]);


            return $this->api_response->set_success([
                'access_token'  => $new_access_token,
                'refresh_token' => $new_refresh_token,
                'token_type'    => 'Bearer',
                'expires_in'    => 3600,
            ], 'Token renovado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Alterar senha logado ─────────────────────────────────────────────────
    public function change_password()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'current_password' => ['label' => 'Senha atual',          'rules' => 'required'],
            'password'       => ['label' => 'Nova senha',           'rules' => 'required|min_length[6]|max_length[255]|alpha_numeric_punct'],
            'conf_password'  => ['label' => 'Confirmar nova senha', 'rules' => 'required|matches[password]'],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Pega usuário logado via JWT (injetado pelo middleware)
            $id_usuario = current_user_id();


            $usuario = $this->usuario->get_user_by_id($id_usuario);

            if (!$usuario) {
                return $this->api_response->set_error('Utilizador não encontrado!', 404);
            }

            if (!password_verify($data['current_password'], $usuario['password_hash'])) {
                return $this->api_response->set_error('Senha atual incorreta!', 401);
            }

            $this->usuario->change_current_password($id_usuario, $data['password']);

            // Revoga todos os refresh tokens do usuário (força novo login nos outros dispositivos)
            $this->apiTokenModel
                ->where('id_usuario', $id_usuario)
                ->delete();

            return $this->api_response->set_success([], 'Senha alterada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    /**
     * validate reset form fields
     */

    private function _validate_reset_form_fields()
    {
        return [
            'email' => [
                'label' => 'E-mail',
                'rules' => 'required|valid_email'
            ],
            'token' => [
                'label' => 'Token',
                'rules' => 'required'
            ],
            'password' => [
                'label' => 'Nova palavra-passe',
                'rules' => 'required|min_length[6]|max_length[255]|alpha_numeric_punct'
            ],
            'conf_password' => [
                'label' => 'Confirmar palavra-passe',
                'rules' => 'required|min_length[6]|max_length[255]|matches[password]'
            ],
        ];
    }

    /**
     * validate login form
     */

    private function _form_validation()
    {
        return [
            'email' => [
                'label'     => 'E-mail de acesso',
                'rules'     => 'required|valid_email|min_length[3]|max_length[50]',
            ],
            'password' => [
                'label'     => 'Palavra-passe',
                'rules'     => 'required|min_length[6]|max_length[255]|alpha_numeric_punct'
            ]
        ];
    }

    
}
