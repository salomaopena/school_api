<?php

namespace App\Controllers;

use App\Libraries\ApiResponse;
use App\Models\TurmaDisciplinaModel;
use App\Models\DocenciaModel;

class DocenciaController extends BaseController
{
    protected TurmaDisciplinaModel  $turma_disciplina;
    protected DocenciaModel         $docencia;
    protected ApiResponse           $api_response;

    public function __construct()
    {
        $this->turma_disciplina = model(TurmaDisciplinaModel::class);
        $this->docencia         = model(DocenciaModel::class);
        $this->api_response     = new ApiResponse();
    }

    // ─── 1. Atribuir disciplinas à turma (em massa) ───────────────────────────
    public function atribuir_disciplinas_turma()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_validate_assign_curse(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            if (empty($data['disciplinas']) || !is_array($data['disciplinas'])) {
                return $this->api_response->set_error('Lista de disciplinas não informada!', 401);
            }

            $inseridos = [];
            $ignorados = [];

            foreach ($data['disciplinas'] as $item) {

                if (empty($item['id_plano_curricular'])) {
                    $ignorados[] = [
                        'item'   => $item,
                        'motivo' => 'Plano Curricular não informado!'
                    ];
                    continue;
                }

                // Ignora se já existir (UNIQUE KEY da tabela)
                if ($this->turma_disciplina->disciplina_ja_atribuida(
                    $data['id_turma'],
                    $item['id_plano_curricular'],
                    $data['id_ano_letivo'],
                    $data['id_instituicao']
                )) {
                    $ignorados[] = [
                        'id_plano_curricular' => $item['id_plano_curricular'],
                        'motivo'              => 'Disciplina já atribuída à turma neste ano letivo!'
                    ];
                    continue;
                }

                $id = $this->turma_disciplina->insert([
                    'id_turma'            => $data['id_turma'],
                    'id_plano_curricular' => $item['id_plano_curricular'],
                    'id_ano_letivo'       => $data['id_ano_letivo'],
                    'carga_horaria'       => $item['carga_horaria'] ?? null,
                    'id_instituicao'      => $data['id_instituicao'],
                    'created_at'          => date('Y-m-d H:i:s')
                ], true);

                if ($id > 0) {
                    $inseridos[] = [
                        'id_turma_disciplina' => $id,
                        'id_plano_curricular' => $item['id_plano_curricular'],
                        'carga_horaria'       => $item['carga_horaria'] ?? null,
                    ];
                }
            }

            return $this->api_response->set_success([
                'inseridos'       => $inseridos,
                'ignorados'       => $ignorados,
                'total_inseridos' => count($inseridos),
                'total_ignorados' => count($ignorados),
            ], count($inseridos) . ' disciplina(s) atribuída(s) à turma com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 2. Atribuir disciplina ao docente ────────────────────────────────────
    public function atribuir_docente()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_validate_form_teacher(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se turma_disciplina existe
            $turma_disciplina = $this->turma_disciplina
                ->where('id_turma_disciplina', $data['id_turma_disciplina'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->first();

            if (!$turma_disciplina) {
                return $this->api_response->set_error(
                    'Disciplina não encontrada na turma!',
                    404
                );
            }

            // Verifica se professor já está atribuído a esta turma/disciplina
            if ($this->docencia->professor_ja_atribuido(
                $data['id_professor'],
                $turma_disciplina->id_turma_disciplina,
                $turma_disciplina->id_instituicao
            )) {
                return $this->api_response->set_error(
                    'Professor já se encontra atribuído a esta disciplina na turma!',
                    401
                );
            }

            $id = $this->docencia->insert([
                'id_professor'          => $data['id_professor'],
                'id_turma_disciplina'   => $turma_disciplina->id_turma_disciplina,
                'id_instituicao'        => $turma_disciplina->id_instituicao,
                'data_inicio'           => $data['data_inicio'],
                'data_fim'              => $data['data_fim']   ?? null,
                'observacao'            => $data['observacao'] ?? null,
            ], true);

            if ($id > 0) {
                return $this->api_response->set_success(
                    ['id_docencia' => $id],
                    'Docente atribuído à disciplina com sucesso!'
                );
            }

            return $this->api_response->set_error('Erro ao atribuir docente!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 3. Listar disciplinas por turma ──────────────────────────────────────
    public function disciplinas_por_turma()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $builder = $this->turma_disciplina->builder('turma_disciplina td')
                ->select('
                    td.id_turma_disciplina,
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
                    c.codigo_curso
                ')
                ->join('plano_curricular pc', '(pc.id_plano_curricular = td.id_plano_curricular)', 'LEFT')
                ->join('turma t', '(t.id_turma = td.id_turma)', 'LEFT')
                ->join('ano_letivo al', '(al.id_ano_letivo = td.id_ano_letivo)', 'LEFT')
                ->join('disciplina d', '(d.id_disciplina = pc.id_disciplina)', 'LEFT')
                ->join('ano_curricular ac', '(ac.id_ano_curricular = pc.id_ano_curricular)', 'LEFT')
                ->join('semestre_lectivo s', '(s.id_semestre_lectivo = pc.id_semestre_lectivo)', 'LEFT')
                ->join('ciclo_letivo cl', '(pc.id_ciclo_letivo = cl.id_ciclo_letivo)', 'LEFT')
                ->join('curso c', '(pc.id_curso = c.id_curso)', 'LEFT')
                ->where('td.id_instituicao', $data['id_instituicao'])
                ->where('d.id_instituicao', $data['id_instituicao'])
                ->where('c.id_instituicao', $data['id_instituicao'])
                ->where('pc.id_instituicao', $data['id_instituicao'])
                ->where('td.deleted_at', null);


            if (!empty($data['id_turma'])) {
                $builder->where('td.id_turma', $data['id_turma']);
            }


            $disciplinas = $builder->orderBy('pc.id_semestre_lectivo', 'ASC')
                ->orderBy('pc.id_ano_curricular', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($disciplinas)) {
                return $this->api_response->set_error(
                    'Nenhuma disciplina encontrada para esta turma!',
                    404
                );
            }

            return $this->api_response->set_success([
                'id_turma'    => $data['id_turma'],
                'total'       => count($disciplinas),
                'disciplinas' => $disciplinas,
            ], 'Disciplinas retornadas com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 4. Listar docentes por disciplina ────────────────────────────────────
    public function docentes_por_disciplina()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se turma_disciplina existe
            $turma_disciplina = $this->turma_disciplina
                ->where('id_turma_disciplina', $data['id_turma_disciplina'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->first();

            if (!$turma_disciplina) {
                return $this->api_response->set_error(
                    'Disciplina não encontrada na turma!',
                    404
                );
            }

            $builder = $this->docencia->builder('docencia dc')
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
                    c.codigo_curso
                ")
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
                ->where('dc.id_turma_disciplina', $data['id_turma_disciplina'])
                ->where('dc.id_instituicao', $data['id_instituicao'])
                ->where('dc.deleted_at', null);


            if (!empty($data['id_professor'])) {
                $builder->where('dc.id_professor', $data['id_professor']);
            }

            $docentes = $builder->orderBy('dc.data_inicio', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($docentes)) {
                return $this->api_response->set_error(
                    'Nenhum docente atribuído a esta disciplina!',
                    404
                );
            }

            return $this->api_response->set_success([
                'id_turma_disciplina' => $data['id_turma_disciplina'],
                'total'               => count($docentes),
                'docentes'            => $docentes,
            ], 'Docentes retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Remover docente da disciplina ────────────────────────────────────────
    public function remover_docente()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $docencia = $this->docencia
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('id_docencia', $data['id_docencia'])
                ->first();

            if (!$docencia) {
                return $this->api_response->set_error('Docência não encontrada!', 404);
            }

            $this->docencia->delete($docencia->id_docencia);

            return $this->api_response->set_success([], 'Docente removido da disciplina com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Remover disciplina da turma ──────────────────────────────────────────
    public function remover_disciplina_turma()
    {
        $this->api_response->validade_request('DELETE');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $turma_disciplina = $this->turma_disciplina
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('id_turma_disciplina', $data['id_turma_disciplina'])
                ->where('deleted_at', null)
                ->first();

            if (!$turma_disciplina) {
                return $this->api_response->set_error('Disciplina não encontrada na turma!', 404);
            }

            // Verifica se tem docentes atribuídos
            $tem_docentes = $this->docencia
                ->where('id_turma_disciplina', $data['id_turma_disciplina'])
                ->countAllResults();

            if ($tem_docentes > 0) {
                return $this->api_response->set_error(
                    'Não é possível remover. Existem docentes atribuídos a esta disciplina!',
                    401
                );
            }

            $this->turma_disciplina->delete($data['id_turma_disciplina']);

            return $this->api_response->set_success([], 'Disciplina removida da turma com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    /**
     * private funcions
     */

    private function _validate_assign_curse()
    {
        return [
            'id_turma' => [
                'label' => 'Turma',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'disciplinas' => [
                'label' => 'Disciplinas',
                'rules' => 'required'
            ],
        ];
    }

    private function _validate_form_teacher(): array
    {
        return [
            'id_professor' => [
                'label' => 'Professor',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_turma_disciplina' => [
                'label' => 'Turma/Disciplina',
                'rules' => 'required|is_natural_no_zero'
            ],
            'data_inicio' => [
                'label' => 'Data de início',
                'rules' => 'required|valid_date[Y-m-d]'
            ],
            'data_fim' => [
                'label' => 'Data de fim',
                'rules' => 'permit_empty|valid_date[Y-m-d]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[255]'
            ],
        ];
    }
}
