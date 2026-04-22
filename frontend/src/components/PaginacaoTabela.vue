<script setup>
import { computed } from 'vue'

const props = defineProps({
  meta: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['mudar-pagina'])

const ultimaPagina = computed(() => Math.max(1, Number(props.meta.last_page) || 1))
const paginaAtual = computed(() => {
  const c = Number(props.meta.current_page) || 1
  return Math.min(Math.max(1, c), ultimaPagina.value)
})
const totalRegistros = computed(() => Number(props.meta.total) || 0)

const maisDeTresPaginas = computed(() => ultimaPagina.value > 3)

const segmentos = computed(() => {
  const last = ultimaPagina.value
  if (last <= 3) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  return [1, 2, 3, '…', last]
})

function anterior() {
  if (paginaAtual.value > 1) {
    emit('mudar-pagina', paginaAtual.value - 1)
  }
}

function proxima() {
  if (paginaAtual.value < ultimaPagina.value) {
    emit('mudar-pagina', paginaAtual.value + 1)
  }
}

function irPara(n) {
  if (n === '…' || n === paginaAtual.value) return
  emit('mudar-pagina', n)
}

function numeroAtivo(n) {
  if (n === '…') return false
  return n === paginaAtual.value
}
</script>

<template>
  <div class="pagination-wrap">
    <p class="pagination-info">
      Página {{ paginaAtual }} de {{ ultimaPagina }} ({{ totalRegistros }} registros)
    </p>
    <nav class="pagination-numeros" aria-label="Paginação">
      <button
        v-if="maisDeTresPaginas"
        type="button"
        class="num-page nav"
        :disabled="paginaAtual <= 1"
        aria-label="Página anterior"
        @click="anterior"
      >
        &lt;
      </button>
      <button
        v-for="(seg, idx) in segmentos"
        :key="`p-${idx}-${seg}`"
        type="button"
        :class="['num-page', { ativa: numeroAtivo(seg), ellip: seg === '…' }]"
        :disabled="seg === '…' || seg === paginaAtual"
        :aria-label="seg === '…' ? 'Intervalo de páginas' : `Página ${seg}`"
        :aria-current="numeroAtivo(seg) ? 'page' : undefined"
        @click="irPara(seg)"
      >
        {{ seg }}
      </button>
      <button
        v-if="maisDeTresPaginas"
        type="button"
        class="num-page nav"
        :disabled="paginaAtual >= ultimaPagina"
        aria-label="Próxima página"
        @click="proxima"
      >
        &gt;
      </button>
    </nav>
  </div>
</template>

<style scoped>
.pagination-wrap {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.pagination-info {
  margin: 0;
  color: var(--color-muted, #475569);
  font-size: 14px;
}

.pagination-numeros {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 6px;
}

.num-page {
  min-width: 2.25rem;
  height: 2.25rem;
  padding: 0 0.4rem;
  border: 1px solid var(--color-border, #dce5dd);
  border-radius: 8px;
  background: #fff;
  color: var(--color-primary, #3cb371);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

.num-page:hover:not(:disabled) {
  border-color: var(--color-primary, #3cb371);
  background: rgba(60, 179, 113, 0.08);
}

.num-page:disabled:not(.ellip) {
  opacity: 0.5;
  cursor: not-allowed;
}

.num-page:disabled.ellip,
.num-page.ellip {
  border-color: transparent;
  background: transparent;
  color: var(--color-muted, #64748b);
  cursor: default;
  min-width: auto;
}

.num-page.nav {
  font-weight: 700;
}

.num-page:disabled.ativa,
.num-page.ativa {
  border-color: var(--color-primary, #3cb371);
  background: var(--color-primary, #3cb371);
  color: #fff;
  cursor: default;
}
</style>
