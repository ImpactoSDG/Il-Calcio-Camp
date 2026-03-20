<template>
  <div class="container py-4 animate-fade-in">
    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn btn-link text-secondary p-0 me-3" title="Volver">
          <i class="bi bi-arrow-left fs-4"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary text-uppercase">{{ nombreModulo }}</h1>
      </div>
      <div class="d-flex gap-2" v-if="subModulos.length > 1">
        <template v-if="modoOrdenar">
          <button @click="guardarOrden" class="btn btn-sm btn-success" :disabled="guardando">
            <i class="bi bi-check-lg me-1"></i>{{ guardando ? 'Guardando...' : 'Guardar orden' }}
          </button>
          <button @click="cancelarOrden" class="btn btn-sm btn-outline-secondary" :disabled="guardando">
            <i class="bi bi-x-lg me-1"></i>Cancelar
          </button>
        </template>
        <button v-else @click="iniciarOrden" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrows-move me-1"></i>Ordenar
        </button>
      </div>
    </div>

    <div v-if="modoOrdenar" class="alert alert-info py-2 px-3 mb-3 small">
      <i class="bi bi-info-circle me-1"></i>
      Arrastrá las tarjetas para cambiar el orden.
    </div>

    <!-- Modo ordenar -->
    <div v-if="modoOrdenar" class="card-grid">
      <div
        v-for="(hijo, index) in subModulosOrdenables"
        :key="hijo.id"
        class="action-card-wrapper draggable-card"
        draggable="true"
        @dragstart="onDragStart($event, index)"
        @dragover.prevent="onDragOver($event, index)"
        @dragleave="onDragLeave"
        @drop.stop="onDrop($event, index)"
        @dragend="onDragEnd"
        :class="{ 'drag-over': dragOver.index === index, 'is-dragging': draggingIndex === index }"
      >
        <div class="drag-handle">
          <i class="bi bi-grip-vertical"></i>
        </div>
        <div
          class="action-card"
          :style="`--card-color: ${hijo.bg || '#6c757d'};`"
        >
          <div class="icon-container" style="background-color: var(--card-color);">
            <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
          </div>
          <span class="card-label">{{ hijo.nombre }}</span>
        </div>
      </div>
    </div>

    <!-- Modo normal -->
    <div v-else class="card-grid">
      <template v-for="hijo in subModulos" :key="hijo.id">
        <!-- Link Externo -->
        <a
          v-if="hijo.ruta && (hijo.ruta.startsWith('http://') || hijo.ruta.startsWith('https://'))"
          :href="hijo.ruta"
          target="_blank"
          class="action-card"
          :style="`--card-color: ${hijo.bg || '#6c757d'};`"
        >
          <div class="icon-container" style="background-color: var(--card-color);">
            <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
          </div>
          <span class="card-label">{{ hijo.nombre }}</span>
        </a>

        <!-- Link Interno -->
        <router-link
          v-else
          :to="hijo.ruta"
          class="action-card"
          :style="`--card-color: ${hijo.bg || '#6c757d'};`"
        >
          <div class="icon-container" style="background-color: var(--card-color);">
            <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
          </div>
          <span class="card-label">{{ hijo.nombre }}</span>
        </router-link>
      </template>

      <div v-if="subModulos.length === 0" class="no-modules w-100 py-5 text-center text-muted">
        <i class="bi bi-exclamation-circle d-block mb-2 fs-2"></i>
        No tienes sub-módulos asignados en esta sección.
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useUserStore } from '@/stores/userStore';

const route = useRoute();
const userStore = useUserStore();

// Obtenemos el ID del módulo actual desde el meta de la ruta o desde los parámetros
const idModuloActual = computed(() => {
  return Number(route.meta.useParamId ? route.params.id : route.meta.idModulo);
});

const moduloActual = computed(() => {
  return userStore.user?.modulos?.find(m => Number(m.id) === idModuloActual.value);
});

const nombreModulo = computed(() => moduloActual.value?.nombre || 'MENÚ');

const subModulos = computed(() => {
  const modulos = userStore.user?.modulos || [];
  return modulos
    .filter(m => Number(m.id_padre) === idModuloActual.value)
    .sort((a, b) => {
      const oa = a.orden_usuario != null ? Number(a.orden_usuario) : (Number(a.orden_visualizacion) || 0);
      const ob = b.orden_usuario != null ? Number(b.orden_usuario) : (Number(b.orden_visualizacion) || 0);
      return oa - ob;
    });
});

// ── Ordenar ────────────────────────────────────────────────────────────────
const modoOrdenar = ref(false);
const guardando = ref(false);
const subModulosOrdenables = ref([]);
const dragInfo = ref({ fromIndex: null });
const dragOver = ref({ index: null });
const draggingIndex = ref(null);

function iniciarOrden() {
  subModulosOrdenables.value = subModulos.value.map(m => ({ ...m }));
  modoOrdenar.value = true;
}

function cancelarOrden() {
  modoOrdenar.value = false;
  subModulosOrdenables.value = [];
  dragInfo.value = { fromIndex: null };
  dragOver.value = { index: null };
  draggingIndex.value = null;
}

async function guardarOrden() {
  guardando.value = true;
  try {
    const ordenes = subModulosOrdenables.value.map((mod, index) => ({
      id_modulo: mod.id,
      orden: index + 1,
    }));
    await userStore.saveOrdenModulos(ordenes);
    modoOrdenar.value = false;
    subModulosOrdenables.value = [];
  } catch {
    // El error ya se logueó en el store
  } finally {
    guardando.value = false;
  }
}

// ── Drag-and-Drop ──────────────────────────────────────────────────────────
function onDragStart(event, index) {
  dragInfo.value = { fromIndex: index };
  event.dataTransfer.effectAllowed = 'move';

  // Usar solo la tarjeta interior como ghost para evitar capturar contenido extra
  const cardEl = event.currentTarget.querySelector('.action-card');
  const ghost = cardEl.cloneNode(true);
  ghost.style.cssText = `position:fixed;top:-9999px;left:-9999px;width:${cardEl.offsetWidth}px;height:${cardEl.offsetHeight}px;pointer-events:none;opacity:0.9;border-radius:12px;`;
  document.body.appendChild(ghost);
  event.dataTransfer.setDragImage(ghost, cardEl.offsetWidth / 2, cardEl.offsetHeight / 2);

  setTimeout(() => {
    document.body.removeChild(ghost);
    draggingIndex.value = index;
  }, 0);
}

function onDragEnd() {
  draggingIndex.value = null;
  dragOver.value = { index: null };
}

function onDragOver(event, index) {
  event.preventDefault();
  dragOver.value = { index };
}

function onDragLeave() {
  dragOver.value = { index: null };
}

function onDrop(event, toIndex) {
  event.preventDefault();
  dragOver.value = { index: null };

  const { fromIndex } = dragInfo.value;
  if (fromIndex === null || fromIndex === toIndex) return;

  const mods = subModulosOrdenables.value;
  const [moved] = mods.splice(fromIndex, 1);
  mods.splice(toIndex, 0, moved);

  dragInfo.value = { fromIndex: null };
  draggingIndex.value = null;
}
</script>

<style scoped>
.text-secondary {
  color: #6c757d !important;
}

/* Modo ordenar */
.draggable-card {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
  user-select: none;
}

.draggable-card:active {
  cursor: grabbing;
}

.draggable-card.drag-over {
  transform: scale(1.03);
  box-shadow: 0 0 0 2px #0d6efd;
  border-radius: 12px;
}

.drag-handle {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 10;
  background: rgba(255, 255, 255, 0.85);
  border-radius: 50%;
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 0.85rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  pointer-events: none;
}

.is-dragging {
  opacity: 0;
}
</style>
