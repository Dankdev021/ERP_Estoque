<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ErpDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->esvaziarDadosErp();

            $fornecedores = [
                'Distribuidora Alimentar Sul',
                'Móveis e Decoração Ltda',
                'Tech Import Brasil',
                'Papelaria Central',
                'Ferramentas e Parafusos',
                'Moda & Calçados SP',
            ];

            $nomesClientes = [
                'Maria Aparecida Souza',
                'João Carlos Ferreira',
                'Eduardo Martins',
                'Comercial Silva ME',
                'Ana Paula Ribeiro',
                'Loja Bairro Novo',
                'Pedro Henrique Gomes',
                'Juliana Campos',
            ];

            $rows = $this->catalogoProdutos();
            if (count($rows) !== 50) {
                throw new InvalidArgumentException('O catálogo deve conter 50 produtos.');
            }

            $suppliers = collect($fornecedores)->map(fn (string $n) => Supplier::query()->create([
                'name' => $n,
                'country' => 'BR',
            ]))->all();

            $customers = collect($nomesClientes)->map(fn (string $n) => Customer::query()->create([
                'name' => $n,
                'country' => 'BR',
            ]))->all();

            $idsProdutos = [];
            foreach ($rows as $r) {
                $p = Product::query()->create([
                    'name' => $r['nome'],
                    'sale_price' => $r['preco_venda'],
                    'average_cost' => 0,
                    'stock_quantity' => 0,
                ]);
                $idsProdutos[] = $p->id;
            }

            $linha = 0;
            $lote = 0;
            while ($linha < 60) {
                $lote += 1;
                $cod = 'DEMO-CMP-'.str_pad((string) $lote, 3, '0', STR_PAD_LEFT);
                $forn = $suppliers[($lote - 1) % count($suppliers)];

                for ($k = 0; $k < 5 && $linha < 60; $k++) {
                    if ($linha < 50) {
                        $pi = $linha;
                        $q = 24;
                        $custoFator = 0.58;
                    } else {
                        $pi = $linha - 50;
                        $q = 12;
                        $custoFator = 0.62;
                    }
                    $pv = (float) $rows[$pi]['preco_venda'];
                    $unit = (string) $this->arred2($pv * $custoFator);
                    $this->aplicarCompra($cod, $forn, (int) $idsProdutos[$pi], $q, $unit);
                    $linha += 1;
                }
            }

            for ($c = 0; $c < 100; $c++) {
                $pi = $c % 50;
                $pId = (int) $idsProdutos[$pi];
                $pv = (float) $rows[$pi]['preco_venda'];
                $unitVenda = (string) $this->arred2($pv * 0.97);
                $codV = 'DEMO-VND-'.str_pad((string) ($c + 1), 4, '0', STR_PAD_LEFT);
                $cli = $customers[$c % count($customers)];
                $this->aplicarVenda($codV, $cli, $pId, 8, $unitVenda);
            }
        });
    }

    private function esvaziarDadosErp(): void
    {
        Sale::query()->delete();
        Purchase::query()->delete();
        Product::query()->delete();
        Customer::query()->delete();
        Supplier::query()->delete();
    }

    private function arred2(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    private function aplicarCompra(string $numero, Supplier $fornecedor, int $productId, int $quantidade, string $unitPrice): void
    {
        $product = Product::query()->lockForUpdate()->findOrFail($productId);
        $q = (string) $quantidade;
        $lineTotal = bcmul($q, $unitPrice, 2);
        $previousStock = (int) $product->stock_quantity;
        $previousCost = (string) $product->average_cost;
        $newStock = $previousStock + $quantidade;
        if ($newStock > 0) {
            $previousStockValue = bcmul((string) $previousStock, $previousCost, 4);
            $purchaseValue = bcmul($q, $unitPrice, 4);
            $totalValue = bcadd($previousStockValue, $purchaseValue, 4);
            $newAverageCost = bcdiv($totalValue, (string) $newStock, 2);
        } else {
            $newAverageCost = $product->average_cost;
        }
        Purchase::query()->create([
            'purchase_number' => $numero,
            'supplier_id' => $fornecedor->id,
            'product_id' => $product->id,
            'quantity' => $quantidade,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
        ]);
        $product->update([
            'stock_quantity' => $newStock,
            'average_cost' => $newAverageCost,
        ]);
    }

    private function aplicarVenda(string $numero, Customer $cliente, int $productId, int $quantidade, string $unitPrice): void
    {
        $product = Product::query()->lockForUpdate()->findOrFail($productId);
        if ($product->stock_quantity < $quantidade) {
            throw new InvalidArgumentException("Estoque insuficiente no seed (produto {$product->id}).");
        }
        $unitCostAtSale = (string) $product->average_cost;
        Sale::query()->create([
            'sale_number' => $numero,
            'customer_id' => $cliente->id,
            'product_id' => $product->id,
            'quantity' => $quantidade,
            'unit_price' => $unitPrice,
            'unit_cost_at_sale' => $unitCostAtSale,
            'cancelled_at' => null,
        ]);
        $product->update([
            'stock_quantity' => $product->stock_quantity - $quantidade,
        ]);
    }

    private function catalogoProdutos(): array
    {
        $l = function (string $nome, float $pv) {
            return ['nome' => $nome, 'preco_venda' => $this->arred2($pv)];
        };

        return [
            $l('Arroz Tio João 5kg', 32.9),
            $l('Feijão Carioca 1kg Tipo 1', 8.5),
            $l('Açúcar Cristal 1kg', 4.29),
            $l('Café Torrado 500g', 19.9),
            $l('Óleo de soja 900ml', 6.9),
            $l('Leite integral 1L', 4.5),
            $l('Macarrão espaguete 500g', 4.1),
            $l('Molho de tomate 340g', 3.2),
            $l('Sal refinado 1kg', 2.2),
            $l('Farinha de trigo 1kg', 4.4),
            $l('Açúcar demerara 1kg', 4.6),
            $l('Biscoito cream cracker 400g', 5.1),
            $l('Creme de leite 200g', 2.4),
            $l('Fermento químico 100g', 2.0),
            $l('Azeite de oliva 500ml', 39.9),
            $l('Vinagre de álcool 750ml', 2.1),
            $l('Papel higiênico 20m leve 12 pague 11', 22.0),
            $l('Detergente 500ml', 2.4),
            $l('Água sanitária 1L', 2.0),
            $l('Saco de lixo 100L 10un', 16.0),
            $l('Pilha alcalina AA 4un', 18.0),
            $l('Bateria recarregável AA 2un', 32.0),
            $l('Cabo USB-C 1m', 25.0),
            $l('Fone de ouvido com fio P2', 35.0),
            $l('Carregador USB 20W Tipo C', 59.0),
            $l('Mouse sem fio ergonômico', 65.0),
            $l('Teclado ABNT2 USB', 85.0),
            $l('Mesa de escritório 120cm', 450.0),
            $l('Cadeira giratória tela mesh', 620.0),
            $l('Estante 5 prateleiras 80cm', 280.0),
            $l('Colchão solteiro D33 88x188', 590.0),
            $l('Caderno universitário 10 matérias', 35.0),
            $l('Resma papel A4 75g 500fls', 29.0),
            $l('Caneta esferográfica 0,7 3un', 4.0),
            $l('Kit grampeador 26/6 com grampos', 18.0),
            $l('Martelo de unha 27mm', 32.0),
            $l('Furadeira elétrica 550W', 220.0),
            $l('Jogo de chaves 12 peças', 85.0),
            $l('Fita veda rosca 10m', 5.5),
            $l('Tênis esportivo mesh masculino 42', 199.0),
            $l('Tênis casual feminino 37', 189.0),
            $l('Chinelo slide EVA 41', 45.0),
            $l('Meia algodão kit 3 pares', 19.0),
            $l('Calça jeans masculina 42', 120.0),
            $l('Camiseta básica algodão M', 39.0),
            $l('Mochila escolar com rodinha', 160.0),
            $l('Toalha de banho 70x130cm', 45.0),
            $l('Jogo de cama solteiro 3 peças', 89.0),
            $l('Kit panelas 5 peças antiaderente', 199.0),
            $l('Liquidificador 1000W 1,5L', 120.0),
        ];
    }
}
