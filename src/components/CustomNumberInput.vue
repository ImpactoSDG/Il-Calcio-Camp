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
    // Show comma for decimals during edit
    return internalValue.value === null ? '' : internalValue.value.toString().replace('.', ',');
  }
  return formatNumber(internalValue.value, props.decimals);
});

const handleInput = (event) => {
  let value = event.target.value;
  
  // Basic validation: allow numbers, dot/comma
  value = value.replace(/[^0-9,-]/g, '');
  
  // Ensure single comma (or dot which gets replaced)
  value = value.replace('.', ',');
  const parts = value.split(',');
  if (parts.length > 2) {
    value = parts[0] + ',' + parts.slice(1).join('');
  }

  internalValue.value = parseNumber(value);
  emit('update:modelValue', internalValue.value);
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
