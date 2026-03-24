<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuario';
    protected $primaryKey       = 'id_usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = false;
    protected $allowedFields    = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];


    /**
     * Buscar usuário por email
     */
    public function get_user_by_email(string $email)
    {
        return $this->db->table('usuario u')
            ->select('
                u.id_usuario,
                u.email,
                u.password_hash,
                u.nome_usuario,
                u.sobrenome_usuario,
                u.url_foto,
                u.is_active,
                u.id_instituicao,
                u.telefone_fixo,
                GROUP_CONCAT(DISTINCT r.nome_role)        AS roles,
                GROUP_CONCAT(DISTINCT p.nome_permission)  AS permissions
            ')
            ->join('usuario_roles ur',      'ur.id_usuario = u.id_usuario', 'left')
            ->join('roles r',               'r.id_role     = ur.id_role',  'left')
            ->join('role_permissions rp',   'rp.id_role    = r.id_role', 'left')
            ->join('permissions p',         'p.id_permission = rp.id_permission AND p.is_active = 1', 'left')
            ->where('u.email', $email)
            ->where('u.deleted_at', null)
            ->where('u.is_active', 1)
            ->where('u.deleted_at', null)
            ->groupBy('u.id_usuario')
            ->get()
            ->getRowArray();
    }


    /**
     * Buscar usuário por id para regenerar o token
     */

    public function get_user_for_generate_token(int $user_id)
    {
        return $this->db->table('usuario u')
            ->select('
                 u.id_usuario,
                 u.email,
                 u.password_hash,
                 u.nome_usuario,
                 u.sobrenome_usuario,
                 u.url_foto,
                 u.is_active,
                 u.id_instituicao,
                 u.telefone_fixo,
                 GROUP_CONCAT(DISTINCT r.nome_role)        AS roles,
                 GROUP_CONCAT(DISTINCT p.nome_permission)  AS permissions
             ')
            ->join('usuario_roles ur',      'ur.id_usuario = u.id_usuario', 'left')
            ->join('roles r',               'r.id_role     = ur.id_role',  'left')
            ->join('role_permissions rp',   'rp.id_role    = r.id_role', 'left')
            ->join('permissions p',         'p.id_permission = rp.id_permission AND p.is_active = 1', 'left')
            ->where('u.id_usuario', $user_id)
            ->where('u.deleted_at', null)
            ->where('u.is_active', 1)
            ->where('u.deleted_at', null)
            ->groupBy('u.id_usuario')
            ->get()
            ->getRowArray();
    }


    /**
     *   Método para buscar as roles do utilizador para o Token
     */
    public function get_user_by_id(int $user_id)
    {
        return $this->where('id_usuario', $user_id)
            ->get()->getRowArray();
    }

    public function change_current_password(int $id_usuario, $new_password)
    {
        return $this->update($id_usuario, [
            'password_hash' => password_hash($new_password, PASSWORD_DEFAULT),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }




    /**
     * user forgot password
     */
    public function forgot_password($user_id, $token, $expires_at)
    {
        $this->update($user_id, [
            'password_reset_token'      => hash('sha256', $token),
            'password_reset_expires'    => $expires_at,
            'updated_at'                => date('Y-m-d H:i:s')
        ]);
    }


    /**
     * reset_password
     */
    public function reset_password($user_id, $new_password)
    {
        $this->update($user_id, [
            'password_hash'             => password_hash($new_password, PASSWORD_DEFAULT),
            'password_reset_token'      => null,
            'password_reset_expires'    => null,
            'updated_at'                => date('Y-m-d H:i:s')
        ]);
    }
}
