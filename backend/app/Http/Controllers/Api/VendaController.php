<?php

namespace App\Http\Controllers\Api;

use App\Domain\Support\Exceptions\RegraNegocioException;
use App\Domain\Vendas\VendaService;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarVendaRequest;
use App\Http\Resources\LinhaVendaResource;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendaController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->query('busca', ''));

        $vendas = Sale::query()
            ->with(['customer', 'product'])
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where('sale_number', 'like', '%'.$busca.'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($busca) {
                        $customerQuery->where('name', 'like', '%'.$busca.'%');
                    })
                    ->orWhereHas('product', function ($productQuery) use ($busca) {
                        $productQuery->where('name', 'like', '%'.$busca.'%');
                    });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return LinhaVendaResource::collection($vendas);
    }

    public function store(RegistrarVendaRequest $request): JsonResponse
    {
        $result = app(VendaService::class)->registrar($request->validated());

        return response()->json($result, 201);
    }

    public function cancelar(string $numero_venda): JsonResponse
    {
        try {
            app(VendaService::class)->cancelar($numero_venda);
        } catch (RegraNegocioException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->status());
        }

        return response()->json(['message' => 'Venda cancelada com sucesso.']);
    }
}
