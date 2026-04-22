<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinhaVendaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalLinha = bcmul((string) $this->quantity, (string) $this->unit_price, 2);
        $custoLinha = bcmul((string) $this->quantity, (string) $this->unit_cost_at_sale, 2);
        $lucroLinha = bcsub($totalLinha, $custoLinha, 2);

        return [
            'id' => $this->id,
            'numero_venda' => $this->sale_number,
            'cliente' => $this->customer?->name,
            'produto' => [
                'id' => $this->product_id,
                'nome' => $this->product?->name,
            ],
            'quantidade' => $this->quantity,
            'preco_unitario' => $this->unit_price,
            'custo_unitario_na_venda' => $this->unit_cost_at_sale,
            'total_linha' => $totalLinha,
            'lucro_linha' => $lucroLinha,
            'cancelada_em' => $this->cancelled_at?->toIso8601String(),
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
