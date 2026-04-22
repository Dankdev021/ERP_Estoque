<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarVendaRequest;
use App\Http\Resources\LinhaVendaResource;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendaController extends Controller
{
    public function index()
    {
        $vendas = Sale::query()
            ->with(['customer', 'product'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return LinhaVendaResource::collection($vendas);
    }

    public function store(RegistrarVendaRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $customer = Customer::query()->firstOrCreate(
                ['name' => $payload['cliente']],
                ['country' => 'BR'],
            );

            $saleNumber = 'VND-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));

            $totalVenda = '0';
            $lucroTotal = '0';
            $itens = [];

            foreach ($payload['produtos'] as $line) {
                $product = Product::query()->lockForUpdate()->findOrFail($line['id']);
                $quantity = (int) $line['quantidade'];
                $unitPrice = (string) $line['preco_unitario'];

                if ($product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'estoque' => [
                            'Estoque insuficiente para o produto '.$product->name.
                            ' (disponível: '.$product->stock_quantity.', solicitado: '.$quantity.').',
                        ],
                    ]);
                }

                $unitCostAtSale = (string) $product->average_cost;
                $lineTotal = bcmul((string) $quantity, $unitPrice, 2);
                $lineCost = bcmul((string) $quantity, $unitCostAtSale, 2);
                $lineProfit = bcsub($lineTotal, $lineCost, 2);

                Sale::query()->create([
                    'sale_number' => $saleNumber,
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_cost_at_sale' => $unitCostAtSale,
                    'cancelled_at' => null,
                ]);

                $newStock = $product->stock_quantity - $quantity;

                $product->update([
                    'stock_quantity' => $newStock,
                ]);

                $product->refresh();

                $totalVenda = bcadd($totalVenda, $lineTotal, 2);
                $lucroTotal = bcadd($lucroTotal, $lineProfit, 2);

                $itens[] = [
                    'id' => $product->id,
                    'nome' => $product->name,
                    'quantidade' => $quantity,
                    'preco_unitario' => $unitPrice,
                    'total_linha' => $lineTotal,
                    'custo_unitario_na_venda' => $unitCostAtSale,
                    'lucro_linha' => $lineProfit,
                    'estoque_atual' => $product->stock_quantity,
                ];
            }

            return [
                'numero_venda' => $saleNumber,
                'cliente' => $customer->name,
                'total_venda' => $totalVenda,
                'lucro' => $lucroTotal,
                'produtos' => $itens,
            ];
        });

        return response()->json($result, 201);
    }

    public function cancelar(string $numero_venda): JsonResponse
    {
        $status = DB::transaction(function () use ($numero_venda) {
            $linhas = Sale::query()
                ->where('sale_number', $numero_venda)
                ->whereNull('cancelled_at')
                ->lockForUpdate()
                ->get();

            if ($linhas->isEmpty()) {
                return 'nao_encontrada';
            }

            foreach ($linhas as $linha) {
                $product = Product::query()->lockForUpdate()->findOrFail($linha->product_id);
                $product->update([
                    'stock_quantity' => $product->stock_quantity + $linha->quantity,
                ]);
                $linha->update(['cancelled_at' => now()]);
            }

            return 'ok';
        });

        if ($status === 'nao_encontrada') {
            return response()->json([
                'message' => 'Venda não encontrada ou já cancelada.',
            ], 404);
        }

        return response()->json(['message' => 'Venda cancelada com sucesso.']);
    }
}
