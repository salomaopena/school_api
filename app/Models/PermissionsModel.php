<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionsModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id_permission';
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


    public function get_permission_by_user_id($user_id)
    {

        return $this->db->query("
         SELECT p.nome_permission
         FROM usuario_roles ur
         JOIN role_permissions rp ON rp.id_role = ur.id_role
         JOIN permissions p ON p.id_permission = rp.id_permission
         WHERE ur.id_usuario = ?", [$user_id])->getResultArray();
    }




    // Busca permissões diretas das roles
    public function getPermissaoComRoles($roleIds)
    {
        return $this->select('permissions.nome_permission')
            ->join('role_permissions', 'permissions.id_permission = role_permissions.id_permission')
            ->whereIn('role_permissions.id_role', $roleIds)
            ->findAll();
    }
}
