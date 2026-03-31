<?php

namespace App\Models;

use CodeIgniter\Model;

class PagamentoModel extends Model
{
    protected $table            = 'pagamentos';
    protected $primaryKey       = 'id_pagamento';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = false;
    protected $allowedFields    = [];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function calcular_total(
        float $valor_pago,
        float $valor_multa = 0,
        float $desconto    = 0
    ): float {
        return round(($valor_pago + $valor_multa) - $desconto, 2);
    }

    public function historico_por_usuario(int $id_usuario, int $id_instituicao): array
    {
        return $this->db->table('pagamentos p')
            ->select('
                p.id_pagamento,
                p.id_taxa,
                p.id_forma_pag,
                p.valor_pago,
                p.valor_multa,
                p.desconto_pag,
                p.total_pago,
                p.situacao,
                p.data_pagamento,
                p.nome_item,
                p.codigo_transacao,
                p.observacao,
                ip.tipo_pag,
                ip.nome_pag
            ')
            ->join('taxas t',               't.id_taxa      = p.id_taxa',       'left')
            ->join('itens_pagamento ip',    'ip.id_item_pag = t.id_item_pag',   'left')
            ->where('p.id_usuario',         $id_usuario)
            ->where('p.id_instituicao',     $id_instituicao)
            ->where('p.deleted_at',         null)
            ->orderBy('p.data_pagamento',   'DESC')
            ->get()
            ->getResultArray();
    }
}
