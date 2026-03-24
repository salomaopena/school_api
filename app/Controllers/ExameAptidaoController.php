<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
use App\Models\CandidaturaModel;
use App\Models\ExameAptidaoModel;

class ExameAptidaoController extends BaseController
{

    protected ApiResponse $api_response;
    protected CandidaturaModel $candidatura;
    protected ExameAptidaoModel $exame_aptidao;


    public function __construct()
    {
        $this->api_response = new ApiResponse();
        $this->candidatura = model(CandidaturaModel::class);
        $this->exame_aptidao = model(ExameAptidaoModel::class);
    }


    // ─── 1. Registar candidato para exame ─────────────────────────────────────
    public function create()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Busca candidatura e valida estado
            $candidatura = $this->candidatura
                ->where('id_candidatura', $data['id_candidatura'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            if ($candidatura->estado_candidatura !== 'Validada') {
                return $this->api_response->set_error(
                    'Apenas candidaturas validadas podem ser inscritas no exame!',
                    401
                );
            }

            // Verifica se candidato já tem exame no mesmo ano letivo
            if ($this->exame_aptidao->candidato_ja_inscrito($candidatura->id_candidato, $candidatura->id_ano_letivo, $candidatura->id_instituicao)) {
                return $this->api_response->set_error(
                    'Candidato já possui exame inscrito para este ano letivo!',
                    401
                );
            }

            // Transfere dados da candidatura para o exame
            $array_data = [
                'id_candidato'      => $candidatura->id_candidato,
                'id_turma_origem'   => $candidatura->id_turma_origem,
                'id_instituicao'    => $candidatura->id_instituicao,
                'id_ano_letivo'     => $candidatura->id_ano_letivo,
                'id_ano_curricular' => $candidatura->id_ano_curricular,
                'id_periodo_letivo' => $candidatura->id_periodo_letivo,
                'id_curso'          => $candidatura->id_curso,
                'inscricao_data'    => date('Y-m-d'),
                'data_prova'        => null,
                'nota_portugues'    => 0,
                'nota_matematica'   => 0,
                'situacao_exame'    => 'Inscrito',
                'aprovado'          => 0,
            ];

            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);

            $id = $this->exame_aptidao->insert($array_data, true);

            if ($id > 0) {
                return $this->api_response->set_success(
                    ['id_exame_apetidao' => $id],
                    'Candidato inscrito no exame com sucesso!'
                );
            }

            return $this->api_response->set_error('Erro ao inscrever candidato no exame!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 2. Definir data do exame por curso e ano letivo ───────────────────────────────────
    public function definir_data()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate(
            $this->_validate_form_define_exame_date(),
            ['message' => 'Preencha corretamente os campos']
        )) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            // Verifica se existem exames inscritos para este curso/ano/instituição
            $total = $this->exame_aptidao
                ->where('id_curso', $data['id_curso'])
                ->where('id_ano_letivo', $data['id_ano_letivo'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('situacao_exame', 'Inscrito')
                ->where('deleted_at', null)
                ->countAllResults();

            if ($total === 0) {
                return $this->api_response->set_error(
                    'Nenhum candidato inscrito encontrado para este curso e ano letivo!',
                    404
                );
            }

            // Atualiza a data para todos os inscritos do curso/ano/instituição
            db_connect()->table('exames_aptidao')
                ->where('id_curso', $data['id_curso'])
                ->where('id_ano_letivo', $data['id_ano_letivo'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('situacao_exame', 'Inscrito')
                ->where('deleted_at', null)
                ->update([
                    'data_prova'  => $data['data_prova'],
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            return $this->api_response->set_success([
                'total_atualizados' => $total,
            ], "Data do exame definida para {$total} candidato(s) com sucesso!");
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 3. Registar notas individualmente ───────────────────────────────────
    public function registar_notas()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'nota_portugues' => [
                'label' => 'Nota de português',
                'rules' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
            'nota_matematica' => [
                'label' => 'Nota de matemática',
                'rules' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $exame = $this->exame_aptidao
                ->where('id_exame_apetidao', $data['id_exame_apetidao'])
                ->where('deleted_at', null)
                ->first();

            if (!$exame) {
                return $this->api_response->set_error('Exame não encontrado!', 404);
            }

            if ($exame->situacao_exame !== 'Inscrito') {
                return $this->api_response->set_error(
                    'Notas já foram registadas para este exame!',
                    401
                );
            }

            $nota_portugues  = (float) $data['nota_portugues'];
            $nota_matematica = (float) $data['nota_matematica'];
            $classificacao   = round(($nota_portugues + $nota_matematica) / 2, 2);
            $aprovado        = $classificacao >= 10 ? 1 : 0;

            $this->exame_aptidao->update($exame->id_exame_apetidao, [
                'nota_portugues'      => $nota_portugues,
                'nota_matematica'     => $nota_matematica,
                'classificacao_final' => $classificacao,
                'situacao_exame'      => $aprovado ? 'Aprovado' : 'Reprovado',
                'aprovado'            => $aprovado,
                'updated_at'          => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([
                'classificacao_final' => $classificacao,
                'situacao_exame'      => $aprovado ? 'Aprovado' : 'Reprovado',
            ], 'Notas registadas com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── 4. Publicar resultados ───────────────────────────────────────────────
    public function listar()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;

            $builder = $this->exame_aptidao->builder();

            $builder->where('exames_aptidao.deleted_at', null)
                ->where('exames_aptidao.id_instituicao', $data['id_instituicao']);;

            // ─── Filtros ───────────────────────────────────────────────────────
            if (!empty($data['id_ano_letivo'])) {
                $builder->where('exames_aptidao.id_ano_letivo', $data['id_ano_letivo']);
            }

            if (!empty($data['id_curso'])) {
                $builder->where('exames_aptidao.id_curso', $data['id_curso']);
            }


            // ─── 5. Filtro aprovados/reprovados ───────────────────────────────
            if (!empty($data['situacao_exame'])) {
                $builder->where('exames_aptidao.situacao_exame', $data['situacao_exame']);
            }

            if (!empty($data['data_prova'])) {
                $builder->where('exames_aptidao.data_prova', $data['data_prova']);
            }

            $total  = $builder->countAllResults(false);
            $exames = $builder
                ->select('
                              exames_aptidao.id_exame_apetidao,
                              exames_aptidao.id_candidato,
                              exames_aptidao.inscricao_data,
                              exames_aptidao.data_prova,
                              exames_aptidao.nota_portugues,
                              exames_aptidao.nota_matematica,
                              exames_aptidao.classificacao_final,
                              exames_aptidao.situacao_exame,
                              exames_aptidao.aprovado,
                              exames_aptidao.id_curso,
                              exames_aptidao.id_ano_letivo,
                              exames_aptidao.id_instituicao
                          ')
                ->orderBy('exames_aptidao.classificacao_final', 'DESC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $exames,
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

    // ─── Ver exame por ID ─────────────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $exame = $this->exame_aptidao
                ->where('id_exame_apetidao', $data['id_exame_apetidao'])
                ->where('deleted_at', null)
                ->first();

            if (!$exame) {
                return $this->api_response->set_error('Exame não encontrado!', 404);
            }

            return $this->api_response->set_success($exame, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    private function _validate_form_define_exame_date()
    {
        return [
            'id_curso' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_instituicao' => [
                'label' => 'Instituição',
                'rules' => 'required|is_natural_no_zero'
            ],
            'data_prova' => [
                'label' => 'Data da prova',
                'rules' => 'required|valid_date[Y-m-d]'
            ]
        ];
    }
}
