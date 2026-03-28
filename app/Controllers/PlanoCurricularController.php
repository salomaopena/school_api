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


    public function index()
    {
        //
    }
}
