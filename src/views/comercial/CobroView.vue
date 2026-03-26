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
      <button @click="abrirReporte" class="btn-primary-modern btn-sm d-flex align-items-center gap-2">
        <i class="bi bi-bar-chart-line"></i>
        <span class="d-none d-sm-inline">Reporte del día</span>
      </button>
    </div>

    <!-- ── Filtros ─────────────────────────────────────────────── -->
    <div class="filtros-card mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Cliente</label>
          <FuzzySearch
            v-model="filtroCliente"
            :data="[]"
            :keys="[]"
            placeholder="Nombre del cliente..."
          />
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Fecha desde</label>
          <input v-model="filtros.fecha_desde" type="date" class="form-control form-control-sm" />
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label form-label-sm mb-1 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Fecha hasta</label>
          <input v-model="filtros.fecha_hasta" type="date" class="form-control form-control-sm" />
        </div>
        <div class="col-12 col-md-3">
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
            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
          </button>
        </div>
      </div>
    </div>

    <!-- ── Tabs ────────────────────────────────────────────────── -->
    <ul class="nav cobro-tabs mb-4">
      <li class="nav-item">
        <button class="cobro-tab" :class="{ active: tabActiva === 'realizados' }" @click="tabActiva = 'realizados'">
          <i class="bi bi-check-all me-1"></i>Cobros realizados
          <span class="badge ms-1" :class="tabActiva === 'realizados' ? 'bg-primary-custom' : 'bg-secondary-subtle text-secondary'">
            {{ cobrosRealizadosSorted.length }}
          </span>
        </button>
      </li>
      <li class="nav-item">
        <button class="cobro-tab" :class="{ active: tabActiva === 'pendientes' }" @click="tabActiva = 'pendientes'">
          <i class="bi bi-exclamation-circle me-1"></i>Ventas pendientes
          <span class="badge ms-1" :class="tabActiva === 'pendientes' ? 'bg-danger' : 'bg-danger-subtle text-danger'">
            {{ ventasPendientesSorted.length }}
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
         SECCIÓN 1 – Cobros realizados (Unificado)
    ════════════════════════════════════════════════════════════ -->
    <template v-if="!loading && tabActiva === 'realizados'">
      <div v-if="cobrosRealizadosSorted.length === 0" class="empty-state">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="text-muted mt-2">No hay cobros realizados para los filtros seleccionados.</p>
      </div>

      <div v-else class="table-responsive cobro-table-wrapper">
        <table class="table cobro-table align-middle mb-0">
          <SortableTableHead
            :columns="realizadosColumnas"
            :sort-key="realizadosSort.sortKey.value"
            :sort-dir="realizadosSort.sortDir.value"
            the-head-class="cobro-th-container"
            @sort="realizadosSort.handleSort"
          />
          <tbody>
            <tr v-for="cobro in cobrosRealizadosSorted" :key="cobro.id" class="cobro-row">
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
              <td class="text-end fw-bold">${{ formatMonto(totalSeccion(cobrosFiltrados)) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ═══════════════════════════════════════════════════════════
         SECCIÓN 2 – Ventas con cobros pendientes
    ════════════════════════════════════════════════════════════ -->
    <template v-if="!loading && tabActiva === 'pendientes'">
      <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3 rounded-3 border-warning-subtle" style="font-size:0.85rem">
        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
        <span>Estas ventas tienen un saldo pendiente de cobro.</span>
      </div>

      <div v-if="ventasPendientesSorted.length === 0" class="empty-state">
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
              <td class="text-end fw-bold">${{ formatMonto(ventasPendientesSorted.reduce((a, v) => a + Number(v.total_venta), 0)) }}</td>
              <td class="text-end fw-bold text-success">${{ formatMonto(ventasPendientesSorted.reduce((a, v) => a + Number(v.total_cobrado), 0)) }}</td>
              <td class="text-end">
                <span class="badge-saldo">${{ formatMonto(ventasPendientesSorted.reduce((a, v) => a + Number(v.saldo_pendiente), 0)) }}</span>
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </template>

    <!-- ── Modal Registrar Pago ────────────────────────────────── -->    <div v-if="showModalPago" class="modal-backdrop-custom animate-fade-in" @click.self="cerrarModalPago">
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

    <!-- ── Modal Reporte del día ──────────────────────────────── -->
    <div v-if="showModalReporte" class="modal-backdrop-custom animate-fade-in">
      <div class="modal-dialog-custom modal-dialog-reporte-wide">
        <div class="modal-content-custom modern-card shadow-lg">
          <div class="modal-header-custom p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-0 fw-bold text-primary-custom">REPORTE DE COBROS DIARIO</h5>
              <small class="text-muted">Resumen visual y detallado por usuario</small>
            </div>
            <button @click="cerrarReporte" class="btn-close"></button>
          </div>
          <div class="modal-body-custom p-4">
            <!-- Selector de fecha -->
            <div class="mb-4" style="max-width: 250px;">
              <label class="form-label form-label-sm mb-2 text-uppercase fw-semibold text-secondary" style="font-size:0.7rem">Día de Consulta</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event"></i></span>
                <input v-model="reporteFecha" type="date" class="form-control border-start-0 form-control-sm" @change="cargarReporte" />
              </div>
            </div>

            <!-- Spinner -->
            <div v-if="loadingReporte" class="text-center py-5">
              <div class="spinner-border text-primary-custom" role="status">
                <span class="visually-hidden">Cargando…</span>
              </div>
            </div>

            <!-- Sin datos -->
            <div v-else-if="reporteUsuarios.length === 0" class="empty-state py-5">
              <div class="mb-3"><i class="bi bi-inbox fs-1 text-muted"></i></div>
              <h6 class="fw-bold text-secondary">No se encontraron cobros</h6>
              <p class="text-muted small">No hay registros de cobros realizados para la fecha seleccionada.</p>
            </div>

            <!-- Layout Principal -->
            <div v-else class="row g-4">
              <!-- Columna Gráfico -->
              <div class="col-lg-6">
                <div class="reporte-viz-container h-100 p-3 bg-white border rounded-4 shadow-sm">
                  <h6 class="small fw-bold text-secondary text-uppercase mb-4 px-2 border-start border-primary-custom border-3">Distribución Visual</h6>
                  
                  <div class="reporte-barras-wrapper">
                    <div v-for="u in reporteUsuarios" :key="'bar-'+u.id_usuario" class="reporte-columna">
                      <div class="reporte-barra-apilada">
                        <div 
                          v-for="(mp, idx) in u.medios_pago" 
                          :key="mp.id_medio_pago"
                          class="reporte-segmento"
                          :style="{ 
                            height: (mp.total / reporteMaxUsuario * 100) + '%',
                            backgroundColor: mp.color
                          }"
                          :title="`${mp.medio_pago}: $${formatMonto(mp.total)}`"
                        ></div>
                      </div>
                      <div class="reporte-barra-nombre mt-2 fw-bold text-truncate w-100 text-center" style="font-size: 0.7rem;">{{ u.nombre_usuario }}</div>
                      <div class="reporte-barra-monto text-primary-custom fw-bold small">${{ formatMonto(u.total) }}</div>
                    </div>
                  </div>

                  <div class="reporte-leyenda mt-4 p-3 bg-light rounded-3">
                    <div class="row g-2">
                      <div v-for="(color, idx) in leyendaColores" :key="idx" class="col-6">
                        <div class="d-flex align-items-center gap-2">
                          <span class="leyenda-dot shadow-sm" :style="{ backgroundColor: color.color }"></span>
                          <span class="leyenda-text text-truncate">{{ color.label }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Columna Detalle -->
              <div class="col-lg-6">
                <div class="reporte-detalle-scroll custom-scrollbar">
                  <h6 class="small fw-bold text-secondary text-uppercase mb-3 px-2 border-start border-primary-custom border-3">Desglose por Usuario</h6>
                  
                  <div class="reporte-usuarios-compactos">
                    <div v-for="usuario in reporteUsuarios" :key="usuario.id_usuario" class="reporte-usuario-compact mb-3 p-3 bg-white border rounded-2" style="border: 1px solid #e3e6f0;">
                      <div class="reporte-usuario-header d-flex align-items-center gap-2 mb-2">
                        <div class="user-avatar d-flex align-items-center justify-content-center bg-primary-custom text-white rounded-circle fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem; flex-shrink: 0;">
                          {{ usuario.nombre_usuario.charAt(0).toUpperCase() }}
                        </div>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ usuario.nombre_usuario }}</span>
                        <span class="fw-bold text-primary-custom" style="font-size: 0.95rem; flex-shrink: 0;">${{ formatMonto(usuario.total) }}</span>
                      </div>
                      <div class="reporte-usuario-medios">
                        <div v-for="mp in usuario.medios_pago" :key="mp.id_medio_pago" class="reporte-medio-compact d-flex justify-content-between align-items-center px-2 py-1" style="font-size: 0.8rem; border-bottom: 1px dashed #f0f0f0;">
                          <span class="text-muted d-flex align-items-center gap-1" style="min-width: 0; flex: 1; overflow: hidden;">
                            <i class="bi bi-circle-fill" :style="{ color: mp.color, fontSize: '0.5rem', 'flex-shrink': 0 }"></i>
                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ mp.medio_pago }}</span>
                          </span>
                          <span class="fw-bold text-dark" style="flex-shrink: 0; margin-left: 0.5rem;">${{ formatMonto(mp.total) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                </div>

                <!-- Gran total al final -->
                <div class="reporte-gran-total-modern d-flex justify-content-between align-items-center p-3 mt-4 shadow rounded-4 bg-primary-custom text-white">
                  <div class="d-flex align-items-center gap-2 opacity-75">
                    <i class="bi bi-wallet2 fs-4"></i>
                    <span class="fw-bold text-uppercase small">Total General Cobrado</span>
                  </div>
                  <span class="fw-bold fs-4">${{ formatMonto(reporteGranTotal) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import cobrosService from '@/services/comercial/cobrosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';
import { formatMoney } from '@/utils/formatters';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';

const toast = useToastStore();
const userStore = useUserStore();

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
const cobrosRealizados = ref([]);
const ventasPendientes = ref([]);
const filtroCliente    = ref('');

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
    const payload = {
      ...formPago.value,
      id_usuario: userStore.user?.id || 0
    };
    await cobrosService.registrarCobroVenta(payload);
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

// ── Filtrado Difuso para Cobros Realizados ───────────────────
const cobrosFiltrados = computed(() => {
  if (!filtroCliente.value.trim()) return cobrosRealizados.value;
  const q = filtroCliente.value.toLowerCase();
  return cobrosRealizados.value.filter(c => 
    (c.cliente_nombre || 'Sin cliente').toLowerCase().includes(q)
  );
});

// ── Sorting para Cobros realizados ────────────────────────────
const realizadosSort = useSorting('fecha', 'desc');
const realizadosColumnas = [
  { key: 'id', label: 'Nro.', thClass: 'ps-4', sortable: true },
  { key: 'fecha', label: 'Fecha', sortable: true },
  { key: 'cliente_nombre', label: 'Cliente', sortable: true },
  { key: 'monto_total', label: 'Monto', thClass: 'text-end', sortable: true },
  { key: 'medios_pago', label: 'Medios de pago', sortable: false },
];
const cobrosRealizadosSorted = computed(() => 
  realizadosSort.sortItems(cobrosFiltrados.value)
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
  pendientesSort.sortItems(ventasPendientes.value.filter(v => {
    if (!filtroCliente.value.trim()) return true;
    const q = filtroCliente.value.toLowerCase();
    return (v.cliente_nombre || '—').toLowerCase().includes(q);
  }))
);

// ── Helpers ──────────────────────────────────────────────────────
const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const part = String(fecha).split('T')[0].split(' ')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const formatMonto = (value) => {
  if (value === null || value === undefined) return '0';
  return formatMoney(value);
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
    
    // Unificar cobros con y sin cliente
    cobrosRealizados.value = [
      ...sinCliente.map(c => ({ ...c, cliente_nombre: 'Sin cliente' })),
      ...conCliente
    ];
    
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
  filtroCliente.value = '';
  cargarDatos();
};

// ── Reporte del día ──────────────────────────────────────────────
const showModalReporte = ref(false);
const loadingReporte   = ref(false);
const reporteFecha     = ref(getHoy());
const reporteFilas     = ref([]);

/** Agrupa filas planas del backend en [ { id_usuario, nombre_usuario, medios_pago[], total } ] */
const reporteUsuarios = computed(() => {
  const map = new Map();
  for (const fila of reporteFilas.value) {
    const key = fila.id_usuario;
    if (!map.has(key)) {
      map.set(key, { id_usuario: fila.id_usuario, nombre_usuario: fila.nombre_usuario, medios_pago: [], total: 0 });
    }
    const u = map.get(key);
    // Asignar color fijo por medio de pago para consistencia
    const mpId = fila.id_medio_pago;
    u.medios_pago.push({ 
      id_medio_pago: mpId, 
      medio_pago: fila.medio_pago, 
      total: Number(fila.total),
      color: getMedioColor(u.medios_pago.length) // O usar una lógica basada en el nombre del medio
    });
    u.total += Number(fila.total);
  }
  return [...map.values()];
});

/** Colores para los medios de pago en el gráfico */
const getMedioColor = (idx) => {
  const colores = ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#858796', '#36b9cc', '#5a5c69'];
  return colores[idx % colores.length];
};

const reporteMaxUsuario = computed(() => Math.max(...reporteUsuarios.value.map(u => u.total), 1));

const reporteGranTotal = computed(() => reporteUsuarios.value.reduce((sum, u) => sum + u.total, 0));

/** Extrae medios de pago únicos con color fijo para la leyenda */
const leyendaColores = computed(() => {
  const mediosMap = new Map();
  // Primero recolectamos todos los nombres de medios de pago únicos
  const todosLosMedios = new Set();
  reporteUsuarios.value.forEach(u => u.medios_pago.forEach(mp => todosLosMedios.add(mp.medio_pago)));
  
  // Asignamos color por orden de aparición global
  Array.from(todosLosMedios).forEach((medio, idx) => {
    mediosMap.set(medio, getMedioColor(idx));
  });

  // Actualizamos los colores en los objetos de usuario para que coincidan con la leyenda
  reporteUsuarios.value.forEach(u => {
    u.medios_pago.forEach(mp => {
      mp.color = mediosMap.get(mp.medio_pago);
    });
  });

  return Array.from(mediosMap).map(([label, color]) => ({ label, color }));
});

const cargarReporte = async () => {
  loadingReporte.value = true;
  try {
    reporteFilas.value = await cobrosService.getReporteDia({ fecha: reporteFecha.value });
  } catch {
    toast.showToast({ message: 'Error al cargar el reporte.', type: 'danger' });
  } finally {
    loadingReporte.value = false;
  }
};

const abrirReporte = () => {
  showModalReporte.value = true;
  document.body.style.overflow = 'hidden';
  cargarReporte();
};

const cerrarReporte = () => {
  showModalReporte.value = false;
  document.body.style.overflow = '';
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
/* Modal Reporte Wide */
.modal-dialog-reporte-wide {
  width: 90vw !important;
  max-width: 90vw !important;
  margin: 1.75rem auto;
}

@media (min-width: 1400px) {
  .modal-dialog-reporte-wide {
    width: 85vw !important;
    max-width: 85vw !important;
  }
}

@media (max-width: 991px) {
  .modal-dialog-reporte-wide {
    width: 95vw !important;
    max-width: 95vw !important;
  }
}

/* Gráfico Visual */
.reporte-viz-container {
  background: #f8f9fc;
  border-radius: 12px;
  padding: 2.5rem 1rem 1rem 1rem;
  border: 1px solid #eaecf4;
  height: 400px;
  position: relative;
}

.reporte-y-axis {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.y-grid-line {
  position: absolute;
  left: 0;
  right: 0;
  border-top: 1px dashed #e3e6f0;
}

.y-grid-label {
  position: absolute;
  left: -2px;
  top: -10px;
  font-size: 0.65rem;
  color: #858796;
  background: white;
  padding: 0 4px;
  border-radius: 4px;
}

.reporte-bar-stack {
  position: relative;
  z-index: 1;
}

.reporte-bar-column {
  width: 50px;
  display: flex;
  flex-direction: column-reverse;
  background: #eaecf4;
  border-radius: 6px 6px 0 0;
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  min-height: 4px;
}

.reporte-bar-column:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.reporte-bar-segment {
  width: 100%;
  transition: height 0.3s ease;
  position: relative;
}

.reporte-bar-segment:hover::after {
  content: attr(title);
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  background: #333;
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  white-space: nowrap;
  z-index: 10;
}

.reporte-bar-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #4e73df;
  margin-top: 8px;
  text-align: center;
  word-wrap: break-word;
}

/* Detalle por Usuario Moderno */
.reporte-detalles-container {
  max-height: 500px;
  overflow-y: auto;
  padding-right: 8px;
}

.reporte-card-modern {
  background: #fff;
  border: 1px solid #e3e6f0;
  border-radius: 10px;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: border-color 0.2s;
}

.reporte-card-modern:hover {
  border-color: #4e73df;
}

.user-avatar-circle {
  width: 36px;
  height: 36px;
  background: #4e73df;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  box-shadow: 0 2px 4px rgba(78,115,223,0.2);
}

.medio-pago-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 0.75rem;
}

.medio-pago-pill {
  background: #f8f9fc;
  padding: 0.5rem;
  border-radius: 8px;
  border: 1px solid #eaecf4;
}

.medio-pago-pill .label {
  font-size: 0.7rem;
  color: #858796;
  text-transform: uppercase;
  margin-bottom: 2px;
}

.medio-pago-pill .value {
  font-weight: 700;
  color: #2e59d9;
}

/* Leyenda Colores */
.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
  margin-right: 6px;
}

.legend-text {
  font-size: 0.75rem;
  color: #5a5c69;
}

/* Custom Scrollbar */
.reporte-detalles-container::-webkit-scrollbar {
  width: 6px;
}
.reporte-detalles-container::-webkit-scrollbar-thumb {
  background: #d1d3e2;
  border-radius: 10px;
}
.reporte-detalles-container::-webkit-scrollbar-track {
  background: transparent;
}

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
  align-items: flex-start;
  justify-content: center;
  padding: 2rem 0;
  overflow-y: auto;
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

/* ── Modal Reporte del día ──────────────────────────────────────── */
.modal-dialog-reporte {
  max-width: 520px;
}

.reporte-card {
  border: 1px solid var(--color-border, #dee2e6);
  border-radius: 12px;
  overflow: hidden;
}

.reporte-card-header {
  background: var(--color-background-soft, #f8f9fa);
  border-bottom: 1px solid var(--color-border, #dee2e6);
  font-size: 0.9rem;
}

.reporte-total-usuario {
  font-weight: 700;
  font-size: 1rem;
  color: var(--color-primary, #00558c);
}

.reporte-card-body {
  background: #fff;
}

.reporte-medio-row {
  border-bottom: 1px dashed #f0f0f0;
  font-size: 0.875rem;
}

.reporte-medio-row:last-child {
  border-bottom: none;
}

.reporte-gran-total {
  background: var(--color-primary, #00558c);
  border-radius: 10px;
  color: #fff;
}

.reporte-total-general {
  font-size: 1.15rem;
  font-weight: 800;
  color: #fff;
}

/* ── Gráfico de barras apiladas ────────────────────────────────── */
.reporte-barras-wrapper {
  display: flex;
  justify-content: space-around;
  align-items: flex-end;
  height: 160px;
  padding: 10px 20px;
  border-bottom: 2px solid #e9ecef;
  gap: 12px;
}

.reporte-columna {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}

.reporte-barra-apilada {
  width: 32px;
  height: 100%;
  display: flex;
  flex-direction: column-reverse;
  border-radius: 6px 6px 0 0;
  overflow: hidden;
  background: #f1f3f5;
}

.reporte-segmento {
  width: 100%;
  transition: transform 0.3s ease;
  cursor: help;
}

.reporte-segmento:hover {
  filter: brightness(1.15);
}

.reporte-barra-label {
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #6c757d;
  margin-top: 6px;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  width: 40px;
}

.leyenda-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  display: inline-block;
}

.leyenda-text {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6c757d;
}
</style>
