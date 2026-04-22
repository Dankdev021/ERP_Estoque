<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarCompraRequest;
use App\Http\Resources\LinhaCompraResource;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Purchase::query()
            ->with(['supplier', 'product'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return LinhaCompraResource::collection($compras);
    }

    public function store(RegistrarCompraRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $supplier = Supplier::query()->firstOrCreate(
                ['name' => $payload['fornecedor']],
                ['country' => 'BR'],
            );

            $purchaseNumber = 'CMP-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));

            $items = [];

            foreach ($payload['produtos'] as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line['id']);
                $quantity = (int) $line['quantidade'];
                $unitPrice = (string) $line['preco_unitario'];
                $lineTotal = bcmul((string) $quantity, $unitPrice, 2);

                $previousStock = (int) $product->stock_quantity;
                $previousCost = (string) $product->average_cost;
                $newStock = $previousStock + $quantity;

                if ($newStock > 0) {
                    $previousStockValue = bcmul((string) $previousStock, $previousCost, 4);
                    $purchaseValue = bcmul((string) $quantity, $unitPrice, 4);
                    $totalValue = bcadd($previousStockValue, $purchaseValue, 4);
                    $newAverageCost = bcdiv($totalValue, (string) $newStock, 2);
                } else {
                    $newAverageCost = $product->average_cost;
                }

                Purchase::query()->create([
                    'purchase_number' => $purchaseNumber,
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $product->update([
                    'stock_quantity' => $newStock,
                    'average_cost' => $newAverageCost,
                ]);

                $product->refresh();

                $items[] = [
                    'id' => $product->id,
                    'nome' => $product->name,
                    'quantidade' => $quantity,
                    'preco_unitario' => $unitPrice,
                    'total_linha' => $lineTotal,
                    'custo_medio_atualizado' => $product->average_cost,
                    'estoque_atual' => $product->stock_quantity,
                ];
            }

            return [
                'numero_compra' => $purchaseNumber,
                'fornecedor' => $supplier->name,
                'produtos' => $items,
            ];
        });

        return response()->json($result, 201);
    }
}
