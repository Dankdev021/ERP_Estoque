<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompraTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_vazia_paginada(): void
    {
        $this->getJson('/api/compras')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_registra_compra_atualiza_estoque_e_custo_medio(): void
    {
        $p1 = Product::query()->create([
            'name' => 'Mesa',
            'sale_price' => 200,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);
        $p2 = Product::query()->create([
            'name' => 'Cadeira',
            'sale_price' => 100,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $r = $this->postJson('/api/compras', [
            'fornecedor' => 'Loja Móveis',
            'produtos' => [
                ['id' => $p1->id, 'quantidade' => 10, 'preco_unitario' => 50],
                ['id' => $p2->id, 'quantidade' => 4, 'preco_unitario' => 25.5],
            ],
        ]);

        $r->assertStatus(201);
        $r->assertJsonPath('fornecedor', 'Loja Móveis');
        $this->assertIsString($r->json('numero_compra'));
        $this->assertStringStartsWith('CMP-', $r->json('numero_compra'));
        $this->assertCount(2, $r->json('produtos'));

        $p1->refresh();
        $p2->refresh();
        $this->assertEquals(10, $p1->stock_quantity);
        $this->assertEquals('50.00', (string) $p1->average_cost);
        $this->assertEquals(4, $p2->stock_quantity);
        $this->assertEquals('25.50', (string) $p2->average_cost);

        $this->assertEquals(2, Purchase::query()->count());
    }

    public function test_segunda_compra_recalcula_custo_medio_ponderado(): void
    {
        $p = Product::query()->create([
            'name' => 'Folha A4',
            'sale_price' => 0.2,
            'average_cost' => 0,
            'stock_quantity' => 10,
        ]);

        $this->postJson('/api/compras', [
            'fornecedor' => 'Papelaria',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 10, 'preco_unitario' => 0.1],
            ],
        ])->assertStatus(201);

        $p->refresh();
        $this->assertEquals(20, $p->stock_quantity);
        $this->assertEquals('0.05', (string) $p->average_cost);
    }

    public function test_rejeita_payload_sem_fornecedor_422(): void
    {
        $p = Product::query()->create([
            'name' => 'X',
            'sale_price' => 1,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $this->postJson('/api/compras', [
            'fornecedor' => '',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 1, 'preco_unitario' => 1],
            ],
        ])->assertStatus(422);
    }
}
