<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidaturaModel extends Model
{
    protected $table            = 'candidatura';
    protected $primaryKey       = 'id_candidatura';
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


    public function gerar_numero_candidatura(int $id_instituicao): string
    {
        $ano = date('Y');

        $total = $this->where('deleted_at', null)
            ->where('id_instituicao', $id_instituicao)
            ->like('numero_candidatura', 'CAN-' . $ano, 'after')
            ->countAllResults();

        $proximo = $total + 1;

        return 'CAN-' . $ano . str_pad($proximo, 5, '0', STR_PAD_LEFT);
    }

    // mostrar informações da candidatura
    public function show_candidatura_info(int $id_instituicao, int $id_candidatura)
    {
        $builder = db_connect()->table('candidatura c');

        $builder->select("
            c.id_candidatura,

            c.id_candidato,
            GROUP_CONCAT(u.nome_usuario,' ', u.sobrenome_usuario) AS nome_candidato,
            u.data_nascimento,
            u.nome_pai,
            u.nome_mae,
            u.numero_doc,
            u.telefone_fixo,
            u.email,
            
            s.id_sexo,
            s.sexo_sigla,
            s.designacao_sexo,
            
            CONCAT(e.bairro,', ',e.rua, ', ',e.casa) as endereco,
        
            p.id_provincia,
            p.nome_provincia,
            m.id_municipio,
            m.nome_municipio,
            
            c.numero_candidatura,
            c.data_candidatura,
            c.id_instituicao,
            c.id_curso,
            c.id_ano_letivo,
            c.estado_candidatura,
            c.id_turma,
            c.id_turma_origem,
            c.id_ciclo_letivo,
            c.id_ano_curricular,
            c.id_periodo_letivo,
            c.nota_portugues,
            c.nota_matematica,
            c.classificacao_final,
            c.lingua_opcao,
            c.observacao,
            c.id_funcionario,
            c.candidatura_aberta,
            c.documento_file,
            c.documento_file1,
            c.documento_file3,
            c.aprovado,
            c.created_at,
            c.updated_at
        ")
            ->join('usuario u', '(u.id_usuario = c.id_candidato)')
            ->join('sexo s', '(u.id_sexo = s.id_sexo)', 'LEFT')
            ->join('endereco e', '(u.id_endereco = e.id_endereco)', 'LEFT')
            ->join('municipio m', '(u.id_municipio = m.id_municipio)', 'LEFT')
            ->join('provincia p', '(m.id_provincia = p.id_provincia)', 'LEFT');


        $candidatura = $builder
            ->where('c.id_instituicao', $id_instituicao)
            ->where('id_candidatura', $id_candidatura)
            ->where('c.deleted_at', null)
            ->groupBy('id_candidato, id_curso, id_periodo_letivo')
            ->orderBy('c.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return $candidatura;
    }
}
