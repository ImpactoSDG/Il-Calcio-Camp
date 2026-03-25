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
          <button
            @click="cambiarModoOrden"
            class="btn btn-sm"
            :class="tipoOrden === 'orden_submodulos' ? 'btn-info' : 'btn-light'"
            title="Ordenar submódulos"
          >
            <i class="bi bi-boxes me-1"></i>Submódulos
          </button>
          <button
            @click="cambiarModoOrden"
            class="btn btn-sm"
            :class="tipoOrden === 'orden_categorias' ? 'btn-info' : 'btn-light'"
            title="Ordenar categorías"
          >
            <i class="bi bi-folder2-open me-1"></i>Categorías
          </button>
          <div class="vr"></div>
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
      {{ tipoOrden === 'orden_submodulos' ? 'Arrastrá las tarjetas para cambiar el orden de submódulos.' : 'Arrastrá las categorías para cambiar su orden.' }}
    </div>

    <!-- Modo ordenar submódulos -->
    <div v-if="modoOrdenar && tipoOrden === 'orden_submodulos'" class="card-grid">
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

    <!-- Modo ordenar categorías -->
    <div v-if="modoOrdenar && tipoOrden === 'orden_categorias'" class="mb-5">
      <div
        v-for="(grupo, index) in categoriasOrdenables"
        :key="grupo.categoria"
        class="category-section-draggable draggable-category"
        draggable="true"
        @dragstart="onDragStartCategoria($event, index)"
        @dragover.prevent="onDragOverCategoria($event, index)"
        @dragleave="onDragLeaveCategoria"
        @drop.stop="onDropCategoria($event, index)"
        @dragend="onDragEndCategoria"
        :class="{ 'drag-over': dragOverCategoria.index === index, 'is-dragging': draggingCategoryIndex === index }"
      >
        <div class="drag-handle-categoria">
          <i class="bi bi-grip-vertical"></i>
        </div>
        <h2 class="h6 fw-bold text-uppercase text-secondary mb-3 ps-2" style="border-left: 4px solid var(--color-primary, #00558c); padding-left: 0.75rem;">
          {{ grupo.categoria || 'Sin categoría' }}
          <span class="badge bg-secondary ms-2">{{ grupo.articulos.length }}</span>
        </h2>
      </div>
    </div>

    <!-- Modo normal -->
    <div v-else-if="!modoOrdenar">
      <!-- Agrupar por categorías -->
      <template v-if="subModulosPorCategoria.length > 0">
        <div v-for="grupo in subModulosPorCategoria" :key="grupo.categoria" class="mb-5">
          <h2 class="h6 fw-bold text-uppercase text-secondary mb-3 ps-2" style="border-left: 4px solid var(--color-primary, #00558c); padding-left: 0.75rem;">
            {{ grupo.categoria || 'Sin categoría' }}
          </h2>
          <div class="card-grid">
            <template v-for="hijo in grupo.articulos" :key="hijo.id">
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
                :to="generarRuta(hijo)"
                class="action-card"
                :style="`--card-color: ${hijo.bg || '#6c757d'};`"
              >
                <div class="icon-container" style="background-color: var(--card-color);">
                  <i :class="hijo.icon || 'bi bi-app-indicator'"></i>
                </div>
                <span class="card-label">{{ hijo.nombre }}</span>
              </router-link>
            </template>
          </div>
        </div>
      </template>

      <div v-else class="no-modules w-100 py-5 text-center text-muted">
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

// Obtenemos el nombre del módulo desde los parámetros
const nombreModuloParam = computed(() => {
  return route.params.nombre;
});

const moduloActual = computed(() => {
  return userStore.user?.modulos?.find(m => 
    m.nombre.toLowerCase().replace(/\s+/g, '-') === nombreModuloParam.value.toLowerCase()
  );
});

const idModuloActual = computed(() => {
  return Number(moduloActual.value?.id);
});

const nombreModulo = computed(() => moduloActual.value?.nombre || 'MENÚ');

// ── Almacenamiento de orden de categorías ──────────────────────────────────
const storageKeyCategories = computed(() => `categorias_orden_${idModuloActual.value}`);

const loadCategoriesOrder = () => {
  try {
    const stored = localStorage.getItem(storageKeyCategories.value);
    return stored ? JSON.parse(stored) : [];
  } catch {
    return [];
  }
};

const saveCategoriesOrderLocal = () => {
  const orden = categoriasOrdenables.value.map(g => g.categoria);
  localStorage.setItem(storageKeyCategories.value, JSON.stringify(orden));
};

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

// Agrupar submódulos por categoría
const subModulosPorCategoria = computed(() => {
  const grupos = {};
  subModulos.value.forEach(modulo => {
    const cat = modulo.categoria || 'General';
    if (!grupos[cat]) {
      grupos[cat] = [];
    }
    grupos[cat].push(modulo);
  });
  
  let categorias = Object.keys(grupos).sort().map(cat => ({
    categoria: cat,
    articulos: grupos[cat]
  }));

  // Aplicar orden guardado en localStorage
  const ordenGuardado = loadCategoriesOrder();
  if (ordenGuardado.length > 0) {
    const map = new Map(categorias.map(g => [g.categoria, g]));
    const ordenado = [];
    
    // Primero las categorías en orden guardado
    for (const cat of ordenGuardado) {
      if (map.has(cat)) {
        ordenado.push(map.get(cat));
        map.delete(cat);
      }
    }
    
    // Luego las categorías nuevas (que no estaban guardadas)
    for (const grupo of map.values()) {
      ordenado.push(grupo);
    }
    
    categorias = ordenado;
  }
  
  return categorias;
});

// Función para generar rutas
const generarRuta = (modulo) => {
  if (!modulo.ruta) return '#';
  
  // Si es un link interno que apunta a /submenu/..., cambiar a usar nombre
  if (modulo.ruta && modulo.ruta.startsWith('/submenu/')) {
    const nombreSubmenu = modulo.nombre.toLowerCase().replace(/\s+/g, '-');
    return `/submenu/${nombreSubmenu}`;
  }
  
  return modulo.ruta;
};

// ── Ordenar ────────────────────────────────────────────────────────────────
const modoOrdenar = ref(false);
const tipoOrden = ref('orden_submodulos'); // 'orden_submodulos' o 'orden_categorias'
const guardando = ref(false);
const subModulosOrdenables = ref([]);
const categoriasOrdenables = ref([]);

// Drag-and-drop para submódulos
const dragInfo = ref({ fromIndex: null });
const dragOver = ref({ index: null });
const draggingIndex = ref(null);

// Drag-and-drop para categorías
const dragInfoCategoria = ref({ fromIndex: null });
const dragOverCategoria = ref({ index: null });
const draggingCategoryIndex = ref(null);

function cambiarModoOrden() {
  tipoOrden.value = tipoOrden.value === 'orden_submodulos' ? 'orden_categorias' : 'orden_submodulos';
  
  if (tipoOrden.value === 'orden_categorias') {
    // Cargar orden de categorías guardado
    const ordenGuardado = loadCategoriesOrder();
    if (ordenGuardado.length > 0) {
      const map = new Map(categoriasOrdenables.value.map(g => [g.categoria, g]));
      categoriasOrdenables.value = ordenGuardado
        .map(cat => map.get(cat))
        .filter(Boolean);
      // Agregar categorías nuevas que no estaban guardadas
      subModulosPorCategoria.value.forEach(g => {
        if (!categoriasOrdenables.value.find(c => c.categoria === g.categoria)) {
          categoriasOrdenables.value.push(g);
        }
      });
    }
  }
}

function iniciarOrden() {
  subModulosOrdenables.value = subModulos.value.map(m => ({ ...m }));
  categoriasOrdenables.value = subModulosPorCategoria.value.map(g => ({ ...g, articulos: [...g.articulos] }));
  tipoOrden.value = 'orden_submodulos';
  modoOrdenar.value = true;
}

function cancelarOrden() {
  modoOrdenar.value = false;
  tipoOrden.value = 'orden_submodulos';
  subModulosOrdenables.value = [];
  categoriasOrdenables.value = [];
  dragInfo.value = { fromIndex: null };
  dragOver.value = { index: null };
  draggingIndex.value = null;
  dragInfoCategoria.value = { fromIndex: null };
  dragOverCategoria.value = { index: null };
  draggingCategoryIndex.value = null;
}

async function guardarOrden() {
  guardando.value = true;
  try {
    if (tipoOrden.value === 'orden_submodulos') {
      // Guardar orden de submódulos
      const ordenes = subModulosOrdenables.value.map((mod, index) => ({
        id_modulo: mod.id,
        orden: index + 1,
      }));
      await userStore.saveOrdenModulos(ordenes);
    } else {
      // Guardar orden de categorías en localStorage
      saveCategoriesOrderLocal();
    }
    modoOrdenar.value = false;
    tipoOrden.value = 'orden_submodulos';
    subModulosOrdenables.value = [];
    categoriasOrdenables.value = [];
  } catch {
    // El error ya se logueó en el store
  } finally {
    guardando.value = false;
  }
}

// ── Drag-and-Drop Submódulos ──────────────────────────────────────────────
function onDragStart(event, index) {
  dragInfo.value = { fromIndex: index };
  event.dataTransfer.effectAllowed = 'move';

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

// ── Drag-and-Drop Categorías ──────────────────────────────────────────────
function onDragStartCategoria(event, index) {
  dragInfoCategoria.value = { fromIndex: index };
  event.dataTransfer.effectAllowed = 'move';

  const sectionEl = event.currentTarget;
  const ghost = sectionEl.cloneNode(true);
  ghost.style.cssText = `position:fixed;top:-9999px;left:-9999px;width:${sectionEl.offsetWidth}px;height:${sectionEl.offsetHeight}px;pointer-events:none;opacity:0.9;border-radius:8px;`;
  document.body.appendChild(ghost);
  event.dataTransfer.setDragImage(ghost, sectionEl.offsetWidth / 2, 20);

  setTimeout(() => {
    document.body.removeChild(ghost);
    draggingCategoryIndex.value = index;
  }, 0);
}

function onDragEndCategoria() {
  draggingCategoryIndex.value = null;
  dragOverCategoria.value = { index: null };
}

function onDragOverCategoria(event, index) {
  event.preventDefault();
  dragOverCategoria.value = { index };
}

function onDragLeaveCategoria() {
  dragOverCategoria.value = { index: null };
}

function onDropCategoria(event, toIndex) {
  event.preventDefault();
  dragOverCategoria.value = { index: null };

  const { fromIndex } = dragInfoCategoria.value;
  if (fromIndex === null || fromIndex === toIndex) return;

  const cats = categoriasOrdenables.value;
  const [moved] = cats.splice(fromIndex, 1);
  cats.splice(toIndex, 0, moved);

  dragInfoCategoria.value = { fromIndex: null };
  draggingCategoryIndex.value = null;
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

/* Modo ordenar categorías */
.category-section-draggable {
  cursor: grab;
  transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
  user-select: none;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  margin-bottom: 1rem;
  border-left: 5px solid var(--color-primary, #00558c);
  position: relative;
}

.category-section-draggable:active {
  cursor: grabbing;
}

.category-section-draggable.drag-over {
  transform: scale(1.02);
  box-shadow: 0 0 0 2px #0d6efd, 0 4px 12px rgba(13, 110, 253, 0.2);
  border-radius: 8px;
  background: #e7f1ff;
}

.category-section-draggable.is-dragging {
  opacity: 0.3;
}

.drag-handle-categoria {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 10;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 0.9rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  pointer-events: none;
  border: 1px solid #dee2e6;
}
</style>
