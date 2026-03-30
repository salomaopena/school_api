<?php

namespace App\Models;

use CodeIgniter\Model;

class DocenciaModel extends Model
{
    protected $table            = 'docencia';
    protected $primaryKey       = 'id_docencia';
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


    public function professor_ja_atribuido(
        int $id_professor,
        int $id_turma_disciplina,
        int $id_instituicao
    ): bool {
        return $this->where('id_professor', $id_professor)
            ->where('id_turma_disciplina', $id_turma_disciplina)
            ->where('id_instituicao', $id_instituicao)
            ->countAllResults() > 0;
    }
}
