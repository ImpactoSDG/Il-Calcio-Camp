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
          <div class="col-12 col-md-2">
            <button
              @click="cargar"
              class="btn btn-primary-modern w-100"
              :disabled="cargando"
            >
              <span v-if="cargando" class="spinner-border spinner-border-sm me-1" role="status"></span>
              <i v-else class="bi bi-search me-1"></i>
              Consultar
            </button>
          </div>
          <div class="col-12 col-md-4 d-flex align-items-end">
            <span v-if="consultado" class="text-muted small">
              <i class="bi bi-info-circle me-1"></i>
              Período: <strong>{{ formatFecha(fechaDesde) }}</strong> al <strong>{{ formatFecha(fechaHasta) }}</strong>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tarjetas resumen -->
    <div v-if="consultado" class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="resumen-card resumen-card--primary">
          <div class="resumen-card__icon"><i class="bi bi-box-seam"></i></div>
          <div class="resumen-card__value">{{ stats.totalArticulos }}</div>
          <div class="resumen-card__label">Artículos activos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="resumen-card resumen-card--success">
          <div class="resumen-card__icon"><i class="bi bi-cart-check"></i></div>
          <div class="resumen-card__value">{{ formatNum(stats.totalVendido) }}</div>
          <div class="resumen-card__label">Unidades vendidas</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="resumen-card resumen-card--info">
          <div class="resumen-card__icon"><i class="bi bi-boxes"></i></div>
          <div class="resumen-card__value">{{ formatNum(stats.totalStock) }}</div>
          <div class="resumen-card__label">Stock total hasta "hasta"</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="resumen-card resumen-card--warning">
          <div class="resumen-card__icon"><i class="bi bi-exclamation-triangle"></i></div>
          <div class="resumen-card__value">{{ stats.sinStock }}</div>
          <div class="resumen-card__label">Sin stock al cierre</div>
        </div>
      </div>
    </div>

    <!-- Barra de búsqueda -->
    <div v-if="consultado" class="mb-3">
      <input
        v-model="busqueda"
        type="text"
        class="form-control form-control-sm w-auto"
        placeholder="Buscar artículo..."
        style="min-width: 260px;"
      />
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
          <thead class="table-light">
            <tr>
              <th class="ps-4 py-3 fw-semibold text-secondary small">ARTÍCULO</th>
              <th class="py-3 text-end fw-semibold text-secondary small" style="min-width:140px;">
                VENDIDO EN PERÍODO
              </th>
              <th class="py-3 pe-4 text-end fw-semibold text-secondary small" style="min-width:160px;">
                STOCK AL CIERRE
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in filasFiltradas"
              :key="item.id"
              :class="{ 'table-danger': item.stock_disponible <= 0 }"
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

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import articulosService from '@/services/inventario/articulosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

// ── Estado ──────────────────────────────────────────────────────────────────

const hoy = new Date().toISOString().slice(0, 10);
const fechaDesde = ref(hoy);
const fechaHasta = ref(hoy);
const cargando   = ref(false);
const consultado = ref(false);
const busqueda   = ref('');
const datos      = ref([]);

// ── Carga ────────────────────────────────────────────────────────────────────

const cargar = async () => {
  if (!fechaDesde.value || !fechaHasta.value) return;
  cargando.value = true;
  try {
    datos.value = await articulosService.getArticulosVendidos(fechaDesde.value, fechaHasta.value);
    consultado.value = true;
    busqueda.value = '';
  } catch (e) {
    toast.error(e?.response?.data?.message ?? 'Error al consultar artículos vendidos.');
  } finally {
    cargando.value = false;
  }
};

// ── Computed ──────────────────────────────────────────────────────────────────

const filasFiltradas = computed(() => {
  const q = busqueda.value.trim().toLowerCase();
  if (!q) return datos.value;
  return datos.value.filter(i => i.nombre.toLowerCase().includes(q));
});

const stats = computed(() => ({
  totalArticulos: datos.value.length,
  totalVendido:   datos.value.reduce((s, i) => s + i.cantidad_vendida, 0),
  totalStock:     datos.value.reduce((s, i) => s + i.stock_disponible, 0),
  sinStock:       datos.value.filter(i => i.stock_disponible <= 0).length,
}));

// ── Helpers ───────────────────────────────────────────────────────────────────

const formatNum = (n) => {
  const num = Number(n);
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

/* Tarjetas resumen */
.resumen-card {
  border-radius: 1rem;
  padding: 1.1rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  text-align: center;
}
.resumen-card__icon { font-size: 1.5rem; }
.resumen-card__value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
.resumen-card__label { font-size: 0.75rem; color: #6c757d; }

.resumen-card--primary  { background: #eef2ff; color: #4361ee; }
.resumen-card--success  { background: #d1fae5; color: #065f46; }
.resumen-card--info     { background: #e0f2fe; color: #0369a1; }
.resumen-card--warning  { background: #fef9c3; color: #854d0e; }

.fs-xs { font-size: 0.8rem; }
</style>
