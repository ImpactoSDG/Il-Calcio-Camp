<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">INGRESOS DE ARTÍCULO</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo Ingreso
      </button>
    </div>

    <!-- Filtros -->
    <div class="row g-3 mb-4 align-items-end">
      <div class="col-md-3">
        <label class="form-label fs-xs fw-bold text-uppercase text-secondary mb-1">Buscar Artículo</label>
        <div class="position-relative">
          <input
            v-model="searchQuery"
            type="text"
            class="form-control"
            placeholder="Nombre del artículo..."
          />
          <i class="bi bi-search position-absolute end-0 top-50 translate-middle-y me-3 text-muted opacity-50"></i>
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label fs-xs fw-bold text-uppercase text-secondary mb-1">Desde</label>
        <input v-model="filterDateFrom" type="date" class="form-control" />
      </div>
      <div class="col-md-2">
        <label class="form-label fs-xs fw-bold text-uppercase text-secondary mb-1">Hasta</label>
        <input v-model="filterDateTo" type="date" class="form-control" />
      </div>
      <div class="col-md-auto ms-auto">
        <button @click="resetFilters" class="btn btn-outline-secondary d-flex align-items-center" title="Limpiar filtros">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '300px' : 'auto' }">
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
    
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <SortableTableHead
            :columns="columns"
            :sort-key="sortKey"
            :sort-dir="sortDir"
            @sort="handleSort"
          />
          <tbody class="bg-white">
            <tr v-for="item in ingresosFiltrados" :key="item.id" class="article-row">
              
              <td class="ps-4" style="width: 60px;">
                <div class="articulo-img-thumb shadow-sm border overflow-hidden rounded-2 d-flex align-items-center justify-content-center bg-light" style="width: 40px; height: 40px;">
                  <img v-if="item.url_imagen" :src="`${apiBaseUrl}/${item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                  <i v-else class="bi bi-image text-muted opacity-25" style="font-size: 1.2rem;"></i>
                </div>
              </td>
            
              <td class="fw-semibold text-dark w-25">
                {{ item.articulo_nombre }}
              </td>
            
              <td class="text-muted small text-nowrap">
                <i class="bi bi-calendar3 me-1"></i>{{ formatFecha(item.fecha_ingreso) }}
              </td>
            
              <td class="text-muted small text-nowrap">
                <span v-if="item.vencimiento">
                  <i class="bi bi-calendar-x me-1"></i>{{ formatFecha(item.vencimiento) }}
                </span>
                <span v-else class="text-muted opacity-50">—</span>
              </td>
            
              <td class="text-end fw-bold text-primary-custom">
                {{ item.cantidad }}
              </td>
            
              <td class="text-end text-muted small text-nowrap">
                ${{ formatMoney(item.precio_unitario) }}
              </td>
            
              <td class="text-end fw-bold text-dark text-nowrap">
                ${{ formatMoney(item.total) }}
              </td>
            
              <td class="text-center">
                <span :class="Number(item.es_perecedero) ? 'status-pill status-pill--danger' : 'status-pill status-pill--inactive'">
                  {{ Number(item.es_perecedero) ? 'Pered.' : 'No' }}
                </span>
              </td>
            
              <td class="text-center">
                <span :class="Number(item.es_ajuste) ? 'status-pill status-pill--info' : 'status-pill status-pill--inactive'">
                  {{ Number(item.es_ajuste) ? 'Ajuste' : 'No' }}
                </span>
              </td>
            
              <td class="pe-4 text-end">
                <div class="d-flex justify-content-end gap-1 flex-nowrap">
                  <button @click="openModal(item)" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1" title="Editar">
                    <i class="bi bi-pencil fs-6"></i>
                    <span class="small fw-bold">Editar</span>
                  </button>
                  <button @click="prepareDelete(item.id)" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 px-2 py-1" title="Eliminar">
                    <i class="bi bi-trash3 fs-6"></i>
                    <span class="small fw-bold">Eliminar</span>
                  </button>
                </div>
              </td>
            
            </tr>
          
            <tr v-if="ingresosFiltrados.length === 0 && !loading">
              <td colspan="10" class="text-center py-5 text-muted">
                No hay ingresos que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-box-arrow-in-down'"></i>
              {{ isEditing ? 'Editar Ingreso' : 'Nuevo Ingreso de Artículo' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Artículo <span class="text-danger">*</span></label>
                  <FuzzySearch 
                    ref="fuzzySearchModal"
                    v-model="modalSearchQuery"
                    :data="articulos" 
                    :keys="['nombre', 'cod_barra']" 
                    placeholder="Buscar por nombre o código..."
                    @selected="onArticuloSelected"
                  >
                    <template #default="{ item }">
                      <div class="d-flex align-items-center w-100 py-1">
                        <div class="articulo-img-thumb-mini border rounded-2 bg-light me-3 d-flex align-items-center justify-content-center overflow-hidden" 
                             style="width: 35px; height: 35px; min-width: 35px;">
                          <img v-if="item.url_imagen" :src="`${apiBaseUrl}/${item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                          <i v-else class="bi bi-image text-muted opacity-50" style="font-size: 0.9rem;"></i>
                        </div>
                        <div class="d-flex flex-column">
                          <span class="fw-semibold text-dark lh-sm">{{ item.nombre }}</span>
                          <small class="text-muted" style="font-size: 0.75rem;">{{ item.cod_barra }}</small>
                        </div>
                      </div>
                    </template>
                  </FuzzySearch>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha de Ingreso <span class="text-danger">*</span></label>
                  <input v-model="form.fecha_ingreso" type="date" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                  <CustomNumberInput v-model="form.cantidad" placeholder="0" :decimals="2" required @update:modelValue="recalculateFromQuantity" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Costo Unitario ($) <span class="text-danger">*</span></label>
                  <CustomNumberInput v-model="form.precio_unitario" placeholder="0.00" :decimals="2" required @update:modelValue="recalculateFromUnit" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Total ($)</label>
                  <CustomNumberInput v-model="form.total" placeholder="0.00" :decimals="2" @update:modelValue="recalculateFromTotal" />
                </div>
                <!-- Fila de Vencimiento y Switches -->
                <div class="col-md-6">
                  <label class="form-label">Vencimiento</label>
                  <input v-model="form.vencimiento" type="date" class="form-control" />
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-around pt-4">
                  <div class="form-check form-switch">
                    <input v-model="form.es_perecedero" class="form-check-input" type="checkbox" role="switch" id="chkPerecedero" />
                    <label class="form-check-label fw-semibold ms-2" for="chkPerecedero">Perecedero</label>
                  </div>
                  <div class="form-check form-switch">
                    <input v-model="form.es_ajuste" class="form-check-input" type="checkbox" role="switch" id="chkAjuste" />
                    <label class="form-check-label fw-semibold ms-2" for="chkAjuste">Es Ajuste</label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Ingreso"
      message="¿Estás seguro de eliminar este ingreso de artículo? Esta acción no se puede deshacer."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import CustomNumberInput from '@/components/CustomNumberInput.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import articulosService from '@/services/inventario/articulosService';
import { useToastStore } from '@/stores/toastStore';
import { formatMoney } from '@/utils/formatters';
import Fuse from 'fuse.js';

const toast = useToastStore();
const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'imagen',           label: '',               sortable: false, thClass: 'ps-4 py-3', thStyle: 'width: 50px' },
  { key: 'articulo_nombre',  label: 'Artículo',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 20%' },
  { key: 'fecha_ingreso',    label: 'Fecha Ingreso',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 12%' },
  { key: 'vencimiento',      label: 'Vencimiento',    sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 12%' },
  { key: 'cantidad',         label: 'Cantidad',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 10%' },
  { key: 'precio_unitario',  label: 'Precio Unit.',   sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 10%' },
  { key: 'total',            label: 'Total',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 10%' },
  { key: 'es_perecedero',    label: 'Pered.',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 8%' },
  { key: 'es_ajuste',        label: 'Ajuste',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 8%' },
  { key: 'acciones',         label: 'Acciones',       sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 9%' },
]

const ingresos = ref([]);
const articulos = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const modalSearchQuery = ref('');
const filterDateFrom = ref('');
const filterDateTo = ref('');
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);

const fuzzySearchModal = ref(null);

let fuseIngresos = null;

const setupFuse = () => {
  if (ingresos.value.length > 0) {
    fuseIngresos = new Fuse(ingresos.value, {
      keys: ['articulo_nombre'],
      threshold: 0.3,
      distance: 100
    });
  }
};

watch(ingresos, () => {
  setupFuse();
}, { deep: true });

const emptyForm = () => ({
  id: null,
  id_articulo: null,
  fecha_ingreso: new Date().toISOString().split('T')[0],
  cantidad: null,
  precio_unitario: null,
  total: null,
  vencimiento: null,
  es_perecedero: false,
  es_ajuste: false,
});
const form = ref(emptyForm());
const originalForm = ref({});

const onArticuloChange = () => {
  const art = articulos.value.find(a => a.id === form.value.id_articulo);
  if (art) {
    // Al seleccionar el artículo, completamos con el costo_actual (precio de costo)
    form.value.precio_unitario = Number(art.costo_actual) || 0;
    recalculateFromUnit();
  }
};

const onArticuloSelected = (art) => {
  form.value.id_articulo = art.id;
  modalSearchQuery.value = art.nombre; // Sincronizar el v-model del input del modal con el nombre seleccionado
  onArticuloChange();
};

const recalculateFromQuantity = () => {
  const qty = Number(form.value.cantidad) || 0;
  const unit = Number(form.value.precio_unitario) || 0;
  form.value.total = Number((qty * unit).toFixed(2));
};

const recalculateFromUnit = () => {
  const qty = Number(form.value.cantidad) || 0;
  const unit = Number(form.value.precio_unitario) || 0;
  form.value.total = Number((qty * unit).toFixed(2));
};

const recalculateFromTotal = () => {
  const qty = Number(form.value.cantidad) || 0;
  const total = Number(form.value.total) || 0;
  if (qty > 0) {
    form.value.precio_unitario = Number((total / qty).toFixed(2));
  }
};

const ingresosFiltrados = computed(() => {
  let items = [...ingresos.value];

  // Filtro por búsqueda de texto difusa
  if (searchQuery.value.trim() !== '') {
    if (fuseIngresos) {
      items = fuseIngresos.search(searchQuery.value).map(res => res.item);
    } else {
      const q = searchQuery.value.toLowerCase();
      items = items.filter(i => i.articulo_nombre?.toLowerCase().includes(q));
    }
  }

  // Filtro por fechas
  if (filterDateFrom.value) {
    items = items.filter(i => i.fecha_ingreso >= filterDateFrom.value);
  }
  if (filterDateTo.value) {
    items = items.filter(i => i.fecha_ingreso <= filterDateTo.value);
  }

  return sortItems(items);
});

const resetFilters = () => {
  searchQuery.value = '';
  const today = new Date();
  const lastWeek = new Date();
  lastWeek.setDate(today.getDate() - 7);
  
  filterDateFrom.value = lastWeek.toISOString().split('T')[0];
  filterDateTo.value = today.toISOString().split('T')[0];
};

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  return new Date(fecha).toLocaleDateString('es-AR');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const [resIngresos, resArticulos] = await Promise.all([
      articulosService.getIngresos(),
      articulosService.getArticulos(),
    ]);
    ingresos.value = resIngresos;
    articulos.value = resArticulos;
    setupFuse();
  } catch {
    toast.showToast({ message: 'Error al cargar los ingresos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = {
      ...item,
      es_perecedero: Boolean(Number(item.es_perecedero)),
      es_ajuste: Boolean(Number(item.es_ajuste)),
    };
    originalForm.value = { ...form.value };
    // Sincronizar el nombre en el input del FuzzySearch al editar
    modalSearchQuery.value = item.articulo_nombre || '';
  } else {
    isEditing.value = false;
    form.value = emptyForm();
    modalSearchQuery.value = '';
  }

  // Dar foco al input de productos tras abrir el modal
  setTimeout(() => {
    if (fuzzySearchModal.value) {
      fuzzySearchModal.value.focus();
    }
  }, 100);
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.id_articulo || !form.value.fecha_ingreso || !form.value.cantidad || !form.value.precio_unitario) {
    toast.showToast({ message: 'Artículo, fecha, cantidad y precio son obligatorios.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  const payload = { ...form.value };

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await articulosService.actualizarIngreso(payload);
      toast.showToast({ message: 'Ingreso actualizado correctamente.', type: 'success' });
    } else {
      await articulosService.crearIngreso(payload);
      toast.showToast({ message: 'Ingreso registrado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el ingreso.', type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await articulosService.eliminarIngreso(idToDelete.value);
    toast.showToast({ message: 'Ingreso eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el ingreso.', type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

onMounted(() => {
  resetFilters();
  fetchData();
});
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }

/* ── Mejoras Visuales (Sincronizado con ArticulosView) ── */
.article-row {
  transition: all 0.2s ease;
}
.article-row:hover {
  background-color: rgba(var(--color-primary-rgb, 0, 85, 140), 0.04) !important;
}

/* ── Pills de Estado ── */
.status-pill {
  font-size: 0.70rem;
  font-weight: 700;
  padding: 0.25rem 0.65rem;
  border-radius: 50px;
  display: inline-block;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.status-pill--danger {
  background-color: #fdeaea;
  color: #dc3545;
}
.status-pill--info {
  background-color: #e7f3ff;
  color: #0d6efd;
}
.status-pill--inactive {
  background-color: #f1f3f5;
  color: #6c757d;
}

/* ── Botones de Acción ── */
.btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: none;
  transition: all 0.2s ease;
}
.btn-light-primary {
  background-color: #eef4f8;
  color: var(--color-primary, #00558c);
}
.btn-light-primary:hover {
  background-color: var(--color-primary, #00558c);
  color: #fff;
}
.btn-light-danger {
  background-color: #fdeaea;
  color: #dc3545;
}
.btn-light-danger:hover {
  background-color: #dc3545;
  color: #fff;
}

.text-primary-custom {
  color: var(--color-primary, #00558c) !important;
}

.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>
