<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <!-- ── Encabezado ─────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">VENTAS</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small d-none d-md-inline">Presioná <kbd>N</kbd> para nueva venta</span>
        <button @click="openVentaModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva Venta
        </button>
      </div>
    </div>

    <!-- ── Buscador ───────────────────────────────────────────── -->
    <div class="mb-4">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por cliente, mesa/equipo o descripción..." />
    </div>

    <!-- ── VENTAS ABIERTAS ────────────────────────────────────── -->
    <div class="mb-4">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="ventas-section-badge ventas-section-badge--abierta">
          <i class="bi bi-folder2-open me-1"></i> ABIERTAS
        </span>
        <span class="badge rounded-pill bg-warning-subtle text-warning fw-bold">{{ ventasAbiertas.length }}</span>
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary-custom" role="status" style="width:2.5rem;height:2.5rem">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>

      <div v-else-if="ventasAbiertas.length === 0" class="venta-empty-state">
        <i class="bi bi-inbox"></i>
        <span>No hay ventas abiertas</span>
      </div>

      <div v-else class="ventas-grid">
        <div
          v-for="venta in ventasAbiertas"
          :key="venta.id"
          class="venta-card venta-card--abierta"
          :class="{ 'venta-card--expanded': ventaExpandida === venta.id }"
        >
          <!-- Cabecera de la tarjeta -->
          <div class="venta-card__header">
            <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
              <span class="venta-card__id">#{{ venta.id }}-{{ venta.simbolo }}</span>
              <div class="min-w-0">
                <div class="fw-semibold text-dark lh-sm text-truncate">
                  {{ venta.cliente_nombre || 'Sin cliente' }}
                </div>
                <div class="text-muted" style="font-size:0.78rem">
                  <span v-if="venta.equipo_nombre" class="badge bg-primary-subtle text-primary-custom rounded-pill px-2 me-1">
                    <i class="bi bi-table me-1" style="font-size:0.6rem"></i>{{ venta.equipo_nombre }}
                  </span>
                  {{ formatFecha(venta.fecha) }}
                </div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
              <button @click="openVentaModal(venta)" class="btn btn-sm btn-outline-primary-modern d-flex align-items-center px-2 py-1" title="Agregar artículos">
                <i class="bi bi-cart-plus me-1"></i>
                <span class="d-none d-sm-inline">Productos</span>
              </button>
              <button @click="iniciarCierreVenta(venta)" class="btn btn-sm btn-success-modern d-flex align-items-center px-2 py-1" title="Cerrar venta">
                <i class="bi bi-check2-circle me-1"></i>
                <span class="d-none d-sm-inline">Cerrar</span>
              </button>
              <button @click="prepareDeleteVenta(venta.id)" class="btn btn-link link-danger p-0 ms-1" title="Eliminar">
                <i class="bi bi-trash3 fs-5"></i>
              </button>
            </div>
          </div>

          <!-- Detalle expandido -->
          <div v-if="ventaExpandida === venta.id" class="venta-card__detalle">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-primary-custom fw-bold small">
                <i class="bi bi-box-seam me-1"></i>Artículos
              </span>
              <button @click="openVentaModal(venta)" class="btn btn-sm btn-outline-primary-modern">
                <i class="bi bi-plus me-1"></i>Agregar artículo
              </button>
            </div>
            <div v-if="loadingDetalle" class="text-center py-2">
              <div class="spinner-border spinner-border-sm text-primary-custom"></div>
            </div>
            <table v-else class="table table-sm align-middle mb-1">
              <thead>
                <tr>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1">Artículo</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Cant.</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">P.Unit.</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Total</th>
                  <th class="py-1"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="av in articulosDeVenta" :key="av.id_articulo_venta">
                  <td class="fw-medium small">{{ av.articulo_nombre }}</td>
                  <td class="text-end small">{{ av.cantidad }}</td>
                  <td class="text-end small text-muted">${{ Number(av.precio_unitario).toFixed(2) }}</td>
                  <td class="text-end small fw-semibold">${{ Number(av.total).toFixed(2) }}</td>
                  <td class="text-end">
                    <button @click="prepareDeleteArticuloVenta(av.id_articulo_venta)" class="btn btn-link link-danger p-0">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="articulosDeVenta.length === 0">
                  <td colspan="5" class="text-center text-muted py-2 small">Sin artículos cargados aún.</td>
                </tr>
              </tbody>
              <tfoot v-if="articulosDeVenta.length > 0">
                <tr class="border-top">
                  <td colspan="3" class="text-end fw-bold small py-1">Total:</td>
                  <td class="text-end fw-bold small py-1">${{ totalDetalleVenta }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>

            <!-- Cerrar venta rápido -->
            <div class="d-flex justify-content-end mt-2">
              <button
                @click="iniciarCierreVenta(venta)"
                class="btn btn-sm btn-success-modern d-flex align-items-center gap-1"
                :disabled="articulosDeVenta.length === 0"
              >
                <i class="bi bi-check2-circle"></i> Cerrar venta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── VENTAS CERRADAS ────────────────────────────────────── -->
    <div class="mt-4">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="ventas-section-badge ventas-section-badge--cerrada">
          <i class="bi bi-check-circle me-1"></i> CERRADAS
        </span>
        <span class="badge rounded-pill bg-success-subtle text-success fw-bold">{{ ventasCerradas.length }}</span>
        <button class="btn btn-link link-secondary p-0 ms-1 small" @click="mostrarCerradas = !mostrarCerradas" style="font-size:0.8rem">
          {{ mostrarCerradas ? 'Ocultar' : 'Mostrar' }}
        </button>
      </div>

      <div v-if="mostrarCerradas">
        <div v-if="loading" class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-primary-custom"></div>
        </div>
        <div v-else-if="ventasCerradas.length === 0" class="venta-empty-state">
          <i class="bi bi-archive"></i>
          <span>No hay ventas cerradas</span>
        </div>
        <div v-else class="card shadow-sm border-0 rounded-3 overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <SortableTableHead :columns="columnsCerradas" :sort-key="sortKey" :sort-dir="sortDir" @sort="handleSort" />
              <tbody class="bg-white">
                <template v-for="venta in ventasCerradas" :key="venta.id">
                  <tr :class="{ 'table-active': ventaExpandida === venta.id }">
                    <td class="ps-4 text-muted fw-bold small">{{ venta.id }}-{{ venta.simbolo }}</td>
                    <td class="text-muted small">{{ formatFecha(venta.fecha) }}</td>
                    <td class="fw-medium text-dark small">{{ venta.cliente_nombre || '—' }}</td>
                    <td class="small">
                      <span v-if="venta.equipo_nombre" class="badge bg-primary-subtle text-primary-custom rounded-pill px-2">
                        {{ venta.equipo_nombre }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="text-muted small">
                      <span v-if="venta.tipo_vta === 1" class="badge bg-info-subtle text-info border border-info-subtle px-2">Común</span>
                      <span v-else-if="venta.tipo_vta === 0" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2">A Cuenta</span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="pe-4 text-end">
                      <button @click="toggleDetalle(venta.id)" class="btn btn-link link-secondary p-1 me-1" :title="ventaExpandida === venta.id ? 'Ocultar' : 'Ver'">
                        <i class="bi fs-5" :class="ventaExpandida === venta.id ? 'bi-chevron-up' : 'bi-list-ul'"></i>
                      </button>
                      <button @click="prepareDeleteVenta(venta.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                        <i class="bi bi-trash3 fs-5"></i>
                      </button>
                    </td>
                  </tr>
                  <!-- Detalle expandido de cerrada -->
                  <tr v-if="ventaExpandida === venta.id" class="bg-light">
                    <td colspan="6" class="px-4 py-3">
                      <div v-if="loadingDetalle" class="text-center py-2">
                        <div class="spinner-border spinner-border-sm text-primary-custom"></div>
                      </div>
                      <table v-else class="table table-sm align-middle mb-0">
                        <thead>
                          <tr>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1">Artículo</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Cant.</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">P.Unit.</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="av in articulosDeVenta" :key="av.id_articulo_venta">
                            <td class="fw-medium small">{{ av.articulo_nombre }}</td>
                            <td class="text-end small">{{ av.cantidad }}</td>
                            <td class="text-end small text-muted">${{ Number(av.precio_unitario).toFixed(2) }}</td>
                            <td class="text-end small fw-semibold">${{ Number(av.total).toFixed(2) }}</td>
                          </tr>
                          <tr v-if="articulosDeVenta.length === 0">
                            <td colspan="4" class="text-center text-muted py-2 small">Sin artículos.</td>
                          </tr>
                          <tr v-if="articulosDeVenta.length > 0" class="border-top">
                            <td colspan="3" class="text-end fw-bold small py-1">Total:</td>
                            <td class="text-end fw-bold small py-1">${{ totalDetalleVenta }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Eliminar Venta -->
    <ConfirmModal
      v-model="showDeleteVentaModal"
      title="Eliminar Venta"
      message="¿Estás seguro de eliminar esta venta? Se eliminarán también sus artículos asociados."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDeleteVenta"
    />

    <!-- Confirm Eliminar Artículo de Venta -->
    <ConfirmModal
      v-model="showDeleteAvModal"
      title="Quitar Artículo"
      message="¿Estás seguro de quitar este artículo de la venta?"
      confirm-button-text="Quitar"
      variant="danger"
      :is-loading="isDeletingAv"
      @confirm="confirmDeleteArticuloVenta"
    />

    <!-- Modal de Venta Unificado -->
    <VentaModal
      v-model="showVentaModal"
      :is-editing="isEditing"
      :initial-form="ventaForm"
      :clientes="clientes"
      :equipos="equipos"
      :estados-venta="estadosVenta"
      :medios-cobro="mediosCobro"
      :articulos="articulos"
      :id-estado-cerrada="ID_ESTADO_CERRADA"
      :id-cuenta-corriente="ID_CUENTA_CORRIENTE"
      :simbolo-dia="simboloDia"
      @save="handleSaveVenta"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import VentaModal from "@/components/VentaModal.vue";
import ventasService from '@/services/ventasService';
import clientesService from '@/services/clientesService';
import articulosService from '@/services/articulosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import configuracionService from '@/services/configuracionService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const { sortKey, sortDir, handleSort, sortItems } = useSorting('id', 'desc');

const ID_ESTADO_ABIERTA = computed(() =>
  estadosVenta.value.find(e => e.descripcion?.toLowerCase().includes('abierta'))?.id ?? 1
);
const ID_ESTADO_CERRADA = computed(() =>
  estadosVenta.value.find(e => e.descripcion?.toLowerCase().includes('cerrada'))?.id ?? 2
);
const ID_CUENTA_CORRIENTE = computed(() =>
  mediosCobro.value.find(m => m.descripcion?.toLowerCase().includes('cuenta corriente'))?.id ?? null
);

const columnsCerradas = [
  { key: 'id',            label: 'Nro Venta',     sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width:100px' },
  { key: 'fecha',         label: 'Fecha',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cliente_nombre',label: 'Cliente',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_nombre', label: 'Mesa',        sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'tipo_vta',      label: 'Medio pago',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',      label: '',            sortable: false, thClass: 'pe-4 py-3 text-end' },
];

const ventas          = ref([]);
const clientes        = ref([]);
const equipos         = ref([]);
const estadosVenta    = ref([]);
const mediosCobro     = ref([]);
const articulos       = ref([]);
const loading         = ref(false);
const searchQuery     = ref('');
const mostrarCerradas = ref(true);
const simboloDia      = ref('$');

const ventaExpandida    = ref(null);
const articulosDeVenta  = ref([]);
const loadingDetalle    = ref(false);
const totalesVenta      = ref({});

const showVentaModal = ref(false);
const isEditing      = ref(false);
const isSaving       = ref(false);

const emptyVentaForm = () => ({
  id: null,
  fecha: new Date().toISOString().split('T')[0],
  id_cliente: null,
  id_equipo: null,
  id_estado_venta: 3,
  descripcion_cliente: '',
  tipo_vta: 1,
  simbolo: simboloDia.value,
  id_medio_cobro: 1,
  articulos: [],
});

const ventaForm = ref(emptyVentaForm());

const showArticuloVentaModal = ref(false);
const ventaIdParaArticulo    = ref(null);
const isSavingArticulo       = ref(false);
const avForm = ref({ id_articulo: null, cantidad: null, precio_unitario: null });
const avTotalCalculado = computed(() =>
  ((Number(avForm.value.cantidad) || 0) * (Number(avForm.value.precio_unitario) || 0)).toFixed(2)
);

const showDeleteVentaModal = ref(false);
const isDeleting           = ref(false);
const idVentaToDelete      = ref(null);
const showDeleteAvModal    = ref(false);
const isDeletingAv         = ref(false);
const idAvToDelete         = ref(null);

const totalDetalleVenta = computed(() =>
  articulosDeVenta.value.reduce((acc, av) => acc + Number(av.total || 0), 0).toFixed(2)
);

const ventasFiltradas = computed(() => {
  let items = ventas.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(v =>
      v.cliente_nombre?.toLowerCase().includes(q) ||
      v.equipo_nombre?.toLowerCase().includes(q) ||
      v.estado_descripcion?.toLowerCase().includes(q) ||
      v.descripcion_cliente?.toLowerCase().includes(q)
    );
  }
  return sortItems(items);
});

const ventasAbiertas = computed(() =>
  ventasFiltradas.value.filter(v => Number(v.id_estado_venta) === Number(ID_ESTADO_ABIERTA.value))
);
const ventasCerradas = computed(() =>
  ventasFiltradas.value.filter(v => Number(v.id_estado_venta) === Number(ID_ESTADO_CERRADA.value))
);

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const part = String(fecha).split('T')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const fetchData = async () => {
  loading.value = true;
  try {
    [ventas.value, clientes.value, equipos.value, estadosVenta.value, mediosCobro.value, articulos.value, simboloDia.value] = await Promise.all([
      ventasService.getVentas(),
      clientesService.getClientes(),
      datosMaestrosService.getEquipos(),
      datosMaestrosService.getEstadosVenta(),
      datosMaestrosService.getMediosCobro(),
      articulosService.getAllActivos(),
      configuracionService.getSimbolo(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar datos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const toggleDetalle = async (idVenta) => {
  if (ventaExpandida.value === idVenta) {
    ventaExpandida.value = null;
    articulosDeVenta.value = [];
    return;
  }
  ventaExpandida.value = idVenta;
  loadingDetalle.value = true;
  try {
    articulosDeVenta.value = await ventasService.getArticulosDeVenta(idVenta);
    totalesVenta.value[idVenta] = articulosDeVenta.value.reduce((acc, av) => acc + Number(av.total || 0), 0).toFixed(2);
  } catch {
    toast.showToast({ message: 'Error al cargar artículos.', type: 'danger' });
  } finally {
    loadingDetalle.value = false;
  }
};

const openVentaModal = async (item = null, forceCierre = false) => {
  if (item) {
    // MODO EDICIÓN / AGREGAR ARTÍCULOS
    isEditing.value = true;
    loadingDetalle.value = true;
    try {
      // Cargamos los artículos actuales de la venta para que aparezcan en el modal
      const articulosExistentes = await ventasService.getArticulosDeVenta(item.id);
      ventaForm.value = { 
        ...item, 
        simbolo: item.simbolo || simboloDia.value,
        articulos: articulosExistentes.map(av => ({
          id_articulo: av.id_articulo,
          nombre: av.articulo_nombre,
          cantidad: av.cantidad,
          precio_unitario: av.precio_unitario,
          total: av.total,
          stock_actual: av.stock_actual // Asegurar que el componente lo reciba si está disponible
        })),
        forceCierre: forceCierre
      };
      
      if (forceCierre) {
        ventaForm.value.id_estado_venta = ID_ESTADO_CERRADA.value;
      }
    } catch (e) {
      toast.showToast({ message: 'Error al cargar detalles de la venta.', type: 'danger' });
      return;
    } finally {
      loadingDetalle.value = false;
    }
  } else {
    // MODO NUEVA VENTA
    isEditing.value = false;
    ventaForm.value = { 
      ...emptyVentaForm(), 
      id_estado_venta: ID_ESTADO_CERRADA.value,
      simbolo: simboloDia.value,
      articulos: [],
      forceCierre: false
    };
  }
  showVentaModal.value = true;
};

const iniciarCierreVenta = (venta) => {
  openVentaModal(venta, true);
};

const handleSaveVenta = async ({ venta, articulos: articulosCarrito }) => {
  if (isSaving.value) return; // Evitar múltiples clicks
  isSaving.value = true;
  try {
    const esCerrada = Number(venta.id_estado_venta) === Number(ID_ESTADO_CERRADA.value);
    const medioCobroDesc = mediosCobro.value.find(m => m.id === venta.id_medio_cobro)?.descripcion ?? '';
    const payload = {
      ...venta,
      tipo_vta: esCerrada ? medioCobroDesc : '',
      id_estado_cerrada: ID_ESTADO_CERRADA.value, // Enviamos el ID para lógica backend
      articulos: (articulosCarrito || []).map(i => ({
        id_articulo: i.id_articulo,
        cantidad: i.cantidad,
        precio_unitario: i.precio_unitario,
        total: i.total,
      })),
    };

    let idVenta = null;
    if (isEditing.value) {
      await ventasService.actualizarVenta(payload);
      toast.showToast({ message: 'Venta actualizada.', type: 'success' });
      idVenta = venta.id;
    } else {
      const resp = await ventasService.crearVenta(payload);
      toast.showToast({ message: 'Venta creada.', type: 'success' });
      idVenta = resp?.id ?? null;
    }
    showVentaModal.value = false;
    await fetchData();

    if (esCerrada && idVenta) {
      await descargarTicketVenta(idVenta);
    }
  } catch (err) {
    const msg = err?.response?.data?.message || 'Error al guardar.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const descargarTicketVenta = async (idVenta) => {
  try {
    const blob = await ventasService.descargarTicketVenta(idVenta);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ticket_venta_${idVenta}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch {
    toast.showToast({ message: 'Venta guardada. No se pudo generar el ticket.', type: 'warning' });
  }
};

const prepareDeleteVenta = (id) => {
  idVentaToDelete.value = id;
  showDeleteVentaModal.value = true;
};
const confirmDeleteVenta = async () => {
  isDeleting.value = true;
  try {
    await ventasService.eliminarVenta(idVentaToDelete.value);
    toast.showToast({ message: 'Venta eliminada.', type: 'success' });
    showDeleteVentaModal.value = false;
    if (ventaExpandida.value === idVentaToDelete.value) ventaExpandida.value = null;
    await fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar.', type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

const openArticuloVentaModal = (idVenta) => {
  ventaIdParaArticulo.value = idVenta;
  avForm.value = { id_articulo: null, cantidad: null, precio_unitario: null };
  showArticuloVentaModal.value = true;
};
const autocompletarPrecio = () => {
  if (avForm.value.id_articulo) {
    const art = articulos.value.find(a => Number(a.id) === Number(avForm.value.id_articulo));
    if (art && !avForm.value.precio_unitario) avForm.value.precio_unitario = art.precio_actual;
  }
};
const saveArticuloVenta = async () => {
  isSavingArticulo.value = true;
  try {
    await ventasService.crearArticuloVenta({
      id_articulo: avForm.value.id_articulo,
      id_venta: ventaIdParaArticulo.value,
      cantidad: avForm.value.cantidad,
      precio_unitario: avForm.value.precio_unitario,
      total: avTotalCalculado.value,
    });
    toast.showToast({ message: 'Artículo agregado.', type: 'success' });
    showArticuloVentaModal.value = false;
    const idV = ventaExpandida.value;
    ventaExpandida.value = null;
    await toggleDetalle(idV);
  } catch {
    toast.showToast({ message: 'Error.', type: 'danger' });
  } finally {
    isSavingArticulo.value = false;
  }
};

const prepareDeleteArticuloVenta = (id) => {
  idAvToDelete.value = id;
  showDeleteAvModal.value = true;
};
const confirmDeleteArticuloVenta = async () => {
  isDeletingAv.value = true;
  try {
    await ventasService.eliminarArticuloVenta(idAvToDelete.value);
    toast.showToast({ message: 'Artículo quitado.', type: 'success' });
    showDeleteAvModal.value = false;
    const idV = ventaExpandida.value;
    ventaExpandida.value = null;
    await toggleDetalle(idV);
  } catch {
    toast.showToast({ message: 'Error.', type: 'danger' });
  } finally {
    isDeletingAv.value = false;
  }
};

const onKeydown = (e) => {
  if (showVentaModal.value || showArticuloVentaModal.value) return;
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
  if (e.key === 'n' || e.key === 'N') { e.preventDefault(); openVentaModal(); }
};

onMounted(() => { fetchData(); window.addEventListener('keydown', onKeydown); });
onUnmounted(() => { window.removeEventListener('keydown', onKeydown); });
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.ventas-section-badge { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.6px; text-transform: uppercase; padding: 3px 10px; border-radius: 20px; }
.ventas-section-badge--abierta { background: #fff8e1; color: #856404; border: 1px solid #ffe082; }
.ventas-section-badge--cerrada { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.ventas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 0.85rem; }
.venta-card { border-radius: 12px; border: 1.5px solid #dee2e6; background: #fff; transition: all 0.2s; overflow: hidden; }
.venta-card--abierta { border-left: 4px solid #fbbf24; }
.venta-card--expanded { border-color: var(--color-primary); box-shadow: 0 4px 16px rgba(0, 85, 140, 0.1); }
.venta-card__header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; padding: 0.75rem 0.85rem; }
.venta-card__id { font-size: 0.72rem; font-weight: 700; color: #6c757d; padding-top: 2px; }
.venta-card__detalle { padding: 0.75rem 0.85rem 0.85rem; border-top: 1px solid #f1f1f1; background: #f8f9fa; }
.venta-empty-state { display: flex; align-items: center; gap: 0.5rem; color: #adb5bd; font-size: 0.875rem; padding: 1.25rem 0.5rem; }
.btn-success-modern { background-color: #198754; border: none; border-radius: 7px; color: white; font-weight: 600; font-size: 0.8rem; padding: 5px 12px; }
.btn-outline-primary-modern { color: var(--color-primary); border-color: var(--color-primary); font-size: 0.78rem; padding: 3px 10px; border-radius: 7px; }
.btn-outline-primary-modern:hover { background-color: var(--color-primary); color: white; }
</style>
