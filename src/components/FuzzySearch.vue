<template>
  <div class="search-container" ref="containerRef">
    <input
      type="text"
      ref="searchInput"
      v-model="query"
      :placeholder="placeholder"
      @input="onSearch"
      @focus="onFocus"
      @click="onFocus"
      @keydown.enter.prevent="onEnter"
      class="form-control"
    />

    <div 
      v-if="results.length > 0 && (query !== '' || showAllOnFocus)" 
      class="list-group position-absolute w-100 shadow-sm z-3"
    >
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
import { ref, watch, onMounted, onUnmounted } from 'vue';
import Fuse from 'fuse.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  data: { type: Array, default: () => [] },
  keys: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Buscar...' },
  threshold: { type: Number, default: 0.3 },
  showAllOnFocus: { type: Boolean, default: false },
  maxResults: { type: Number, default: 10 },
});

const emit = defineEmits(['update:modelValue', 'selected']);

const searchInput = ref(null);
const containerRef = ref(null);
const query = ref(props.modelValue);
const results = ref([]);

let fuse = null;

const focus = () => {
  if (searchInput.value) {
    searchInput.value.focus();
  }
};

defineExpose({ focus });

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
  
  if (query.value.trim() === '') {
    if (props.showAllOnFocus && props.data.length > 0) {
      results.value = props.data.slice(0, props.maxResults).map(item => ({ item }));
    } else {
      results.value = [];
    }
    return;
  }

  if (!fuse) {
    results.value = [];
    return;
  }

  if (props.data.length > 0 && props.keys.length > 0) {
    results.value = fuse.search(query.value).slice(0, props.maxResults);
  }
};

const onFocus = () => {
  if (!props.showAllOnFocus && query.value.trim() === '') return;
  onSearch();
};

const onEnter = () => {
  if (results.value.length > 0) {
    selectItem(results.value[0].item);
  }
};

const selectItem = (item) => {
  const keyToUse = props.keys.find(k => k === 'nombre') || props.keys[0];
  query.value = item[keyToUse] || ''; 
  results.value = [];
  emit('selected', item);
  emit('update:modelValue', query.value);
};

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    results.value = [];
  }
};

onMounted(() => {
  setupFuse();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

watch(() => props.data, () => {
  setupFuse();
}, { deep: true });
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