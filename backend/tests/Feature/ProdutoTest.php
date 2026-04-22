<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_vazia_retorna_paginacao_com_total_zero(): void
    {
        $this->getJson('/api/produtos')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 0);
    }

    public function test_lista_paginada_15_por_pagina(): void
    {
        for ($i = 0; $i < 16; $i++) {
            Product::query()->create([
                'name' => 'Prod '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sale_price' => 10,
                'average_cost' => 0,
                'stock_quantity' => 0,
            ]);
        }

        $r1 = $this->getJson('/api/produtos?page=1');
        $r1->assertOk();
        $this->assertCount(15, $r1->json('data'));
        $this->assertEquals(2, $r1->json('meta.last_page'));
        $this->assertEquals(16, $r1->json('meta.total'));

        $r2 = $this->getJson('/api/produtos?page=2');
        $r2->assertOk();
        $this->assertCount(1, $r2->json('data'));
    }

    public function test_cadastra_produto_201_e_estrutura(): void
    {
        $criado = $this->postJson('/api/produtos', [
            'nome' => 'Caderno',
            'preco_venda' => 19.9,
            'estoque_inicial' => 5,
        ]);
        $criado->assertStatus(201);
        $d = $criado->json('data');
        $this->assertSame('Caderno', $d['nome']);
        $this->assertEquals(19.9, (float) $d['preco_venda']);
        $this->assertEquals(5, (int) $d['estoque']);
        $this->assertEquals(0.0, (float) $d['custo_medio']);

        $this->assertDatabaseHas('products', [
            'name' => 'Caderno',
            'sale_price' => 19.9,
            'stock_quantity' => 5,
        ]);
    }

    public function test_cadastro_rejeita_nome_curto_422(): void
    {
        $this->postJson('/api/produtos', [
            'nome' => 'AB',
            'preco_venda' => 1,
        ])
            ->assertStatus(422);
    }

    public function test_atualiza_produto(): void
    {
        $p = Product::query()->create([
            'name' => 'Caneta',
            'sale_price' => 2,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $at = $this->putJson("/api/produtos/{$p->id}", [
            'nome' => 'Caneta azul',
            'preco_venda' => 3.5,
        ]);
        $at->assertOk();
        $this->assertSame('Caneta azul', $at->json('data.nome'));
        $this->assertEquals(3.5, (float) $at->json('data.preco_venda'));

        $this->assertDatabaseHas('products', [
            'id' => $p->id,
            'name' => 'Caneta azul',
        ]);
    }

    public function test_exclui_produto_sem_vinculo_204(): void
    {
        $p = Product::query()->create([
            'name' => 'Borracha',
            'sale_price' => 1,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $this->deleteJson("/api/produtos/{$p->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $p->id]);
    }

    public function test_nao_exclui_com_compra_422(): void
    {
        $p = Product::query()->create([
            'name' => 'Goma',
            'sale_price' => 5,
            'average_cost' => 0,
            'stock_quantity' => 0,
        ]);

        $s = Supplier::query()->create(['name' => 'Forn A', 'country' => 'BR']);
        Purchase::query()->create([
            'purchase_number' => 'CMP-20260101-ABCD12EF',
            'supplier_id' => $s->id,
            'product_id' => $p->id,
            'quantity' => 1,
            'unit_price' => 1,
            'line_total' => 1,
        ]);

        $this->deleteJson("/api/produtos/{$p->id}")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Não é possível excluir produto com compras ou vendas vinculadas.');

        $this->assertDatabaseHas('products', ['id' => $p->id]);
    }

    public function test_nao_exclui_com_venda_422(): void
    {
        $p = Product::query()->create([
            'name' => 'Lápis',
            'sale_price' => 2,
            'average_cost' => 1,
            'stock_quantity' => 5,
        ]);

        $customer = Customer::query()->create(['name' => 'João', 'country' => 'BR']);
        Sale::query()->create([
            'sale_number' => 'VND-20260101-XXYYZZ12',
            'customer_id' => $customer->id,
            'product_id' => $p->id,
            'quantity' => 1,
            'unit_price' => 2,
            'unit_cost_at_sale' => 1,
            'cancelled_at' => null,
        ]);

        $this->deleteJson("/api/produtos/{$p->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $p->id]);
    }
}
