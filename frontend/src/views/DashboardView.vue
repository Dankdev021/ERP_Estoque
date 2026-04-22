<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { obterTotais } from '@/services/dashboard'
import { moeda } from '@/utils/format'

const totais = ref({
  produtos: 0,
  compras: 0,
  vendas: 0,
  lucro_liquido: '0.00',
  lucro_presumido: '0.00',
})
const carregando = ref(true)
const erro = ref('')

onMounted(async () => {
  carregando.value = true
  erro.value = ''
  try {
    totais.value = await obterTotais()
  } catch (e) {
    erro.value = e?.message || 'Não foi possível carregar os totais.'
  } finally {
    carregando.value = false
  }
})
</script>

<template>
  <section class="stack">
    <div>
      <h2 class="page-title">Dashboard</h2>
      <p class="page-subtitle">Acesse rapidamente os módulos do ERP.</p>
    </div>
    <p v-if="erro" class="erro-msg">{{ erro }}</p>
    <p class="section-label">Métricas financeiras</p>
    <div class="grid-2">
      <div class="card metric-panel">
        <span class="metric-titulo">Lucro líquido</span>
        <span class="metric" aria-label="Lucro líquido">{{
          carregando ? '—' : moeda(totais.lucro_liquido)
        }}</span>
        <p class="metric-detalhe">Lucro em vendas não canceladas</p>
      </div>
      <div class="card metric-panel">
        <span class="metric-titulo">Lucro presumido</span>
        <span class="metric" aria-label="Lucro presumido">{{
          carregando ? '—' : moeda(totais.lucro_presumido)
        }}</span>
        <p class="metric-detalhe">Margem se o estoque for vendido ao preço sugerido</p>
      </div>
    </div>
    <p class="section-label">Acesso rápido</p>
    <div class="grid-2">
      <RouterLink class="card link-card" to="/produtos">
        <span class="link-titulo">Gerenciar Produtos</span>
        <span class="metric" aria-label="Quantidade de produtos">{{
          carregando ? '—' : totais.produtos
        }}</span>
      </RouterLink>
      <RouterLink class="card link-card" to="/compras">
        <span class="link-titulo">Registrar Compras</span>
        <span class="metric" aria-label="Quantidade de compras">{{
          carregando ? '—' : totais.compras
        }}</span>
      </RouterLink>
      <RouterLink class="card link-card" to="/vendas">
        <span class="link-titulo">Registrar Vendas</span>
        <span class="metric" aria-label="Quantidade de vendas">{{
          carregando ? '—' : totais.vendas
        }}</span>
      </RouterLink>
    </div>
  </section>
</template>

<style scoped>
.link-card {
  text-decoration: none;
  color: var(--color-text);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  font-size: 18px;
  font-weight: 600;
  min-width: 0;
}

.link-titulo {
  min-width: 0;
  flex: 1;
}

.link-card:hover {
  border-color: var(--color-primary);
}

.metric {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-primary, #3cb371);
  flex-shrink: 0;
  font-variant-numeric: tabular-nums;
}

.erro-msg {
  color: var(--color-danger, #b91c1c);
  font-size: 14px;
  margin: 0;
}

.section-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-muted, #64748b);
  margin: 0 0 4px 0;
}

.grid-2 + .section-label {
  margin-top: 20px;
}

.metric-panel {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-start;
}

.metric-titulo {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text, #0f172a);
}

.metric-detalhe {
  margin: 0;
  font-size: 13px;
  color: var(--color-muted, #64748b);
  font-weight: 500;
  line-height: 1.35;
}
</style>
