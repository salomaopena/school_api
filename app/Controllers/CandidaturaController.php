<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
use App\Models\CandidaturaModel;
use CodeIgniter\HTTP\ResponseInterface;

use function PHPUnit\Framework\isNull;

class CandidaturaController extends BaseController
{

    protected ApiResponse $api_response;
    protected CandidaturaModel $candidatura;

    public function __construct()
    {
        $this->api_response = new ApiResponse();
        $this->candidatura = model(CandidaturaModel::class);
    }


    // ─── Criar candidatura ────────────────────────────────────────────────────
    public function create()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_form_validate_create(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        // Detecta tipo de conteúdo
        $contentType = $this->request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = $this->request->getJSON(true) ?? [];
            $files = [];
        } else {
            $data = $this->request->getPost();
            $files = $this->request->getFiles();
        }

        try {

            // Verificar se o candidato já existe
            $is_inscrito = $this->candidatura
                ->where('id_candidato',     $data['id_candidato'])
                ->where('id_instituicao',   $data['id_instituicao'])
                ->where('id_curso',         $data['id_curso'])
                ->where('id_turma',         $data['id_turma'])
                ->where('id_periodo_letivo', $data['id_periodo_letivo'])
                ->where('id_ano_curricular', $data['id_ano_curricular'])
                ->where('id_ciclo_letivo',  $data['id_ciclo_letivo'])
                ->first();

            if ($is_inscrito) {
                return $this->api_response->set_error('Candidato já inscrito. Solicite alteração de dados.', 409);
            }

            // Upload dos ficheiros
            $documentos = $this->_upload_documentosFomFiles($files);

            if (isset($documentos['error'])) {
                return $this->api_response->set_error(
                    $documentos['error'],
                    $documentos['code'] ?? 400
                );
            }


            $array_data = [
                'id_candidato'        => $data['id_candidato'],
                'numero_candidatura'  => $this->candidatura->gerar_numero_candidatura($data['id_instituicao']),
                'data_candidatura'    => date('Y-m-d'),
                'id_instituicao'      => $data['id_instituicao'],
                'id_curso'            => $data['id_curso'],
                'id_ano_letivo'       => $data['id_ano_letivo'],
                'estado_candidatura'  => 'Pendente',
                'id_turma'            => $data['id_turma'],
                'id_turma_origem'     => $data['id_turma_origem'],
                'id_ciclo_letivo'     => $data['id_ciclo_letivo'],
                'id_ano_curricular'   => $data['id_ano_curricular'],
                'id_periodo_letivo'   => $data['id_periodo_letivo'],
                'nota_portugues'      => $data['nota_portugues']        ?? null,
                'nota_matematica'     => $data['nota_matematica']       ?? null,
                'classificacao_final' => $data['classificacao_final']   ?? null,
                'lingua_opcao'        => $data['lingua_opcao'],
                'observacao'          => $data['observacao']            ?? null,
                'id_funcionario'      => $data['id_funcionario'],
                'candidatura_aberta'  => 1,
                'documento_file'      => $documentos['documento_file']  ?? null,
                'documento_file1'     => $documentos['documento_file1'] ?? null,
                'documento_file3'     => $documentos['documento_file3'] ?? null,
                'created_at'          => date('Y-m-d H:i:s'),
            ];

            // Esta linha transforma qualquer string vazia em NULL real
            $array_data = array_map(function ($value) {
                return ($value === '') ? null : $value;
            }, $array_data);


            $id = $this->candidatura->insert($array_data, true);

            if ($id > 0) {
                // adicionar dados no histórico
                db_connect()
                    ->table('historico_candidatura')
                    ->insert([
                        'id_candidatura'        => $id,
                        'id_candidato'          => $array_data['id_candidato'],
                        'estado_candidatura'    => $array_data['estado_candidatura'],
                        'id_funcionario'        => $array_data['id_funcionario'] ?? null,
                        'created_at'            => date('Y-m-d H:i:s')
                    ]);

                return $this->api_response->set_success(
                    [
                        'id_candidatura'        => $id,
                        'numero_candidatura'    => $array_data['numero_candidatura']
                    ],
                    'Candidatura submetida com sucesso!'
                );
            }

            return $this->api_response->set_error('Erro ao submeter candidatura!', 401);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Listar candidaturas ──────────────────────────────────────────────────
    public function list()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;

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

            // Filtros
            if (!empty($data['numero_candidatura'])) {
                $builder->like('c.numero_candidatura', $data['numero_candidatura']);
            }

            if (!empty($data['estado_candidatura'])) {
                $builder->where('c.estado_candidatura', $data['estado_candidatura']);
            }

            if (!empty($data['id_curso'])) {
                $builder->where('c.id_curso', $data['id_curso']);
            }

            if (!empty($data['id_ano_letivo'])) {
                $builder->where('c.id_ano_letivo', $data['id_ano_letivo']);
            }

            if (!empty($data['id_periodo_letivo'])) {
                $builder->where('c.id_periodo_letivo', $data['id_periodo_letivo']);
            }

            if (!empty($data['id_turma'])) {
                $builder->where('c.id_turma', $data['id_turma']);
            }


            $total        = $builder->countAllResults(false);
            $candidaturas = $builder
                ->where('c.id_instituicao', $data['id_instituicao'])
                ->where('c.deleted_at', null)
                ->groupBy('id_candidato, id_curso, id_periodo_letivo')
                ->orderBy('c.created_at', 'DESC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $candidaturas,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($total / $per_page),
                    'from'         => $offset + 1,
                    'to'           => min($offset + $per_page, $total),
                ]
            ], 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Ver candidatura por ID ───────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {
            $candidatura = $this->candidatura->show_candidatura_info(
                $data['id_instituicao'],
                $data['id_candidatura']
            );

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            return $this->api_response->set_success($candidatura, 'Dados retornados com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Atualizar estado ─────────────────────────────────────────────────────
    public function update_status()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate([
            'estado_candidatura' => [
                'label' => 'Estado',
                'rules' => 'required|in_list[Pendente,Paga,Validada,Rejeitada]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[200]'
            ]
        ], ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $candidatura = $this->candidatura
                ->where('id_candidatura', $data['id_candidatura'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            $array_data = [
                'estado_candidatura' => $data['estado_candidatura'],
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            // Aprova automaticamente se estado for Validada
            if ($data['estado_candidatura'] === 'Validada') {
                $array_data['candidatura_aberta'] = 1;
                $array_data['aprovado'] = 1;
            }

            // Fecha candidatura se Rejeitada
            if ($data['estado_candidatura'] === 'Rejeitada') {
                $array_data['candidatura_aberta'] = 0;
                $array_data['aprovado']           = 0;
            }

            if (!empty($data['observacao'])) {
                $array_data['observacao'] = $data['observacao'];
            }

            $this->candidatura->update($candidatura->id_candidatura, $array_data);

            return $this->api_response->set_success([], 'Estado atualizado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── Cancelar/Deletar candidatura ─────────────────────────────────────────
    public function delete()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $candidatura = $this->candidatura
                ->where('id_candidatura', $data['id_candidatura'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            if ($candidatura['estado_candidatura'] == 'Validada') {
                return $this->api_response->set_error('Candidatura validada não pode ser cancelada!', 401);
            }

            $this->candidatura->delete($candidatura->id_candidatura);

            return $this->api_response->set_success([], 'Candidatura cancelada com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // submenter comprovante de pagamento
    public function submit_proof()
    {
        $this->api_response->validade_request('POST');

        // Detecta tipo de conteúdo
        $contentType = $this->request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = $this->request->getJSON(true) ?? [];
        } else {
            $data = $this->request->getPost();
        }

        try {

            $candidatura = $this->candidatura
                ->where('id_candidatura', $data['id_candidatura'])
                ->whereNotIn('estado_candidatura', ['Rejeitada'])
                ->where('deleted_at', null)
                ->first();

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            if ($candidatura->estado_candidatura !== 'Pendente') {
                return $this->api_response->set_error(
                    'Apenas candidaturas pendentes podem submeter comprovativo!',
                    401
                );
            }

            $ficheiro = $this->request->getFile('documento_file3');

            if (!$ficheiro || !$ficheiro->isValid()) {
                return $this->api_response->set_error('Comprovativo não enviado!', 401);
            }

            if ($ficheiro->getClientMimeType() !== 'application/pdf') {
                return $this->api_response->set_error('O comprovativo deve ser um PDF!', 401);
            }

            if ($ficheiro->getSize() > 5 * 1024 * 1024) {
                return $this->api_response->set_error('O ficheiro não pode ultrapassar 5MB!', 401);
            }

            $caminho = WRITEPATH . 'uploads/candidaturas/comprovativos/';

            if (!is_dir($caminho)) {
                mkdir($caminho, 0755, true);
            }

            // Remove comprovativo anterior se existir
            if (!empty($candidatura->documento_file3)) {
                $ficheiro_antigo = WRITEPATH . $candidatura->documento_file3;
                if (file_exists($ficheiro_antigo)) {
                    unlink($ficheiro_antigo);
                }
            }

            $nome = $ficheiro->getRandomName();
            $ficheiro->move($caminho, $nome);

            $this->candidatura->update($candidatura->id_candidatura, [
                'documento_file3'    => 'uploads/candidaturas/comprovativos/' . $nome,
                'estado_candidatura' => 'Paga',
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            return $this->api_response->set_success([], 'Comprovativo submetido com sucesso! Aguarde a validação.');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    public function validate_payment()
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

            $candidatura = $this->candidatura
                ->where('id_candidatura', $data['id_candidatura'])
                ->where('id_instituicao', $data['id_instituicao'])
                ->where('deleted_at', null)
                ->first();

            if (!$candidatura) {
                return $this->api_response->set_error('Candidatura não encontrada!', 404);
            }

            if ($candidatura->estado_candidatura !== 'Paga') {
                return $this->api_response->set_error(
                    'Apenas candidaturas com comprovativo submetido podem ser validadas!',
                    401
                );
            }

            if (empty($candidatura->documento_file3)) {
                return $this->api_response->set_error(
                    'Candidatura não possui comprovativo anexado!',
                    401
                );
            }

            $aprovado = (int) $data['aprovado'];

            $array_data = [
                'aprovado'           => $aprovado,
                'estado_candidatura' => $aprovado === 1 ? 'Validada' : 'Rejeitada',
                'candidatura_aberta' => $aprovado === 1 ? 1 : 0,
                'observacao'         => $data['observacao'] ?? $candidatura['observacao'],
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            $this->candidatura->update($candidatura->id_candidatura, $array_data);

            $mensagem = $aprovado === 1
                ? 'Pagamento validado! Candidatura aprovada com sucesso.'
                : 'Pagamento rejeitado! Candidatura marcada como rejeitada.';


            $email_info = $this->candidatura->show_candidatura_info(
                $candidatura->id_candidatura,
                $candidatura->id_candidatura
            );
            

            $nome_usuario = $email_info['nome_candidato'] ;

            $email_service = new \App\Services\EmailService();
            // TODO: descomentar as linas a baixo só após a configuração dos serviços de emails em: EmailService
            // $enviado = $email_service->send_mail_info($email_info['email'], $nome_usuario, $mensagem);

            // if (!$enviado) {
            //     return $this->api_response->set_error('Erro ao enviar o email! Tente novamente.', 500);
            // }

            return $this->api_response->set_success([], $mensagem);
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── Upload de documentos ─────────────────────────────────────────────────
    private function _upload_documentosFomFiles(array $files): array
    {
        $campos    = ['documento_file', 'documento_file1', 'documento_file3'];
        $resultado = [];
        $caminho   = WRITEPATH . 'uploads/candidaturas/';

        if (!is_dir($caminho)) {
            mkdir($caminho, 0755, true);
        }

        // inicializa todas as chaves como null
        $resultado = array_fill_keys($campos, null);

        foreach ($campos as $campo) {

            if (!isset($files[$campo])) {
                continue;
            }

            $ficheiro = $files[$campo];

            if (!$ficheiro->isValid()) {
                continue;
            }

            if ($ficheiro->getClientMimeType() !== 'application/pdf') {
                return ['error' => "O ficheiro {$campo} deve ser um PDF!", 'code' => 422];
            }

            if ($ficheiro->getSize() > 5 * 1024 * 1024) {
                return ['error' => "O ficheiro {$campo} não pode ultrapassar 5MB!", 'code' => 422];
            }


            try {
                $nome = $ficheiro->getRandomName();
                $ficheiro->move($caminho, $nome);

                $resultado[$campo] = 'uploads/candidaturas/' . $nome;
            } catch (\Exception $e) {
                return [
                    'error' => "Erro ao salvar o ficheiro {$campo}",
                    'code'  => 500
                ];
            }
        }

        return $resultado;
    }

    // ─── Validações ───────────────────────────────────────────────────────────
    private function _form_validate_create(): array
    {
        return [
            'id_candidato' => [
                'label' => 'Candidato',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_instituicao' => [
                'label' => 'Instituição',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_curso' => [
                'label' => 'Curso',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_ano_letivo' => [
                'label' => 'Ano letivo',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_turma' => [
                'label' => 'Turma',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_turma_origem' => [
                'label' => 'Turma de origem',
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
            'id_funcionario' => [
                'label' => 'Funcionário',
                'rules' => 'permit_empty|is_natural_no_zero'
            ],
            'lingua_opcao' => [
                'label' => 'Língua de opção',
                'rules' => 'required|in_list[Inglês,Francês]'
            ],
            'nota_portugues' => [
                'label' => 'Nota de português',
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
            'nota_matematica' => [
                'label' => 'Nota de matemática',
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
            'classificacao_final' => [
                'label' => 'Classificação final',
                'rules' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[20]'
            ],
            'observacao' => [
                'label' => 'Observação',
                'rules' => 'permit_empty|max_length[200]'
            ],
            'documento_file' => [
                'label' => 'Bilhete de Identidade',
                'rules' => 'permit_empty'
            ],
            'documento_file1' => [
                'label' => 'Certificado',
                'rules' => 'permit_empty'
            ],
            'documento_file3' => [
                'label' => 'Comprovante',
                'rules' => 'permit_empty'
            ],
        ];
    }
}
