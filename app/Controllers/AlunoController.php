<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\AlunoModel;
use App\Models\ExameAptidaoModel;
use App\Models\CandidaturaModel;

class AlunoController extends BaseController
{
    protected AlunoModel       $aluno;
    protected ExameAptidaoModel $exame;
    protected CandidaturaModel  $candidatura;
    protected ApiResponse $api_response;

    public function __construct()
    {
        $this->aluno       = new AlunoModel();
        $this->exame       = new ExameAptidaoModel();
        $this->candidatura = new CandidaturaModel();
        $this->api_response = new ApiResponse();
    }

    // ─── 1. Seriar candidato individualmente ──────────────────────────────────
    public function seriar()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'nota_minima' => [
                'label' => 'Nota mínima',
                'rules' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            // Busca exame com dados da candidatura
            $exame = $this->exame
                ->select('
                    exames_aptidao.id_exame_apetidao,
                    exames_aptidao.id_candidato,
                    exames_aptidao.id_curso,
                    exames_aptidao.id_instituicao,
                    exames_aptidao.situacao_exame,
                    exames_aptidao.aprovado,
                    exames_aptidao.classificacao_final
                ')
                ->where('exames_aptidao.id_exame_apetidao', $data['id_exame_apetidao'])
                ->where('exames_aptidao.classificacao_final >= ', $data['nota_minima'])
                ->where('exames_aptidao.id_instituicao', $data['id_instituicao'])
                ->where('exames_aptidao.deleted_at', null)
                ->get()
                ->getRowArray();

            if (!$exame) {
                return $this->api_response->set_error('Exame não encontrado!', 404);
            }


            // Valida se foi aprovado
            if ($exame['situacao_exame'] !== 'Aprovado' || $exame['aprovado'] != 1) {
                return $this->api_response->set_error(
                    'Apenas candidatos aprovados no exame podem ser seriados!',
                    401
                );
            }



            // Valida nota mínima
            if ((float) $exame['classificacao_final'] < (float) $data['nota_minima']) {
                return $this->api_response->set_error(
                    "Candidato não atingiu a nota mínima de {$data['nota_minima']} para seriação!",
                    401
                );
            }


            // Verifica se já é aluno
            if ($this->aluno->candidato_ja_e_aluno(
                $exame['id_candidato'],
                $exame['id_curso'],
                intval($exame['id_instituicao'])
            )) {
                return $this->api_response->set_error(
                    'Candidato já se encontra seriado como aluno neste curso!',
                    401
                );
            }

            $numero_aluno = $this->aluno->gerar_numero_aluno($exame['id_instituicao']);

            $this->aluno->insert([
                'id_aluno'       => $exame['id_candidato'],
                'id_profissao'   => null,
                'id_instituicao' => $exame['id_instituicao'],
                'numero_aluno'   => $numero_aluno,
                'id_curso'       => $exame['id_curso'],
            ]);

            if ($this->aluno->db->affectedRows() > 0) {
                return $this->api_response->set_success([
                    'id_aluno'     => $exame['id_candidato'],
                    'numero_aluno' => $numero_aluno,
                ], 'Candidato seriado como aluno com sucesso!');
            }

            return $this->api_response->set_success([
                'numero_aluno' => $numero_aluno,
            ], 'Candidato seriado como aluno com sucesso!');

            return $this->api_response->set_error('Erro ao seriar candidato!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 2. Seriar em massa (todos aprovados do curso) ────────────────────────
    public function seriar_massa()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'id_curso' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'nota_minima' => [
                'label' => 'Nota mínima',
                'rules' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Busca todos os aprovados do curso/ano/instituição acima da nota mínima
            $aprovados = $this->exame
                ->select('
                    exames_aptidao.id_candidato,
                    exames_aptidao.id_curso,
                    exames_aptidao.id_instituicao,
                    MAX(exames_aptidao.classificacao_final) as classificacao_final
                ')
                ->where('exames_aptidao.id_curso', $data['id_curso'])
                ->where('exames_aptidao.id_ano_letivo', $data['id_ano_letivo'])
                ->where('exames_aptidao.id_instituicao', $data['id_instituicao'])
                ->where('exames_aptidao.situacao_exame', 'Aprovado')
                ->where('exames_aptidao.aprovado', 1)
                ->where('exames_aptidao.classificacao_final >= ', $data['nota_minima'])
                ->where('exames_aptidao.deleted_at', null)
                ->groupBy([
                    'exames_aptidao.id_candidato',
                    'exames_aptidao.id_curso',
                    'exames_aptidao.id_instituicao'
                ])
                ->orderBy('exames_aptidao.classificacao_final', 'DESC')
                ->get()
                ->getResultArray();


            if (empty($aprovados)) {
                return $this->api_response->set_error(
                    'Nenhum candidato aprovado encontrado com a nota mínima informada!',
                    404
                );
            }


            $seriados = [];
            $erros    = [];


            foreach ($aprovados as $aprovado) {

                $id = $aprovado['id_candidato'];

                if (isset($seriados[$id])) {
                    continue;
                }

                $processados[$id] = true;

                // Verifica se já é aluno
                if ($this->aluno->candidato_ja_e_aluno(
                    $aprovado['id_candidato'],
                    $aprovado['id_curso'],
                    $aprovado['id_instituicao']
                )) {
                    $erros[] = [
                        'id_candidato' => $aprovado['id_candidato'],
                        'erro'         => 'Candidato já se encontra seriado como aluno neste curso!'
                    ];
                    continue;
                }

                $numero_aluno = $this->aluno->gerar_numero_aluno($aprovado['id_instituicao']);

                //$insert
                $this->aluno->insert([
                    'id_aluno'       => $aprovado['id_candidato'],
                    'id_profissao'   => null,
                    'id_instituicao' => $aprovado['id_instituicao'],
                    'numero_aluno'   => $numero_aluno,
                    'id_curso'       => $aprovado['id_curso'],
                    'ativo'          => 1,
                ]);

                if ($this->aluno->db->affectedRows() > 0) {
                    $seriados[] = [
                        'id_aluno'            => $id,
                        'id_candidato'        => $aprovado['id_candidato'],
                        'numero_aluno'        => $numero_aluno,
                        'classificacao_final' => $aprovado['classificacao_final'],
                    ];
                } else {
                    $erros[] = [
                        'id_candidato' => $aprovado['id_candidato'],
                        'erro'         => 'Erro ao seriar candidato!'
                    ];
                }
            }

            return $this->api_response->set_success([
                'seriados'        => $seriados,
                'erros'           => $erros,
                'total_seriados'  => count($seriados),
                'total_erros'     => count($erros),
            ], count($seriados) . ' candidato(s) seriado(s) com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 3. Listar alunos ─────────────────────────────────────────────────────
    public function list()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;

            $builder = $this->aluno->builder();

            $builder->where('aluno.deleted_at', null)
                ->where('aluno.id_instituicao', $data['id_instituicao']);

            if (!empty($data['id_curso'])) {
                $builder->where('aluno.id_curso', $data['id_curso']);
            }

            if (!empty($data['numero_aluno'])) {
                $builder->like('aluno.numero_aluno', $data['numero_aluno']);
            }

            if (!empty($data['numero_doc'])) {
                $builder->like('aluno.numero_doc', $data['numero_doc']);
            }


            $total  = $builder->countAllResults(false);
            $alunos = $builder
                ->select("
                        aluno. id_aluno,
                        u.nome_usuario,
                        u.sobrenome_usuario,
                        aluno.numero_aluno,
                        u.data_nascimento,
                        u.nome_pai,
                        u.nome_mae,
                        u.nif,
                        u.numero_doc,
                        u.data_emisao_doc,
                        u.data_emisao_doc,
                        COALESCE(u.telefone_fixo,' ', u.telefone_movel) as contato,
                        u.url_foto,
                        u.email,
                        u.id_municipio,
                        m.nome_municipio,
                        p.nome_provincia,
                        aluno.ativo,
                        
                        e.rua,  
                        e.bairro ,
                        e.distrito,
                        c.nome as comuna,
                        
                        pr.nome_profissao,
                        aluno.id_instituicao,

                        CONCAT(cr.nome_curso, ' (',cr.codigo_curso,')') as curso,
                        aluno.created_at
                          ")
                ->join('usuario u', '(aluno.id_aluno = u.id_usuario)')
                ->join('municipio m', '(u.id_municipio = m.id_municipio)', 'LEFT')
                ->join('provincia p', '(m.id_provincia = p.id_provincia)', 'LEFT')
                ->join('endereco e', '(u.id_endereco = e.id_endereco)', 'LEFT')
                ->join('comuna c', '(e.id_comuna = c.id_comuna)', 'LEFT')
                ->join('profissao pr', '(pr.id_profissao = aluno.id_profissao)', 'LEFT')
                ->join('curso cr', '(cr.id_curso = aluno.id_curso)', 'LEFT')
                ->orderBy('aluno.numero_aluno', 'ASC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $alunos,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($total / $per_page),
                    'from'         => $offset + 1,
                    'to'           => min($offset + $per_page, $total),
                ],
            ], 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 4. Ver aluno por ID ──────────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $aluno = $this->aluno
                ->select("
                        aluno. id_aluno,
                        u.nome_usuario,
                        u.sobrenome_usuario,
                        aluno.numero_aluno,
                        u.data_nascimento,
                        u.nome_pai,
                        u.nome_mae,
                        u.nif,
                        u.numero_doc,
                        u.data_emisao_doc,
                        u.data_emisao_doc,
                        COALESCE(u.telefone_fixo,' ', u.telefone_movel) as contato,
                        u.url_foto,
                        u.email,
                        u.id_municipio,
                        m.nome_municipio,
                        p.nome_provincia,
                        aluno.ativo,
                        
                        e.rua,  
                        e.bairro ,
                        e.distrito,
                        c.nome as comuna,
                        
                        pr.nome_profissao,
                        aluno.id_instituicao,

                        CONCAT(cr.nome_curso, ' (',cr.codigo_curso,')') as curso,
                        aluno.created_at
                          ")
                ->join('usuario u', '(aluno.id_aluno = u.id_usuario)')
                ->join('municipio m', '(u.id_municipio = m.id_municipio)', 'LEFT')
                ->join('provincia p', '(m.id_provincia = p.id_provincia)', 'LEFT')
                ->join('endereco e', '(u.id_endereco = e.id_endereco)', 'LEFT')
                ->join('comuna c', '(e.id_comuna = c.id_comuna)', 'LEFT')
                ->join('profissao pr', '(pr.id_profissao = aluno.id_profissao)', 'LEFT')
                ->join('curso cr', '(cr.id_curso = aluno.id_curso)', 'LEFT')
                ->where('aluno.id_aluno', $data['id_aluno'])
                ->where('aluno.id_instituicao', $data['id_instituicao'])
                ->where('aluno.deleted_at', null)
                ->first();

            if (!$aluno) {
                return $this->api_response->set_error('Aluno não encontrado!', 404);
            }

            return $this->api_response->set_success($aluno, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }
}
