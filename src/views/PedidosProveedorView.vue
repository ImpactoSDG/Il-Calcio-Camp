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
      <div class="d-flex align-items-center gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo Pedido
        </button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="row g-3 mb-4 align-items-end">
      <div class="col-12 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Proveedor</label>
        <select v-model="filtroProveedor" class="form-select">
          <option value="">Todos los proveedores</option>
          <option v-for="p in proveedoresActivos" :key="p.id_proveedor" :value="p.id_proveedor">
            {{ p.nombre_fantasia || p.nombre }}
          </option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Desde</label>
        <input type="date" v-model="filtroFechaDesde" class="form-control" />
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Hasta</label>
        <input type="date" v-model="filtroFechaHasta" class="form-control" />
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Estado</label>
        <select v-model="filtroEstado" class="form-select">
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendientes</option>
          <option value="recibido">Recibidos</option>
          <option value="cancelado">Cancelados</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex gap-1">
        <button @click="resetFilters" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
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
            <tr v-for="item in pedidosFiltrados" :key="item.id_pedido_proveedor" @click="verDetalle(item)" class="cursor-pointer">
              <td class="ps-4 fw-medium text-dark">
                {{ item.proveedor_fantasia || item.proveedor_nombre }}
                <small v-if="item.proveedor_fantasia" class="d-block text-muted">{{ item.proveedor_nombre }}</small>
              </td>
              <td class="text-muted">{{ formatDate(item.fecha_pedido) }}</td>
              <td class="text-muted">{{ item.fecha_entrega ? formatDate(item.fecha_entrega) : '—' }}</td>
              <td class="text-center" @click.stop>
                <select 
                  :value="item.estado" 
                  class="form-select form-select-sm fw-bold rounded-pill px-3 state-select"
                  :class="estadoSelectClass(item.estado)"
                  :disabled="item.estado !== 'pendiente'"
                  @change="(e) => handleEstadoChangeInTable(item, e.target.value)"
                >
                  <option value="pendiente" class="text-dark bg-white">PENDIENTE</option>
                  <option value="recibido" class="text-dark bg-white">RECIBIDO</option>
                  <option value="cancelado" class="text-dark bg-white">CANCELADO</option>
                </select>
              </td>
              <td class="text-center text-muted">{{ item.total_items }}</td>
              <td class="text-end text-muted">{{ formatMonto(item.total_estimado) }}</td>
              <td class="pe-4 text-end" @click.stop>
                <button v-if="item.estado === 'pendiente'" @click="openModal(item)" class="btn btn-link link-secondary p-1 me-1" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(item.id_pedido_proveedor)" class="btn btn-link link-danger p-1" title="Eliminar">
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
      <div v-if="showFormModal" class="modal-fullscreen-container animate-fade-in">
        <div class="modal-fullscreen-content shadow-2xl overflow-hidden d-flex flex-column">
          <!-- Header Minimalista -->
          <div class="modal-header border-0 bg-white p-4 pb-0 d-flex align-items-center justify-content-between flex-shrink-0">
            <div class="d-flex align-items-center gap-3">
              <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px;">
                <i class="bi bi-cart-plus fs-4"></i>
              </div>
              <h5 class="modal-title fw-black text-dark text-uppercase letter-spacing-1 mb-0" style="font-size: 1.1rem;">
                {{ isEditing ? `Pedido #${form.id_pedido_proveedor}` : 'Nuevo Pedido a Proveedor' }}
              </h5>
            </div>
            <button type="button" class="btn-close shadow-none p-2" @click="showFormModal = false"></button>
          </div>

          <form @submit.prevent="save" class="flex-grow-1 d-flex flex-column overflow-hidden">
            <div class="modal-body p-0 flex-grow-1 overflow-hidden">
              <div class="container-fluid h-100 p-0">
                <div class="row g-0 h-100">
                  <!-- COLUMNA 1: PROVEEDOR Y DATOS (1/3) -->
                  <div class="col-lg-4 bg-light-subtle border-end p-4 d-flex flex-column overflow-y-auto custom-scrollbar">
                    <div class="section-container mb-4">
                      <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                          1. DATOS DEL PEDIDO
                        </h6>
                      </div>

                      <div class="row g-3">
                        <div class="col-12">
                          <label class="form-label fw-semibold text-secondary small mb-1">Proveedor <span class="text-danger">*</span></label>
                          <select v-model="form.id_proveedor" class="form-select border-2 rounded-3" required>
                            <option value="" disabled>Seleccionar proveedor...</option>
                            <option v-for="p in proveedoresActivos" :key="p.id_proveedor" :value="p.id_proveedor">
                              {{ p.nombre_fantasia || p.nombre }} {{ p.apellido || '' }}
                            </option>
                          </select>
                        </div>

                        <div class="col-12">
                          <label class="form-label fw-semibold text-secondary small mb-1">Fecha de Entrega Estimada</label>
                          <input v-model="form.fecha_entrega" type="date" class="form-control border-2 rounded-3" />
                        </div>

                        <div class="col-12">
                          <label class="form-label fw-semibold text-secondary small mb-1">Observaciones</label>
                          <textarea v-model.trim="form.observaciones" class="form-control border-2 rounded-3" rows="3" placeholder="Ej: Pago al contado, entregar en depósito..."></textarea>
                        </div>
                      </div>
                    </div>

                    <!-- RESUMEN -->
                    <div class="section-container mt-auto pt-4 border-top">
                      <h6 class="fw-bold mb-3 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                        RESUMEN
                      </h6>

                      <div class="bg-white border-2 rounded-4 p-4 border shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="text-muted fw-medium">Cant. de Artículos:</span>
                          <span class="fw-bold text-dark">{{ form.items.length }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center border-top border-2 pt-2 mt-2">
                          <span class="text-primary fw-black fs-5">TOTAL ESTIMADO:</span>
                          <span class="text-primary fw-black fs-5">{{ formatMonto(totalEstimado) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- COLUMNA 2: ARTÍCULOS (2/3) -->
                  <div class="col-lg-8 bg-white p-4 d-flex flex-column overflow-hidden">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-shrink-0">
                      <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                        2. ARTÍCULOS DEL PEDIDO
                      </h6>
                      <button type="button" class="btn btn-sm btn-primary-modern" @click="agregarItem">
                        <i class="bi bi-plus-circle me-1"></i> Agregar artículo
                      </button>
                    </div>

                    <!-- Tabla de Artículos -->
                    <div class="table-responsive flex-grow-1 custom-scrollbar overflow-y-auto mt-2" style="max-height: calc(100vh - 400px); min-height: 200px; border: 1px solid #f1f3f5; border-radius: 12px; background: #fafbfc; overflow: visible !important;">  
                      <table class="table table-hover align-middle mb-0">      
                        <thead class="bg-light sticky-top" style="z-index: 10;">
                          <tr>
                            <th class="ps-3 border-0 py-3 text-uppercase fs-xs fw-bold text-secondary">Artículo</th>
                            <th class="border-0 py-3 text-uppercase fs-xs fw-bold text-secondary" style="width: 130px">Cantidad</th>
                            <th class="border-0 py-3 text-uppercase fs-xs fw-bold text-secondary" style="width: 180px">Costo</th>
                            <th class="border-0 py-3 text-uppercase fs-xs fw-bold text-secondary text-end" style="width: 140px">Subtotal</th>
                            <th class="border-0 py-3 text-secondary text-center" style="width: 50px"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, idx) in form.items" :key="idx" class="animate-row-in">
                            <td class="ps-3" style="overflow: visible !important;">
                              <FuzzySearch 
                                v-model="item.temp_search"
                                :data="articulos" 
                                :keys="['nombre', 'cod_barra']" 
                                placeholder="Buscar artículo..."
                                @selected="(art) => onArticuloSelectedInItem(idx, art)"
                              >
                                <template #default="{ item: art }">
                                  <div class="d-flex align-items-center w-100 py-1">
                                    <div class="articulo-img-thumb-mini border rounded-2 bg-light me-3 d-flex align-items-center justify-content-center overflow-hidden" 
                                         style="width: 35px; height: 35px; min-width: 35px;">
                                      <img v-if="art.url_imagen" :src="`${apiBaseUrl}/${art.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                                      <i v-else class="bi bi-image text-muted opacity-50" style="font-size: 0.9rem;"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                      <span class="fw-semibold text-dark lh-sm" style="font-size: 0.85rem;">{{ art.nombre }}</span>
                                      <small class="text-muted" style="font-size: 0.75rem;">{{ art.cod_barra }}</small>
                                    </div>
                                  </div>
                                </template>
                              </FuzzySearch>
                            </td>
                            <td>
                              <input v-model.number="item.cantidad" type="number" min="0.01" step="0.01"
                                     class="form-control border-2 rounded-3 text-center fw-bold" placeholder="0" required />
                            </td>
                            <td>
                              <div class="input-group">
                                <span class="input-group-text border-2 bg-light pe-1">$</span>
                                <input v-model.number="item.precio_unitario_estimado" type="number" min="0" step="0.01"
                                       class="form-control border-2 border-start-0 rounded-end-3 text-end fw-bold" 
                                       placeholder="0.00"
                                       @blur="handlePrecioChange(item)" />
                              </div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                              {{ formatMonto((item.cantidad || 0) * (item.precio_unitario_estimado || 0)) }}
                            </td>
                            <td class="text-center">
                              <button type="button" class="btn btn-link link-danger p-0 active-scale transition-transform" @click="quitarItem(idx)" title="Quitar">
                                <i class="bi bi-trash3 fs-5"></i>
                              </button>
                            </td>
                          </tr>
                          <tr v-if="form.items.length === 0">
                            <td colspan="5" class="text-center text-muted py-5">
                              <i class="bi bi-cart-x fs-1 d-block mb-2 opacity-25"></i>
                              Agregue al menos un artículo para comenzar.
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Moderno -->
            <div class="modal-footer border-0 p-4 bg-white justify-content-end gap-2 flex-shrink-0">
              <button 
                @click="showFormModal = false" 
                type="button" 
                class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-bold"
              >
                CANCELAR
              </button>
              <button
                type="submit"
                class="btn btn-primary-modern px-4 py-2 rounded-3 fw-bold d-flex align-items-center gap-2"
                :disabled="isSaving"
              >
                <span v-if="isSaving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>{{ isSaving ? 'GUARDANDO...' : (isEditing ? 'ACTUALIZAR PEDIDO' : 'CREAR PEDIDO') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ============ MODAL DETALLE (solo lectura) ============ -->
    <Teleport to="body">
      <div v-if="showDetalleModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-file-earmark-text me-2"></i>
                <template v-if="pedidoDetalle">
                  Pedido #{{ pedidoDetalle.id_pedido_proveedor }}
                  <span class="badge ms-2" :class="estadoBadge(pedidoDetalle.estado)">{{ pedidoDetalle.estado }}</span>
                </template>
                <template v-else>
                  Cargando detalle...
                </template>
              </h5>
              <button type="button" class="btn-close" @click="showDetalleModal = false"></button>
            </div>
            <div class="modal-body position-relative" style="min-height: 200px;">
              <div v-if="loadingDetalle" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center border-0 rounded-bottom">
                <div class="spinner-border text-primary-custom" role="status" style="width: 2.5rem; height: 2.5rem;">
                  <span class="visually-hidden">Cargando...</span>
                </div>
                <div class="mt-2 text-muted small fw-medium">Obteniendo información...</div>
              </div>

              <template v-if="pedidoDetalle">
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
              </template>
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
      :message="`¿Confirmas que recibiste el pedido?\n\nEsta acción registrará ${pedidoAccion?.total_items} ingreso(s) de artículos al stock`"
      confirm-button-text="Sí, marcar como recibido"
      variant="success"
      :is-loading="isChangingState"
      @confirm="ejecutarCambioEstado('recibido')"
    />

    <ConfirmModal
      v-model="showCancelarModal"
      title="Cancelar pedido"
      :message="`¿Estás seguro de cancelar el pedido #${pedidoAccion?.id_pedido_proveedor}?`"
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

    <!-- MODAL ACTUALIZAR COSTO -->
    <ConfirmModal
      v-model="showUpdateCostoModal"
      title="Actualizar Costo en Catálogo"
      :message="`Detectamos un cambio en el costo de '${itemParaActualizarCosto?.temp_search}'.\n\n¿Deseas actualizar el costo de compra en la ficha del artículo a ${formatMonto(itemParaActualizarCosto?.precio_unitario_estimado)}?`"
      confirm-button-text="Actualizar Catálogo"
      cancel-button-text="Solo en este pedido"
      variant="primary"
      :is-loading="isUpdatingCosto"
      @confirm="confirmUpdateCosto"
      @cancel="cancelUpdateCosto"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import proveedoresService from '@/services/proveedoresService';
import articulosService from '@/services/articulosService';
import { useToastStore } from '@/stores/toastStore';
import { formatMoney } from '@/utils/formatters';

const toast = useToastStore();
const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';
const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const columns = [
  { key: 'proveedor_nombre',    label: 'Proveedor',   sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
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
const filtroProveedor = ref('');
const filtroFechaDesde = ref('');
const filtroFechaHasta = ref('');

// Estado de UI
const loading = ref(false);
const showFormModal = ref(false);
const showDetalleModal = ref(false);
const showRecibirModal = ref(false);
const showCancelarModal = ref(false);
const showDeleteModal = ref(false);
const loadingDetalle = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isChangingState = ref(false);
const isDeleting = ref(false);
const isUpdatingCosto = ref(false);

const pedidoDetalle = ref(null);
const pedidoAccion = ref(null);
const idToDelete = ref(null);
const showUpdateCostoModal = ref(false);
const itemParaActualizarCosto = ref(null);

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
  let res = [...pedidos.value];

  // Filtro por Estado
  if (filtroEstado.value) {
    res = res.filter(p => p.estado === filtroEstado.value);
  }

  // Filtro por Proveedor
  if (filtroProveedor.value) {
    res = res.filter(p => p.id_proveedor == filtroProveedor.value);
  }

  // Filtro por Fecha (Pedido)
  if (filtroFechaDesde.value) {
    res = res.filter(p => p.fecha_pedido >= filtroFechaDesde.value);
  }
  if (filtroFechaHasta.value) {
    res = res.filter(p => p.fecha_pedido <= filtroFechaHasta.value);
  }

  return sortItems(res);
});

const totalEstimado = computed(() =>
  form.value.items.reduce((acc, it) => acc + ((it.cantidad || 0) * (it.precio_unitario_estimado || 0)), 0)
);

const resetFilters = () => {
  filtroEstado.value = '';
  filtroProveedor.value = '';
  filtroFechaDesde.value = '';
  filtroFechaHasta.value = '';
};

// Helpers
const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d.getTime()) ? val : d.toLocaleDateString('es-AR');
};

const formatMonto = (val) => {
  const n = parseFloat(val);
  return isNaN(n) ? '$0' : '$' + formatMoney(n);
};

const estadoSelectClass = (estado) => ({
  'bg-warning-subtle text-warning border-warning': estado === 'pendiente',
  'bg-success-subtle text-success border-success': estado === 'recibido',
  'bg-secondary-subtle text-secondary border-secondary': estado === 'cancelado',
});

const estadoBadge = (estado) => {
  switch (estado) {
    case 'pendiente': return 'bg-warning-subtle text-warning border-warning';
    case 'recibido': return 'bg-success-subtle text-success border-success';
    case 'cancelado': return 'bg-secondary-subtle text-secondary border-secondary';
    default: return 'bg-light text-dark';
  }
};

const handleEstadoChangeInTable = (item, nuevoEstado) => {
  if (nuevoEstado === 'recibido') {
    confirmarRecibir(item);
  } else if (nuevoEstado === 'cancelado') {
    confirmarCancelar(item);
  }
};

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
          temp_search: it.articulo_nombre || '', // Inicializar búsqueda con el nombre actual
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
  form.value.items.push({ 
    id_articulo: '', 
    cantidad: null, 
    precio_unitario_estimado: null,
    temp_search: '' 
  });
};

const quitarItem = (idx) => {
  form.value.items.splice(idx, 1);
};

const onArticuloSelectedInItem = (idx, art) => {
  form.value.items[idx].id_articulo = art.id;
  form.value.items[idx].temp_search = art.nombre;
  
  // Guardar el costo actual para detectar cambios
  form.value.items[idx].costo_original_catalogo = parseFloat(art.costo_actual) || 0;
  form.value.items[idx].precio_unitario_estimado = parseFloat(art.costo_actual) || 0;
};

const handlePrecioChange = (item) => {
  if (item.id_articulo && item.precio_unitario_estimado !== item.costo_original_catalogo) {
    itemParaActualizarCosto.value = item;
    showUpdateCostoModal.value = true;
  }
};

const confirmUpdateCosto = async () => {
  if (!itemParaActualizarCosto.value) return;
  
  isUpdatingCosto.value = true;
  try {
    const art = articulos.value.find(a => a.id === itemParaActualizarCosto.value.id_articulo);
    if (!art) return;

    await articulosService.update(art.id, {
      ...art,
      costo_actual: itemParaActualizarCosto.value.precio_unitario_estimado
    });
    
    // Actualizar en la lista local para que no vuelva a saltar el modal
    art.costo_actual = itemParaActualizarCosto.value.precio_unitario_estimado;
    itemParaActualizarCosto.value.costo_original_catalogo = itemParaActualizarCosto.value.precio_unitario_estimado;

    toast.showToast({ message: 'Costo actualizado en el catálogo.', type: 'success' });
  } catch (err) {
    toast.showToast({ message: 'Error al actualizar costo en catálogo.', type: 'danger' });
  } finally {
    isUpdatingCosto.value = false;
    showUpdateCostoModal.value = false;
    itemParaActualizarCosto.value = null;
  }
};

const cancelUpdateCosto = () => {
  // Si cancela, simplemente actualizamos el "original" local para que no vuelva a preguntar 
  // por este cambio específico en este ítem, o lo dejamos así si queremos que pregunte cada vez.
  // Por UX, vamos a marcarlo como "aceptado" localmente aunque no se guarde en DB.
  if (itemParaActualizarCosto.value) {
    itemParaActualizarCosto.value.costo_original_catalogo = itemParaActualizarCosto.value.precio_unitario_estimado;
  }
  showUpdateCostoModal.value = false;
  itemParaActualizarCosto.value = null;
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
  showDetalleModal.value = true;
  loadingDetalle.value = true;
  pedidoDetalle.value = null; // Reset anterior
  try {
    pedidoDetalle.value = await proveedoresService.getPedidoById(item.id_pedido_proveedor);
  } catch {
    toast.showToast({ message: 'Error al cargar el detalle del pedido.', type: 'danger' });
    showDetalleModal.value = false;
  } finally {
    loadingDetalle.value = false;
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

.cursor-pointer { cursor: pointer; }

/* Estilo para los selectores de estado para que no se corten y opciones limpias */
.state-select {
  min-width: 150px;
  width: auto;
  display: inline-block;
}

.state-select option {
  padding: 10px;
  background-color: white !important;
  color: #212529 !important;
}

/* Estilos de Modal Fullscreen (Igual que VentaModal.vue) */
.modal-fullscreen-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 2rem 0;
  margin: 0;
  overflow-y: auto;
}

.modal-fullscreen-content {
  width: 90vw;
  min-height: 90vh;
  background: white;
  display: flex;
  flex-direction: column;
  border-radius: 1.5rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  margin-bottom: 2rem;
}

@media (min-width: 1200px) {
  .modal-fullscreen-container {
    padding: 3rem 0;
  }
  .modal-fullscreen-content {
    width: 85vw;
    min-height: 85vh;
  }
}

.fw-black { font-weight: 900; }
.letter-spacing-1 { letter-spacing: 1px; }
.shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
.bg-light-subtle { background-color: #f8fbff; }

.animate-fade-in { animation: fadeIn 0.2s ease-out; }
.animate-row-in { animation: slideIn 0.3s ease-out; }

@keyframes fadeIn { 
  from { opacity: 0; } 
  to { opacity: 1; } 
}

@keyframes slideIn { 
  from { opacity: 0; transform: translateX(10px); } 
  to { opacity: 1; transform: translateX(0); } 
}

.active-scale:active { transform: scale(0.95); }
.transition-transform { transition: transform 0.1s; }

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #999;
}
</style>
