<template>
  <thead :class="theadClass">
    <tr>
      <th
        v-for="col in columns"
        :key="col.key"
        :class="[col.thClass, col.sortable ? 'sortable-th' : '']"
        :style="col.thStyle"
        @click="col.sortable ? emit('sort', col.key) : undefined"
      >
        <span class="d-inline-flex align-items-center gap-1">
          <i v-if="col.icon" :class="col.icon"></i>
          {{ col.label }}
          <span v-if="col.sortable" class="sort-icon-wrapper">
            <i
              v-if="sortKey === col.key"
              :class="sortDir === 'asc' ? 'bi bi-caret-up-fill' : 'bi bi-caret-down-fill'"
              class="sort-active"
            ></i>
            <i v-else class="bi bi-caret-up-fill sort-idle"></i>
          </span>
        </span>
      </th>
    </tr>
  </thead>
</template>

<script>
import { ref } from 'vue'

/**
 * useSorting — Lógica de ordenamiento de tablas.
 * Exportada desde este archivo para centralizar todo en el mismo componente.
 *
 * @param {string} defaultKey - Columna inicial de ordenamiento (vacío = sin orden)
 * @param {string} defaultDir - Dirección inicial: 'asc' | 'desc'
 * @returns {{ sortKey, sortDir, handleSort, sortItems }}
 */
export function useSorting(defaultKey = '', defaultDir = 'asc') {
  const sortKey = ref(defaultKey)
  const sortDir = ref(defaultDir)

  function handleSort(key) {
    if (sortKey.value === key) {
      sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortDir.value = 'asc'
    }
  }

  function sortItems(items) {
    if (!sortKey.value || !items || items.length === 0) return items

    return [...items].sort((a, b) => {
      const valA = a[sortKey.value]
      const valB = b[sortKey.value]

      if (valA == null && valB == null) return 0
      if (valA == null) return 1
      if (valB == null) return -1

      const numA = Number(valA)
      const numB = Number(valB)
      if (!isNaN(numA) && !isNaN(numB) && valA !== '' && valB !== '') {
        return sortDir.value === 'asc' ? numA - numB : numB - numA
      }

      const strA = String(valA).toLowerCase()
      const strB = String(valB).toLowerCase()
      return sortDir.value === 'asc'
        ? strA.localeCompare(strB, 'es')
        : strB.localeCompare(strA, 'es')
    })
  }

  return { sortKey, sortDir, handleSort, sortItems }
}
</script>

<script setup>
/**
 * SortableTableHead — Encabezado de tabla reutilizable con soporte de ordenamiento.
 *
 * Props:
 *   columns    {Array}  - Definición de columnas:
 *                         { key, label, sortable?, thClass?, thStyle?, icon? }
 *   sortKey    {String} - Clave de columna actualmente ordenada
 *   sortDir    {String} - Dirección actual: 'asc' | 'desc'
 *   theadClass {String} - Clases CSS para el <thead> (default: 'bg-light')
 *
 * Emits:
 *   sort(key) - Emitido al hacer click en un encabezado sortable
 *
 * Named export:
 *   import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue'
 */
defineProps({
  columns: { type: Array, required: true },
  sortKey: { type: String, default: '' },
  sortDir: { type: String, default: 'asc' },
  theadClass: { type: String, default: 'bg-light' },
})

const emit = defineEmits(['sort'])
</script>

<style scoped>
.sortable-th {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.15s ease;
}

.sortable-th:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.sort-icon-wrapper {
  display: inline-flex;
  align-items: center;
  font-size: 0.65rem;
  line-height: 1;
}

.sort-active {
  color: var(--bs-primary, #0d6efd);
}

.sort-idle {
  opacity: 0.25;
}
</style>
