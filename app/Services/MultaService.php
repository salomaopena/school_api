<?php

namespace App\Services;

class MultaService
{
    /**
     * Calcula multa por atraso com juros compostos
     *
     * Fórmula: M = P × (1 + i)^n  — onde:
     * P = valor original
     * i = percentual diário / 100
     * n = dias de atraso
     * Multa = M - P
     */
    public function calcular(
        float  $valor,
        int    $dia_vencimento,
        int    $id_mes,
        int    $ano,
        float  $percentual_diario,
        bool   $multa_ativa = true
    ): array {

        // Sem multa se desativada
        if (!$multa_ativa) {
            return $this->_resultado(0, 0, 0, $valor, 'Multa desativada para esta taxa.');
        }

        // Monta data de vencimento
        $data_vencimento = \DateTime::createFromFormat(
            'Y-n-j',
            "{$ano}-{$id_mes}-{$dia_vencimento}"
        );

        if (!$data_vencimento) {
            return $this->_resultado(0, 0, 0, $valor, 'Data de vencimento inválida.');
        }

        $hoje         = new \DateTime(date('Y-m-d'));
        $dias_atraso  = (int) $hoje->diff($data_vencimento)->days;
        $esta_atrasado = $hoje > $data_vencimento;

        // Sem multa se não está atrasado
        if (!$esta_atrasado || $dias_atraso === 0) {
            return $this->_resultado(0, 0, $dias_atraso, $valor, 'Dentro do prazo.');
        }

        // Juros compostos: M = P × (1 + i)^n
        $taxa   = $percentual_diario / 100;
        $montante = $valor * pow(1 + $taxa, $dias_atraso);
        $multa    = round($montante - $valor, 2);
        $total    = round($montante, 2);

        return $this->_resultado($multa, $dias_atraso, $dias_atraso, $total, 'Em atraso.');
    }

    /**
     * Calcula multa para múltiplos meses em atraso
     */
    public function calcular_multiplos(
        float $valor,
        int   $dia_vencimento,
        array $meses,
        int   $ano,
        float $percentual_diario,
        bool  $multa_ativa = true
    ): array {
        $resultado = [];

        foreach ($meses as $id_mes) {
            $calculo = $this->calcular(
                $valor,
                $dia_vencimento,
                $id_mes,
                $ano,
                $percentual_diario,
                $multa_ativa
            );

            $resultado[] = [
                'id_mes'          => $id_mes,
                'valor_original'  => $valor,
                'dias_atraso'     => $calculo['dias_atraso'],
                'valor_multa'     => $calculo['valor_multa'],
                'total'           => $calculo['total'],
                'situacao'        => $calculo['situacao'],
            ];
        }

        return $resultado;
    }

    private function _resultado(
        float  $valor_multa,
        int    $dias_atraso,
        int    $dias_diff,
        float  $total,
        string $situacao
    ): array {
        return [
            'valor_multa' => $valor_multa,
            'dias_atraso' => $dias_atraso,
            'total'       => $total,
            'situacao'    => $situacao,
        ];
    }
}