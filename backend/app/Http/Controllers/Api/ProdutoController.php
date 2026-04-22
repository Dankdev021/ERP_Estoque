<?php

namespace App\Http\Controllers\Api;

use App\Domain\Produtos\ProdutoService;
use App\Domain\Support\Exceptions\RegraNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CadastrarProdutoRequest;
use App\Http\Requests\AtualizarProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProdutoController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca', ''));

        $produtos = Product::query()
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where('name', 'like', '%'.$busca.'%');
            })
            ->orderBy('name')
            ->paginate(15);

        return ProdutoResource::collection($produtos);
    }

    public function store(CadastrarProdutoRequest $request): JsonResponse
    {
        $produto = app(ProdutoService::class)->criar($request->validated());

        return ProdutoResource::make($produto)
            ->response()
            ->setStatusCode(201);
    }

    public function update(AtualizarProdutoRequest $request, Product $produto): ProdutoResource
    {
        $produtoAtualizado = app(ProdutoService::class)->atualizar($produto, $request->validated());

        return ProdutoResource::make($produtoAtualizado);
    }

    public function destroy(Product $produto): Response
    {
        try {
            app(ProdutoService::class)->excluir($produto);
        } catch (RegraNegocioException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status());
        }

        return response()->noContent();
    }
}
