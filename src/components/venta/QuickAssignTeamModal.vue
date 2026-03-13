<template>
  <Teleport to="body">
    <div v-if="modelValue" class="quick-team-modal-overlay" @click.self="close">
      <div class="quick-team-modal-content animate-pop-in">
        <div class="modal-header border-0 p-4 pb-2 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-people-fill fs-5"></i>
            </div>
            <h5 class="fw-bold mb-0 text-dark">ASOCIAR EQUIPO</h5>
          </div>
          <button type="button" class="btn-close shadow-none" @click="close"></button>
        </div>

        <div class="modal-body p-4 pt-2">
          <p class="text-muted small mb-4">
            Selecciona el equipo que deseas asociar a <strong>{{ clienteNombre }}</strong>.
          </p>

          <div class="mb-4 position-relative">
            <label class="form-label fw-semibold text-secondary small mb-1">Buscar y Seleccionar Equipo <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-white border-2 border-end-0">
                <i class="bi bi-search"></i>
              </span>
              <input 
                ref="searchInputRef"
                v-model="searchQuery" 
                type="text" 
                class="form-control border-2 border-start-0 py-2 px-3 rounded-end-3" 
                placeholder="Escribe el nombre del equipo..."
                @input="filterEquipos"
              />
            </div>

            <!-- Lista de resultados -->
            <div v-if="filteredEquipos.length > 0 && searchQuery" class="list-group mt-2 border-2 shadow-sm rounded-3 overflow-hidden custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
              <button 
                v-for="e in filteredEquipos" 
                :key="e.id"
                type="button"
                class="list-group-item list-group-item-action py-2 px-3 small border-0 border-bottom d-flex justify-content-between align-items-center"
                @click="selectEquipo(e)"
              >
                <span>{{ e.nombre }}</span>
                <span class="text-muted fs-xs">{{ e.disciplina || '' }}</span>
              </button>
            </div>
            <div v-else-if="searchQuery && filteredEquipos.length === 0" class="p-3 text-center text-muted small border rounded-3 mt-2">
              No se encontraron equipos con ese nombre.
            </div>
          </div>

          <div v-if="selectedEquipo" class="bg-primary-subtle p-3 rounded-3 d-flex align-items-center justify-content-between animate-fade-in">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill text-primary"></i>
              <div>
                <div class="fw-bold text-dark small">{{ selectedEquipo.nombre }}</div>
                <div class="text-muted" style="font-size: 0.75rem;">Equipo seleccionado</div>
              </div>
            </div>
            <button @click="selectedEquipo = null" class="btn btn-sm btn-link link-danger p-0">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
        </div>

        <div class="modal-footer border-0 p-4 pt-0 gap-2">
          <button @click="close" type="button" class="btn btn-light px-4 py-2 rounded-3 fw-bold flex-fill" :disabled="isLoading">
            CANCELAR
          </button>
          <button 
            @click="confirmar" 
            type="button" 
            class="btn btn-primary px-4 py-2 rounded-3 fw-bold flex-fill"
            :disabled="!selectedEquipo || isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
            CONFIRMAR
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick, computed } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  clienteId: [Number, String],
  clienteNombre: String,
  equipos: {
    type: Array,
    default: () => []
  },
  isLoading: Boolean
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const searchQuery = ref('');
const searchInputRef = ref(null);
const selectedEquipo = ref(null);
const filteredEquipos = ref([]);

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    searchQuery.value = '';
    selectedEquipo.value = null;
    filteredEquipos.value = [];
    nextTick(() => {
      searchInputRef.value?.focus();
    });
  }
});

const filterEquipos = () => {
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) {
    filteredEquipos.value = [];
    return;
  }
  filteredEquipos.value = props.equipos.filter(e => 
    e.nombre.toLowerCase().includes(q)
  ).slice(0, 5);
};

const selectEquipo = (equipo) => {
  selectedEquipo.value = equipo;
  searchQuery.value = '';
  filteredEquipos.value = [];
};

const close = () => {
  emit('update:modelValue', false);
};

const confirmar = () => {
  if (!selectedEquipo.value) return;
  emit('confirm', selectedEquipo.value);
};
</script>

<style scoped>
.quick-team-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 100001;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.quick-team-modal-content {
  background: white;
  width: 100%;
  max-width: 400px;
  border-radius: 1.25rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  overflow: hidden;
}

.animate-pop-in {
  animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

.animate-fade-in {
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.fs-xs { font-size: 0.7rem; }

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}
</style>