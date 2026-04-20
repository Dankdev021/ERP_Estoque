<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $produtos = Product::query()->orderBy('name')->get();

        return ProductResource::collection($produtos);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $estoque = (int) ($data['estoque_inicial'] ?? 0);

        $produto = Product::query()->create([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
            'average_cost' => 0,
            'stock_quantity' => $estoque,
        ]);

        return ProductResource::make($produto)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $produto): ProductResource
    {
        $data = $request->validated();

        $produto->update([
            'name' => $data['nome'],
            'sale_price' => $data['preco_venda'],
        ]);

        return ProductResource::make($produto->fresh());
    }

    public function destroy(Product $produto): Response
    {
        if ($produto->purchases()->exists() || $produto->sales()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir o produto pois existem compras ou vendas vinculadas.',
            ], 422);
        }

        $produto->delete();

        return response()->noContent();
    }
}
