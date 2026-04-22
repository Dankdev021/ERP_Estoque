<script setup>
defineProps({
  aberto: {
    type: Boolean,
    default: false,
  },
  titulo: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['fechar'])

function fechar() {
  emit('fechar')
}
</script>

<template>
  <Teleport to="body">
    <div v-if="aberto" class="modal-root">
      <div class="modal-backdrop" @click.self="fechar" />
      <div class="modal-panel" role="dialog" aria-modal="true">
        <div class="modal-header">
          <h3>{{ titulo }}</h3>
          <button type="button" class="modal-close" aria-label="Fechar" @click="fechar">×</button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
        <div v-if="$slots.rodape" class="modal-footer">
          <slot name="rodape" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-root {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 16px;
  box-sizing: border-box;
}

.modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 23, 42, 0.32);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
}

.modal-panel {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 640px;
  max-height: min(90vh, 800px);
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid var(--color-border, #dce5dd);
  border-radius: 12px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--color-border, #dce5dd);
  flex-shrink: 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text, #0f172a);
}

.modal-close {
  border: none;
  background: transparent;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
  color: var(--color-muted, #475569);
  padding: 0 4px;
}

.modal-body {
  padding: 16px 18px;
  overflow-y: auto;
  flex: 1;
  min-height: 0;
}

.modal-footer {
  padding: 12px 18px 16px;
  border-top: 1px solid var(--color-border, #dce5dd);
  flex-shrink: 0;
}
</style>
