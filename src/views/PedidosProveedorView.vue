<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">PEDIDOS A PROVEEDORES</h1>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <!-- Filtro de estado -->
        <select v-model="filtroEstado" class="form-select form-select-sm" style="width: auto;">
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendientes</option>
          <option value="recibido">Recibidos</option>
          <option value="cancelado">Cancelados</option>
        </select>
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo Pedido
        </button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loading ? '300px' : 'auto' }">
      <div v-if="loading" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
        <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <SortableTableHead :columns="columns" :sort-key="sortKey" :sort-dir="sortDir" @sort="handleSort" />
          <tbody class="bg-white">
            <tr v-for="item in pedidosFiltrados" :key="item.id_pedido_proveedor">
              <td class="ps-4 text-muted fw-bold">{{ item.id_pedido_proveedor }}</td>
              <td class="fw-medium text-dark">
                {{ item.proveedor_fantasia || item.proveedor_nombre }}
                <small v-if="item.proveedor_fantasia" class="d-block text-muted">{{ item.proveedor_nombre }}</small>
              </td>
              <td class="text-muted">{{ formatDate(item.fecha_pedido) }}</td>
              <td class="text-muted">{{ item.fecha_entrega ? formatDate(item.fecha_entrega) : '—' }}</td>
              <td class="text-center">
                <span class="badge rounded-pill px-3" :class="estadoBadge(item.estado)">
                  {{ item.estado }}
                </span>
              </td>
              <td class="text-center text-muted">{{ item.total_items }}</td>
              <td class="text-end text-muted">{{ formatMonto(item.total_estimado) }}</td>
              <td class="pe-4 text-end">
                <button @click="verDetalle(item)" class="btn btn-link link-primary p-1 me-1" title="Ver detalle">
                  <i class="bi bi-eye fs-4"></i>
                </button>
                <button v-if="item.estado === 'pendiente'" @click="openModal(item)" class="btn btn-link link-secondary p-1 me-1" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button v-if="item.estado === 'pendiente'" @click="confirmarRecibir(item)" class="btn btn-link link-success p-1 me-1" title="Marcar como recibido">
                  <i class="bi bi-check-circle fs-4"></i>
                </button>
                <button v-if="item.estado === 'pendiente'" @click="confirmarCancelar(item)" class="btn btn-link link-warning p-1 me-1" title="Cancelar pedido">
                  <i class="bi bi-x-circle fs-4"></i>
                </button>
                <button v-if="item.estado !== 'recibido'" @click="prepareDelete(item.id_pedido_proveedor)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="pedidosFiltrados.length === 0 && !loading">
              <td colspan="8" class="text-center py-5 text-muted">No hay pedidos para mostrar.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============ MODAL CREAR / EDITAR ============ -->
    <Teleport to="body">
      <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-cart-plus'"></i>
                {{ isEditing ? 'Editar Pedido #' + form.id_pedido_proveedor : 'Nuevo Pedido a Proveedor' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="row g-3 mb-4">
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Proveedor <span class="text-danger">*</span></label>
                    <select v-model="form.id_proveedor" class="form-select" required>
                      <option value="" disabled>Seleccionar proveedor...</option>
                      <option v-for="p in proveedoresActivos" :key="p.id_proveedor" :value="p.id_proveedor">
                        {{ p.nombre_fantasia || p.nombre }} {{ p.apellido || '' }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Fecha de entrega estimada</label>
                    <input v-model="form.fecha_entrega" type="date" class="form-control" />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold">Observaciones</label>
                    <input v-model.trim="form.observaciones" type="text" class="form-control" placeholder="Ej: Pago al contado, entregar en depósito..." />
                  </div>
                </div>

                <!-- Ítems del pedido -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="fw-bold mb-0">Artículos del pedido</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="agregarItem">
                    <i class="bi bi-plus me-1"></i> Agregar artículo
                  </button>
                </div>
                <div class="table-responsive rounded border">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3">Artículo</th>
                        <th style="width: 130px">Cantidad</th>
                        <th style="width: 160px">Precio Unit. Estimado</th>
                        <th style="width: 120px" class="text-end">Subtotal</th>
                        <th style="width: 50px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, idx) in form.items" :key="idx">
                        <td class="ps-3">
                          <select v-model="item.id_articulo" class="form-select form-select-sm" required>
                            <option value="" disabled>Seleccionar artículo...</option>
                            <option v-for="a in articulos" :key="a.id" :value="a.id">
                              {{ a.nombre }}
                            </option>
                          </select>
                        </td>
                        <td>
                          <input v-model.number="item.cantidad" type="number" min="0.01" step="0.01"
                                 class="form-control form-control-sm" placeholder="0" required />
                        </td>
                        <td>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input v-model.number="item.precio_unitario_estimado" type="number" min="0" step="0.01"
                                   class="form-control" placeholder="0.00" />
                          </div>
                        </td>
                        <td class="text-end fw-semibold">
                          {{ formatMonto((item.cantidad || 0) * (item.precio_unitario_estimado || 0)) }}
                        </td>
                        <td class="text-center">
                          <button type="button" class="btn btn-link link-danger p-0" @click="quitarItem(idx)" title="Quitar">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </td>
                      </tr>
                      <tr v-if="form.items.length === 0">
                        <td colspan="5" class="text-center text-muted py-3">Agregue al menos un artículo.</td>
                      </tr>
                    </tbody>
                    <tfoot v-if="form.items.length > 0" class="table-light">
                      <tr>
                        <td colspan="3" class="text-end fw-bold pe-2">Total estimado:</td>
                        <td class="text-end fw-bold">{{ formatMonto(totalEstimado) }}</td>
                        <td></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
              <div class="modal-footer">
                <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
                <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isEditing ? 'Actualizar pedido' : 'Crear pedido' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============ MODAL DETALLE (solo lectura) ============ -->
    <Teleport to="body">
      <div v-if="showDetalleModal && pedidoDetalle" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-file-earmark-text me-2"></i>
                Pedido #{{ pedidoDetalle.id_pedido_proveedor }}
                <span class="badge ms-2" :class="estadoBadge(pedidoDetalle.estado)">{{ pedidoDetalle.estado }}</span>
              </h5>
              <button type="button" class="btn-close" @click="showDetalleModal = false"></button>
            </div>
            <div class="modal-body">
              <div class="row mb-3 g-2">
                <div class="col-6">
                  <small class="text-muted d-block">Proveedor</small>
                  <strong>{{ pedidoDetalle.proveedor_fantasia || pedidoDetalle.proveedor_nombre }} {{ pedidoDetalle.proveedor_apellido || '' }}</strong>
                </div>
                <div class="col-3">
                  <small class="text-muted d-block">Fecha pedido</small>
                  <strong>{{ formatDate(pedidoDetalle.fecha_pedido) }}</strong>
                </div>
                <div class="col-3">
                  <small class="text-muted d-block">Fecha entrega</small>
                  <strong>{{ pedidoDetalle.fecha_entrega ? formatDate(pedidoDetalle.fecha_entrega) : '—' }}</strong>
                </div>
                <div class="col-12" v-if="pedidoDetalle.observaciones">
                  <small class="text-muted d-block">Observaciones</small>
                  <span>{{ pedidoDetalle.observaciones }}</span>
                </div>
              </div>
              <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Artículo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Precio Unit.</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="it in pedidoDetalle.items" :key="it.id">
                    <td>{{ it.articulo_nombre }}</td>
                    <td class="text-center">{{ it.cantidad }}</td>
                    <td class="text-end">{{ formatMonto(it.precio_unitario_estimado) }}</td>
                    <td class="text-end fw-semibold">{{ formatMonto(it.cantidad * it.precio_unitario_estimado) }}</td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="3" class="text-end fw-bold">Total estimado:</td>
                    <td class="text-end fw-bold">
                      {{ formatMonto(pedidoDetalle.items.reduce((acc, it) => acc + (it.cantidad * (it.precio_unitario_estimado || 0)), 0)) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="modal-footer">
              <button @click="showDetalleModal = false" class="btn btn-light px-4">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============ MODALES DE CONFIRMACIÓN ============ -->
    <ConfirmModal
      v-model="showRecibirModal"
      title="Confirmar recepción de pedido"
      :message="`¿Confirmas que recibiste el pedido #${pedidoAccion?.id_pedido_proveedor}?\n\nEsta acción registrará ${pedidoAccion?.total_items} ingreso(s) de artículos al stock y no se puede deshacer.`"
      confirm-button-text="Sí, marcar como recibido"
      variant="success"
      :is-loading="isChangingState"
      @confirm="ejecutarCambioEstado('recibido')"
    />

    <ConfirmModal
      v-model="showCancelarModal"
      title="Cancelar pedido"
      :message="`¿Estás seguro de cancelar el pedido #${pedidoAccion?.id_pedido_proveedor}? Esta acción no se puede deshacer.`"
      confirm-button-text="Sí, cancelar pedido"
      variant="warning"
      :is-loading="isChangingState"
      @confirm="ejecutarCambioEstado('cancelado')"
    />

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Pedido"
      message="¿Estás seguro de eliminar este pedido? Se eliminarán también sus ítems."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import proveedoresService from '@/services/proveedoresService';
import articulosService from '@/services/articulosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const columns = [
  { key: 'id_pedido_proveedor', label: 'ID',         sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 70px' },
  { key: 'proveedor_nombre',    label: 'Proveedor',   sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_pedido',        label: 'Pedido',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_entrega',       label: 'Entrega',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'estado',              label: 'Estado',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'total_items',         label: 'Ítems',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 80px' },
  { key: 'total_estimado',      label: 'Total Est.', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 120px' },
  { key: 'acciones',            label: 'Acciones',    sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 180px' },
];

// Data
const pedidos = ref([]);
const proveedoresActivos = ref([]);
const articulos = ref([]);
const filtroEstado = ref('');

// Estado de UI
const loading = ref(false);
const showFormModal = ref(false);
const showDetalleModal = ref(false);
const showRecibirModal = ref(false);
const showCancelarModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isChangingState = ref(false);
const isDeleting = ref(false);

const pedidoDetalle = ref(null);
const pedidoAccion = ref(null);
const idToDelete = ref(null);

const emptyForm = () => ({
  id_pedido_proveedor: null,
  id_proveedor: '',
  fecha_entrega: '',
  observaciones: '',
  items: [],
});
const form = ref(emptyForm());

// Computed
const pedidosFiltrados = computed(() => {
  const base = filtroEstado.value
    ? pedidos.value.filter(p => p.estado === filtroEstado.value)
    : pedidos.value;
  return sortItems(base);
});

const totalEstimado = computed(() =>
  form.value.items.reduce((acc, it) => acc + ((it.cantidad || 0) * (it.precio_unitario_estimado || 0)), 0)
);

// Helpers
const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d.getTime()) ? val : d.toLocaleDateString('es-AR');
};

const formatMonto = (val) => {
  const n = parseFloat(val);
  return isNaN(n) ? '$0,00' : n.toLocaleString('es-AR', { style: 'currency', currency: 'ARS' });
};

const estadoBadge = (estado) => ({
  'bg-warning-subtle text-warning': estado === 'pendiente',
  'bg-success-subtle text-success': estado === 'recibido',
  'bg-secondary-subtle text-secondary': estado === 'cancelado',
});

// Métodos
const fetchData = async () => {
  loading.value = true;
  try {
    [pedidos.value, proveedoresActivos.value, articulos.value] = await Promise.all([
      proveedoresService.getPedidos(),
      proveedoresService.getProveedoresActivos(),
      articulosService.getAllActivos(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar datos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    // Cargar detalle completo con ítems para editar
    proveedoresService.getPedidoById(item.id_pedido_proveedor).then(detalle => {
      form.value = {
        id_pedido_proveedor: detalle.id_pedido_proveedor,
        id_proveedor: detalle.id_proveedor,
        fecha_entrega: detalle.fecha_entrega || '',
        observaciones: detalle.observaciones || '',
        items: detalle.items.map(it => ({
          id_articulo: it.id_articulo,
          cantidad: parseFloat(it.cantidad),
          precio_unitario_estimado: parseFloat(it.precio_unitario_estimado) || 0,
        })),
      };
      showFormModal.value = true;
    });
  } else {
    isEditing.value = false;
    form.value = emptyForm();
    showFormModal.value = true;
  }
};

const agregarItem = () => {
  form.value.items.push({ id_articulo: '', cantidad: null, precio_unitario_estimado: null });
};

const quitarItem = (idx) => {
  form.value.items.splice(idx, 1);
};

const save = async () => {
  if (!form.value.id_proveedor) {
    toast.showToast({ message: 'Seleccioná un proveedor.', type: 'warning' });
    return;
  }
  if (form.value.items.length === 0) {
    toast.showToast({ message: 'Agregá al menos un artículo al pedido.', type: 'warning' });
    return;
  }
  const itemInvalido = form.value.items.find(it => !it.id_articulo || !it.cantidad || it.cantidad <= 0);
  if (itemInvalido) {
    toast.showToast({ message: 'Todos los artículos deben tener artículo y cantidad mayor a cero.', type: 'warning' });
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await proveedoresService.actualizarPedido(form.value);
      toast.showToast({ message: 'Pedido actualizado correctamente.', type: 'success' });
    } else {
      await proveedoresService.crearPedido(form.value);
      toast.showToast({ message: 'Pedido creado en estado PENDIENTE. El stock no será afectado hasta su recepción.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al guardar el pedido.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const verDetalle = async (item) => {
  try {
    pedidoDetalle.value = await proveedoresService.getPedidoById(item.id_pedido_proveedor);
    showDetalleModal.value = true;
  } catch {
    toast.showToast({ message: 'Error al cargar el detalle del pedido.', type: 'danger' });
  }
};

const confirmarRecibir = (item) => {
  pedidoAccion.value = item;
  showRecibirModal.value = true;
};

const confirmarCancelar = (item) => {
  pedidoAccion.value = item;
  showCancelarModal.value = true;
};

const ejecutarCambioEstado = async (nuevoEstado) => {
  isChangingState.value = true;
  try {
    await proveedoresService.cambiarEstadoPedido(pedidoAccion.value.id_pedido_proveedor, nuevoEstado);
    const msg = nuevoEstado === 'recibido'
      ? '¡Pedido recibido! El stock fue actualizado correctamente.'
      : 'Pedido cancelado.';
    toast.showToast({ message: msg, type: nuevoEstado === 'recibido' ? 'success' : 'info' });
    showRecibirModal.value = false;
    showCancelarModal.value = false;
    fetchData();
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al cambiar el estado del pedido.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isChangingState.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await proveedoresService.eliminarPedido(idToDelete.value);
    toast.showToast({ message: 'Pedido eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al eliminar el pedido.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

onMounted(fetchData);
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }
.loading-overlay-local {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
</style>
