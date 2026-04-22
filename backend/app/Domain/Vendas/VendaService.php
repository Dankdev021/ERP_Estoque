<?php

namespace App\Domain\Vendas;

use App\Domain\Support\Exceptions\RegraNegocioException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VendaService
{
    public function registrar(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
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

                $product->update([
                    'stock_quantity' => $product->stock_quantity - $quantity,
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
    }

    public function cancelar(string $numeroVenda): void
    {
        $status = DB::transaction(function () use ($numeroVenda) {
            $linhas = Sale::query()
                ->where('sale_number', $numeroVenda)
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
            throw new RegraNegocioException('Venda não encontrada ou já cancelada.', 404);
        }
    }
}
