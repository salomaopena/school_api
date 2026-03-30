<?php

namespace App\Models;

use CodeIgniter\Model;

class TurmaDisciplinaModel extends Model
{
    protected $table            = 'turma_disciplina';
    protected $primaryKey       = 'id_turma_disciplina';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
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

    public function disciplina_ja_atribuida(
        int $id_turma,
        int $id_plano_curricular,
        int $id_ano_letivo,
        int $id_instituicao
    ): bool {
        return $this->where('id_turma', $id_turma)
                    ->where('id_plano_curricular', $id_plano_curricular)
                    ->where('id_ano_letivo', $id_ano_letivo)
                    ->where('id_instituicao', $id_instituicao)
                    ->countAllResults() > 0;
    }
}
