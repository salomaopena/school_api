<?php

namespace App\Models;

use CodeIgniter\Model;

class AlunoModel extends Model
{
    protected $table            = 'aluno';
    protected $primaryKey       = 'id_aluno';
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

    public function gerar_numero_aluno(int $id_instituicao): string
    {
        $ano = date('Y');

        $total = $this->where('deleted_at', null)
            ->where('id_instituicao', $id_instituicao)
            ->like('numero_aluno', 'AL-' . $ano, 'after')
            ->countAllResults();

        $proximo = $total + 1;

        return 'AL-' . $ano . str_pad($proximo, 4, '0', STR_PAD_LEFT);
    }

    public function candidato_ja_e_aluno(int $id_aluno, int $id_curso, int $id_instituicao): bool
    {
        return $this->where('id_aluno', $id_aluno)
            ->where('id_curso', $id_curso)
            ->where('id_instituicao', $id_instituicao)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }
}
