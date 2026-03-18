<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">STOCK DE PRODUCTOS</h1>
      </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4 flex-wrap">
      <div class="col-6 col-md-4 col-lg">
        <div class="stock-summary-card stock-summary-card--total" @click="limpiarFiltros">
          <div class="stock-summary-card__icon"><i class="bi bi-boxes"></i></div>
          <div class="stock-summary-card__value">{{ stats.total }}</div>
          <div class="stock-summary-card__label">Total productos</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div
          class="stock-summary-card stock-summary-card--danger"
          :class="{ 'stock-summary-card--active': filtroAlerta === 'sin_stock' }"
          @click="toggleFiltroAlerta('sin_stock')"
          style="cursor:pointer"
        >
          <div class="stock-summary-card__icon"><i class="bi bi-exclamation-octagon"></i></div>
          <div class="stock-summary-card__value">{{ stats.sinStock }}</div>
          <div class="stock-summary-card__label">Sin stock</div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg">
        <div
          class="stock-summary-card stock-summary-card--warning"
          :class="{ 'stock-summary-card--active': filtroAlerta === 'stock_bajo' }"
          @click="toggleFiltroAlerta('stock_bajo')"
          style="cursor:pointer"
        >
          <div class="stock-summary-card__icon"><i class="bi bi-arrow-down-circle"></i></div>
          <div class="stock-summary-card__value">{{ stats.stockBajo }}</div>
          <div class="stock-summary-card__label">Stock bajo</div>
        </div>
      </div>
      <div class="col-6 col-md-6 col-lg">
        <div
          class="stock-summary-card stock-summary-card--info"
          :class="{ 'stock-summary-card--active': filtroAlerta === 'por_vencer' }"
          @click="toggleFiltroAlerta('por_vencer')"
          style="cursor:pointer"
        >
          <div class="stock-summary-card__icon"><i class="bi bi-calendar-event"></i></div>
          <div class="stock-summary-card__value">{{ stats.porVencer }}</div>
          <div class="stock-summary-card__label">Próx. a vencer ({{ DIAS_ALERTA_VENCIMIENTO }}d)</div>
        </div>
      </div>
      <div class="col-6 col-md-6 col-lg">
        <div
          class="stock-summary-card stock-summary-card--vencido"
          :class="{ 'stock-summary-card--active': filtroAlerta === 'vencido' }"
          @click="toggleFiltroAlerta('vencido')"
          style="cursor:pointer"
        >
          <div class="stock-summary-card__icon"><i class="bi bi-calendar-x"></i></div>
          <div class="stock-summary-card__value">{{ stats.vencidos }}</div>
          <div class="stock-summary-card__label">Vencidos</div>
        </div>
      </div>
    </div>

    <!-- Barra de filtros -->
    <div class="row g-2 mb-3 align-items-center">
      <div class="col-12 col-md-5">
        <FuzzySearch
          v-model="searchQuery"
          :data="articulosUnicos"
          :keys="['nombre', 'cod_barra', 'categoria_descripcion']"
          placeholder="Buscar por nombre, categoría o código de barras..."
        >
          <template #default="{ item }">
            <div class="d-flex justify-content-between align-items-center w-100">
              <div>
                <span class="fw-medium d-block">{{ item.nombre }}</span>
                <span class="text-muted extra-small" style="font-size: 0.7rem;">{{ item.categoria_descripcion || 'Sin categoría' }}</span>
              </div>
              <span v-if="item.cod_barra" class="badge bg-light text-dark border font-monospace ms-2" style="font-size: 0.65rem;">
                {{ item.cod_barra }}
              </span>
            </div>
          </template>
        </FuzzySearch>
      </div>
      <div class="col-6 col-md-3">
        <select v-model="filtroCategoria" class="form-select">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
        </select>
      </div>
      <div class="col-5 col-md-3">
        <select v-model="filtroAlerta" class="form-select">
          <option value="">Todos los estados</option>
          <option value="sin_stock">Sin stock</option>
          <option value="stock_bajo">Stock bajo</option>
          <option value="por_vencer">Próximo a vencer</option>
          <option value="vencido">Vencido</option>
          <option value="ok">Sin alertas</option>
        </select>
      </div>
      <div class="col-1">
        <button class="btn btn-light w-100 d-flex align-items-center justify-content-center" @click="limpiarFiltros" title="Limpiar filtros" style="height:38px">
          <i class="bi bi-x-circle"></i>
        </button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm border-0 rounded-3 overflow-hidden position-relative" :style="{ minHeight: loading ? '300px' : 'auto' }">
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="text-muted small mt-3 mb-0">Calculando stock...</p>
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
            <tr
              v-for="item in stockFiltrado"
              :key="item.es_lote ? `ing-${item.id_lote}` : `art-${item.id}`"
              :class="{ 'table-danger-soft': Number(item.stock_actual) <= 0 }"
            >
              <!-- Imagen -->
              <td class="ps-4">
                <div class="articulo-img-thumb shadow-sm border overflow-hidden rounded-2 d-flex align-items-center justify-content-center bg-light" style="width: 40px; height: 40px;">
                  <img v-if="item.url_imagen" :src="`${apiBaseUrl}/${item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                  <i v-else class="bi bi-image text-muted opacity-25" style="font-size: 1.2rem;"></i>
                </div>
              </td>

              <!-- Producto -->
              <td>
                <div class="fw-medium text-dark lh-sm">{{ item.nombre }}</div>
                <span v-if="item.categoria_descripcion" class="badge bg-primary-subtle text-primary-custom rounded-pill px-2 mt-1" style="font-size:0.7rem">
                  {{ item.categoria_descripcion }}
                </span>
              </td>

              <!-- Código de barras -->
              <td class="text-muted font-monospace" style="font-size:0.82rem">
                {{ item.cod_barra || '—' }}
              </td>

              <!-- Info de Lote -->
              <td>
                <div v-if="item.es_lote">
                  <div class="small fw-semibold text-secondary">Lote: #{{ item.id_lote }}</div>
                  <div class="text-muted" style="font-size:0.75rem">Ingreso: {{ formatFecha(item.fecha_ingreso_lote) }}</div>
                </div>
                <span v-else class="text-muted small">Sin ingresos</span>
              </td>

              <!-- Cantidad Original Ingreso -->
              <td class="text-center">
                <span v-if="item.es_lote" class="text-muted fw-medium" style="font-size:0.85rem">
                  {{ item.cantidad_original }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>

              <!-- Stock actual -->
              <td class="text-center">
                <div class="d-flex flex-column align-items-center gap-1">
                  <span class="fw-bold fs-5 lh-1" :class="stockColorClass(item.cantidad_lote, item.ROP)">
                    {{ Number(item.cantidad_lote) }}
                  </span>
                  <span class="badge rounded-pill px-2" style="font-size:0.68rem" :class="stockBadgeClass(item.cantidad_lote, item.ROP)">
                    {{ stockLabel(item.cantidad_lote, item.ROP) }}
                  </span>
                </div>
              </td>

              <!-- Próximo vencimiento -->
              <td class="text-center">
                <template v-if="item.vencimiento_proximo">
                  <span class="badge rounded-pill px-2 d-block mb-1" :class="vencimientoBadgeClass(item.vencimiento_proximo)">
                    <i class="bi bi-calendar3 me-1" style="font-size:0.65rem"></i>
                    {{ formatFecha(item.vencimiento_proximo) }}
                  </span>
                  <span class="text-muted" style="font-size:0.7rem">{{ diasHastaVencimiento(item.vencimiento_proximo) }}</span>
                </template>
                <span v-else class="text-muted">—</span>
              </td>

              <!-- Precio -->
              <td class="text-end fw-semibold text-dark">
                {{ item.precio_actual != null ? `$${formatMoney(item.precio_actual)}` : '—' }}
              </td>

              <!-- Acciones -->
              <td class="pe-4 text-end">
                <button @click="verDetalle(item)" class="btn btn-link link-secondary p-1" title="Ver detalle de lotes">
                  <i class="bi bi-eye fs-4"></i>
                </button>
              </td>
            </tr>

            <tr v-if="stockFiltrado.length === 0 && !loading">
              <td colspan="6" class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1 d-block mb-2 opacity-25"></i>
                <span class="fw-medium">No hay productos que coincidan con los filtros aplicados.</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer con total de resultados -->
      <div v-if="!loading && stockFiltrado.length > 0" class="px-4 py-2 border-top bg-light d-flex align-items-center justify-content-between">
        <span class="text-muted small">
          Mostrando <strong>{{ stockFiltrado.length }}</strong> registros de ingresos/productos
        </span>
        <span class="text-muted small">
          Cantidad total en estos lotes: <strong class="text-dark">{{ stockTotal }}</strong> unidades
        </span>
      </div>
    </div>

    <!-- ==========================================
         MODAL DE DETALLE
         ========================================== -->
    <Teleport to="body">
    <div
      v-if="showDetalle && detalleItem"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);"
      @click.self="showDetalle = false"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">

          <!-- Header del modal -->
          <div class="modal-header border-bottom-0 pb-0">
            <div>
              <h5 class="modal-title fw-bold text-dark">{{ detalleItem.nombre }}</h5>
              <span v-if="detalleItem.categoria_descripcion" class="badge bg-primary-subtle text-primary-custom rounded-pill px-3 mt-1">
                {{ detalleItem.categoria_descripcion }}
              </span>
            </div>
            <button type="button" class="btn-close ms-auto" @click="showDetalle = false"></button>
          </div>

          <div class="modal-body pt-3">

            <!-- Métricas del artículo -->
            <div class="row g-3 mb-4">
              <div class="col-6 col-md-3">
                <div class="detalle-metric-card detalle-metric-card--stock" :class="stockMetricVariant(detalleItem.stock_actual, detalleItem.ROP)">
                  <div class="detalle-metric-card__label">Stock actual</div>
                  <div class="detalle-metric-card__value">{{ Number(detalleItem.stock_actual) }}</div>
                  <div class="detalle-metric-card__sublabel">Stock Mín. (ROP): {{ detalleItem.ROP || 1 }}</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="detalle-metric-card">
                  <div class="detalle-metric-card__label">Precio venta</div>
                  <div class="detalle-metric-card__value" style="font-size:1.25rem">
                    {{ detalleItem.precio_actual != null ? `$${formatMoney(detalleItem.precio_actual)}` : '—' }}
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="detalle-metric-card">
                  <div class="detalle-metric-card__label">Costo</div>
                  <div class="detalle-metric-card__value" style="font-size:1.25rem">
                    {{ detalleItem.costo_actual != null ? `$${formatMoney(detalleItem.costo_actual)}` : '—' }}
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="detalle-metric-card">
                  <div class="detalle-metric-card__label">Cód. de barras</div>
                  <div class="detalle-metric-card__value font-monospace" style="font-size:0.85rem; word-break:break-all">
                    {{ detalleItem.cod_barra || '—' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Alerta de vencimiento próximo -->
            <div
              v-if="detalleItem.vencimiento_proximo"
              class="alert d-flex align-items-center gap-2 py-2 mb-3"
              :class="alertaVencimientoClass(detalleItem.vencimiento_proximo)"
              role="alert"
            >
              <i class="bi bi-calendar-x fs-5 flex-shrink-0"></i>
              <span class="small">
                <strong>Vencimiento más próximo:</strong>
                {{ formatFecha(detalleItem.vencimiento_proximo) }} — {{ diasHastaVencimiento(detalleItem.vencimiento_proximo) }}
              </span>
            </div>

            <!-- Tabla de lotes -->
            <h6 class="fw-bold text-uppercase text-secondary mb-2" style="font-size:0.75rem; letter-spacing:0.5px">
              <i class="bi bi-list-ul me-1"></i> Historial de ingresos
            </h6>

            <div v-if="lotesDelDetalle.length === 0" class="text-center py-4 text-muted bg-light rounded-3">
              <i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>
              No hay ingresos registrados para este producto.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-sm table-hover align-middle border rounded-3 overflow-hidden mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-3 text-uppercase text-muted fw-bold py-2" style="font-size:0.7rem">Fecha ingreso</th>
                    <th class="text-uppercase text-muted fw-bold py-2 text-end" style="font-size:0.7rem">Cant. Orig</th>
                    <th class="text-uppercase text-muted fw-bold py-2 text-end" style="font-size:0.7rem">Vendida</th>
                    <th class="text-uppercase text-muted fw-bold py-2 text-end" style="font-size:0.7rem">Disponible</th>
                    <th class="text-uppercase text-muted fw-bold py-2 text-center" style="font-size:0.7rem">Vencimiento</th>
                    <th class="pe-3 text-uppercase text-muted fw-bold py-2 text-center" style="font-size:0.7rem">Perecedero</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="lote in lotesDelDetalle" :key="lote.id">
                    <td class="ps-3 text-muted small">{{ formatFecha(lote.fecha_ingreso) }}</td>
                    <td class="text-end text-muted small">{{ lote.cantidad }}</td>
                    <td class="text-end text-danger small">{{ lote.cantidad_vendida }}</td>
                    <td class="text-end fw-bold small" :class="Number(lote.cantidad_disponible) > 0 ? 'text-success' : 'text-danger'">
                      {{ lote.cantidad_disponible }}
                    </td>
                    <td class="text-center">
                      <span v-if="lote.vencimiento" class="badge rounded-pill px-2" style="font-size:0.68rem" :class="vencimientoBadgeClass(lote.vencimiento)">
                        {{ formatFecha(lote.vencimiento) }}
                      </span>
                      <span v-else class="text-muted small">—</span>
                    </td>
                    <td class="pe-3 text-center">
                      <span
                        class="badge rounded-pill px-2"
                        style="font-size:0.68rem"
                        :class="Number(lote.es_perecedero) ? 'bg-orange-subtle text-orange' : 'bg-secondary-subtle text-secondary'"
                      >
                        <i class="bi me-1" :class="Number(lote.es_perecedero) ? 'bi-check-circle-fill' : 'bi-dash'"></i>
                        {{ Number(lote.es_perecedero) ? 'Sí' : 'No' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td class="ps-3 fw-bold text-dark small" colspan="1">Totales</td>
                    <td class="text-end fw-bold text-secondary small">{{ totalIngresadoDetalle }}</td>
                    <td colspan="1"></td>
                    <td class="text-end fw-bold text-primary-custom small">{{ totalDisponibleDetalle }}</td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div class="modal-footer border-top-0 pt-0">
            <button class="btn btn-light px-4" @click="showDetalle = false">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import articulosService from '@/services/articulosService';
import { useToastStore } from '@/stores/toastStore';
import { formatMoney } from '@/utils/formatters';

const toast = useToastStore();
const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';
const { sortKey, sortDir, handleSort, sortItems } = useSorting('nombre', 'asc');

// ─── Constantes configurables ───────────────────────────────────────────────
const DIAS_ALERTA_VENCIMIENTO = 30;

// ─── Columnas de la tabla ────────────────────────────────────────────────────
const columns = [
  { key: 'imagen',              label: '',                  sortable: false, thClass: 'ps-4 py-3', thStyle: 'width: 50px' },
  { key: 'nombre',              label: 'Producto',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cod_barra',           label: 'Cód. Barra',        sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'id_lote',             label: 'Lote',              sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cantidad_original',   label: 'Ingreso',          sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width:120px' },
  { key: 'stock_actual',        label: 'Disponible',        sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width:140px' },
  { key: 'vencimiento_proximo', label: 'Vencimiento',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width:175px' },
  { key: 'precio_actual',       label: 'Precio',            sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end',   thStyle: 'width:110px' },
  { key: 'acciones',            label: 'Detalle',           sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width:80px' },
];

// ─── Estado ──────────────────────────────────────────────────────────────────
const articulos = ref([]);
const ingresos  = ref([]);
const loading   = ref(false);

const searchQuery     = ref('');
const filtroCategoria = ref('');
const filtroAlerta    = ref('');

/** Lista de productos únicos para el buscador desplegable */
const articulosUnicos = computed(() => {
  const seen = new Set();
  return articulos.value.filter(a => {
    if (seen.has(a.id)) return false;
    seen.add(a.id);
    return true;
  });
});

const showDetalle = ref(false);
const detalleItem = ref(null);

// ─── Helpers de fecha ────────────────────────────────────────────────────────
const todayStr = () => new Date().toISOString().split('T')[0];

const formatFecha = (fechaStr) => {
  if (!fechaStr) return '—';
  const part = String(fechaStr).split('T')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const diasHastaVencimiento = (fechaStr) => {
  if (!fechaStr) return '';
  const diff = Math.ceil((new Date(fechaStr) - new Date(todayStr())) / 86_400_000);
  if (diff < 0) return `Vencido hace ${Math.abs(diff)} día${Math.abs(diff) !== 1 ? 's' : ''}`;
  if (diff === 0) return 'Vence hoy';
  return `En ${diff} día${diff !== 1 ? 's' : ''}`;
};

// ─── Helpers de stock ────────────────────────────────────────────────────────
const stockColorClass = (stock, rop = 1) => {
  const s = Number(stock);
  const r = Number(rop || 1);
  if (s <= 0) return 'text-danger';
  if (s <= r) return 'text-warning';
  return 'text-success';
};

const stockBadgeClass = (stock, rop = 1) => {
  const s = Number(stock);
  const r = Number(rop || 1);
  if (s <= 0) return 'bg-danger text-white';
  if (s <= r) return 'bg-warning-subtle text-warning';
  return 'bg-success-subtle text-success';
};

const stockLabel = (stock, rop = 1) => {
  const s = Number(stock);
  const r = Number(rop || 1);
  if (s <= 0) return 'Sin stock';
  if (s <= r) return 'Stock bajo';
  return 'Disponible';
};

const stockMetricVariant = (stock, rop = 1) => {
  const s = Number(stock);
  const r = Number(rop || 1);
  if (s <= 0) return 'detalle-metric-card--danger';
  if (s <= r) return 'detalle-metric-card--warning';
  return 'detalle-metric-card--success';
};

// ─── Helpers de vencimiento ──────────────────────────────────────────────────
const vencimientoBadgeClass = (fechaStr) => {
  if (!fechaStr) return 'bg-secondary-subtle text-secondary';
  const diff = Math.ceil((new Date(fechaStr) - new Date(todayStr())) / 86_400_000);
  if (diff < 0)  return 'bg-danger text-white';
  if (diff <= 7) return 'bg-danger-subtle text-danger';
  if (diff <= DIAS_ALERTA_VENCIMIENTO) return 'bg-warning-subtle text-warning';
  return 'bg-secondary-subtle text-secondary';
};

const alertaVencimientoClass = (fechaStr) => {
  if (!fechaStr) return '';
  const diff = Math.ceil((new Date(fechaStr) - new Date(todayStr())) / 86_400_000);
  if (diff < 0)  return 'alert-danger';
  if (diff <= DIAS_ALERTA_VENCIMIENTO) return 'alert-warning';
  return 'alert-info';
};

// ─── Datos computados ────────────────────────────────────────────────────────
const categorias = computed(() =>
  [...new Set(articulos.value.map(a => a.categoria_descripcion).filter(Boolean))].sort()
);

/** Artículos enriquecidos y desagrupados por ingresos para mostrar trazabilidad de vencimientos */
const articulosEnriquecidos = computed(() => {
  const result = [];
  
  articulos.value.forEach(art => {
    const ingresosArt = ingresos.value.filter(i => Number(i.id_articulo) === Number(art.id));

    if (ingresosArt.length === 0) {
      // Si no tiene ingresos registrados, mostrar al menos el registro base
      result.push({
        ...art,
        stock_actual: Number(art.stock_actual ?? 0),
        vencimiento_proximo: null,
        fecha_ingreso_lote: null,
        cantidad_lote: Number(art.stock_actual ?? 0),
        cantidad_original: Number(art.stock_actual ?? 0),
        cantidad_vendida: 0,
        es_lote: false
      });
    } else {
      // Desagrupar por cada ingreso
      ingresosArt.forEach(ing => {
        const cantOriginal = Number(ing.cantidad ?? 0);
        const cantVendida = Number(ing.cantidad_vendida ?? 0);
        const stockDisponibleLote = Math.max(0, cantOriginal - cantVendida);

        result.push({
          ...art,
          stock_actual: Number(art.stock_actual ?? 0), // Stock total del producto informado por API
          vencimiento_proximo: ing.vencimiento ? String(ing.vencimiento).split('T')[0] : null,
          fecha_ingreso_lote: ing.fecha_ingreso ? String(ing.fecha_ingreso).split('T')[0] : null,
          cantidad_lote: stockDisponibleLote,
          cantidad_original: cantOriginal,
          cantidad_vendida: cantVendida,
          es_lote: true,
          id_lote: ing.id
        });
      });
    }
  });

  return result;
});

/** Estadísticas para las tarjetas de resumen */
const stats = computed(() => {
  const hoy = todayStr();
  return {
    total: articulosEnriquecidos.value.length,
    sinStock: articulosEnriquecidos.value.filter(a => a.stock_actual <= 0).length,
    stockBajo: articulosEnriquecidos.value.filter(a => a.stock_actual > 0 && a.stock_actual <= (a.ROP || 1)).length,
    porVencer: articulosEnriquecidos.value.filter(a => {
      if (!a.vencimiento_proximo || a.cantidad_lote <= 0) return false;
      const diff = Math.ceil((new Date(a.vencimiento_proximo) - new Date(hoy)) / 86_400_000);
      return diff >= 0 && diff <= DIAS_ALERTA_VENCIMIENTO;
    }).length,
    vencidos: articulosEnriquecidos.value.filter(a => {
      if (!a.vencimiento_proximo || a.cantidad_lote <= 0) return false;
      return a.vencimiento_proximo < hoy;
    }).length,
  };
});

const stockFiltrado = computed(() => {
  const hoy = todayStr();
  let items = [...articulosEnriquecidos.value];

  // Búsqueda de texto
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(a =>
      a.nombre?.toLowerCase().includes(q) ||
      a.categoria_descripcion?.toLowerCase().includes(q) ||
      a.cod_barra?.toLowerCase().includes(q)
    );
  }

  // Filtro por categoría
  if (filtroCategoria.value) {
    items = items.filter(a => a.categoria_descripcion === filtroCategoria.value);
  }

  // Filtro por alerta
  if (filtroAlerta.value) {
    items = items.filter(a => {
      const s = a.stock_actual; // Usamos el stock total del producto para alertas de stock bajo
      const r = Number(a.ROP || 1);
      const venc = a.vencimiento_proximo;
      switch (filtroAlerta.value) {
        case 'sin_stock':   return s <= 0;
        case 'stock_bajo':  return s > 0 && s <= r;
        case 'por_vencer': {
          if (!venc || a.cantidad_lote <= 0) return false;
          const diff = Math.ceil((new Date(venc) - new Date(hoy)) / 86_400_000);
          return diff >= 0 && diff <= DIAS_ALERTA_VENCIMIENTO;
        }
        case 'vencido': return venc ? venc < hoy : false;
        case 'ok': {
          if (s <= r) return false;
          if (!venc) return true;
          const diff = Math.ceil((new Date(venc) - new Date(hoy)) / 86_400_000);
          return diff > DIAS_ALERTA_VENCIMIENTO;
        }
        default: return true;
      }
    });
  }

  // Aplicar lógica FEFO por defecto si no hay ordenamiento manual activo
  // O si el usuario pide explícitamente ordenar por vencimiento
  if (sortKey.value === 'nombre' && sortDir.value === 'asc') {
    // Si es el orden por defecto, priorizamos FEFO: Vencimiento ASC, luego Fecha Ingreso ASC
    items.sort((a, b) => {
      // 1. Tratar nulos en vencimiento (mandar al final)
      if (a.vencimiento_proximo && !b.vencimiento_proximo) return -1;
      if (!a.vencimiento_proximo && b.vencimiento_proximo) return 1;
      if (a.vencimiento_proximo && b.vencimiento_proximo) {
        const diff = a.vencimiento_proximo.localeCompare(b.vencimiento_proximo);
        if (diff !== 0) return diff;
      }
      
      // 2. Criterio secundario: Fecha de ingreso
      if (a.fecha_ingreso_lote && b.fecha_ingreso_lote) {
        const diffIng = a.fecha_ingreso_lote.localeCompare(b.fecha_ingreso_lote);
        if (diffIng !== 0) return diffIng;
      }

      // 3. Criterio terciario: Nombre
      return (a.nombre || '').localeCompare(b.nombre || '');
    });
    return items;
  }

  return sortItems(items);
});

/** Stock total visible en la tabla */
const stockTotal = computed(() =>
  stockFiltrado.value.reduce((acc, a) => acc + Number(a.cantidad_lote ?? 0), 0)
);

// ─── Datos del modal de detalle ──────────────────────────────────────────────
const lotesDelDetalle = computed(() => {
  if (!detalleItem.value) return [];
  return ingresos.value
    .filter(i => Number(i.id_articulo) === Number(detalleItem.value.id))
    .map(lote => ({
      ...lote,
      cantidad_disponible: Math.max(0, Number(lote.cantidad ?? 0) - Number(lote.cantidad_vendida ?? 0))
    }))
    .sort((a, b) => String(b.fecha_ingreso).localeCompare(String(a.fecha_ingreso)));
});

const totalIngresadoDetalle = computed(() =>
  lotesDelDetalle.value.reduce((acc, l) => acc + Number(l.cantidad ?? 0), 0)
);

const totalDisponibleDetalle = computed(() =>
  lotesDelDetalle.value.reduce((acc, l) => acc + Number(l.cantidad_disponible ?? 0), 0)
);

// ─── Acciones ────────────────────────────────────────────────────────────────
const verDetalle = (item) => {
  detalleItem.value = item;
  showDetalle.value = true;
};

const toggleFiltroAlerta = (valor) => {
  filtroAlerta.value = filtroAlerta.value === valor ? '' : valor;
};

const limpiarFiltros = () => {
  searchQuery.value = '';
  filtroCategoria.value = '';
  filtroAlerta.value = '';
};

const fetchData = async () => {
  loading.value = true;
  try {
    [articulos.value, ingresos.value] = await Promise.all([
      articulosService.getAllActivos(),
      articulosService.getIngresos(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar el stock de productos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

onMounted(fetchData);
</script>

<style scoped>
/* ── Utilidades locales ──────────────────────────────────────── */
.fs-xs { font-size: 0.75rem; }

.loading-overlay-local {
  position: absolute;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.88);
  z-index: 10;
}

/* Fila con stock 0 */
.table-danger-soft { background-color: rgba(220, 53, 69, 0.04) !important; }

/* ── Tarjetas de resumen ─────────────────────────────────────── */
.stock-summary-card {
  border-radius: 14px;
  padding: 1.1rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: 2px solid transparent;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  user-select: none;
}

.stock-summary-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
}

.stock-summary-card--active {
  border-color: currentColor !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.14) !important;
}

.stock-summary-card__icon {
  font-size: 1.6rem;
  margin-bottom: 0.4rem;
  line-height: 1;
}

.stock-summary-card__value {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1.1;
}

.stock-summary-card__label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-top: 0.3rem;
  opacity: 0.8;
}

/* Variantes de color de las tarjetas */
.stock-summary-card--total {
  background: linear-gradient(135deg, #e8f4fd, #d0eaf8);
  color: #00558c;
  border-color: #b8dcf5;
}

.stock-summary-card--danger {
  background: linear-gradient(135deg, #fdeaea, #fcd5d5);
  color: #842029;
  border-color: #f5c2c7;
}

.stock-summary-card--warning {
  background: linear-gradient(135deg, #fff8e1, #ffeeba);
  color: #856404;
  border-color: #ffe082;
}

.stock-summary-card--info {
  background: linear-gradient(135deg, #e4f3f4, #c8eaec);
  color: #0a6069;
  border-color: #a3d9df;
}
.stock-summary-card--vencido {
  background: linear-gradient(135deg, #f8f9fa, #e9ecef);
  color: #495057;
  border-color: #dee2e6;
}
/* ── Tarjetas métricas del modal ─────────────────────────────── */
.detalle-metric-card {
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 10px;
  padding: 0.85rem 0.75rem;
  text-align: center;
  height: 100%;
}

.detalle-metric-card__label {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: #6c757d;
  margin-bottom: 0.3rem;
}

.detalle-metric-card__value {
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.1;
  color: #333;
}

.detalle-metric-card__sublabel {
  font-size: 0.68rem;
  font-weight: 600;
  margin-top: 0.25rem;
  color: #6c757d;
}

.detalle-metric-card--success .detalle-metric-card__value { color: #198754; }
.detalle-metric-card--warning .detalle-metric-card__value { color: #856404; }
.detalle-metric-card--danger  .detalle-metric-card__value { color: #dc3545; }

/* ── Perecedero naranja ──────────────────────────────────────── */
.bg-orange-subtle { background-color: rgba(253, 126, 20, 0.15); }
.text-orange { color: #e07b00; }
</style>
