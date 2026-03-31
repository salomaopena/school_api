<?php

namespace App\Models;

use CodeIgniter\Model;

class ProfessorModel extends Model
{
    protected $table            = 'professor';
    protected $primaryKey       = 'id_professor';
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


    public function professor_ja_existe(int $id_usuario, int $id_instituicao): bool
    {
        return $this->where('id_professor', $id_usuario)
            ->where('id_instituicao', $id_instituicao)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    public function carga_horaria(int $id_professor, int $id_ano_letivo, int $id_instituicao): array
    {
        return $this->db->table('docencia d')
            ->select('
                SUM(td.carga_horaria)   AS carga_total,
                COUNT(d.id_docencia)    AS total_disciplinas
            ')
            ->join('turma_disciplina td', 'td.id_turma_disciplina = d.id_turma_disciplina', 'left')
            ->where('d.id_professor', $id_professor)
            ->where('td.id_ano_letivo', $id_ano_letivo)
            ->where('d.id_instituicao', $id_instituicao)
            ->get()
            ->getRowArray();
    }
}
