<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CadastrarProdutoRequest;
use App\Http\Requests\AtualizarProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProdutoController extends Controller
{
    public function index()
    {
        $produtos = Product::query()->orderBy('name')->paginate(15);

        return ProdutoResource::collection($produtos);
    }

    public function store(CadastrarProdutoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $initialStock = (int) ($data['estoque_inicial'] ?? 0);

        $produto = Product::query()->create([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
            'average_cost' => 0,
            'stock_quantity' => $initialStock,
        ]);

        return ProdutoResource::make($produto)
            ->response()
            ->setStatusCode(201);
    }

    public function update(AtualizarProdutoRequest $request, Product $produto): ProdutoResource
    {
        $data = $request->validated();

        $produto->update([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
        ]);

        return ProdutoResource::make($produto->fresh());
    }

    public function destroy(Product $produto): Response
    {
        if ($produto->purchases()->exists() || $produto->sales()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir produto com compras ou vendas vinculadas.',
            ], 422);
        }

        $produto->delete();

        return response()->noContent();
    }
}
