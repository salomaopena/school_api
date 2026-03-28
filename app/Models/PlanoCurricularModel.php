<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanoCurricularModel extends Model
{
    protected $table            = 'plano_curricular';
    protected $primaryKey       = 'id_plano_curricular';
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


    public function disciplina_ja_existe(
        int $id_curso,
        int $id_disciplina,
        int $id_ano_curricular,
        int $id_semestre_lectivo,
        int $id_instituicao
    ): bool {
        return $this->where('id_curso', $id_curso)
            ->where('id_disciplina', $id_disciplina)
            ->where('id_ano_curricular', $id_ano_curricular)
            ->where('id_semestre_lectivo', $id_semestre_lectivo)
            ->where('id_instituicao', $id_instituicao)
            ->countAllResults() > 0;
    }
}
