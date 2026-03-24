<?php

namespace App\Models;

use CodeIgniter\Model;

class ExameAptidaoModel extends Model
{
    protected $table            = 'exames_aptidao';
    protected $primaryKey       = 'id_exame_apetidao';
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

    public function candidato_ja_inscrito(int $id_candidato, int $id_ano_letivo, int $id_instituicao): bool
    {
        return $this->where('id_candidato', $id_candidato)
            ->where('id_ano_letivo', $id_ano_letivo)
            ->where('deleted_at', null)
            ->where('id_instituicao', $id_instituicao)
            ->countAllResults() > 0;
    }
}
