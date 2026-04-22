<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import AlertMessage from '@/components/AlertMessage.vue'
import ModalBase from '@/components/ModalBase.vue'
import PaginacaoTabela from '@/components/PaginacaoTabela.vue'
import {
  atualizarProduto,
  cadastrarProduto,
  excluirProduto,
  listarProdutos,
} from '@/services/produtos'
import { moeda } from '@/utils/format'

const produtos = ref([])
const meta = ref({})
const carregando = ref(false)
const salvando = ref(false)
const erro = ref('')
const sucesso = ref('')
const editandoId = ref(null)
const modalAberto = ref(false)
const modalExclusaoAberto = ref(false)
const produtoParaExcluir = ref(null)

const form = reactive({
  nome: '',
  preco_venda: '',
})

const tituloModal = computed(() => (editandoId.value ? 'Editar produto' : 'Cadastrar produto'))

function limparMensagens() {
  erro.value = ''
  sucesso.value = ''
}

function limparFormulario() {
  form.nome = ''
  form.preco_venda = ''
  editandoId.value = null
}

function mensagemErro(err) {
  if (err?.data?.errors) {
    const primeira = Object.values(err.data.errors)[0]
    if (Array.isArray(primeira)) return primeira[0]
  }
  return err?.message || 'Falha ao processar a operação.'
}

function abrirModalCadastro() {
  limparMensagens()
  limparFormulario()
  modalAberto.value = true
}

function abrirModalEdicao(produto) {
  limparMensagens()
  editandoId.value = produto.id
  form.nome = produto.nome
  form.preco_venda = produto.preco_venda
  modalAberto.value = true
}

function fecharModal() {
  modalAberto.value = false
  limparFormulario()
}

async function carregar(page = 1) {
  carregando.value = true
  try {
    const resposta = await listarProdutos(page)
    produtos.value = resposta.data || []
    meta.value = resposta.meta || {}
  } catch (e) {
    erro.value = mensagemErro(e)
  } finally {
    carregando.value = false
  }
}

async function salvar() {
  limparMensagens()
  salvando.value = true
  try {
    const payload = {
      nome: form.nome,
      preco_venda: Number(form.preco_venda),
    }
    if (editandoId.value) {
      await atualizarProduto(editandoId.value, {
        nome: payload.nome,
        preco_venda: payload.preco_venda,
      })
      sucesso.value = 'Produto atualizado com sucesso.'
    } else {
      await cadastrarProduto(payload)
      sucesso.value = 'Produto cadastrado com sucesso.'
    }
    fecharModal()
    await carregar(meta.value.current_page || 1)
  } catch (e) {
    erro.value = mensagemErro(e)
  } finally {
    salvando.value = false
  }
}

async function remover(id) {
  limparMensagens()
  try {
    await excluirProduto(id)
    sucesso.value = 'Produto removido com sucesso.'
    await carregar(meta.value.current_page || 1)
  } catch (e) {
    erro.value = mensagemErro(e)
  }
}

function abrirModalExclusao(produto) {
  limparMensagens()
  produtoParaExcluir.value = produto
  modalExclusaoAberto.value = true
}

function fecharModalExclusao() {
  modalExclusaoAberto.value = false
  produtoParaExcluir.value = null
}

async function confirmarExclusao() {
  if (!produtoParaExcluir.value) return
  const id = produtoParaExcluir.value.id
  fecharModalExclusao()
  await remover(id)
}

onMounted(() => {
  carregar()
})
</script>

<template>
  <section class="stack">
    <div class="page-toolbar">
      <h2 class="page-title">Produtos</h2>
      <p class="page-subtitle">Lista de produtos do estoque.</p>
      <button type="button" class="btn" @click="abrirModalCadastro">Cadastrar produto</button>
    </div>

    <AlertMessage tipo="erro" :mensagem="erro" />
    <AlertMessage tipo="sucesso" :mensagem="sucesso" />

    <div class="card">
      <p v-if="carregando" class="muted">Carregando...</p>
      <table v-else class="table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Custo médio</th>
            <th>Preço venda</th>
            <th>Estoque</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in produtos" :key="item.id">
            <td>{{ item.nome }}</td>
            <td>{{ moeda(item.custo_medio) }}</td>
            <td>{{ moeda(item.preco_venda) }}</td>
            <td>{{ item.estoque }}</td>
            <td class="inline">
              <button type="button" class="btn btn-outline" @click="abrirModalEdicao(item)">
                Editar
              </button>
              <button type="button" class="btn btn-danger" @click="abrirModalExclusao(item)">
                Excluir
              </button>
            </td>
          </tr>
          <tr v-if="!produtos.length && !carregando">
            <td colspan="5" class="muted">Nenhum produto cadastrado.</td>
          </tr>
        </tbody>
      </table>
      <PaginacaoTabela :meta="meta" @mudar-pagina="carregar" />
    </div>

    <ModalBase :aberto="modalAberto" :titulo="tituloModal" @fechar="fecharModal">
      <form class="stack" @submit.prevent="salvar">
        <div class="field">
          <label>Nome</label>
          <input v-model="form.nome" required minlength="3" />
        </div>
        <div class="field">
          <label>Preço de venda (Preço sugerido)</label>
          <input v-model="form.preco_venda" required min="0.01" step="0.01" type="number" />
        </div>
        <div class="inline modal-acoes">
          <button class="btn btn-outline" type="button" @click="fecharModal">Cancelar</button>
          <button class="btn" :disabled="salvando" type="submit">
            {{ salvando ? 'Salvando...' : editandoId ? 'Salvar' : 'Cadastrar' }}
          </button>
        </div>
      </form>
    </ModalBase>

    <ModalBase :aberto="modalExclusaoAberto" titulo="Excluir produto" @fechar="fecharModalExclusao">
      <p>
        Confirma a exclusão do produto <strong>{{ produtoParaExcluir?.nome }}</strong>?
      </p>
      <template #rodape>
        <div class="inline modal-acoes">
          <button class="btn btn-outline" type="button" @click="fecharModalExclusao">Cancelar</button>
          <button class="btn btn-danger" type="button" @click="confirmarExclusao">Excluir</button>
        </div>
      </template>
    </ModalBase>
  </section>
</template>

<style scoped>
.modal-acoes {
  justify-content: flex-end;
  margin-top: 8px;
}
</style>
