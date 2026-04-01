<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ApiResponse;
use App\Models\PagamentoModel;
use App\Models\PedidoModel;
use App\Services\MultaService;
use CodeIgniter\Database\BaseConnection;

class PagamentoController extends BaseController
{
    protected PagamentoModel    $pagamento;
    protected ApiResponse       $api_response;
    protected BaseConnection    $db;
    protected PedidoModel       $pedido;
    protected MultaService      $multa_service;

    public function __construct()
    {
        $this->pagamento = model(PagamentoModel::class);
        $this->pedido = model(PedidoModel::class);
        $this->api_response =  new ApiResponse();
        $this->db = \Config\Database::connect();
        $this->multa_service = new MultaService();
    }


    // ─── 1. Checkout — recebe carrinho e gera pedido ──────────────────────────
    public function checkout()
    {
        $this->api_response->validade_request('POST');

        if (!$this->validate($this->_validate_checkout_form(), ['message' => 'Preencha corretamente os campos'])) {
            return $this->api_response->set_validation_errors($this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            if (empty($data['itens']) || !is_array($data['itens'])) {
                return $this->api_response->set_error('Carrinho vazio!', 401);
            }

            // Pré-valida todos os itens antes de inserir qualquer coisa
            $itens_validados = [];

            foreach ($data['itens'] as $item) {

                if (empty($item['id_taxa'])) {
                    return $this->api_response->set_error(
                        'Todos os itens devem ter id_taxa!',
                        401
                    );
                }

                $taxa = $this->db->table('taxas t')
                    ->select('t.*, ip.nome_pag, ip.tipo_pag')
                    ->join('itens_pagamento ip', 'ip.id_item_pag = t.id_item_pag', 'LEFT')
                    ->where('t.id_taxa',    $item['id_taxa'])
                    ->where('t.id_instituicao', $data['id_instituicao'])
                    ->where('t.is_ativo',   1)
                    ->where('t.deleted_at', null)
                    ->get()
                    ->getRowArray();

                if (!$taxa) {
                    return $this->api_response->set_error(
                        "Taxa {$item['id_taxa']} não encontrada ou inativa!",
                        401
                    );
                }

                $valor_pago  = (float) ($taxa['valor']);
                $desconto    = (float) ($data['desconto_pag'] ?? 0);


                // Calcula multa automaticamente se mensalidade
                $valor_multa = 0;

                if ($taxa['tipo_pag'] === 'Mensalidade' && $taxa['multa_ativa']) {
                    $calculo     = $this->multa_service->calcular(
                        valor: $valor_pago,
                        dia_vencimento: (int)   $taxa['dia_vencimento'],
                        id_mes: (int)   $taxa['id_mes'],
                        ano: (int)   ($data['ano'] ?? date('Y')),
                        percentual_diario: (float) $taxa['percentual_multa'],
                        multa_ativa: (bool)  $taxa['multa_ativa']
                    );
                    $valor_multa = $calculo['valor_multa'];
                } else {
                    $valor_multa = (float) ($item['valor_multa'] ?? 0);
                }

                $total_item = round(($valor_pago + $valor_multa) - $desconto, 2);



                $itens_validados[] = [
                    'taxa'        => $taxa,
                    'valor_pago'  => $valor_pago,
                    'valor_multa' => $valor_multa,
                    'desconto'    => $desconto,
                    'total_item'  => $total_item,
                    'observacao'  => $item['observacao'] ?? null,
                ];
            }

            // Calcula totais do pedido
            $subtotal       = array_sum(array_column($itens_validados, 'valor_pago'));
            $total_multa    = array_sum(array_column($itens_validados, 'valor_multa'));
            $total_desconto = array_sum(array_column($itens_validados, 'desconto'));
            $total_geral    = array_sum(array_column($itens_validados, 'total_item'));

            // Inicia transação no banco
            $this->db->transException(true)->transStart();

            // Cria o pedido
            $uuid_pedido = bin2hex(random_bytes(16));

            $id_pedido = $this->pedido->insert([
                'uuid_pedido'      => $uuid_pedido,
                'id_usuario'       => $data['id_usuario'],
                'id_funcionario'   => $data['id_funcionario'],
                'id_forma_pag'     => $data['id_forma_pag'],
                'id_instituicao'   => $data['id_instituicao'],
                'total_itens'      => count($itens_validados),
                'subtotal'         => round($subtotal,       2),
                'total_multa'      => round($total_multa,    2),
                'total_desconto'   => round($total_desconto, 2),
                'total_geral'      => round($total_geral,    2),
                'situacao'         => 'Pago',
                'codigo_transacao' => $data['codigo_transacao'] ?? null,
                'data_pedido'      => date('Y-m-d H:i:s'),
                'observacao'       => $data['observacao']        ?? null,
            ], true);

            // Insere os itens do pagamento
            $itens_inseridos = [];

            foreach ($itens_validados as $item) {

                $id_pag = $this->pagamento->insert([
                    'id_pedido'    => $id_pedido,
                    'id_taxa'      => $item['taxa']['id_taxa'],
                    'valor_pago'   => $item['valor_pago'],
                    'valor_multa'  => $item['valor_multa'],
                    'desconto_pag' => $item['desconto'],
                    'total_pago'   => $item['total_item'],
                    'nome_item'    => $item['taxa']['nome_pag'],
                ], true);

                $itens_inseridos[] = [
                    'id_pagamento' => $id_pag,
                    'id_taxa'      => $item['taxa']['id_taxa'],
                    'nome_item'    => $item['taxa']['nome_pag'],
                    'tipo_pag'     => $item['taxa']['tipo_pag'],
                    'valor_pago'   => $item['valor_pago'],
                    'valor_multa'  => $item['valor_multa'],
                    'desconto'     => $item['desconto'],
                    'total_item'   => $item['total_item'],
                ];
            }

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                return $this->api_response->set_error('Erro ao processar o pedido!', 500);
            }

            return $this->api_response->set_success([
                'id_pedido'     => $id_pedido,
                'uuid_pedido'   => $uuid_pedido,
                'itens'         => $itens_inseridos,
                'total_itens'   => count($itens_inseridos),
                'subtotal'      => round($subtotal,       2),
                'total_multa'   => round($total_multa,    2),
                'total_desconto' => round($total_desconto, 2),
                'total_geral'   => round($total_geral,    2),
                'situacao'      => 'Pendente',
            ], 'Pedido gerado com sucesso!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->api_response->set_error($e->getMessage(), 500);
        }
    }


    // ─── 3. Consultar pedido ──────────────────────────────────────────────────
    public function show()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $id_pedido = (int) $data['id_pedido'];
            $id_instituicao = (int) $data['id_instituicao'];

            $builder = $this->pedido->builder('pedidos p')
                ->select("
                p.id_pedido,
                p.uuid_pedido,
                CONCAT(us.nome_usuario,' ',us.sobrenome_usuario) AS utente,
                COALESCE(us.telefone_fixo,' ',us.telefone_movel) AS telefone,
                us.numero_doc,
                a.numero_aluno,
                CONCAT(uf.nome_usuario,' ',uf.sobrenome_usuario) AS funcionario,
                p.total_itens,
                p.subtotal,
                p.total_multa,
                p.total_desconto,
                p.total_geral,
                p.situacao,
                fp.descricao_forma_pag,
                p.codigo_transacao,
                p.data_pedido,
                p.observacao,
                c.nome_curso,
                p.created_at,
                p.updated_at
                ")
                ->join('usuario us', 'p.id_usuario = us.id_usuario', 'LEFT')
                ->join('funcionario fc', 'fc.id_funcionario = p.id_funcionario', 'LEFT')
                ->join('usuario uf', 'uf.id_usuario = p.id_funcionario', 'LEFT')
                ->join('forma_pagamento fp', 'fp.id_forma_pag = p.id_forma_pag', 'LEFT')
                ->join('aluno a', 'a.id_aluno = us.id_usuario', 'LEFT')
                ->join('curso c', 'a.id_curso = c.id_curso', 'LEFT')
                ->where('p.id_instituicao', $id_instituicao)
                ->where('p.id_pedido', $id_pedido);

            // Filtros
            if (!empty($data['telefone'])) {
                $builder->where('us.telefone_fixo', $data['telefone'])
                    ->orWhere('us.telefone_movel', $data['telefone']);
            }

            if (!empty($data['numero_doc'])) {
                $builder->where('us.numero_doc', $data['numero_doc']);
            }

            if (!empty($data['numero_aluno'])) {
                $builder->where('a.numero_aluno', $data['numero_aluno']);
            }

            $pedido = $builder->get()
                ->getFirstRow();

            if (!$pedido) {
                return $this->api_response->set_error('Pedido não encontrado!', 404);
            }

            $itens = $this->pagamento->itens_do_pedido($id_pedido, $id_instituicao);

            return $this->api_response->set_success([
                'pedido' => $pedido,
                'itens'  => $itens,
            ], 'Pedido retornado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }

    // ─── 4. Histórico de pedidos do usuário ───────────────────────────────────
    public function historico()
    {
        $this->api_response->validade_request('POST');

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            if (empty($data['id_instituicao'])) {
                return $this->api_response->set_error('Instituição não informada!', 401);
            }

            $page     = $data['page']     ?? 1;
            $per_page = $data['per_page'] ?? 15;
            $offset   = ($page - 1) * $per_page;


            $id_usuario = (int) $data['id_usuario'];
            $id_instituicao = (int) $data['id_instituicao'];

            $builder = $this->pedido->builder('pedidos p')
                ->select("
                    p.id_pedido,
                    p.uuid_pedido,
                    CONCAT(us.nome_usuario,' ',us.sobrenome_usuario) AS utente,
                    COALESCE(us.telefone_fixo,' ',us.telefone_movel) AS telefone,
                    us.numero_doc,
                    a.numero_aluno,
                    CONCAT(uf.nome_usuario,' ',uf.sobrenome_usuario) AS funcionario,
                    p.total_itens,
                    p.subtotal,
                    p.total_multa,
                    p.total_desconto,
                    p.total_geral,
                    p.situacao,
                    fp.descricao_forma_pag,
                    p.codigo_transacao,
                    p.data_pedido,
                    p.observacao,
                    c.nome_curso,
                    p.created_at,
                    p.updated_at
                    ")
                ->join('usuario us', 'p.id_usuario = us.id_usuario', 'LEFT')
                ->join('funcionario fc', 'fc.id_funcionario = p.id_funcionario', 'LEFT')
                ->join('usuario uf', 'uf.id_usuario = p.id_funcionario', 'LEFT')
                ->join('forma_pagamento fp', 'fp.id_forma_pag = p.id_forma_pag', 'LEFT')
                ->join('aluno a', 'a.id_aluno = us.id_usuario', 'LEFT')
                ->join('curso c', 'a.id_curso = c.id_curso', 'LEFT')
                ->where('p.id_instituicao', $id_instituicao)
                ->where('p.id_usuario', $id_usuario);

            if (!empty($data['situacao'])) {
                $builder->where('situacao', $data['situacao']);
            }

            if (!empty($data['data_inicio']) && !empty($data['data_fim'])) {
                $builder->where('data_pedido >=', $data['data_inicio'])
                    ->where('data_pedido <=',  $data['data_fim']);
            }

            if (!empty($data['numero_doc'])) {
                $builder->where('us.numero_doc', $data['numero_doc']);
            }

            if (!empty($data['numero_aluno'])) {
                $builder->where('a.numero_aluno', $data['numero_aluno']);
            }

            $total   = $builder->countAllResults(false);
            $pedidos = $builder
                ->orderBy('data_pedido', 'DESC')
                ->limit($per_page, $offset)
                ->get()
                ->getResultArray();

            return $this->api_response->set_success([
                'data'       => $pedidos,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $per_page,
                    'current_page' => (int) $page,
                    'last_page'    => (int) ceil($total / $per_page),
                    'from'         => $offset + 1,
                    'to'           => min($offset + $per_page, $total),
                ],
            ], 'Histórico retornado com sucesso!');
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // ─── 6. Comprovante ───────────────────────────────────────────────────────
    public function comprovante()
    {
        $this->api_response->validade_request('POST');
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        try {

            $id_pedido = (int) $data['id_pedido'];
            $id_instituicao = (int) $data['id_instituicao'];

            $pedido = $this->pedido
                ->where('id_instituicao', $id_instituicao)
                ->where('id_pedido', $id_pedido)
                ->first();

            if (!$pedido) {
                return $this->api_response->set_error('Pedido não encontrado!', 404);
            }

            if ($pedido['situacao'] !== 'Pago') {
                return $this->api_response->set_error(
                    'Comprovante disponível apenas para pedidos pagos!',
                    401
                );
            }

            $itens = $this->pagamento->itens_do_pedido($id_pedido, $id_instituicao);

            $comprovante = [
                'cabecalho' => [
                    'titulo'           => 'Comprovante de Pagamento',
                    'id_instituicao'   => $pedido['id_instituicao'],
                    'data_emissao'     => date('Y-m-d H:i:s'),
                    'numero_documento' => 'COMP-' . str_pad($id_pedido, 8, '0', STR_PAD_LEFT),
                ],
                'pedido' => [
                    'id_pedido'        => $pedido['id_pedido'],
                    'uuid_pedido'      => $pedido['uuid_pedido'],
                    'codigo_transacao' => $pedido['codigo_transacao'],
                    'data_pedido'      => $pedido['data_pedido'],
                    'situacao'         => $pedido['situacao'],
                    'id_forma_pag'     => $pedido['id_forma_pag'],
                    'observacao'       => $pedido['observacao'],
                ],
                'itens' => array_map(fn($i) => [
                    'nome_item'   => $i['nome_item'],
                    'tipo_pag'    => $i['tipo_pag'],
                    'valor_pago'  => $i['valor_pago'],
                    'valor_multa' => $i['valor_multa'],
                    'desconto'    => $i['desconto_pag'],
                    'total_item'  => $i['total_pago'],
                ], $itens),
                'totais' => [
                    'total_itens'    => $pedido['total_itens'],
                    'subtotal'       => $pedido['subtotal'],
                    'total_multa'    => $pedido['total_multa'],
                    'total_desconto' => $pedido['total_desconto'],
                    'total_geral'    => $pedido['total_geral'],
                ],
                'usuario' => [
                    'id_usuario'     => $pedido['id_usuario'],
                    'id_funcionario' => $pedido['id_funcionario'],
                ],
            ];

            return $this->api_response->set_success(
                $comprovante,
                'Comprovante emitido com sucesso!'
            );
        } catch (\Exception $e) {
            return $this->api_response->set_error($e->getMessage(), 401);
        }
    }


    // validations forms

    private function _validate_checkout_form()
    {
        return [
            'id_usuario' => [
                'label' => 'Usuário',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_funcionario' => [
                'label' => 'Funcionário',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_instituicao' => [
                'label' => 'Instituição',
                'rules' => 'required|is_natural_no_zero'
            ],
            'id_forma_pag' => [
                'label' => 'Forma de pagamento',
                'rules' => 'required|is_natural_no_zero'
            ],
            'itens' => [
                'label' => 'Itens',
                'rules' => 'required'
            ],
        ];
    }
}
