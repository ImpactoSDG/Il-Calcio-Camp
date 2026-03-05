<template>
  <Teleport to="body">
    <div class="modal fade" tabindex="-1" ref="modalElement">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header" :class="headerClass">
            <h5 class="modal-title">
              <i :class="iconClass" class="me-2"></i>{{ title }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center py-4">
            <slot>
              <p class="mb-0 fs-5">{{ message }}</p>
            </slot>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-light px-4" @click="closeModal" :disabled="isLoading">
              Cancelar
            </button>
            <button type="button" class="btn" :class="confirmButtonClass" @click="onConfirm" :disabled="isLoading">
              <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
              {{ isLoading ? 'Procesando...' : confirmButtonText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
  modelValue: { 
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirmar Acción',
  },
  message: {
    type: String,
    default: '¿Estás seguro de que deseas realizar esta acción?',
  },
  confirmButtonText: {
    type: String,
    default: 'Confirmar',
  },
  variant: { 
    type: String,
    default: 'danger',
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const modalElement = ref(null);
const bsModal = ref(null);

onMounted(() => {
  if (modalElement.value) {
    bsModal.value = new Modal(modalElement.value, { backdrop: 'static', keyboard: false });
  }
});

watch(() => props.modelValue, (show) => {
  if (show) {
    bsModal.value?.show();
  } else {
    bsModal.value?.hide();
  }
});

const closeModal = () => {
  emit('update:modelValue', false);
};

const onConfirm = () => {
  emit('confirm');
};

const headerClass = computed(() => `variant-${props.variant}`);
const confirmButtonClass = computed(() => props.variant === 'danger' ? 'btn-danger px-4' : 'btn-primary-modern px-4');
const iconClass = computed(() => {
  if (props.variant === 'danger') return 'bi bi-exclamation-triangle-fill';
  if (props.variant === 'warning') return 'bi bi-exclamation-circle-fill';
  return 'bi bi-question-circle-fill';
});
</script>

<style scoped>
.modal {
  z-index: 1055 !important; /* era 1050 */
}
</style>