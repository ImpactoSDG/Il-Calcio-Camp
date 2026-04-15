<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">ARTÍCULOS VENDIDOS</h1>
      </div>
    </div>

    <!-- Filtros de fechas -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body py-3 px-4">
        <div class="row g-3 align-items-end">
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Desde</label>
            <input
              v-model="fechaDesde"
              type="date"
              class="form-control form-control-sm"
              :max="fechaHasta"
            />
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label small fw-semibold text-secondary mb-1">Hasta</label>
            <input
              v-model="fechaHasta"
              type="date"
              class="form-control form-control-sm"
              :min="fechaDesde"
            />
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Buscar artículo</label>
            <input
              v-model="busqueda"
              type="text"
              class="form-control form-control-sm"
              placeholder="Ingresá el nombre..."
            />
          </div>
          <div class="col-12 col-md-2">
            <button
              @click="resetFiltros"
              class="btn btn-outline-secondary w-100 shadow-sm"
              title="Limpiar filtros"
            >
              <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cargando -->
    <div v-if="cargando" class="text-center py-5">
      <div class="spinner-border text-primary-custom" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <!-- Estado inicial -->
    <div v-else-if="!consultado" class="text-center py-5 text-muted bg-light rounded-4 border">
      <i class="bi bi-calendar-range fs-1 opacity-25"></i>
      <p class="mt-3 mb-0">Seleccioná un período y presioná <strong>Consultar</strong>.</p>
    </div>

    <!-- Sin resultados -->
    <div v-else-if="filasFiltradas.length === 0" class="text-center py-5 text-muted bg-light rounded-4 border">
      <i class="bi bi-search fs-1 opacity-25"></i>
      <p class="mt-2 mb-0">No se encontraron artículos.</p>
    </div>

    <!-- Tabla -->
    <div v-else class="card border-0 shadow-sm rounded-4">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <SortableTableHead
            :columns="columnas"
            :sort-key="sortKey"
            :sort-dir="sortDir"
            @sort="handleSort"
          />
          <tbody>
              <tr
                v-for="item in filasFiltradas"
                :key="item.id"
                @click="verDetalle(item)"
                class="cursor-pointer"
              >
                <!-- Artículo -->
              <td class="ps-4 py-2">
                <div class="d-flex align-items-center gap-2">
                  <img
                    v-if="item.url_imagen"
                    :src="item.url_imagen"
                    :alt="item.nombre"
                    class="rounded"
                    style="width:36px; height:36px; object-fit:cover;"
                  />
                  <div
                    v-else
                    class="rounded d-flex align-items-center justify-content-center bg-light text-muted"
                    style="width:36px; height:36px; flex-shrink:0;"
                  >
                    <i class="bi bi-box-seam"></i>
                  </div>
                  <span class="fw-medium">{{ item.nombre }}</span>
                </div>
              </td>

              <!-- Vendido en período -->
              <td class="py-2 text-end">
                <span
                  class="badge rounded-pill px-3 py-2 fs-xs"
                  :class="item.cantidad_vendida > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                >
                  {{ formatNum(item.cantidad_vendida) }}
                </span>
              </td>

              <!-- Stock al cierre -->
              <td class="py-2 pe-4 text-end">
                <span
                  class="badge rounded-pill px-3 py-2 fs-xs fw-semibold"
                  :class="stockClass(item.stock_disponible)"
                >
                  {{ formatNum(item.stock_disponible) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Detalle de Ventas -->
    <div v-if="artSeleccionado" class="modal-backdrop fade show"></div>
    <div v-if="artSeleccionado" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 pb-0">
            <h6 class="modal-title fw-bold text-secondary">DETALLE DE VENTAS</h6>
            <button type="button" class="btn-close" @click="artSeleccionado = null"></button>
          </div>
          <div class="modal-body">
            <div class="text-center mb-3">
              <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ artSeleccionado.nombre }}</span>
            </div>
            
            <div v-if="cargandoDetalle" class="text-center py-4">
              <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
            </div>
            
            <div v-else-if="detalleVentas.length === 0" class="text-center py-4 text-muted small">
              No hay registros detallados.
            </div>
            
            <div v-else class="table-responsive" style="max-height: 300px;">
              <table class="table table-sm table-borderless align-middle mb-0">
                <thead class="bg-light sticky-top">
                  <tr>
                    <th class="small fw-semibold text-secondary">FECHA</th>
                    <th class="small fw-semibold text-secondary text-end">CANT.</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(v, idx) in detalleVentas" :key="idx" class="border-bottom-custom">
                    <td class="small py-2">{{ formatFecha(v.fecha) }}</td>
                    <td class="small py-2 text-end fw-bold text-success">{{ formatNum(v.cantidad) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-light btn-sm w-100 rounded-3" @click="artSeleccionado = null">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import articulosService from '@/services/inventario/articulosService';
import { useToastStore } from '@/stores/toastStore';
import { fusionSearch } from '@/utils/searchUtils';

const toast = useToastStore();

// ── Estado ──────────────────────────────────────────────────────────────────

const hoy = new Date().toISOString().slice(0, 10);
const fechaDesde = ref(hoy);
const fechaHasta = ref(hoy);
const cargando   = ref(false);
const consultado = ref(false);
const busqueda   = ref('');
const datos      = ref([]);

// Detalle modal
const artSeleccionado = ref(null);
const detalleVentas   = ref([]);
const cargandoDetalle = ref(false);

// ── Ordenamiento ─────────────────────────────────────────────────────────────

const { sortKey, sortDir, handleSort, sortItems } = useSorting('cantidad_vendida', 'desc');

const columnas = [
  { key: 'nombre', label: 'ARTÍCULO', sortable: true, thClass: 'ps-4' },
  { key: 'cantidad_vendida', label: 'VENDIDO EN PERÍODO', sortable: true, thClass: 'text-end', thStyle: 'min-width:140px;' },
  { key: 'stock_disponible', label: 'STOCK AL CIERRE', sortable: true, thClass: 'pe-4 text-end', thStyle: 'min-width:160px;' },
];

// ── Carga ────────────────────────────────────────────────────────────────────

const cargar = async () => {
  if (!fechaDesde.value || !fechaHasta.value) return;
  cargando.value = true;
  try {
    datos.value = await articulosService.getArticulosVendidos(fechaDesde.value, fechaHasta.value);
    consultado.value = true;
    // busqueda.value = ''; // Se elimina para que persista el texto buscado
  } catch (e) {
    toast.error(e?.response?.data?.message ?? 'Error al consultar artículos vendidos.');
  } finally {
    cargando.value = false;
  }
};

const verDetalle = async (item) => {
  artSeleccionado.value = item;
  cargandoDetalle.value = true;
  detalleVentas.value = [];
  try {
    const data = await articulosService.getDetalleVentaArticulo(
      item.id,
      fechaDesde.value,
      fechaHasta.value
    );
    
    console.log('Detalle de ventas recibido:', data);
    
    // Asegurar que los datos tienen el formato correcto
    if (Array.isArray(data)) {
      detalleVentas.value = data.map(v => ({
        fecha: v.fecha || '',
        cantidad: parseFloat(v.cantidad_total) || parseFloat(v.cantidad) || 0
      }));
    } else {
      detalleVentas.value = [];
    }
  } catch (e) {
    console.error('Error en verDetalle:', e);
    toast.error('No se pudo cargar el detalle de ventas.');
    detalleVentas.value = [];
  } finally {
    cargandoDetalle.value = false;
  }
};

const resetFiltros = () => {
  const hoy = new Date().toISOString().slice(0, 10);
  fechaDesde.value = hoy;
  fechaHasta.value = hoy;
  busqueda.value = '';
};

// ── Observadores ─────────────────────────────────────────────────────────────

watch([fechaDesde, fechaHasta], () => {
  cargar();
});

onMounted(() => {
  cargar();
});

// ── Computed ──────────────────────────────────────────────────────────────────

const filasFiltradas = computed(() => {
  const q = busqueda.value.trim();
  const filtrados = q 
    ? fusionSearch(datos.value, q, ['nombre'])
    : datos.value;
    
  return sortItems(filtrados);
});

// ── Helpers ───────────────────────────────────────────────────────────────────

const formatNum = (n) => {
  if (n === null || n === undefined || n === '') return '0';
  const num = Number(n);
  if (isNaN(num)) return '0';
  return Number.isInteger(num) ? num.toString() : num.toFixed(2);
};

const formatFecha = (iso) => {
  if (!iso) return '';
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
};

const stockClass = (stock) => {
  if (stock <= 0) return 'bg-danger text-white';
  if (stock <= 3) return 'bg-warning text-dark';
  return 'bg-info-subtle text-info-emphasis';
};
</script>

<style scoped>
.btn-back-arrow {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #6c757d;
  padding: 0.25rem 0.5rem;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: background 0.15s;
}
.btn-back-arrow:hover {
  background: #f1f3f5;
}

.btn-primary-modern {
  background: #4361ee;
  color: #fff;
  border: none;
  border-radius: 0.5rem;
  padding: 0.45rem 1.1rem;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background 0.2s, opacity 0.2s;
}
.btn-primary-modern:hover:not(:disabled) {
  background: #3451d1;
}
.btn-primary-modern:disabled {
  opacity: 0.65;
}

.fs-xs { font-size: 0.8rem; }

.cursor-pointer { cursor: pointer; }
.border-bottom-custom { border-bottom: 1px solid #f1f3f5; }

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1040;
}
.modal {
  z-index: 1050;
}
</style>
