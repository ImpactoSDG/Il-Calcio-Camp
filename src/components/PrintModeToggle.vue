<template>
  <div v-if="canToggle" class="print-mode-toggle-container">
    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
      <div class="flex-grow-1">
        <h6 class="mb-1 fw-bold text-secondary small text-uppercase">Modo de Impresión</h6>
        <p class="mb-0 small text-muted">
          <i class="bi me-1" :class="isPrint ? 'bi-printer-fill' : 'bi-download'"></i>
          {{ isPrint ? 'Impresora Térmica' : 'Descargar PDF' }}
        </p>
      </div>
      
      <div class="form-check form-switch">
        <input 
          type="checkbox" 
          class="form-check-input" 
          :id="`printModeToggle-${_uid}`"
          :checked="isPrint"
          @change="toggleMode"
        />
        <label class="form-check-label" :for="`printModeToggle-${_uid}`"></label>
      </div>
    </div>
    
    <small class="text-muted d-block mt-2">
      <i class="bi bi-info-circle me-1"></i>
      Esta opción solo está disponible en modo desarrollo (localhost).
    </small>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePrintModeStore } from '@/stores/printModeStore';

const printStore = usePrintModeStore();

const canToggle = computed(() => printStore.canTogglePrintMode());
const isPrint = computed(() => printStore.isPrintMode());

const toggleMode = () => {
  printStore.togglePrintMode();
};
</script>

<style scoped>
.print-mode-toggle-container {
  margin: 1rem 0;
}

.form-check-input:checked {
  background-color: #198754;
  border-color: #198754;
}

.form-check-input {
  width: 2.5rem;
  height: 1.5rem;
}

.form-check-label {
  margin-top: 0.25rem;
}
</style>
