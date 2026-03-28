<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\MatriculaModel;
use App\Models\AlunoModel;
use App\Models\ExameAptidaoModel;

class MatriculaController extends BaseController
{
    protected MatriculaModel    $matricula;
    protected AlunoModel        $aluno;
    protected ApiResponse       $api_response;
    protected ExameAptidaoModel $exame;

    public function __construct()
    {
        $this->matricula    = new MatriculaModel();
        $this->aluno        = new AlunoModel();
        $this->api_response = new ApiResponse();
        $this->exame        = new ExameAptidaoModel();
    }

    // ─── Criar matrícula ──────────────────────────────────────────────────────
    public function create()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_create(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se aluno existe e está seriado
            $aluno = $this->aluno
                ->where('id_aluno', $data['id_aluno'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();
            if (!$aluno) {
                return $this->api_response->set_error('Aluno não encontrado!', 404);
            }


            // Verifica se já tem matrícula no mesmo ano letivo
            if ($this->matricula->aluno_ja_matriculado(
                $data['id_aluno'],
                $data['id_ano_letivo'],
                $data['id_instituicao']
            )) {
                return $this->api_response->set_error(
                    'Aluno já possui matrícula para este ano letivo! Deseja alterá-la?',
                    401
                );
            }

            $has_funcionario = (int)($data['id_funcionario']);


            $array_data = [
                'data_matricula'      => date('Y-m-d'),
                'id_aluno'            => $aluno->id_aluno,
                'id_funcionario'      => $has_funcionario,
                'id_instituicao'      => $data['id_instituicao'],
                'id_ano_letivo'       => $data['id_ano_letivo'],
                'estado_matricula'    => $has_funcionario ? 'Validada' : 'Pendente',
                'is_reconfirmacao'    => 0,
                'comprovativo_pag'    => null,
                'estado_pagamento'    => 'Pago',
                'id_turma'            => $data['id_turma'],
                'id_ciclo_letivo'     => $data['id_ciclo_letivo'],
                'id_ano_curricular'   => $data['id_ano_curricular'],
                'id_periodo_letivo'   => $data['id_periodo_letivo'],
                'id_semestre_lectivo' => $data['id_semestre_lectivo'],
                'id_sala'             => $data['id_sala'],
                'lingua_opcao'        => $data['lingua_opcao'],
                'lingua_estudada'     => $data['lingua_estudada'],
                'observacao'          => $data['observacao']       ?? null,
            ];

            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);


            $id = $this->matricula->insert($array_data, true);

            if ($id > 0) {
                return $this->api_response->set_success(
                    ['id_matricula' => $id],
                    'Matrícula realizada com sucesso!'
                );
            }
            return $this->api_response->set_error('Erro ao realizar matrícula!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Criar matrícula ──────────────────────────────────────────────────────
    public function update()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_create(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se aluno existe e está seriado
            $aluno = $this->aluno
                ->where('id_aluno', $data['id_aluno'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();



            $matricula = $this->matricula
                ->where('id_matricula', $data['id_matricula'])
                ->where('id_aluno', $data['id_aluno'])
                ->where('id_ano_letivo', $data['id_ano_letivo'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$aluno && !$matricula) {
                return $this->api_response->set_error('Matrícula não encontrada! Verifique os dados fornecidos.', 404);
            }

            
            $has_funcionario = (int)($data['id_funcionario']);


            $array_data = [
                'data_matricula'      => date('Y-m-d'),
                'id_aluno'            => $aluno->id_aluno,
                'id_funcionario'      => $has_funcionario,
                'id_instituicao'      => $data['id_instituicao'],
                'id_ano_letivo'       => $data['id_ano_letivo'],
                'estado_matricula'    => $has_funcionario ? 'Validada' : 'Pendente',
                'estado_pagamento'    => 'Pago',
                'id_turma'            => $data['id_turma'],
                'id_ciclo_letivo'     => $data['id_ciclo_letivo'],
                'id_ano_curricular'   => $data['id_ano_curricular'],
                'id_periodo_letivo'   => $data['id_periodo_letivo'],
                'id_semestre_lectivo' => $data['id_semestre_lectivo'],
                'id_sala'             => $data['id_sala'],
                'lingua_opcao'        => $data['lingua_opcao'],
                'lingua_estudada'     => $data['lingua_estudada'],
                'observacao'          => $data['observacao']       ?? null,
            ];

            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);


            $this->matricula->update($matricula->id_matricula, $array_data);

            if ($this->matricula->db->affectedRows() > 0) {
                return $this->api_response->set_success(
                    ['id_matricula' => $matricula->id_matricula],
                    'Matrícula atualizada com sucesso!'
                );
            }
            return $this->api_response->set_error('Erro ao realizar matrícula!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Validar matrícula ────────────────────────────────────────────────────
    public function validar()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $matricula = $this->matricula
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('id_matricula', $data['id_matricula'])
                ->where('deleted_at', null)
                ->first();


            if (!$matricula) {
                return $this->api_response->set_error('Matrícula não encontrada!', 404);
            }

            if ($matricula->estado_matricula === 'Validada') {
                return $this->api_response->set_error('Matrícula já se encontra validada!', 401);
            }

            $this->matricula->update($$matricula->id_matricula, [
                'estado_matricula' => 'Validada',
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Matrícula validada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Listar matrículas ────────────────────────────────────────────────────
    public function list()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;

            $builder = $this->matricula->builder();

            $builder->select("
                        matricula.id_matricula,
                        matricula.data_matricula,
                        matricula.id_aluno,
                        a.numero_aluno,
                        u.nome_usuario,
                        u.sobrenome_usuario,
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
                ->where('matricula.deleted_at', null)
                ->where('matricula.id_instituicao', $data['id_instituicao']);

            // ─── Filtros ───────────────────────────────────────────────────────

            // Filtro: Turma
            if (!empty($data['id_turma'])) {
                $builder->where('matricula.id_turma', $data['id_turma']);
            }

            // Filtro: Classe (ano curricular)
            if (!empty($data['id_ano_curricular'])) {
                $builder->where('matricula.id_ano_curricular', $data['id_ano_curricular']);
            }

            // Filtro: Período
            if (!empty($data['id_periodo_letivo'])) {
                $builder->where('matricula.id_periodo_letivo', $data['id_periodo_letivo']);
            }

            // Filtro: Curso — via join com aluno
            if (!empty($data['id_curso'])) {
                $builder->where('a.id_curso', $data['id_curso']);
            }

            // Filtro: Ano letivo
            if (!empty($data['numero_aluno'])) {
                $builder->where('a.numero_aluno', $data['numero_aluno']);
            }


            // Filtro: Ano letivo
            if (!empty($data['id_ano_letivo'])) {
                $builder->where('matricula.id_ano_letivo', $data['id_ano_letivo']);
            }

            $total      = $builder->countAllResults(false);
            $matriculas = $builder
                ->orderBy('matricula.created_at', 'DESC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $matriculas,
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

    // ─── Ver matrícula por ID ─────────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('GET');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $matricula = $this->matricula
                ->select("
                        matricula.id_matricula,
                        matricula.data_matricula,
                        matricula.id_aluno,
                        a.numero_aluno,
                        u.nome_usuario,
                        u.sobrenome_usuario,
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
                ->where('matricula.id_matricula', $data['id_matricula'])
                ->where('matricula.id_instituicao', $data['id_instituicao'])
                ->where('matricula.deleted_at', null)
                ->first();

            if (!$matricula) {
                return $this->api_response->set_error('Matrícula não encontrada!', 404);
            }

            return $this->api_response->set_success($matricula, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Reconfirmação de matrícula ───────────────────────────────────────────
    public function reconfirmar()
    {
        // TODO: Fazer teste do metodo reconfirmar 
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_reconfirmacao(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $aluno = $this->aluno
                ->where('id_aluno', $data['id_aluno'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$aluno) {
                return $this->api_response->set_error('Aluno não encontrado!', 404);
            }

            // Verifica se teve matrícula no ano anterior
            $matricula_anterior = $this->matricula
                ->where('id_aluno', $data['id_aluno'])
                ->where('estado_matricula', 'Validada')
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->orderBy('id_ano_letivo', 'DESC')
                ->first();

            if (!$matricula_anterior) {
                return $this->api_response->set_error(
                    'Aluno não possui matrícula validada em anos anteriores!',
                    401
                );
            }

            // Bloqueia reconfirmação duplicada no mesmo ano letivo
            if ($this->matricula->aluno_ja_matriculado(
                $data['id_aluno'],
                $data['id_ano_letivo'],
                $data['id_instituicao']
            )) {
                return $this->api_response->set_error(
                    'Aluno já possui matrícula para este ano letivo! Deseja alterá-la?',
                    401
                );
            }

            $has_funcionario = (int)($data['id_funcionario']);

            $array_data = [
                'data_matricula'      => date('Y-m-d'),
                'id_aluno'            => $data['id_aluno'],
                'id_funcionario'      => $data['id_funcionario'],
                'id_instituicao'      => $data['id_instituicao'] ?? $matricula_anterior->id_instituicao,
                'id_ano_letivo'       => $data['id_ano_letivo'],
                'estado_matricula'    => $has_funcionario ? 'Validada' : 'Pendente',
                'is_reconfirmacao'    => 1,
                'estado_pagamento'    => $has_funcionario ? 'Pago' : 'Pendente',
                'id_turma'            => $data['id_turma']            ?? $matricula_anterior->id_turma,
                'id_ciclo_letivo'     => $data['id_ciclo_letivo']     ?? $matricula_anterior->id_ciclo_letivo,
                'id_ano_curricular'   => $data['id_ano_curricular']   ?? $matricula_anterior->id_ano_curricular,
                'id_periodo_letivo'   => $data['id_periodo_letivo']   ?? $matricula_anterior->id_periodo_letivo,
                'id_semestre_lectivo' => $data['id_semestre_lectivo'] ?? $matricula_anterior->id_semestre_lectivo,
                'id_sala'             => $data['id_sala']             ?? $matricula_anterior->id_sala,
                'lingua_opcao'        => $data['lingua_opcao']        ?? $matricula_anterior->lingua_opcao,
                'lingua_estudada'     => $data['lingua_estudada']     ?? $matricula_anterior->lingua_estudada,
                'observacao'          => $data['observacao']          ?? null,
            ];


            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);

            $id = $this->matricula->insert($array_data, true);


            if ($id > 0) {
                return $this->api_response->set_success(
                    ['id_matricula' => $id],
                    'Reconfirmação registada com sucesso! Aguarda validação do pagamento.'
                );
            }

            return $this->api_response->set_error('Erro ao registar reconfirmação!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Submeter comprovativo de pagamento ───────────────────────────────────
    public function submeter_comprovativo_matricula()
    {
        $this->api_response->validade_request('POST');

        // Detecta tipo de conteúdo
        $contentType = $this->request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = $this->request->getJSON(true) ?? [];
            $ficheiro = $this->request->getFile('comprovativo_pag');
        } else {
            $data = $this->request->getPost();
            $ficheiro = $this->request->getFile('comprovativo_pag');
        }

        try {

            $matricula = $this->matricula
                ->where('id_matricula', $data['id_matricula'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('is_reconfirmacao', 1)
                ->where('deleted_at', null)
                ->first();

            if (!$matricula) {
                return $this->api_response->set_error('Reconfirmação não encontrada!', 404);
            }

            if ($matricula->estado_pagamento !== 'Pendente') {
                return $this->api_response->set_error(
                    'Comprovativo já foi submetido anteriormente!',
                    401
                );
            }



            if (!$ficheiro || !$ficheiro->isValid()) {
                return $this->api_response->set_error('Comprovativo não enviado!', 401);
            }

            if ($ficheiro->getClientMimeType() !== 'application/pdf') {
                return $this->api_response->set_error('O comprovativo deve ser um PDF!', 401);
            }

            if ($ficheiro->getSize() > 5 * 1024 * 1024) {
                return $this->api_response->set_error('O ficheiro não pode ultrapassar 5MB!', 401);
            }

            $caminho = WRITEPATH . 'uploads/matriculas/comprovativos/';

            if (!is_dir($caminho)) {
                mkdir($caminho, 0755, true);
            }

            // Remove comprovativo anterior se existir
            if (!empty($matricula->comprovativo_pag)) {
                $ficheiro_antigo = WRITEPATH . $matricula['comprovativo_pag'];
                if (file_exists($ficheiro_antigo)) {
                    unlink($ficheiro_antigo);
                }
            }

            $nome = $ficheiro->getRandomName();
            $ficheiro->move($caminho, $nome);

            $this->matricula->update($matricula->id_matricula, [
                'comprovativo_pag' => 'uploads/matriculas/comprovativos/' . $nome,
                'estado_pagamento' => 'Pago',
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Comprovativo submetido! Aguarda validação do funcionário.');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Validar pagamento da reconfirmação ───────────────────────────────────
    public function validar_pagamento_matricula()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'aprovado' => [
                'label' => 'Decisão',
                'rules' => 'required|in_list[0,1]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[200]'
            ],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $matricula = $this->matricula
                ->where('id_matricula', $data['id_matricula'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('is_reconfirmacao', 1)
                ->where('deleted_at', null)
                ->first();

            if (!$matricula) {
                return $this->api_response->set_error('Reconfirmação não encontrada!', 404);
            }

            if ($matricula->estado_pagamento !== 'Pago') {
                return $this->api_response->set_error(
                    'Reconfirmação não possui comprovativo submetido!',
                    401
                );
            }

            $aprovado = (int) $data['aprovado'];

            $this->matricula->update($matricula->id_matricula, [
                'estado_pagamento' => $aprovado ? 'Pago'     : 'Rejeitado',
                'estado_matricula' => $aprovado ? 'Validada' : 'Pendente',
                'observacao'       => $data['observacao'] ?? $matricula->observacao,
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);

            $mensagem = $aprovado
                ? 'Pagamento validado! Reconfirmação aprovada com sucesso.'
                : 'Pagamento rejeitado! Aluno deve submeter novo comprovativo.';

            $email_info = $this->matricula->show_matricula_info(
                $matricula->id_instituicao,
                $matricula->id_matricula
            );


            $nome_usuario = $email_info->nome_usuario . ' ' . $email_info->sobrenome_usuario;

            $email_service = new \App\Services\EmailService();
            // TODO: descomentar as linas a baixo só após a configuração dos serviços de emails em: EmailService
            // $enviado = $email_service->send_mail_info($email_info->email, $nome_usuario, $mensagem);

            // if (!$enviado) {
            //     return $this->api_response->set_error('Erro ao enviar o email! Tente novamente.', 500);
            // }

            return $this->api_response->set_success([], $mensagem);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Cancelar matrícula ───────────────────────────────────────────────────
    public function cancelar()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $matricula = $this->matricula
                ->where('id_matricula', $data['id_matricula'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('estado_matricula <>', 'Anulada')
                ->where('deleted_at', null)
                ->first();

            if (!$matricula) {
                return $this->api_response->set_error('Matrícula não encontrada!', 404);
            }


            $this->matricula->update($data['id_matricula'], [
                'estado_matricula'  => 'Anulada',
                'updated_at'        => date('Y-m-d H:i:s'),
                'id_funcionario'    => $data['id_funcionario']
            ]);

            return $this->api_response->set_success([], 'Matrícula anulada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Validações ───────────────────────────────────────────────────────────
    private function _form_validate_create(): array
    {
        return [
            'id_aluno' => [
                'label' => 'Aluno',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_funcionario' => [
                'label' => 'Funcionário',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_turma' => [
                'label' => 'Turma',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ciclo_letivo' => [
                'label' => 'Ciclo letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_curricular' => [
                'label' => 'Ano curricular',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_periodo_letivo' => [
                'label' => 'Período letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_semestre_lectivo' => [
                'label' => 'Semestre letivo',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_sala' => [
                'label' => 'Sala',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'lingua_opcao' => [
                'label' => 'Língua de opção',
                'rules' => 'required|in_list[Inglês,Francês]'
            ],
            'lingua_estudada' => [
                'label' => 'Língua estudada',
                'rules' => 'permit_empty|in_list[Inglês,Francês]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[200]'
            ],
        ];
    }


    private function _form_validate_reconfirmacao()
    {
        return [
            'id_aluno' => [
                'label' => 'Aluno',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_funcionario' => [
                'label' => 'Funcionário',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_turma' => [
                'label' => 'Turma',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_ano_curricular' => [
                'label' => 'Ano curricular',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_periodo_letivo' => [
                'label' => 'Período letivo',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_semestre_lectivo' => [
                'label' => 'Semestre letivo',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'id_sala' => [
                'label' => 'Sala',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'lingua_opcao' => [
                'label' => 'Língua de opção',
                'rules' => 'permit_empty|in_list[Inglês,Francês]'
            ],
            'lingua_estudada' => [
                'label' => 'Língua estudada',
                'rules' => 'permit_empty|in_list[Inglês,Francês]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[200]'
            ],
        ];
    }
}
