<?php

namespace App\Models;

use CodeIgniter\Model;

class MatriculaModel extends Model
{
    protected $table            = 'matricula';
    protected $primaryKey       = 'id_matricula';
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

    public function aluno_ja_matriculado(int $id_aluno, int $id_ano_letivo, int $id_instituicao): bool
    {
        return $this->where('id_aluno', $id_aluno)
            ->where('id_ano_letivo', $id_ano_letivo)
            ->where('id_instituicao', $id_instituicao)
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    public function show_matricula_info(int $id_instituicao, int $id_matricula)
    {
        return $this->select("
                matricula.id_matricula,
                matricula.data_matricula,
                matricula.id_aluno,
                a.numero_aluno,
                u.nome_usuario,
                u.sobrenome_usuario,
                u.email,
                u.numero_doc,
                u.data_emisao_doc,
                u.data_emisao_doc,

                matricula.id_funcionario,
                f.nome_usuario,
                f.sobrenome_usuario,
                
                matricula.id_ano_letivo,
                al.ano_letivo,
                
                matricula.estado_matricula,
                is_reconfirmacao,
                comprovativo_pag,
                estado_pagamento,
                
                CONCAT(cr.nome_curso, ' (',cr.codigo_curso,')') as curso,
                
                matricula.id_turma,
                t.nome_turma,
                
                matricula.id_ciclo_letivo,
                cl.ciclo_letivo,
                
                matricula.id_ano_curricular,
                ac.ano_curricular,
                
                matricula.id_periodo_letivo,
                pl.periodo_letivo,
                
                matricula.id_semestre_lectivo,
                s.semestre_lectivo,
                
                matricula.id_sala,
                
                matricula.lingua_opcao,
                matricula.lingua_estudada,
                matricula.observacao,
                matricula.created_at
            ")
            ->join('aluno a', '(a.id_aluno = matricula.id_aluno)')
            ->join('ano_letivo al', '(al.id_ano_letivo = matricula.id_ano_letivo)')
            ->join('turma t', '(t.id_turma =  matricula.id_turma)')
            ->join('ciclo_letivo cl', '(cl.id_ciclo_letivo = matricula.id_ciclo_letivo)')
            ->join('ano_curricular ac', '(ac.id_ano_curricular = matricula.id_ano_curricular)')
            ->join('periodo_letivo pl', '(pl.id_periodo_letivo = matricula.id_periodo_letivo)')
            ->join('semestre_lectivo s', '(s.id_semestre_lectivo = matricula.id_semestre_lectivo)', 'left')
            ->join('sala sa', '(sa.id_sala = matricula.id_sala)', 'left')
            ->join('usuario u', '(matricula.id_aluno = u.id_usuario)')
            ->join('curso cr', '(cr.id_curso = a.id_curso)', 'left')
            ->join('usuario f', '(matricula.id_funcionario = f.id_usuario)', 'left')
            ->where('matricula.id_matricula', $id_matricula)
            ->where('matricula.id_instituicao', $id_instituicao)
            ->where('matricula.deleted_at', null)
            ->first();
    }
}
