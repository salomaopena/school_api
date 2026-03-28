<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
use App\Models\AnoCurricularModel;
use App\Models\CursoModel;
use App\Models\DisciplinaModel;
use App\Models\PlanoCurricularModel;
use App\Models\SemestreLectivoModel;

class PlanoCurricularController extends BaseController
{
    protected ApiResponse           $api_response;
    protected CursoModel            $curso;
    protected DisciplinaModel       $disciplina;
    protected AnoCurricularModel    $ano_curricular;
    protected SemestreLectivoModel  $semestre_lectivo;
    protected PlanoCurricularModel  $plano_curricular;

    public function __construct()
    {
        $this->api_response         = new ApiResponse();
        $this->plano_curricular     = model(PlanoCurricularModel::class);
        $this->ano_curricular       = model(AnoCurricularModel::class);
        $this->semestre_lectivo     = model(SemestreLectivoModel::class);
        $this->curso                = model(CursoModel::class);
        $this->disciplina           = model(DisciplinaModel::class);
    }

    // ─── Criar plano curricular (disciplinas em massa) ─────────────────────────
    public function create()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_validate_create_form(), ['message' => 'Preencha corretamente os campos'])) {
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

                if (empty($item['id_disciplina']) || empty($item['id_semestre_lectivo'])) {
                    $ignorados[] = [
                        'item' => $item,
                        'motivo' => 'Disciplina ou \"Semestre Lectivo\" não informado!'
                    ];
                    continue;
                }

                // Ignora se já existir
                if ($this->plano_curricular->disciplina_ja_existe(
                    $data['id_curso'],
                    $item['id_disciplina'],
                    $data['id_ano_curricular'],
                    $item['id_semestre_lectivo'],
                    $data['id_instituicao']
                )) {
                    $ignorados[] = [
                        'id_disciplina'    => $item['id_disciplina'],
                        'id_semestre_lectivo' => $item['id_semestre_lectivo'],
                        'motivo'           => 'Disciplina já existe no plano!'
                    ];
                    continue;
                }

                $id = $this->plano_curricular->insert([
                    'id_curso'            => $data['id_curso'],
                    'id_disciplina'       => $item['id_disciplina'],
                    'id_ano_curricular'   => $data['id_ano_curricular'],
                    'id_ciclo_letivo'     => $data['id_ciclo_letivo'],
                    'id_instituicao'      => $data['id_instituicao'],
                    'id_semestre_lectivo' => $item['id_semestre_lectivo'],
                ], true);

                if ($id > 0) {
                    $inseridos[] = [
                        'id_plano_curricular' => $id,
                        'id_disciplina'       => $item['id_disciplina'],
                        'id_semestre_lectivo' => $item['id_semestre_lectivo'],
                    ];
                }
            }

            return $this->api_response->set_success([
                'inseridos'        => $inseridos,
                'ignorados'        => $ignorados,
                'total_inseridos'  => count($inseridos),
                'total_ignorados'  => count($ignorados),
            ], count($inseridos) . ' disciplina(s) adicionada(s) ao plano curricular!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Consultar plano curricular por curso ──────────────────────────────────
    public function consultar()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $builder = $this->plano_curricular->builder('plano_curricular pc');

            $builder->select('
                    pc.id_plano_curricular,
    
                    pc.id_curso,
                    c.nome_curso,
                    c.codigo_curso,
                    c.codigo_ministerio_educacao,
                    
                    pc.id_disciplina,
                    d.nome_disciplina,
                    d.abreviatura,
                    d.carga_horaria_semanal,
                    d.carga_horaria_total,
                    
                    pc.id_ano_curricular,
                    ac.ano_curricular,
                    
                    pc.id_ciclo_letivo,
                    cl.ciclo_letivo,
                    
                    pc.id_semestre_lectivo,
                    s.semestre_lectivo,
                    
                    pc.id_instituicao
                ')
                ->join('curso c', '(c.id_curso = pc.id_curso)')
                ->join('disciplina d', '(d.id_disciplina = pc.id_disciplina)')
                ->join('ano_curricular ac', '(ac.id_ano_curricular = pc.id_ano_curricular)')
                ->join('ciclo_letivo cl', '(cl.id_ciclo_letivo = pc.id_ciclo_letivo)')
                ->join('semestre_lectivo s', '(s.id_semestre_lectivo = pc.id_semestre_lectivo)')
                ->where('pc.id_instituicao', $data['id_instituicao']);


            // ─── Filtros ───────────────────────────────────────────────────────

            // Filtro: Classe (ano curricular)
            if (!empty($data['id_ciclo_letivo'])) {
                $builder->where('pc.id_ciclo_letivo', $data['id_ciclo_letivo']);
            }

            // Filtro: Curso — via join com aluno
            if (!empty($data['id_curso'])) {
                $builder->where('pc.id_curso', $data['id_curso']);
            }

            $plano = $builder->orderBy('pc.id_ano_curricular', 'ASC')
                ->orderBy('pc.id_semestre_lectivo', 'ASC')
                ->get()
                ->getResultArray();


            if (empty($plano)) {
                return $this->api_response->set_error(
                    'Nenhum plano curricular encontrado para este curso!',
                    404
                );
            }

            // Agrupa por ano curricular e semestre
            $agrupado = [];

            foreach ($plano as $item) {
                $ano      = $item['ano_curricular'];
                $semestre = $item['semestre_lectivo'];

                $agrupado[$ano][$semestre][] = [
                    'id_plano_curricular'   => $item['id_plano_curricular'],
                    'nome_curso'            => $item['nome_curso'],
                    'nome_disciplina'       => $item['nome_disciplina'],
                    'abreviatura'           => $item['abreviatura'],
                    'carga_horaria_semanal' => $item['carga_horaria_semanal'],
                    'carga_horaria_total'   => $item['carga_horaria_total'],
                    'ciclo_letivo'          => $item['ciclo_letivo'],
                    'semestre_lectivo'      => $item['semestre_lectivo'],
                    'id_instituicao'        => $item['id_instituicao'],
                ];
            }

            return $this->api_response->set_success([
                'id_curso'      => $data['id_curso'],
                'id_instituicao' => $data['id_instituicao'],
                'total'         => count($plano),
                'plano'         => $agrupado,
            ], 'Plano curricular retornado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Atualizar disciplina do plano curricular ──────────────────────────────
    public function update()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_validate_update_form(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $plano = $this->plano_curricular
                ->where('id_plano_curricular', $data['id_plano_curricular'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$plano) {
                return $this->api_response->set_error('Plano curricular não encontrado!', 404);
            }

            // Verifica se a nova disciplina já existe no mesmo contexto
            if ($this->plano_curricular->disciplina_ja_existe(
                $plano->id_curso,
                $data['id_disciplina'],
                $data['id_ano_curricular'],
                $data['id_semestre_lectivo'],
                $plano->id_instituicao
            )) {
                return $this->api_response->set_error(
                    'Esta disciplina já existe no plano para este ano e semestre!',
                    401
                );
            }


            $this->plano_curricular->update($plano->id_plano_curricular, [
                'id_disciplina'       => $data['id_disciplina'],
                'id_semestre_lectivo' => $data['id_semestre_lectivo'],
                'id_ano_curricular'   => $data['id_ano_curricular'],
                'id_ciclo_letivo'     => $data['id_ciclo_letivo'],
            ]);

            return $this->api_response->set_success([], 'Disciplina atualizada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Remover disciplina do plano curricular ────────────────────────────────
    public function delete()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $plano = $this->plano_curricular
                ->where('id_plano_curricular', $data['id_plano_curricular'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$plano) {
                return $this->api_response->set_error('Disciplina não encontrada no plano curricular!', 404);
            }

            $this->plano_curricular->update(
                $plano->id_plano_curricular,
                [
                    'updated_at' => date('Y-m-d H:i:s'),
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]
            );

            return $this->api_response->set_success([], 'Disciplina removida do plano curricular com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // validate forms
    private function _validate_create_form(): array
    {
        return [
            'id_curso' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_curricular' => [
                'label' => 'Ano curricular',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ciclo_letivo' => [
                'label' => 'Ciclo letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'disciplinas' => [
                'label' => 'Disciplinas',
                'rules' => 'required'
            ],
        ];
    }

    // validate update form
    private function _validate_update_form(): array
    {
        return [
            'id_disciplina' => [
                'label' => 'Disciplina',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_semestre_lectivo' => [
                'label' => 'Semestre',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_curricular' => [
                'label' => 'Ano curricular',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ciclo_letivo' => [
                'label' => 'Ciclo letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
        ];
    }
}
