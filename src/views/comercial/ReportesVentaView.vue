<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">REPORTES DE VENTA</h1>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body py-3 px-4">
        <div class="row g-3 align-items-end">
          <div class="col-6 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Desde</label>
            <input
              v-model="fechaDesde"
              type="date"
              class="form-control form-control-sm"
              :max="fechaHasta"
            />
          </div>
          <div class="col-6 col-md-4">
            <label class="form-label small fw-semibold text-secondary mb-1">Hasta</label>
            <input
              v-model="fechaHasta"
              type="date"
              class="form-control form-control-sm"
              :min="fechaDesde"
            />
          </div>
          <div class="col-12 col-md-4">
            <button
              @click="resetFiltros"
              class="btn btn-outline-secondary w-100 shadow-sm"
            >
              <i class="bi bi-arrow-counterclockwise me-1"></i> Hoy
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

    <template v-else-if="consultado">

      <!-- KPIs -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="kpi-icon bg-success-subtle text-success rounded-3 p-3 fs-4">
                <i class="bi bi-currency-dollar"></i>
              </div>
              <div>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Monto Total</p>
                <p class="h4 fw-bold mb-0 text-dark">${{ formatMoney(resumen.monto_total) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="kpi-icon bg-primary-subtle text-primary rounded-3 p-3 fs-4">
                <i class="bi bi-receipt"></i>
              </div>
              <div>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Ticket Promedio</p>
                <p class="h4 fw-bold mb-0 text-dark">${{ formatMoney(resumen.ticket_promedio) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
              <div class="kpi-icon bg-warning-subtle text-warning rounded-3 p-3 fs-4">
                <i class="bi bi-bag-check"></i>
              </div>
              <div>
                <p class="text-muted small mb-0 fw-semibold text-uppercase">Ventas</p>
                <p class="h4 fw-bold mb-0 text-dark">{{ resumen.cantidad_ventas }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Productos más vendidos -->
      <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
          <h6 class="fw-bold text-secondary mb-0">
            <i class="bi bi-bar-chart-line me-2"></i>PRODUCTOS MÁS VENDIDOS
          </h6>
          <p class="text-muted small mb-0 mt-1">
            Período: {{ formatFecha(fechaDesde) }}
            <template v-if="fechaDesde !== fechaHasta"> al {{ formatFecha(fechaHasta) }}</template>
          </p>
        </div>

        <div v-if="productos.length === 0" class="text-center py-5 text-muted px-4">
          <i class="bi bi-inbox fs-1 opacity-25 d-block mb-2"></i>
          No hay artículos vendidos en el período seleccionado.
        </div>

        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <SortableTableHead
              :columns="columnas"
              :sort-key="sortKey"
              :sort-dir="sortDir"
              @sort="handleSort"
              theadClass="table-light"
            />
            <tbody>
              <tr v-for="(item, index) in productosSorted" :key="item.id">
                <td class="ps-4 py-3 text-muted small">{{ index + 1 }}</td>
                <td class="py-3 fw-medium">
                  {{ item.nombre_articulo }}
                </td>
                <td class="py-3 text-end">
                  <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fw-semibold">
                    {{ formatNum(item.cantidad_vendida) }}
                  </span>
                </td>
                <td class="pe-4 py-3 text-end fw-semibold text-dark">
                  ${{ formatMoney(item.monto_total) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="table-light border-top">
              <tr>
                <td colspan="2" class="ps-4 py-3 small fw-bold text-secondary text-uppercase">Total</td>
                <td class="py-3 text-end">
                  <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-bold">
                    {{ formatNum(totalUnidades) }}
                  </span>
                </td>
                <td class="pe-4 py-3 text-end fw-bold text-dark">
                  ${{ formatMoney(totalMonto) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

    </template>

    <!-- Estado inicial -->
    <div v-else class="text-center py-5 text-muted bg-light rounded-4 border">
      <i class="bi bi-bar-chart-line fs-1 opacity-25 d-block"></i>
      <p class="mt-3 mb-0">Seleccioná un período y presioná <strong>Consultar</strong>.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useToastStore } from '@/stores/toastStore';
import reportesVentaService from '@/services/comercial/reportesVentaService';
import { formatNumber, formatMoney } from '@/utils/formatters';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';

const toast = useToastStore();

// ── Estado ────────────────────────────────────────────────────────────────────

const hoy       = new Date().toISOString().slice(0, 10);
const fechaDesde = ref(hoy);
const fechaHasta = ref(hoy);
const cargando   = ref(false);
const consultado = ref(false);

const resumen = ref({
  cantidad_ventas: 0,
  monto_total:     0,
  ticket_promedio: 0,
});
const productos = ref([]);

// ── Ordenamiento ─────────────────────────────────────────────────────────────

const { sortKey, sortDir, handleSort, sortItems } = useSorting('', 'desc');

const columnas = [
  { key: 'numero', label: '#', sortable: false, thClass: 'ps-4' },
  { key: 'nombre_articulo', label: 'ARTÍCULO', sortable: false },
  { key: 'cantidad_vendida', label: 'CANTIDAD VENDIDA', sortable: true, thClass: 'text-end', thStyle: 'min-width:140px;' },
  { key: 'monto_total', label: 'MONTO TOTAL', sortable: true, thClass: 'pe-4 text-end', thStyle: 'min-width:160px;' },
];

// ── Computed ──────────────────────────────────────────────────────────────────

const productosSorted = computed(() => sortItems(productos.value));

const totalUnidades = computed(() =>
  productos.value.reduce((acc, p) => acc + parseFloat(p.cantidad_vendida ?? 0), 0)
);

const totalMonto = computed(() =>
  productos.value.reduce((acc, p) => acc + parseFloat(p.monto_total ?? 0), 0)
);

// ── Métodos ───────────────────────────────────────────────────────────────────

const consultar = async () => {
  if (!fechaDesde.value || !fechaHasta.value) return;
  cargando.value = true;
  try {
    const data = await reportesVentaService.getReporte(fechaDesde.value, fechaHasta.value);
    resumen.value  = data.resumen  ?? resumen.value;
    productos.value = data.productos_mas_vendidos ?? [];
    consultado.value = true;
  } catch (e) {
    toast.error(e?.response?.data?.message ?? 'Error al obtener el reporte de ventas.');
  } finally {
    cargando.value = false;
  }
};

const resetFiltros = () => {
  const today   = new Date().toISOString().slice(0, 10);
  fechaDesde.value = today;
  fechaHasta.value = today;
};

const formatNum   = (v) => formatNumber(v, 2, true);
const formatFecha = (iso) => {
  if (!iso) return '';
  const [y, m, d] = iso.split('-');
  return `${d}/${m}/${y}`;
};

// ── Watchers (aplicar filtros automáticamente) ────────────────────────────────

watch([fechaDesde, fechaHasta], () => consultar(), { immediate: true });

// ── Ciclo de vida ─────────────────────────────────────────────────────────────

defineOptions({ components: { SortableTableHead } });</script>

<style scoped>
.kpi-icon {
  flex-shrink: 0;
  width: 52px;
  height: 52px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary-custom {
  background-color: var(--bs-primary, #0d6efd);
  color: #fff;
  border: none;
}
.btn-primary-custom:hover:not(:disabled) {
  filter: brightness(0.9);
}
.btn-primary-custom:disabled {
  opacity: 0.65;
}
</style>
