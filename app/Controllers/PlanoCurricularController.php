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

            if (empty($data['id_isciplina']) || !is_array($data['id_isciplina'])) {
                return $this->api_response->set_error('Lista de disciplinas não informada!', 401);
            }

            $inseridos = [];
            $ignorados = [];

            foreach ($data['id_isciplina'] as $item) {

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

    private function _validate_create_form():array{
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
}
