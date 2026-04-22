<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AlertMessage from '@/components/AlertMessage.vue'
import ModalBase from '@/components/ModalBase.vue'
import PaginacaoTabela from '@/components/PaginacaoTabela.vue'
import { listarTodosProdutos } from '@/services/produtos'
import { cancelarVenda, listarVendas, registrarVenda } from '@/services/vendas'
import { moeda } from '@/utils/format'

const produtos = ref([])
const vendas = ref([])
const meta = ref({})
const carregando = ref(false)
const salvando = ref(false)
const erro = ref('')
const sucesso = ref('')
const cancelandoNumero = ref('')
const modalAberto = ref(false)
const modalCancelamentoAberto = ref(false)
const numeroVendaParaCancelar = ref('')

const form = reactive({
  cliente: '',
  produtos: [{ id: '', quantidade: 1, preco_unitario: '' }],
})

const produtosOptions = computed(() => produtos.value.map((p) => ({ value: p.id, label: p.nome })))

function limparMensagens() {
  erro.value = ''
  sucesso.value = ''
}

function limparFormulario() {
  form.cliente = ''
  form.produtos = [{ id: '', quantidade: 1, preco_unitario: '' }]
}

function mensagemErro(err) {
  if (err?.data?.errors) {
    const primeira = Object.values(err.data.errors)[0]
    if (Array.isArray(primeira)) return primeira[0]
  }
  return err?.message || 'Falha ao processar a operação.'
}

function abrirModal() {
  limparMensagens()
  limparFormulario()
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  limparFormulario()
}

async function carregarVendas(page = 1) {
  carregando.value = true
  try {
    const resposta = await listarVendas(page)
    vendas.value = resposta.data || []
    meta.value = resposta.meta || {}
  } catch (e) {
    erro.value = mensagemErro(e)
  } finally {
    carregando.value = false
  }
}

async function carregarProdutos() {
  try {
    produtos.value = await listarTodosProdutos()
  } catch (e) {
    erro.value = mensagemErro(e)
  }
}

function adicionarLinha() {
  form.produtos.push({ id: '', quantidade: 1, preco_unitario: '' })
}

function removerLinha(index) {
  if (form.produtos.length === 1) return
  form.produtos.splice(index, 1)
}

async function salvar() {
  limparMensagens()
  salvando.value = true
  try {
    const resposta = await registrarVenda({
      cliente: form.cliente,
      produtos: form.produtos.map((item) => ({
        id: Number(item.id),
        quantidade: Number(item.quantidade),
        preco_unitario: Number(item.preco_unitario),
      })),
    })
    sucesso.value = `Venda registrada. Total: ${moeda(resposta.total_venda)} — Lucro: ${moeda(resposta.lucro)}.`
    fecharModal()
    await carregarVendas(meta.value.current_page || 1)
    await carregarProdutos()
  } catch (e) {
    erro.value = mensagemErro(e)
  } finally {
    salvando.value = false
  }
}

function primeiraLinhaDoNumero(item) {
  return vendas.value.find((linha) => linha.numero_venda === item.numero_venda)?.id === item.id
}

async function cancelar(numeroVenda) {
  limparMensagens()
  cancelandoNumero.value = numeroVenda
  try {
    await cancelarVenda(numeroVenda)
    sucesso.value = 'Venda cancelada com sucesso.'
    await carregarVendas(meta.value.current_page || 1)
    await carregarProdutos()
  } catch (e) {
    erro.value = mensagemErro(e)
  } finally {
    cancelandoNumero.value = ''
  }
}

function abrirModalCancelamento(numeroVenda) {
  limparMensagens()
  numeroVendaParaCancelar.value = numeroVenda
  modalCancelamentoAberto.value = true
}

function fecharModalCancelamento() {
  modalCancelamentoAberto.value = false
  numeroVendaParaCancelar.value = ''
}

async function confirmarCancelamento() {
  if (!numeroVendaParaCancelar.value) return
  const numeroVenda = numeroVendaParaCancelar.value
  fecharModalCancelamento()
  await cancelar(numeroVenda)
}

onMounted(async () => {
  await carregarProdutos()
  await carregarVendas()
})
</script>

<template>
  <section class="stack">
    <div class="page-toolbar">
      <h2 class="page-title">Vendas</h2>
      <p class="page-subtitle">Saídas de estoque, total e lucro.</p>
      <button type="button" class="btn" @click="abrirModal">Cadastrar venda</button>
    </div>

    <AlertMessage tipo="erro" :mensagem="erro" />
    <AlertMessage tipo="sucesso" :mensagem="sucesso" />

    <div class="card">
      <p v-if="carregando" class="muted">Carregando...</p>
      <table v-else class="table">
        <thead>
          <tr>
            <th>Número</th>
            <th>Cliente</th>
            <th>Produto</th>
            <th>Qtd</th>
            <th>Preço unit. (venda)</th>
            <th>Receita do item</th>
            <th>Lucro do item</th>
            <th>Status</th>
            <th>Ação</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in vendas" :key="item.id">
            <td>{{ item.numero_venda }}</td>
            <td>{{ item.cliente }}</td>
            <td>{{ item.produto?.nome }}</td>
            <td>{{ item.quantidade }}</td>
            <td>{{ moeda(item.preco_unitario) }}</td>
            <td>{{ moeda(item.total_linha) }}</td>
            <td>{{ moeda(item.lucro_linha) }}</td>
            <td>{{ item.cancelada_em ? 'Cancelada' : 'Ativa' }}</td>
            <td>
              <button
                v-if="!item.cancelada_em && primeiraLinhaDoNumero(item)"
                class="btn btn-danger"
                type="button"
                :disabled="cancelandoNumero === item.numero_venda"
                @click="abrirModalCancelamento(item.numero_venda)"
              >
                Cancelar
              </button>
            </td>
          </tr>
          <tr v-if="!vendas.length && !carregando">
            <td colspan="9" class="muted">Nenhuma venda registrada.</td>
          </tr>
        </tbody>
      </table>
      <PaginacaoTabela :meta="meta" @mudar-pagina="carregarVendas" />
    </div>

    <ModalBase :aberto="modalAberto" titulo="Cadastrar venda" @fechar="fecharModal">
      <form class="stack" @submit.prevent="salvar">
        <div class="field">
          <label>Cliente</label>
          <input v-model="form.cliente" required minlength="2" />
        </div>
        <div class="stack">
          <div class="inline linha-titulo">
            <strong>Itens</strong>
            <button class="btn btn-outline" type="button" @click="adicionarLinha">Adicionar item</button>
          </div>
          <div v-for="(item, index) in form.produtos" :key="index" class="grid-2 card item-card">
            <div class="field">
              <label>Produto</label>
              <select v-model="item.id" required>
                <option value="">Selecione</option>
                <option v-for="opt in produtosOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
            <div class="field">
              <label>Quantidade</label>
              <input v-model="item.quantidade" min="1" step="1" required type="number" />
            </div>
            <div class="field">
              <label>Preço unitário</label>
              <input v-model="item.preco_unitario" min="0.01" step="0.01" required type="number" />
            </div>
            <div class="inline end">
              <button class="btn btn-danger" type="button" @click="removerLinha(index)">Remover</button>
            </div>
          </div>
        </div>
        <div class="inline modal-acoes">
          <button class="btn btn-outline" type="button" @click="fecharModal">Cancelar</button>
          <button class="btn" :disabled="salvando" type="submit">
            {{ salvando ? 'Salvando...' : 'Registrar venda' }}
          </button>
        </div>
      </form>
    </ModalBase>

    <ModalBase
      :aberto="modalCancelamentoAberto"
      titulo="Cancelar venda"
      @fechar="fecharModalCancelamento"
    >
      <p>
        Confirma o cancelamento da venda <strong>{{ numeroVendaParaCancelar }}</strong>?
      </p>
      <template #rodape>
        <div class="inline modal-acoes">
          <button class="btn btn-outline" type="button" @click="fecharModalCancelamento">
            Voltar
          </button>
          <button class="btn btn-danger" type="button" @click="confirmarCancelamento">
            Cancelar venda
          </button>
        </div>
      </template>
    </ModalBase>
  </section>
</template>

<style scoped>
.linha-titulo {
  justify-content: space-between;
}

.item-card {
  border-radius: 8px;
}

.end {
  justify-content: flex-end;
  align-self: end;
}

.modal-acoes {
  justify-content: flex-end;
  margin-top: 4px;
}
</style>
