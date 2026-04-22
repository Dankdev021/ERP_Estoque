<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinhaCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_compra' => $this->purchase_number,
            'fornecedor' => $this->supplier?->name,
            'produto' => [
                'id' => $this->product_id,
                'nome' => $this->product?->name,
            ],
            'quantidade' => $this->quantity,
            'preco_unitario' => $this->unit_price,
            'total_linha' => $this->line_total,
            'criado_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
