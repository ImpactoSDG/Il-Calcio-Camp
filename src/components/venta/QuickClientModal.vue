<template>
  <Teleport to="body">
    <div v-if="modelValue" class="quick-client-modal-overlay">
      <div class="quick-client-modal-content animate-pop-in">
        <div class="modal-header border-0 p-4 pb-2 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi" :class="isEditing ? 'bi-pencil-fill' : 'bi-person-plus-fill'"></i>
            </div>
            <h5 class="fw-bold mb-0 text-dark">{{ isEditing ? 'EDITAR CLIENTE' : 'NUEVO CLIENTE RÁPIDO' }}</h5>
          </div>
          <button type="button" class="btn-close shadow-none" @click="close"></button>
        </div>

        <div class="modal-body p-4 pt-2">

          <div class="mb-3">
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

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small mb-1">
                {{ Number(id_condicion_iva_receptor) === 1 ? 'CUIT (11 dígitos)' : 'DNI / CUIT' }}
                <span v-if="Number(id_condicion_iva_receptor) === 1" class="text-danger fw-bold">*</span>
                <span v-else class="text-muted opacity-50 fw-normal ms-1">(Opcional)</span>
              </label>
              <input 
                ref="dniInputRef"
                v-model="dni_cuit" 
                type="text" 
                class="form-control border-2 py-2 px-3 rounded-3" 
                :class="{ 'border-danger': Number(id_condicion_iva_receptor) === 1 && String(dni_cuit).replace(/[^0-9]/g, '').length !== 11 }"
                placeholder="Ej: 20346693364"
                @keydown.enter.prevent="nextPhoneInput"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary small mb-1">Teléfono <span class="text-muted opacity-50 fw-normal ms-1">(Opcional)</span></label>
              <input 
                ref="phoneInputRef"
                v-model="telefono" 
                type="text" 
                class="form-control border-2 py-2 px-3 rounded-3" 
                placeholder="Ej: 351-1234567"
                @keydown.enter.prevent="confirmarAccion"
              />
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-secondary small mb-1">Condición IVA <span class="text-danger">*</span></label>
            <select v-model.number="id_condicion_iva_receptor" class="form-select border-2 py-2 px-3 rounded-3">
              <option v-for="c in filteredCondiciones" :key="c.id" :value="Number(c.id)">{{ c.descripcion_condicion }}</option>
            </select>
          </div>

          <!-- Alerta de Duplicados -->
          <div v-if="duplicados.length > 0 && !isEditing" class="alert alert-warning border-0 rounded-4 shadow-sm animate-fade-in p-3">
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
            :disabled="!canConfirm"
            :title="!canConfirm ? (Number(id_condicion_iva_receptor) === 1 && String(dni_cuit).replace(/[^0-9]/g, '').length !== 11 ? 'CUIT de 11 dígitos obligatorio para RI' : 'Complete los campos obligatorios') : 'Confirmar'"
          >
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
  clientesExistentes: {
    type: Array,
    default: () => []
  },
  condicionesIva: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const isEditing = ref(false);
const nombre = ref('');
const dni_cuit = ref('');
const telefono = ref('');
const id_condicion_iva_receptor = ref(2); // Consumidor Final por defecto

const nameInputRef = ref(null);
const dniInputRef = ref(null);
const phoneInputRef = ref(null);
const duplicados = ref([]);

const filteredCondiciones = computed(() => {
  return props.condicionesIva.filter(c => {
    const desc = c.descripcion_condicion.toLowerCase();
    return desc.includes('responsable inscripto') || desc.includes('consumidor final');
  });
});

const canConfirm = computed(() => {
  if (!nombre.value.trim()) return false;
  
  // Si es Responsable Inscripto (ID 1), CUIT (11 dígitos) es obligatorio para AFIP
  if (Number(id_condicion_iva_receptor.value) === 1) {
    const cuitLimpio = String(dni_cuit.value || '').replace(/[^0-9]/g, '');
    if (cuitLimpio.length !== 11) return false;
  }

  // Si no estamos editando, no podemos confirmar si hay duplicados detectados (lógica previa del componente)
  if (!isEditing.value && duplicados.value.length > 0) return false;

  return true;
});

watch(() => id_condicion_iva_receptor.value, (newVal) => {
  console.log('Condición de IVA cambiada a:', newVal);
});

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    if (props.initialData) {
      isEditing.value = true;
      nombre.value = props.initialData.nombre_cliente || '';
      dni_cuit.value = props.initialData.dni_cliente || props.initialData.cuit_dni || '';
      telefono.value = props.initialData.telefono || '';
      id_condicion_iva_receptor.value = props.initialData.id_condicion_iva_receptor || 2;
    } else {
      isEditing.value = false;
      nombre.value = '';
      dni_cuit.value = '';
      telefono.value = '';
      id_condicion_iva_receptor.value = 2;
    }
    duplicados.value = [];
    nextTick(() => {
      nameInputRef.value?.focus();
    });
  }
});

const close = () => {
  emit('update:modelValue', false);
};

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

const seleccionarExistente = (cliente) => {
  emit('confirm', { ...cliente, isNew: false });
  close();
};

const confirmarAccion = () => {
  if (!nombre.value.trim()) return;
  
  const formattedDni = String(dni_cuit.value || '').replace(/[^0-9]/g, '');
  const data = {
    nombre_cliente: nombre.value.trim(),
    dni_cliente: formattedDni,
    cuit_dni: formattedDni,
    telefono: String(telefono.value || '').trim(),
    id_condicion_iva_receptor: id_condicion_iva_receptor.value,
  };
  
  if (isEditing.value) {
    data.id = props.initialData.id;
    data.isUpdate = true;
  } else {
    data.id = 'temp-' + Date.now();
    data.isNew = true;
  }
  
  emit('confirm', data);
  close();
};

const crearNuevo = () => confirmarAccion();
const crearNuevoDeTodosModos = () => confirmarAccion();

const nextInput = () => {
  if (nombre.value.trim()) {
    dniInputRef.value?.focus();
  }
};

const nextPhoneInput = () => {
  phoneInputRef.value?.focus();
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