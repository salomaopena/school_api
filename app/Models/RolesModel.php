<?php

namespace App\Models;

use CodeIgniter\Model;

class RolesModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id_role';
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
     * Obter roles do usuário diretamente do banco
     */
    public function getUsuarioRoles($userId)
    {
        $result = $this->db->table('usuario_roles')
            ->select('roles.id_role, roles.nome_role, roles.parent_role_id, roles.description_role')
            ->join('roles', 'roles.id_role = usuario_roles.id_role')
            ->where('usuario_roles.id_usuario', $userId)
            ->get()
            ->getResultObject();

        return $result ?: [];
    }

    public function getPermissionsByRole($id_role)
    {
        return $this->select('permissions.nome_permission')
            ->join('role_permissions', 'permissions.id_permission = role_permissions.id_permission')
            ->whereIn('role_permissions.id_role', $id_role)
            ->findAll();
    }
}
