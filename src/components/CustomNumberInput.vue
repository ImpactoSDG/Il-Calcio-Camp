<template>
  <div class="input-group">
    <input
      type="text"
      :class="['form-control text-end', inputClass]"
      :placeholder="placeholder"
      :value="formattedValue"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      :disabled="disabled"
      :readonly="readonly"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { formatNumber, parseNumber } from '../utils/formatters';

const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  placeholder: { type: String, default: '0' },
  decimals: { type: Number, default: 0 },
  inputClass: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false }
});

const emit = defineEmits(['update:modelValue']);

// Local state for internal value
const internalValue = ref(props.modelValue);
const isEditing = ref(false);

// Format internal value for display
const formattedValue = computed(() => {
  if (isEditing.value) {
    // Si no hay valor, devolver vacío
    if (internalValue.value === null || internalValue.value === undefined || internalValue.value === '') return '';
    
    // Formatear con separadores de miles mientras se edita (estilo real-time)
    const valStr = internalValue.value.toString().replace('.', ',');
    const [entero, decimal] = valStr.split(',');
    
    const enteroFormateado = entero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return decimal !== undefined ? `${enteroFormateado},${decimal}` : enteroFormateado;
  }
  return formatNumber(internalValue.value, props.decimals);
});

const handleInput = (event) => {
  let value = event.target.value;
  
  // Guardar posición del cursor para evitar que salte al final
  const selectionStart = event.target.selectionStart;
  const oldLength = value.length;

  // Limpiar todo lo que no sea número o coma para procesar el valor puro
  const pureValue = value.replace(/[^\d,]/g, '').replace(',', '.');
  
  if (pureValue === '' || pureValue === '.') {
    internalValue.value = null;
    emit('update:modelValue', null);
    return;
  }

  const numericValue = parseFloat(pureValue);
  internalValue.value = numericValue;
  emit('update:modelValue', numericValue);

  // Ajustar posición del cursor después de que Vue actualice el DOM
  setTimeout(() => {
    const newLength = event.target.value.length;
    const diff = newLength - oldLength;
    event.target.setSelectionRange(selectionStart + diff, selectionStart + diff);
  }, 0);
};

const handleFocus = (event) => {
  isEditing.value = true;
  // Briefly select text on focus
  setTimeout(() => event.target.select(), 10);
};

const handleBlur = () => {
  isEditing.value = false;
};

// Sync internal value if external changes
watch(() => props.modelValue, (newVal) => {
  if (!isEditing.value) {
    internalValue.value = newVal;
  }
});
</script>

<style scoped>
.form-control:focus {
  z-index: 3;
}
</style>
