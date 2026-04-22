<script setup>
defineProps({
  tipo: {
    type: String,
    default: 'sucesso',
  },
  mensagem: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div v-if="mensagem" :class="['alert', tipo]">{{ mensagem }}</div>
</template>

<style scoped>
.alert {
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 12px;
  border: 1px solid;
}

.sucesso {
  color: #065f46;
  background: #ecfdf5;
  border-color: #34d399;
}

.erro {
  color: #991b1b;
  background: #fef2f2;
  border-color: #fca5a5;
}
</style>
