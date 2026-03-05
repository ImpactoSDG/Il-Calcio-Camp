<template>
  <div class="search-container">
    <input
      type="text"
      v-model="query"
      :placeholder="placeholder"
      @input="onSearch"
      class="form-control"
    />

    <div v-if="results.length > 0 && query !== ''" class="list-group position-absolute w-100 shadow-sm z-3">
      <button 
        v-for="(result, index) in results" 
        :key="index"
        type="button"
        class="list-group-item list-group-item-action text-start"
        @click="selectItem(result.item)"
      >
        <slot :item="result.item">
          {{ result.item }}
        </slot>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, nextTick } from 'vue';
import Fuse from 'fuse.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  data: { type: Array, default: () => [] },
  keys: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Buscar...' },
  threshold: { type: Number, default: 0.3 }
});

const emit = defineEmits(['update:modelValue', 'selected']);

const query = ref(props.modelValue);
const results = ref([]);
let fuse = null;

// Sincronizar query interna con modelValue propeado
watch(() => props.modelValue, (newVal) => {
  query.value = newVal;
});

const setupFuse = () => {
  if (props.data && props.data.length > 0 && props.keys && props.keys.length > 0) {
    fuse = new Fuse(props.data, {
      keys: props.keys,
      threshold: props.threshold,
      distance: 100,
      includeScore: true
    });
  }
};

const onSearch = () => {
  emit('update:modelValue', query.value);
  if (query.value.trim() === '' || !fuse) {
    results.value = [];
    return;
  }
  // Solo buscar si hay datos y llaves para Fuse
  if (props.data.length > 0 && props.keys.length > 0) {
    results.value = fuse.search(query.value).slice(0, 10);
  }
};

const selectItem = (item) => {
  query.value = '';
  results.value = [];
  emit('selected', item);
};

watch(() => props.data, () => {
  setupFuse();
  if (query.value) onSearch();
}, { deep: true });

onMounted(() => setupFuse());
</script>

<style scoped>
.search-container { 
  position: relative; 
  width: 100%; 
}

.list-group {
  max-height: 300px;
  overflow-y: auto;
  margin-top: 2px;
}

.z-3 {
  z-index: 1050;
}
</style>