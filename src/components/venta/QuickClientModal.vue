<template>
  <Teleport to="body">
    <div v-if="modelValue" class="quick-client-modal-overlay">
      <div class="quick-client-modal-content animate-pop-in">
        <div class="modal-header border-0 p-4 pb-2 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-person-plus-fill fs-5"></i>
            </div>
            <h5 class="fw-bold mb-0 text-dark">NUEVO CLIENTE RÁPIDO</h5>
          </div>
          <button type="button" class="btn-close shadow-none" @click="close"></button>
        </div>

        <div class="modal-body p-4 pt-2">

          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary small mb-1">Nombre y Apellido <span class="text-danger">*</span></label>
            <input 
              ref="nameInputRef"
              v-model="nombre" 
              type="text" 
              class="form-control border-2 py-2 px-3 rounded-3" 
              placeholder="Ej: Juan Pérez"
              @input="onInputNombre"
              @keydown.enter.prevent="nextInput"
            />
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold text-secondary small mb-1">Teléfono (Opcional)</label>
            <input 
              ref="phoneInputRef"
              v-model="telefono" 
              type="text" 
              class="form-control border-2 py-2 px-3 rounded-3" 
              placeholder="Ej: 351-1234567"
              @keydown.enter.prevent="confirmarAccion"
            />
          </div>

          <!-- Alerta de Duplicados -->
          <div v-if="duplicados.length > 0" class="alert alert-warning border-0 rounded-4 shadow-sm animate-fade-in p-3">
            <div class="d-flex align-items-start gap-2 mb-2">
              <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
              <div>
                <div class="fw-bold small">Posibles duplicados detectados:</div>
                <div class="small opacity-75">Ya existen clientes con nombres similares.</div>
              </div>
            </div>
            <div class="list-group list-group-flush border rounded-3 overflow-hidden bg-white mb-3">
              <button 
                v-for="d in duplicados" 
                :key="d.id"
                type="button"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 small border-0 border-bottom"
                @click="seleccionarExistente(d)"
              >
                <span>{{ d.nombre_cliente }}</span>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2">Asignar este</span>
              </button>
            </div>
            <button 
              type="button" 
              class="btn btn-sm btn-outline-warning w-100 fw-bold border-2"
              @click="crearNuevoDeTodosModos"
            >
              CREAR CLIENTE NUEVO DE TODOS MODOS
            </button>
          </div>
        </div>

        <div class="modal-footer border-0 p-4 pt-0 gap-2">
          <button @click="close" type="button" class="btn btn-light px-4 py-2 rounded-3 fw-bold flex-fill">
            CANCELAR
          </button>
          <button 
            @click="crearNuevo" 
            type="button" 
            class="btn btn-primary px-4 py-2 rounded-3 fw-bold flex-fill"
            :disabled="!nombre.trim() || duplicados.length > 0"
          >
            CONFIRMAR
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  clientesExistentes: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const nombre = ref('');
const telefono = ref('');
const nameInputRef = ref(null);
const phoneInputRef = ref(null);
const duplicados = ref([]);

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    nombre.value = '';
    telefono.value = '';
    duplicados.value = [];
    nextTick(() => {
      nameInputRef.value?.focus();
    });
  }
});

const onInputNombre = () => {
  const val = nombre.value.trim().toLowerCase();
  if (val.length < 3) {
    duplicados.value = [];
    return;
  }

  duplicados.value = props.clientesExistentes.filter(c => 
    c.nombre_cliente.toLowerCase().includes(val) || 
    val.includes(c.nombre_cliente.toLowerCase())
  ).slice(0, 3);
};

const close = () => {
  emit('update:modelValue', false);
};

const seleccionarExistente = (cliente) => {
  emit('confirm', { ...cliente, isNew: false });
  close();
};

const crearNuevo = () => {
  if (!nombre.value.trim()) return;
  emit('confirm', { 
    id: 'temp-' + Date.now(), 
    nombre_cliente: nombre.value.trim(), 
    telefono: telefono.value.trim(),
    isNew: true 
  });
  close();
};

const nextInput = () => {
  if (nombre.value.trim()) {
    phoneInputRef.value?.focus();
  }
};

const crearNuevoDeTodosModos = () => {
  crearNuevo();
};

const confirmarAccion = () => {
  if (duplicados.value.length === 0) {
    crearNuevo();
  }
};
</script>

<style scoped>
.quick-client-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 100000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.quick-client-modal-content {
  background: white;
  width: 100%;
  max-width: 450px;
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
</style>