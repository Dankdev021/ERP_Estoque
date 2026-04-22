<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumo(): JsonResponse
    {
        $lucroLiquido = Sale::query()
            ->whereNull('cancelled_at')
            ->selectRaw('COALESCE(SUM(quantity * (unit_price - unit_cost_at_sale)), 0) as t')
            ->value('t');

        $lucroPresumido = Product::query()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN (sale_price - average_cost) > 0 THEN (sale_price - average_cost) * stock_quantity ELSE 0 END), 0) as t',
            )
            ->value('t');

        return response()->json([
            'produtos' => Product::query()->count(),
            'compras' => Purchase::query()->count(),
            'vendas' => Sale::query()->count(),
            'lucro_liquido' => $this->formatarDecimal($lucroLiquido),
            'lucro_presumido' => $this->formatarDecimal($lucroPresumido),
        ]);
    }

    private function formatarDecimal(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
