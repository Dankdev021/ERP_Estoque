<?php

namespace App\Http\Controllers\Api;

use App\Domain\Compras\CompraService;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarCompraRequest;
use App\Http\Resources\LinhaCompraResource;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca', ''));

        $compras = Purchase::query()
            ->with(['supplier', 'product'])
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where('purchase_number', 'like', '%'.$busca.'%')
                    ->orWhereHas('supplier', function ($supplierQuery) use ($busca) {
                        $supplierQuery->where('name', 'like', '%'.$busca.'%');
                    })
                    ->orWhereHas('product', function ($productQuery) use ($busca) {
                        $productQuery->where('name', 'like', '%'.$busca.'%');
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return LinhaCompraResource::collection($compras);
    }

    public function store(RegistrarCompraRequest $request): JsonResponse
    {
        $result = app(CompraService::class)->registrar($request->validated());

        return response()->json($result, 201);
    }
}
