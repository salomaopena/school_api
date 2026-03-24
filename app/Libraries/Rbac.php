<?php

namespace App\Libraries;

use App\Models\PermissionsModel;
use CodeIgniter\Database\BaseConnection;

class Rbac
{

    protected BaseConnection $db;

    // Cache interno para evitar queries repetidas
    protected array $permissionsCache = [];
    protected array $rolesCache = [];

    public function __construct()
    {
        $this->db = db_connect();
    }


    /**
     * Return roles of the user in a simple array
     */
    public function user_roles($user_id)
    {
        if (isset($this->rolesCache[$user_id])) {
            return $this->rolesCache[$user_id];
        }

        $results = $this->db->query("
        SELECT r.nome_role
        FROM usuario_roles ur
        JOIN roles r ON r.id_role = ur.id_role
        WHERE ur.id_usuario = ?", [$user_id]);

        $roles = array_column($results->getResultArray(), 'nome_role');
        $this->rolesCache[$user_id] = $roles;
        return $roles;
    }

    /**
     * Return all permissions of the user (direct + via roles)
     */
    public function user_permissions($user_id)
    {
        if (isset($this->permissionsCache[$user_id])) {
            return $this->permissionsCache[$user_id];
        }

        // Permissões diretas do usuário
        $permission = model(PermissionsModel::class);
        $user_permissions = $permission->get_permission_by_user_id($user_id);
        $user_permissions = array_column($user_permissions, 'nome_permission');


          // Permissões via roles
          $roles = $this->user_roles($user_id);
          $role_permissions = [];

        if (!empty($roles)) {
            $placeholders = implode(',', array_fill(0, count($roles), '?'));
            $query = $this->db->query("
                SELECT p.nome_permission
                FROM role_permissions rp
                JOIN permissions p ON p.id_permission = rp.id_permission
                JOIN roles r ON r.id_role = rp.id_role
                WHERE r.nome_role IN ($placeholders)", $roles);

            $role_permissions = array_column($query->getResultArray(), 'nome_permission');

            $allPermissions = array_unique(array_merge($user_permissions, $role_permissions));

            $this->permissionsCache[$user_id] = $allPermissions;
    
            return $allPermissions;
        }
    }

    /**
     * Verifica se usuário possui uma ou várias permissões
     *
     * @param int $user_id
     * @param string|array $permissions
     * @param bool $requireAll se true, usuário precisa ter todas
     * @return bool
     */


     

    // can do something
    public function can(int $user_id, $permissions, bool $requireAll = false): bool
    {
        $user_permissions = $this->user_permissions($user_id);

        if (is_string($permissions)) {
            return in_array($permissions, $user_permissions);
        }

        if (is_array($permissions)) {
            if ($requireAll) {
                return empty(array_diff($permissions, $user_permissions));
            } else {
                return (bool) array_intersect($permissions, $user_permissions);
            }
        }

        return false;
    }
}
