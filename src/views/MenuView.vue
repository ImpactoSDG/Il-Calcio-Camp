<template>
  <div class="container py-4 animate-fade-in">

    <!-- Barra de acciones del menú -->
    <div class="menu-toolbar d-flex justify-content-end mb-3 gap-2">
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

    <div v-if="modoOrdenar" class="alert alert-info py-2 px-3 mb-3 small">
      <i class="bi bi-info-circle me-1"></i>
      Arrastrá las tarjetas para cambiar el orden. El cambio se aplica dentro de cada categoría.
    </div>

    <!-- Modo ordenar -->
    <template v-if="modoOrdenar">
      <div
        v-for="(modulos, categoria) in modulosOrdenables"
        :key="categoria"
        class="category-section mb-5"
      >
        <div class="category-header d-flex align-items-center gap-3 mb-4">
          <h3 class="category-title mb-0">{{ categoria.toUpperCase() }}</h3>
          <div class="category-line flex-grow-1"></div>
        </div>

        <div
          class="card-grid"
          @dragover.prevent
          @drop="onDrop($event, categoria)"
        >
          <div
            v-for="(modulo, index) in modulos"
            :key="modulo.id"
            class="action-card-wrapper draggable-card"
            draggable="true"
            @dragstart="onDragStart($event, categoria, index)"
            @dragover.prevent="onDragOver($event, categoria, index)"
            @dragleave="onDragLeave"
            @drop.stop="onDrop($event, categoria, index)"
            :class="{ 'drag-over': dragOver.categoria === categoria && dragOver.index === index }"
          >
            <div class="drag-handle">
              <i class="bi bi-grip-vertical"></i>
            </div>
            <div
              class="action-card"
              :style="`--card-color: ${modulo.bg || '#6c757d'};`"
            >
              <div class="icon-container" style="background-color: var(--card-color);">
                <i :class="modulo.icon || 'bi bi-app-indicator'"></i>
              </div>
              <span class="card-label">{{ modulo.nombre }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modo normal -->
    <template v-else>
      <div v-for="(modulos, categoria) in menuEstructurado" :key="categoria" class="category-section mb-5">

        <div class="category-header d-flex align-items-center gap-3 mb-4">
          <h3 class="category-title mb-0">{{ categoria.toUpperCase() }}</h3>
          <div class="category-line flex-grow-1"></div>
        </div>

        <div class="card-grid">
          <template v-for="modulo in modulos" :key="modulo.id">
            <div class="action-card-wrapper position-relative">
              <button
                @click.stop.prevent="toggleFavorito(modulo)"
                class="favorite-btn"
                :class="{ 'is-favorite': Number(modulo.favorito) === 1 }"
                title="Marcar como favorito"
              >
                <i class="bi" :class="Number(modulo.favorito) === 1 ? 'bi-star-fill' : 'bi-star'"></i>
              </button>

              <!-- Link Externo -->
              <a
                v-if="modulo.ruta && (modulo.ruta.startsWith('http://') || modulo.ruta.startsWith('https://'))"
                :href="modulo.ruta"
                target="_blank"
                class="action-card"
                :style="`--card-color: ${modulo.bg || '#6c757d'};`"
              >
                <div class="icon-container" style="background-color: var(--card-color);">
                  <i :class="modulo.icon || 'bi bi-app-indicator'"></i>
                </div>
                <span class="card-label">{{ modulo.nombre }}</span>
              </a>

              <!-- Link Interno -->
              <router-link
                v-else
                :to="modulo.ruta"
                class="action-card"
                :style="`--card-color: ${modulo.bg || '#6c757d'};`"
              >
                <div class="icon-container" style="background-color: var(--card-color);">
                  <i :class="modulo.icon || 'bi bi-app-indicator'"></i>
                </div>
                <span class="card-label">{{ modulo.nombre }}</span>
              </router-link>
            </div>
          </template>
        </div>
      </div>
    </template>

    <p v-if="Object.keys(menuEstructurado).length === 0 && userStore.isLoggedIn" class="no-modules">
      No tienes módulos asignados. Contacta a un administrador.
    </p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

// ── Orden fijo de categorías ───────────────────────────────────────────────
const ORDEN_CATEGORIAS = ['FAVORITOS','NEGOCIO', 'CLIENTES', 'DEPORTES', 'TORNEOS', 'USUARIOS', 'CONFIGURACIONES'];

function ordenarCategorias(tree) {
  const sorted = {};
  // Primero las categorías con orden definido
  for (const cat of ORDEN_CATEGORIAS) {
    if (tree[cat]) sorted[cat] = tree[cat];
  }
  // Luego cualquier categoría extra que no esté en la lista
  for (const cat of Object.keys(tree)) {
    if (!sorted[cat]) sorted[cat] = tree[cat];
  }
  return sorted;
}

// ── Favoritos ──────────────────────────────────────────────────────────────
const toggleFavorito = async (modulo) => {
  const nuevoEstado = Number(modulo.favorito) === 1 ? false : true;
  await userStore.toggleFavorito(modulo.id, nuevoEstado);
};

// ── Computed: menú normal ──────────────────────────────────────────────────
const menuEstructurado = computed(() => {
    const modulosRaw = userStore.user?.modulos;
    if (!modulosRaw || !Array.isArray(modulosRaw)) return {};

    const tree = {};

    const padres = modulosRaw
        .filter(m => !m.id_padre || m.id_padre == 0 || m.id_padre == "0")
        .sort((a, b) => {
            const oa = a.orden_usuario != null ? Number(a.orden_usuario) : (Number(a.orden_visualizacion) || 0);
            const ob = b.orden_usuario != null ? Number(b.orden_usuario) : (Number(b.orden_visualizacion) || 0);
            return oa - ob;
        });

    // Favoritos primero
    const favoritos = padres.filter(m => Number(m.favorito) === 1);
    if (favoritos.length > 0) {
        tree['FAVORITOS'] = favoritos;
    }

    padres.forEach(modulo => {
        const catNombre = (modulo.categoria || 'GENERAL').toUpperCase();
        if (!tree[catNombre]) tree[catNombre] = [];
        tree[catNombre].push(modulo);
    });

    return ordenarCategorias(tree);
});

// ── Modo ordenar ───────────────────────────────────────────────────────────
const modoOrdenar = ref(false);
const guardando = ref(false);
const modulosOrdenables = ref({});

// Estado temporal para el drag
const dragInfo = ref({ categoria: null, fromIndex: null });
const dragOver = ref({ categoria: null, index: null });

function iniciarOrden() {
  // Copia mutable del menú (sin FAVORITOS, ya que es una vista derivada)
  const snap = {};
  for (const [cat, mods] of Object.entries(menuEstructurado.value)) {
    if (cat !== 'FAVORITOS') {
      snap[cat] = mods.map(m => ({ ...m }));
    }
  }
  modulosOrdenables.value = ordenarCategorias(snap);
  modoOrdenar.value = true;
}

function cancelarOrden() {
  modoOrdenar.value = false;
  modulosOrdenables.value = {};
  dragInfo.value = { categoria: null, fromIndex: null };
  dragOver.value = { categoria: null, index: null };
}

async function guardarOrden() {
  guardando.value = true;
  try {
    const ordenes = [];
    for (const [, mods] of Object.entries(modulosOrdenables.value)) {
      mods.forEach((mod, index) => {
        ordenes.push({ id_modulo: mod.id, orden: index + 1 });
      });
    }
    await userStore.saveOrdenModulos(ordenes);
    modoOrdenar.value = false;
    modulosOrdenables.value = {};
  } catch {
    // El error ya se logueó en el store
  } finally {
    guardando.value = false;
  }
}

// ── Drag-and-Drop ──────────────────────────────────────────────────────────
function onDragStart(event, categoria, index) {
  dragInfo.value = { categoria, fromIndex: index };
  event.dataTransfer.effectAllowed = 'move';
}

function onDragOver(event, categoria, index) {
  event.preventDefault();
  dragOver.value = { categoria, index };
}

function onDragLeave() {
  dragOver.value = { categoria: null, index: null };
}

function onDrop(event, categoria, toIndex) {
  event.preventDefault();
  dragOver.value = { categoria: null, index: null };

  const { categoria: fromCategoria, fromIndex } = dragInfo.value;
  if (fromCategoria !== categoria || fromIndex === null || fromIndex === toIndex) return;

  const mods = modulosOrdenables.value[categoria];
  const [moved] = mods.splice(fromIndex, 1);
  mods.splice(toIndex, 0, moved);

  dragInfo.value = { categoria: null, fromIndex: null };
}
</script>

<style scoped>
.category-header {
  margin-bottom: 20px;
}

.category-title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #6c757d;
  letter-spacing: 1px;
}

.category-line {
  height: 1px;
  background-color: #e0e0e0;
}

.action-card-wrapper {
  position: relative;
}

.favorite-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 10;
  background: rgba(255, 255, 255, 0.8);
  border: none;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #ccc;
  transition: all 0.2s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.favorite-btn:hover {
  transform: scale(1.1);
  color: #ffc107;
}

.favorite-btn.is-favorite {
  color: #ffc107;
  background: white;
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
</style>
