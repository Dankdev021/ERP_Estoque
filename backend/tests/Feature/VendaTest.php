<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_vazia_paginada(): void
    {
        $this->getJson('/api/vendas')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_registra_venda_baixa_estoque_totais_e_lucro(): void
    {
        $p = Product::query()->create([
            'name' => 'Café',
            'sale_price' => 30,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $this->postJson('/api/compras', [
            'fornecedor' => 'Atacado',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 20, 'preco_unitario' => 8],
            ],
        ])->assertStatus(201);

        $p->refresh();
        $this->assertEquals(20, $p->stock_quantity);

        $r = $this->postJson('/api/vendas', [
            'cliente' => 'Maria',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 2, 'preco_unitario' => 15.5],
            ],
        ]);

        $r->assertStatus(201);
        $r->assertJsonPath('cliente', 'Maria');
        $this->assertEquals(31, (float) $r->json('total_venda'));
        $this->assertEquals(15, (float) $r->json('lucro'));
        $this->assertStringStartsWith('VND-', $r->json('numero_venda'));

        $p->refresh();
        $this->assertEquals(18, $p->stock_quantity);
        $this->assertEquals(1, Sale::query()->count());
    }

    public function test_rejeita_venda_por_estoque_insuficiente_422(): void
    {
        $p = Product::query()->create([
            'name' => 'Chá',
            'sale_price' => 5,
            'average_cost' => 0,
            'stock_quantity' => 1,
        ]);

        $resposta = $this->postJson('/api/vendas', [
            'cliente' => 'Zé',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 3, 'preco_unitario' => 5],
            ],
        ]);
        $resposta->assertStatus(422);
        $this->assertStringContainsString('Estoque insuficiente', $resposta->json('errors.estoque.0'));

        $p->refresh();
        $this->assertEquals(1, $p->stock_quantity);
        $this->assertEquals(0, Sale::query()->count());
    }

    public function test_cancela_venda_reverte_estoque_e_segundo_cancelamento_404(): void
    {
        $p = Product::query()->create([
            'name' => 'Biscoito',
            'sale_price' => 3,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $this->postJson('/api/compras', [
            'fornecedor' => 'Padaria',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 5, 'preco_unitario' => 1.2],
            ],
        ])->assertStatus(201);

        $p->refresh();
        $this->assertEquals(5, $p->stock_quantity);

        $v = $this->postJson('/api/vendas', [
            'cliente' => 'Ana',
            'produtos' => [
                ['id' => $p->id, 'quantidade' => 2, 'preco_unitario' => 3],
            ],
        ]);
        $v->assertStatus(201);
        $numero = $v->json('numero_venda');
        $p->refresh();
        $this->assertEquals(3, $p->stock_quantity);

        $c = $this->postJson("/api/vendas/{$numero}/cancelar");
        $c->assertOk();
        $p->refresh();
        $this->assertEquals(5, $p->stock_quantity);
        $this->assertNotNull(Sale::query()->first()->cancelled_at);

        $this->postJson("/api/vendas/{$numero}/cancelar")
            ->assertStatus(404);
    }
}
