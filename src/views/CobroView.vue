<template>
  <div class="container-fluid p-4 bg-white min-vh-100 animate-fade-in">

    <!-- ── Encabezado ──────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">COBROS</h1>
      </div>
    </div>

    <!-- ── Filtros ─────────────────────────────────────────────── -->
    <div class="filtros-card mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Fecha desde</label>
          <input v-model="filtros.fecha_desde" type="date" class="form-control form-control-sm" />
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Fecha hasta</label>
          <input v-model="filtros.fecha_hasta" type="date" class="form-control form-control-sm" />
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Medio de pago</label>
          <select v-model="filtros.medio_pago_id" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option v-for="m in mediosCobro" :key="m.id" :value="m.id">{{ m.descripcion }}</option>
          </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button @click="aplicarFiltros" class="btn-primary-modern btn-sm flex-fill d-flex align-items-center justify-content-center gap-1">
            <i class="bi bi-search"></i><span class="d-none d-sm-inline">Filtrar</span>
          </button>
          <button @click="limpiarFiltros" class="btn btn-sm btn-outline-secondary flex-fill d-flex align-items-center justify-content-center gap-1" title="Limpiar filtros">
            <i class="bi bi-x-circle"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Tabs ────────────────────────────────────────────────── -->
    <ul class="nav cobro-tabs mb-4">
      <li class="nav-item">
        <button class="cobro-tab" :class="{ active: tabActiva === 'sin-cliente' }" @click="tabActiva = 'sin-cliente'">
          <i class="bi bi-person-dash me-1"></i>Sin cliente
          <span class="badge ms-1" :class="tabActiva === 'sin-cliente' ? 'bg-primary-custom' : 'bg-secondary-subtle text-secondary'">
            {{ cobrosSinCliente.length }}
          </span>
        </button>
      </li>
      <li class="nav-item">
        <button class="cobro-tab" :class="{ active: tabActiva === 'con-cliente' }" @click="tabActiva = 'con-cliente'">
          <i class="bi bi-person-check me-1"></i>Con cliente
          <span class="badge ms-1" :class="tabActiva === 'con-cliente' ? 'bg-primary-custom' : 'bg-secondary-subtle text-secondary'">
            {{ cobrosConCliente.length }}
          </span>
        </button>
      </li>
      <li class="nav-item">
        <button class="cobro-tab" :class="{ active: tabActiva === 'pendientes' }" @click="tabActiva = 'pendientes'">
          <i class="bi bi-exclamation-circle me-1"></i>Ventas pendientes
          <span class="badge ms-1" :class="tabActiva === 'pendientes' ? 'bg-danger' : 'bg-danger-subtle text-danger'">
            {{ ventasPendientes.length }}
          </span>
        </button>
      </li>
    </ul>

    <!-- ── Spinner global ──────────────────────────────────────── -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary-custom" style="width:2.5rem;height:2.5rem" role="status">
        <span class="visually-hidden">Cargando…</span>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECCIÓN 1 – Cobros sin cliente
    ════════════════════════════════════════════════════════════ -->
    <template v-if="!loading && tabActiva === 'sin-cliente'">
      <div v-if="cobrosSinCliente.length === 0" class="empty-state">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="text-muted mt-2">No hay cobros sin cliente para los filtros seleccionados.</p>
      </div>

      <div v-else class="table-responsive cobro-table-wrapper">
        <table class="table cobro-table align-middle mb-0">
          <SortableTableHead
            :columns="sinClienteColumnas"
            :sort-key="sinClienteSort.sortKey.value"
            :sort-dir="sinClienteSort.sortDir.value"
            the-head-class="cobro-th-container"
            @sort="sinClienteSort.handleSort"
          />
          <tbody>
            <tr v-for="cobro in cobrosSinClienteSorted" :key="cobro.id" class="cobro-row">
              <td class="ps-4 fw-semibold text-muted">#{{ cobro.id }}</td>
              <td>{{ formatFecha(cobro.fecha) }}</td>
              <td class="text-end fw-semibold">${{ formatMonto(cobro.monto_total) }}</td>
              <td>
                <span v-if="cobro.medios_pago" class="medios-pago-text">{{ cobro.medios_pago }}</span>
                <span v-else class="text-muted fst-italic small">—</span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="cobro-total-row">
              <td colspan="2" class="ps-4 fw-bold text-secondary text-uppercase small">Total</td>
              <td class="text-end fw-bold">${{ formatMonto(totalSeccion(cobrosSinCliente)) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════
         SECCIÓN 2 – Cobros con cliente
    ════════════════════════════════════════════════════════════ -->
    <template v-if="!loading && tabActiva === 'con-cliente'">
      <div v-if="cobrosConCliente.length === 0" class="empty-state">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="text-muted mt-2">No hay cobros con cliente para los filtros seleccionados.</p>
      </div>

      <div v-else class="table-responsive cobro-table-wrapper">
        <table class="table cobro-table align-middle mb-0">
          <SortableTableHead
            :columns="conClienteColumnas"
            :sort-key="conClienteSort.sortKey.value"
            :sort-dir="conClienteSort.sortDir.value"
            the-head-class="cobro-th-container"
            @sort="conClienteSort.handleSort"
          />
          <tbody>
            <tr v-for="cobro in cobrosConClienteSorted" :key="cobro.id" class="cobro-row">
              <td class="ps-4 fw-semibold text-muted">#{{ cobro.id }}</td>
              <td>{{ formatFecha(cobro.fecha) }}</td>
              <td class="fw-medium">{{ cobro.cliente_nombre }}</td>
              <td class="text-end fw-semibold">${{ formatMonto(cobro.monto_total) }}</td>
              <td>
                <span v-if="cobro.medios_pago" class="medios-pago-text">{{ cobro.medios_pago }}</span>
                <span v-else class="text-muted fst-italic small">—</span>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="cobro-total-row">
              <td colspan="3" class="ps-4 fw-bold text-secondary text-uppercase small">Total</td>
              <td class="text-end fw-bold">${{ formatMonto(totalSeccion(cobrosConCliente)) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════
         SECCIÓN 3 – Ventas con cobros pendientes
    ════════════════════════════════════════════════════════════ -->
    <template v-if="!loading && tabActiva === 'pendientes'">
      <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3 rounded-3 border-warning-subtle" style="font-size:0.85rem">
        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
        <span>Estas ventas tienen un saldo pendiente de cobro.</span>
      </div>

      <div v-if="ventasPendientes.length === 0" class="empty-state">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="text-muted mt-2">¡Todo al día! No hay ventas con cobros pendientes.</p>
      </div>

      <div v-else class="table-responsive cobro-table-wrapper">
        <table class="table cobro-table align-middle mb-0">
          <SortableTableHead
            :columns="pendientesColumnas"
            :sort-key="pendientesSort.sortKey.value"
            :sort-dir="pendientesSort.sortDir.value"
            the-head-class="cobro-th-container"
            @sort="pendientesSort.handleSort"
          />
          <tbody>
            <tr v-for="venta in ventasPendientesSorted" :key="venta.id" class="cobro-row cobro-row--pendiente">
              <td class="ps-4">
                <span class="fw-semibold text-muted">#{{ venta.id }}</span>
                <span v-if="venta.simbolo" class="ms-1 badge bg-light text-secondary border small">{{ venta.simbolo }}</span>
              </td>
              <td>{{ formatFecha(venta.fecha) }}</td>
              <td class="fw-medium">{{ venta.cliente_nombre || '—' }}</td>
              <td>
                <span v-if="venta.equipo_nombre" class="badge bg-primary-subtle text-primary-custom rounded-pill px-2">
                  {{ venta.equipo_nombre }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td class="text-end">${{ formatMonto(venta.total_venta) }}</td>
              <td class="text-end fw-medium text-success">${{ formatMonto(venta.total_cobrado) }}</td>
              <td class="text-end">
                <span class="badge-saldo">${{ formatMonto(venta.saldo_pendiente) }}</span>
              </td>
              <td class="text-center">
                <button @click="abrirModalPago(venta)" class="btn btn-sm btn-outline-success border-0 p-1" title="Registrar entrega/pago">
                  <i class="bi bi-plus-circle-fill fs-5"></i>
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot>
            <tr class="cobro-total-row">
              <td colspan="4" class="ps-4 fw-bold text-secondary text-uppercase small">Saldo total pendiente</td>
              <td class="text-end fw-bold">${{ formatMonto(ventasPendientes.reduce((a, v) => a + Number(v.total_venta), 0)) }}</td>
              <td class="text-end fw-bold text-success">${{ formatMonto(ventasPendientes.reduce((a, v) => a + Number(v.total_cobrado), 0)) }}</td>
              <td class="text-end">
                <span class="badge-saldo">${{ formatMonto(ventasPendientes.reduce((a, v) => a + Number(v.saldo_pendiente), 0)) }}</span>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ── Modal Registrar Pago ────────────────────────────────── -->
    <div v-if="showModalPago" class="modal-backdrop-custom animate-fade-in" @click.self="cerrarModalPago">
      <div class="modal-dialog-custom">
        <div class="modal-content-custom modern-card shadow-lg">
          <div class="modal-header-custom p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0 fw-bold text-primary-custom">REGISTRAR COBRO</h5>
              <small class="text-muted">Venta: #{{ ventaSeleccionada?.id }} - {{ ventaSeleccionada?.cliente_nombre || 'Sin cliente' }}</small>
            </div>
            <button @click="cerrarModalPago" class="btn-close"></button>
          </div>
          <div class="modal-body-custom p-4">
            <div class="row g-3">
              <div class="col-12">
                <div class="p-3 bg-light rounded-3 mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span class="small text-secondary">Saldo Pendiente:</span>
                    <span class="fw-bold text-danger">${{ formatMonto(ventaSeleccionada?.saldo_pendiente) }}</span>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">FECHA</label>
                <input v-model="formPago.fecha" type="date" class="form-control" />
              </div>
              <div class="col-md-6">
                <label class="form-label small fw-bold text-secondary">MEDIO DE PAGO</label>
                <select v-model="formPago.id_medio_pago" class="form-select">
                  <option v-for="m in mediosCobro" :key="m.id" :value="m.id">{{ m.descripcion }}</option>
                </select>
              </div>
              <div class="col-12 mt-3">
                <label class="form-label small fw-bold text-secondary">MONTO A COBRAR</label>
                <div class="input-group">
                  <span class="input-group-text bg-white">$</span>
                  <input v-model.number="formPago.monto" type="number" step="0.01" class="form-control form-control-lg fw-bold text-primary-custom" placeholder="0.00" />
                  <button @click="formPago.monto = ventaSeleccionada.saldo_pendiente" class="btn btn-outline-secondary btn-sm px-2" title="Cobrar todo">
                    saldar total
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer-custom p-4 border-top d-flex gap-2">
            <button @click="cerrarModalPago" class="btn-secondary-modern btn-lg flex-fill">Cancelar</button>
            <button @click="confirmarPago" :disabled="!isPagoValido || isSaving" class="btn-primary-modern btn-lg flex-fill">
              <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
              Registrar Cobro
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import cobrosService from '@/services/cobrosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';

const toast = useToastStore();

// ── Funciones auxiliares para fechas ────────────────────────────
const getHoy = () => new Date().toISOString().split('T')[0];
const getAyer = () => {
  const d = new Date();
  d.setDate(d.getDate() - 1);
  return d.toISOString().split('T')[0];
};

const loading          = ref(false);
const tabActiva        = ref('pendientes');
const mediosCobro      = ref([]);
const cobrosSinCliente = ref([]);
const cobrosConCliente = ref([]);
const ventasPendientes = ref([]);

// ── Modal Pago ────────────────────────────────────────────────
const showModalPago      = ref(false);
const isSaving           = ref(false);
const ventaSeleccionada = ref(null);
const formPago = ref({
  id_venta: null,
  id_medio_pago: 1,
  monto: 0,
  fecha: new Date().toISOString().split('T')[0],
});

const isPagoValido = computed(() => 
  formPago.value.id_medio_pago && 
  formPago.value.monto > 0 && 
  formPago.value.monto <= (ventaSeleccionada.value?.saldo_pendiente || 0) + 0.01 // Permitir pequeños errores de redondeo
);

const abrirModalPago = (venta) => {
  ventaSeleccionada.value = venta;
  formPago.value = {
    id_venta: venta.id,
    id_medio_pago: 1,
    monto: Number(venta.saldo_pendiente),
    fecha: new Date().toISOString().split('T')[0],
  };
  showModalPago.value = true;
};

const cerrarModalPago = () => {
  showModalPago.value = false;
  ventaSeleccionada.value = null;
};

const confirmarPago = async () => {
  if (!isPagoValido.value) return;
  isSaving.value = true;
  try {
    await cobrosService.registrarCobroVenta(formPago.value);
    toast.showToast({ message: 'Cobro registrado exitosamente.', type: 'success' });
    cerrarModalPago();
    cargarDatos(); // Recargar tablas para actualizar saldos
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Error al registrar el cobro.';
    toast.showToast({ message: errorMsg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const filtros = ref({
  fecha_desde:   getAyer(),
  fecha_hasta:   getHoy(),
  medio_pago_id: '',
});

// ── Sorting para Cobros sin cliente ────────────────────────────
const sinClienteSort = useSorting('fecha', 'desc');
const sinClienteColumnas = [
  { key: 'id', label: 'Nro.', thClass: 'ps-4', sortable: true },
  { key: 'fecha', label: 'Fecha', sortable: true },
  { key: 'monto_total', label: 'Monto', thClass: 'text-end', sortable: true },
  { key: 'medios_pago', label: 'Medios de pago', sortable: false },
];
const cobrosSinClienteSorted = computed(() => 
  sinClienteSort.sortItems(cobrosSinCliente.value)
);

// ── Sorting para Cobros con cliente ────────────────────────────
const conClienteSort = useSorting('fecha', 'desc');
const conClienteColumnas = [
  { key: 'id', label: 'Nro.', thClass: 'ps-4', sortable: true },
  { key: 'fecha', label: 'Fecha', sortable: true },
  { key: 'cliente_nombre', label: 'Cliente', sortable: true },
  { key: 'monto_total', label: 'Monto', thClass: 'text-end', sortable: true },
  { key: 'medios_pago', label: 'Medios de pago', sortable: false },
];
const cobrosConClienteSorted = computed(() =>
  conClienteSort.sortItems(cobrosConCliente.value)
);

// ── Sorting para Ventas pendientes ────────────────────────────
const pendientesSort = useSorting('fecha', 'desc');
const pendientesColumnas = [
  { key: 'id', label: 'Venta', thClass: 'ps-4', sortable: true },
  { key: 'fecha', label: 'Fecha', sortable: true },
  { key: 'cliente_nombre', label: 'Cliente', sortable: true },
  { key: 'equipo_nombre', label: 'Equipo', sortable: true },
  { key: 'total_venta', label: 'Total venta', thClass: 'text-end', sortable: true },
  { key: 'total_cobrado', label: 'Cobrado', thClass: 'text-end', sortable: true },
  { key: 'saldo_pendiente', label: 'Saldo', thClass: 'text-end pendiente-header', sortable: true },
  { key: 'acciones', label: 'Acciones', thClass: 'text-center', sortable: false },
];
const ventasPendientesSorted = computed(() =>
  pendientesSort.sortItems(ventasPendientes.value)
);

// ── Helpers ──────────────────────────────────────────────────────
const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const part = String(fecha).split('T')[0].split(' ')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const formatMonto = (value) => {
  if (value === null || value === undefined) return '0,00';
  return Number(value).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const totalSeccion = (lista) => lista.reduce((acc, c) => acc + Number(c.monto_total || 0), 0);

// ── Construir params limpios ─────────────────────────────────────
const buildParams = () => {
  const p = {};
  if (filtros.value.fecha_desde)   p.fecha_desde   = filtros.value.fecha_desde;
  if (filtros.value.fecha_hasta)   p.fecha_hasta   = filtros.value.fecha_hasta;
  if (filtros.value.medio_pago_id) p.medio_pago_id = filtros.value.medio_pago_id;
  return p;
};

// ── Carga de datos ───────────────────────────────────────────────
const cargarDatos = async () => {
  loading.value = true;
  try {
    const params = buildParams();
    const [sinCliente, conCliente, pendientes] = await Promise.all([
      cobrosService.getCobrosSinCliente(params),
      cobrosService.getCobrosConCliente(params),
      cobrosService.getVentasPendientes(params),
    ]);
    cobrosSinCliente.value = sinCliente;
    cobrosConCliente.value = conCliente;
    ventasPendientes.value = pendientes;
  } catch (err) {
    toast.showToast({ message: 'Error al cargar los cobros.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const aplicarFiltros = () => cargarDatos();

const limpiarFiltros = () => {
  filtros.value = { fecha_desde: getAyer(), fecha_hasta: getHoy(), medio_pago_id: '' };
  cargarDatos();
};

onMounted(async () => {
  try {
    mediosCobro.value = await datosMaestrosService.getMediosCobro();
  } catch {
    // No crítico; el filtro quedará sin opciones
  }
  cargarDatos();
});
</script>

<style scoped>
/* ── Filtros card ──────────────────────────────────────────────── */
.filtros-card {
  background: var(--color-background-soft, #f8f9fa);
  border: 1px solid var(--color-border, #dee2e6);
  border-radius: 12px;
  padding: 1rem 1.25rem;
}

/* ── Tabs ──────────────────────────────────────────────────────── */
.cobro-tabs {
  display: flex;
  gap: 0.5rem;
  border-bottom: 2px solid var(--color-border, #dee2e6);
  padding-bottom: 0;
  list-style: none;
  margin: 0;
}

.cobro-tab {
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  color: #6c757d;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.5rem 1rem 0.6rem;
  cursor: pointer;
  transition: all 0.15s ease;
  margin-bottom: -2px;
  border-radius: 0;
  white-space: nowrap;
}

.cobro-tab:hover {
  color: var(--color-primary, #00558c);
}

.cobro-tab.active {
  color: var(--color-primary, #00558c);
  border-bottom-color: var(--color-primary, #00558c);
  font-weight: 600;
}

.bg-primary-custom {
  background-color: var(--color-primary, #00558c) !important;
}

/* ── Tabla ─────────────────────────────────────────────────────── */
.cobro-table-wrapper {
  border: 1px solid var(--color-border, #dee2e6);
  border-radius: 12px;
  overflow: hidden;
}

.cobro-table {
  margin-bottom: 0;
}

/* .cobro-th — Ahora manejado por .cobro-th-container :deep(th) */

.cobro-row td {
  padding: 0.75rem 0.875rem;
  font-size: 0.875rem;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

.cobro-row:last-child td {
  border-bottom: none;
}

.cobro-th-container :deep(th) {
  padding: 0.75rem 0.875rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #6c757d;
  background-color: var(--color-background-soft, #f8f9fa);
  border-bottom: 1px solid var(--color-border, #dee2e6);
  white-space: nowrap;
}

.cobro-th-container :deep(.sortable-th) {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.15s ease;
}

.cobro-th-container :deep(.sortable-th:hover) {
  background-color: rgba(0, 0, 0, 0.05);
}

.cobro-th-container :deep(.sort-icon-wrapper) {
  display: inline-flex;
  align-items: center;
  font-size: 0.65rem;
  line-height: 1;
}

.cobro-th-container :deep(.sort-active) {
  color: var(--bs-primary, #0d6efd);
}

.cobro-th-container :deep(.sort-idle) {
  opacity: 0.25;
}
.cobro-row--pendiente td {
  background-color: #fffbf0;
}

.cobro-row--pendiente:hover td {
  background-color: #fff3cd;
}

/* ── Total footer ──────────────────────────────────────────────── */
.cobro-total-row td {
  padding: 0.6rem 0.875rem;
  font-size: 0.875rem;
  background-color: var(--color-background-soft, #f8f9fa);
  border-top: 2px solid var(--color-border, #dee2e6);
}

/* ── Badges ────────────────────────────────────────────────────── */
.medios-pago-text {
  font-size: 0.8rem;
  color: #495057;
}

.pendiente-header {
  color: #dc3545 !important;
}

.badge-saldo {
  display: inline-block;
  background-color: #fff3cd;
  color: #7a5800;
  border: 1px solid #ffd966;
  border-radius: 20px;
  padding: 0.2rem 0.65rem;
  font-size: 0.8rem;
  font-weight: 700;
  white-space: nowrap;
}

/* ── Empty state ───────────────────────────────────────────────── */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  text-align: center;
}

/* ── Spinner global ──────────────────────────────────────── */
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-dialog-custom {
  width: 90%;
  max-width: 480px;
}

.modal-content-custom {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
}

.modal-header-custom {
  background: #fdfdfd;
}

.btn-secondary-modern {
  background: #f1f3f5;
  border: 1px solid #dee2e6;
  color: #495057;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-secondary-modern:hover {
  background: #e9ecef;
}

.btn-lg {
  font-size: 1rem;
}

/* ── Spinner global ──────────────────────────────────────── */
.animate-fade-in {
  animation: fadeIn 0.25s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive mobile ──────────────────────────────────────────── */
@media (max-width: 576px) {
  .cobro-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
  }

  .cobro-tab {
    font-size: 0.78rem;
    padding: 0.4rem 0.65rem 0.5rem;
  }
}
</style>
