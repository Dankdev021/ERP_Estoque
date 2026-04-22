<?php

namespace App\Domain\Produtos;

use App\Domain\Support\Exceptions\RegraNegocioException;
use App\Models\Product;

class ProdutoService
{
    public function criar(array $data): Product
    {
        return Product::query()->create([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);
    }

    public function atualizar(Product $produto, array $data): Product
    {
        $produto->update([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
        ]);

        return $produto->fresh();
    }

    public function excluir(Product $produto): void
    {
        if ($produto->purchases()->exists() || $produto->sales()->exists()) {
            throw new RegraNegocioException('Não é possível excluir produto com compras ou vendas vinculadas.');
        }

        $produto->delete();
    }
}
