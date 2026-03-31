<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\ProfessorModel;
use App\Models\DocenciaModel;
use App\Models\TurmaDisciplinaModel;
use App\Models\UsuarioModel;

class ProfessorController extends BaseController
{
    protected ProfessorModel        $professor;
    protected DocenciaModel         $docencia;
    protected TurmaDisciplinaModel  $turma_disciplina;
    protected ApiResponse           $api_response;
    protected UsuarioModel          $usuario;

    public function __construct()
    {
        $this->professor        = model(ProfessorModel::class);
        $this->docencia         = model(DocenciaModel::class);
        $this->turma_disciplina = model(TurmaDisciplinaModel::class);
        $this->usuario          = model(UsuarioModel::class);
        $this->api_response     = new ApiResponse();
    }

    // ─── 1. Registar docente ──────────────────────────────────────────────────
    public function create()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_create(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se o usuário existe no sistema
            $usuario = $this->usuario
                ->where('id_usuario', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$usuario) {
                return $this->api_response->set_error(
                    'Usuário não encontrado no sistema!',
                    404
                );
            }

            // Verifica se já é docente na instituição
            if ($this->professor->professor_ja_existe($data['id_professor'], $data['id_instituicao'])) {
                return $this->api_response->set_error(
                    'Usuário já se encontra registado como docente nesta instituição!',
                    401
                );
            }

            $array_data = [
                'id_professor'   => $data['id_professor'],
                'iban_professor' => $data['iban_professor']  ?? null,
                'numero_agente'  => $data['numero_agente']   ?? null,
                'inicio_funcao'  => $data['inicio_funcao']   ?? date('Y-m-d'),
                'id_instituicao' => $data['id_instituicao'],
                'ativo'          => 1,
                'created_at'     => date('Y-m-d H:i:s')
            ];

            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);

            $this->professor->insert($array_data);

            if ($this->professor->db->affectedRows() > 0) {
                return $this->api_response->set_success(
                    ['id_professor' => $data['id_professor']],
                    'Docente registado com sucesso!'
                );
            }

            return $this->api_response->set_error('Erro ao registar docente!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 2. Atualizar dados do docente ────────────────────────────────────────
    public function update()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_update(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $professor = $this->professor
                ->where('id_professor', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$professor) {
                return $this->api_response->set_error('Docente não encontrado!', 404);
            }

            $this->professor->update($professor->id_professor, [
                'iban_professor' => $data['iban_professor'] ?? $professor->iban_professor,
                'numero_agente'  => $data['numero_agente']  ?? $professor->numero_agente,
                'inicio_funcao'  => $data['inicio_funcao']  ?? $professor->inicio_funcao,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Dados do docente atualizados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 3. Associar docente a disciplinas ────────────────────────────────────
    public function associar_disciplinas()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'disciplinas' => [
                'label' => 'Disciplinas',
                'rules' => 'required'
            ],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $professor = $this->professor
                ->where('id_professor', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$professor) {
                return $this->api_response->set_error('Docente não encontrado!', 404);
            }

            if (!$professor->ativo) {
                return $this->api_response->set_error('Docente inativo! Não é possível associar disciplinas.', 401);
            }

            if (empty($data['disciplinas']) || !is_array($data['disciplinas'])) {
                return $this->api_response->set_error('Lista de disciplinas não informada!', 401);
            }

            $associados = [];
            $ignorados  = [];

            foreach ($data['disciplinas'] as $item) {

                if (empty($item['id_turma_disciplina'])) {
                    $ignorados[] = [
                        'item'   => $item,
                        'motivo' => 'id_turma_disciplina não informado!'
                    ];
                    continue;
                }

                // Verifica se turma_disciplina existe
                $turma_disciplina = $this->turma_disciplina
                    ->where('id_instituicao', $data['id_instituicao'])
                    ->where('id_turma_disciplina', $item['id_turma_disciplina'])
                    ->first();

                if (!$turma_disciplina) {
                    $ignorados[] = [
                        'id_turma_disciplina' => $item['id_turma_disciplina'],
                        'motivo'              => 'Disciplina não encontrada na turma!'
                    ];
                    continue;
                }

                // Verifica se professor já está associado
                if ($this->docencia->professor_ja_atribuido(
                    $data['id_professor'],
                    $item['id_turma_disciplina'],
                    $data['id_instituicao']
                )) {
                    $ignorados[] = [
                        'id_turma_disciplina' => $item['id_turma_disciplina'],
                        'motivo'              => 'Docente já associado a esta disciplina!'
                    ];
                    continue;
                }

                $id_docencia = $this->docencia->insert([
                    'id_professor'        => $professor->id_professor,
                    'id_turma_disciplina' => $item['id_turma_disciplina'],
                    'id_instituicao'      => $data['id_instituicao'],
                    'data_inicio'         => $item['data_inicio'] ?? date('Y-m-d'),
                    'data_fim'            => $item['data_fim']    ?? null,
                    'observacao'          => $item['observacao']  ?? null,
                ], true);

                if ($id_docencia > 0) {
                    $associados[] = [
                        'id_docencia'         => $id_docencia,
                        'id_turma_disciplina' => $item['id_turma_disciplina'],
                    ];
                }
            }

            return $this->api_response->set_success([
                'associados'       => $associados,
                'ignorados'        => $ignorados,
                'total_associados' => count($associados),
                'total_ignorados'  => count($ignorados),
            ], count($associados) . ' disciplina(s) associada(s) ao docente com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 4. Listar docentes ───────────────────────────────────────────────────
    public function list()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;

            $builder = $this->professor->builder('professor p')
                ->select('
                    p.id_professor,
                    u.nome_usuario,
                    u.sobrenome_usuario,
                    p.iban_professor,
                    p.numero_agente,
                    p.inicio_funcao,
                    p.id_instituicao,
                    p.ativo,
                    p.created_at
                ')
                ->join('usuario u', '(u.id_usuario = p.id_professor)')
                ->where('p.id_instituicao', $data['id_instituicao'])
                ->where('p.deleted_at', null);

            // Filtros

            if (isset($data['ativo'])) {
                $builder->where('p.ativo', $data['ativo']);
            }

            if (!empty($data['numero_agente'])) {
                $builder->like('p.numero_agente', $data['numero_agente']);
            }

            $total     = $builder->countAllResults(false);
            $docentes  = $builder
                ->orderBy('p.created_at', 'DESC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $docentes,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($total / $per_page),
                    'from'         => $offset + 1,
                    'to'           => min($offset + $per_page, $total),
                ],
            ], 'Docentes retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── 4.1. Listar docentes ───────────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $docente = $this->professor
                ->select("
                    professor.id_professor,
                    u.nome_usuario,
                    u.sobrenome_usuario,
                    sx.sexo_sigla,
                    sx.designacao_sexo,
                    u.data_nascimento,
                    u.nome_pai,
                    u.nome_mae,
                    u.nif,
                    u.numero_doc,
                    u.data_emisao_doc,
                    u.local_emisaao_doc,
                    professor.iban_professor,
                    professor.numero_agente,
                    professor.inicio_funcao,
                    COALESCE(u.telefone_fixo,' ',u.telefone_movel) AS telefone,
                    e.casa,
                    e.rua,
                    e.bairro,
                    professor.id_instituicao,
                    professor.ativo,
                    professor.created_at 
                ")
                ->join('usuario u', '(u.id_usuario = professor.id_professor)')
                ->join('sexo sx', '(u.id_sexo = sx.id_sexo)')
                ->join('endereco e', '(e.id_endereco = u.id_endereco)')
                ->where('professor.id_professor', $data['id_professor'])
                ->where('professor.id_instituicao', $data['id_instituicao'])
                ->where('professor.deleted_at', null)
                ->first();


            if (!$docente) {
                return $this->api_response->set_error('Aluno não encontrado!', 404);
            }


            return $this->api_response->set_success($docente, 'Docentes retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 5. Consultar carga horária do docente ────────────────────────────────
    public function carga_horaria()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $professor = $this->professor
                ->where('id_professor', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$professor) {
                return $this->api_response->set_error('Docente não encontrado!', 404);
            }

            // Carga horária total
            $carga = $this->professor->carga_horaria(
                $professor->id_professor,
                $data['id_ano_letivo'],
                $data['id_instituicao']
            );

            if (!$carga) {
                return $this->api_response->set_error('Dados da carga horária não encontrados', 404);
            }

            // Detalhe por disciplina/turma
            $detalhe = $this->docencia->builder('docencia dc')
                ->select("
                dc.id_docencia,
                CONCAT(us.nome_usuario, ' ',us.sobrenome_usuario) AS nome_professor,
                pf.numero_agente,
                dc.data_inicio,
                dc.data_fim,
                dc.observacao,
                d.nome_disciplina,
                COALESCE(d.carga_horaria_semanal, td.carga_horaria) AS carga_horaria_semanal,
                d.carga_horaria_total,
                t.nome_turma,
                al.ano_letivo,
                ac.ano_curricular,
                s.semestre_lectivo,
                s.data_inicio,
                s.data_fim,
                cl.ciclo_letivo,
                c.nome_curso,
                c.codigo_curso")
                ->join('turma_disciplina td', '(td.id_turma_disciplina = dc.id_turma_disciplina)', 'LEFT')
                ->join('plano_curricular pc', '(pc.id_plano_curricular = td.id_plano_curricular)', 'LEFT')
                ->join('turma t', '(t.id_turma = td.id_turma)', 'LEFT')
                ->join('ano_letivo al', '(al.id_ano_letivo = td.id_ano_letivo)', 'LEFT')
                ->join('disciplina d', '(d.id_disciplina = pc.id_disciplina)', 'LEFT')
                ->join('ano_curricular ac', '(ac.id_ano_curricular = pc.id_ano_curricular)', 'LEFT')
                ->join('semestre_lectivo s', '(s.id_semestre_lectivo = pc.id_semestre_lectivo)', 'LEFT')
                ->join('ciclo_letivo cl', '(pc.id_ciclo_letivo = cl.id_ciclo_letivo)', 'LEFT')
                ->join('curso c', '(pc.id_curso = c.id_curso)', 'LEFT')
                ->join('professor pf', '(pf.id_professor = dc.id_professor)', 'LEFT')
                ->join('usuario us', '(us.id_usuario = pf.id_professor)', 'LEFT')
                ->where('dc.id_instituicao', $data['id_instituicao'])
                ->where('dc.deleted_at', null)
                ->where('dc.id_professor', $professor->id_professor)
                ->where('td.id_ano_letivo', $data['id_ano_letivo'])
                ->orderBy('pc.id_semestre_lectivo', 'ASC')
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'id_professor'      => $professor->id_professor,
                'id_ano_letivo'     => $data['id_ano_letivo'],
                'carga_total'       => $carga['carga_total']       ?? 0,
                'total_disciplinas' => $carga['total_disciplinas']  ?? 0,
                'detalhe'           => $detalhe,
            ], 'Carga horária retornada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Desativar docente ────────────────────────────────────────────────────
    public function deactivate()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $professor = $this->professor
                ->where('id_professor', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$professor) {
                return $this->api_response->set_error('Docente não encontrado!', 404);
            }

            if (!$professor->ativo) {
                return $this->api_response->set_error('Docente já se encontra inativo!', 401);
            }

            $this->professor->update($professor->id_professor, [
                'ativo'      => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Docente desativado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Ativar docente ────────────────────────────────────────────────────
    public function activate()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $professor = $this->professor
                ->where('id_professor', $data['id_professor'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$professor) {
                return $this->api_response->set_error('Docente não encontrado!', 404);
            }

            if ($professor->ativo) {
                return $this->api_response->set_error('Docente já se encontra ativo!', 401);
            }

            $this->professor->update($professor->id_professor, [
                'ativo'      => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Docente ativado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Validações ───────────────────────────────────────────────────────────
    private function _form_validate_create(): array
    {
        return [
            'id_professor' => [
                'label' => 'Usuário',
                'rules' => 'required|is_natural_no_zero'
            ],
            'iban_professor' => [
                'label' => 'IBAN',
                'rules' => 'permit_empty|min_length[15]|max_length[45]'
            ],
            'numero_agente' => [
                'label' => 'Número de agente',
                'rules' => 'permit_empty|max_length[45]'
            ],
            'inicio_funcao' => [
                'label' => 'Início de função',
                'rules' => 'permit_empty|valid_date[Y-m-d]'
            ],
        ];
    }

    private function _form_validate_update(): array
    {
        return [
            'iban_professor' => [
                'label' => 'IBAN',
                'rules' => 'permit_empty|min_length[15]|max_length[45]'
            ],
            'numero_agente' => [
                'label' => 'Número de agente',
                'rules' => 'permit_empty|max_length[45]'
            ],
            'inicio_funcao' => [
                'label' => 'Início de função',
                'rules' => 'permit_empty|valid_date[Y-m-d]'
            ],
        ];
    }
}
