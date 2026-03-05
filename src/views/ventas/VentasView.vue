<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">VENTAS</h1>
      </div>
      <button @click="openVentaModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva Venta
      </button>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por cliente, equipo o estado..." />
    </div>

    <!-- Tabla de ventas -->
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
            <template v-for="item in ventasFiltradas" :key="item.id">
              <tr :class="{ 'table-active': ventaExpandida === item.id }">
                <td class="ps-4 text-muted fw-bold">{{ item.id }}</td>
                <td class="text-muted">{{ formatFecha(item.fecha) }}</td>
                <td class="fw-medium text-dark">{{ item.cliente_nombre || '—' }}</td>
                <td class="text-muted">
                  <span v-if="item.equipo_nombre" class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">
                    {{ item.equipo_nombre }}
                  </span>
                  <span v-else class="text-muted">—</span>
                </td>
                <td class="text-center">
                  <span class="badge rounded-pill px-3" :class="estadoBadgeClass(item.id_estado_venta)">
                    {{ item.estado_descripcion || '—' }}
                  </span>
                </td>
                <td class="text-muted small">{{ item.descripcion_cliente || '—' }}</td>
                <td class="pe-4 text-end">
                  <button
                    @click="toggleDetalle(item.id)"
                    class="btn btn-link link-secondary p-1 me-1"
                    :title="ventaExpandida === item.id ? 'Ocultar artículos' : 'Ver artículos'"
                  >
                    <i class="bi fs-4" :class="ventaExpandida === item.id ? 'bi-chevron-up' : 'bi-list-ul'"></i>
                  </button>
                  <button @click="openVentaModal(item)" class="btn btn-link link-secondary p-1 me-1" title="Editar">
                    <i class="bi bi-pencil-square fs-4"></i>
                  </button>
                  <button @click="prepareDeleteVenta(item.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                    <i class="bi bi-trash3 fs-4"></i>
                  </button>
                </td>
              </tr>
              <!-- Fila expandida: detalle de artículos -->
              <tr v-if="ventaExpandida === item.id" class="bg-light">
                <td colspan="7" class="px-4 py-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0 text-primary-custom fw-bold">
                      <i class="bi bi-box-seam me-1"></i> Artículos en esta venta
                    </h6>
                    <button @click="openArticuloVentaModal(item.id)" class="btn btn-sm btn-outline-primary-modern">
                      <i class="bi bi-plus me-1"></i> Agregar artículo
                    </button>
                  </div>
                  <div v-if="loadingDetalle" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary-custom"></div>
                  </div>
                  <table v-else class="table table-sm align-middle mb-0">
                    <thead>
                      <tr class="bg-white border-bottom">
                        <th class="py-2 text-uppercase fs-xs fw-bold text-secondary">Artículo</th>
                        <th class="py-2 text-uppercase fs-xs fw-bold text-secondary text-end">Cantidad</th>
                        <th class="py-2 text-uppercase fs-xs fw-bold text-secondary text-end">P. Unitario</th>
                        <th class="py-2 text-uppercase fs-xs fw-bold text-secondary text-end">Total</th>
                        <th class="py-2 text-uppercase fs-xs fw-bold text-secondary text-end">Acc.</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="av in articulosDeVenta" :key="av.id_articulo_venta">
                        <td class="fw-medium">{{ av.articulo_nombre }}</td>
                        <td class="text-end">{{ av.cantidad }}</td>
                        <td class="text-end">${{ Number(av.precio_unitario).toFixed(2) }}</td>
                        <td class="text-end fw-semibold">${{ Number(av.total).toFixed(2) }}</td>
                        <td class="text-end">
                          <button @click="prepareDeleteArticuloVenta(av.id_articulo_venta)" class="btn btn-link link-danger p-0" title="Quitar">
                            <i class="bi bi-x-circle fs-5"></i>
                          </button>
                        </td>
                      </tr>
                      <tr v-if="articulosDeVenta.length === 0">
                        <td colspan="5" class="text-center text-muted py-2">Sin artículos cargados.</td>
                      </tr>
                      <tr v-if="articulosDeVenta.length > 0" class="border-top">
                        <td colspan="3" class="text-end fw-bold text-dark py-2">Total Venta:</td>
                        <td class="text-end fw-bold text-dark py-2">${{ totalVenta }}</td>
                        <td></td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </template>
            <tr v-if="ventasFiltradas.length === 0 && !loading">
              <td colspan="7" class="text-center py-5 text-muted">No hay ventas que coincidan con la búsqueda.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Venta -->
    <div v-if="showVentaModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-cart-plus'"></i>
              {{ isEditing ? 'Editar Venta' : 'Nueva Venta' }}
            </h5>
            <button type="button" class="btn-close" @click="showVentaModal = false"></button>
          </div>
          <form @submit.prevent="saveVenta">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Fecha <span class="text-danger">*</span></label>
                  <input v-model="ventaForm.fecha" type="date" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Cliente</label>
                  <select v-model.number="ventaForm.id_cliente" class="form-select">
                    <option :value="null">Sin cliente</option>
                    <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_cliente }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Equipo</label>
                  <select v-model.number="ventaForm.id_equipo" class="form-select">
                    <option :value="null">Sin equipo</option>
                    <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Estado <span class="text-danger">*</span></label>
                  <select v-model.number="ventaForm.id_estado_venta" class="form-select" required>
                    <option :value="null" disabled>Seleccionar estado...</option>
                    <option v-for="ev in estadosVenta" :key="ev.id" :value="ev.id">{{ ev.descripcion }}</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tipo de Venta</label>
                  <input v-model.trim="ventaForm.tipo_vta" type="text" class="form-control" placeholder="Ej: Contado, Cuotas" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Símbolo moneda</label>
                  <input v-model.trim="ventaForm.simbolo" type="text" class="form-control" placeholder="Ej: $" />
                </div>
                <div class="col-12">
                  <label class="form-label">Descripción / Observaciones</label>
                  <textarea v-model.trim="ventaForm.descripcion_cliente" class="form-control" rows="2" placeholder="Observaciones de la venta..."></textarea>
                </div>

                <!-- SECCIÓN DE ARTÍCULOS DENTRO DE LA VENTA (SOLO NUEVA VENTA) -->
                <div v-if="!isEditing" class="col-12 mt-4 pt-3 border-top">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-primary-custom">
                      <i class="bi bi-box-seam me-1"></i> Artículos de la venta
                    </h6>
                    <button type="button" @click="agregarFilaArticulo" class="btn btn-sm btn-outline-primary-modern">
                      <i class="bi bi-plus-circle me-1"></i> Agregar fila
                    </button>
                  </div>

                  <div class="table-responsive border rounded bg-light p-2">
                    <table class="table table-sm align-middle mb-0 bg-white">
                      <thead class="bg-light">
                        <tr class="fs-xs fw-bold text-secondary">
                          <th style="min-width: 250px;">Artículo</th>
                          <th style="width: 100px;">Disp.</th>
                          <th style="width: 100px;">Cant.</th>
                          <th style="width: 120px;">P. Unit.</th>
                          <th style="width: 120px;" class="text-end">Total</th>
                          <th style="width: 50px;"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(item, index) in articulosNvaVenta" :key="index">
                          <td>
                            <select v-model.number="item.id_articulo" class="form-select form-select-sm" @change="alCambiarArticuloNvaVenta(index)">
                              <option :value="null" disabled>Seleccionar...</option>
                              <option v-for="a in articulos" :key="a.id" :value="a.id" :disabled="!a.activo">
                                {{ a.nombre }} {{ !a.activo ? '(Baja)' : '' }}
                              </option>
                            </select>
                          </td>
                          <td class="text-center">
                            <span class="badge" :class="getStockBadgeClass(item.stock_actual)">
                              {{ item.stock_actual !== null ? item.stock_actual : '-' }}
                            </span>
                          </td>
                          <td>
                            <input v-model.number="item.cantidad" type="number" step="0.01" min="0.01" class="form-control form-control-sm" @input="actualizarTotalItem(index)" />
                          </td>
                          <td>
                            <input v-model.number="item.precio_unitario" type="number" step="0.01" min="0" class="form-control form-control-sm" @input="actualizarTotalItem(index)" />
                          </td>
                          <td class="text-end fw-semibold">${{ Number(item.total).toFixed(2) }}</td>
                          <td class="text-center">
                            <button type="button" @click="quitarFilaArticulo(index)" class="btn btn-link link-danger p-0" title="Quitar">
                              <i class="bi bi-dash-circle fs-5"></i>
                            </button>
                          </td>
                        </tr>
                        <tr v-if="articulosNvaVenta.length === 0">
                          <td colspan="6" class="text-center text-muted py-3 small">No se han agregado artículos todavía.</td>
                        </tr>
                      </tbody>
                      <tfoot v-if="articulosNvaVenta.length > 0">
                        <tr class="border-top fw-bold text-dark">
                          <td colspan="4" class="text-end py-2">Total Estimado:</td>
                          <td class="text-end py-2">${{ totalNuevaVenta }}</td>
                          <td></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                  <div v-if="errorStock" class="alert alert-danger d-flex align-items-center mt-2 py-2 mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>{{ errorStock }}</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showVentaModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving || ( !isEditing && !vtaNuevaValida )">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Guardar y Descontar Stock' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Agregar Artículo a Venta -->
    <div v-if="showArticuloVentaModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i>Agregar Artículo</h5>
            <button type="button" class="btn-close" @click="showArticuloVentaModal = false"></button>
          </div>
          <form @submit.prevent="saveArticuloVenta">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Artículo <span class="text-danger">*</span></label>
                  <select v-model.number="avForm.id_articulo" class="form-select" required>
                    <option :value="null" disabled>Seleccionar artículo...</option>
                    <option v-for="art in articulos" :key="art.id" :value="art.id">
                      {{ art.nombre }} — ${{ Number(art.precio_actual || 0).toFixed(2) }}
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                  <input v-model.number="avForm.cantidad" type="number" step="0.01" min="0.01" class="form-control" required @input="autocompletarPrecio" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Precio Unitario ($) <span class="text-danger">*</span></label>
                  <input v-model.number="avForm.precio_unitario" type="number" step="0.01" min="0" class="form-control" required />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Total ($)</label>
                  <input :value="avTotalCalculado" type="number" class="form-control bg-light text-muted" readonly />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showArticuloVentaModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSavingArticulo">
                <span v-if="isSavingArticulo" class="spinner-border spinner-border-sm me-2"></span>
                Agregar
              </button>
            </div>
          </form>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import ventasService from '@/services/ventasService';
import clientesService from '@/services/clientesService';
import articulosService from '@/services/articulosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'id',                  label: 'ID',          sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width: 70px' },
  { key: 'fecha',               label: 'Fecha',        sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cliente_nombre',      label: 'Cliente',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_nombre',       label: 'Equipo',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'id_estado_venta',     label: 'Estado',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center' },
  { key: 'descripcion_cliente', label: 'Descripción',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',            label: 'Acciones',     sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

// Datos principales
const ventas = ref([]);
const clientes = ref([]);
const equipos = ref([]);
const estadosVenta = ref([]);
const articulos = ref([]);
const loading = ref(false);
const searchQuery = ref('');

// Detalle de venta expandida
const ventaExpandida = ref(null);
const articulosDeVenta = ref([]);
const loadingDetalle = ref(false);

// Modal venta
const showVentaModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const emptyVentaForm = () => ({
  id: null,
  fecha: new Date().toISOString().split('T')[0],
  id_cliente: null,
  id_equipo: null,
  id_estado_venta: null,
  descripcion_cliente: '',
  tipo_vta: '',
  simbolo: '$',
});
const ventaForm = ref(emptyVentaForm());
const originalVentaForm = ref({});

// Para Nueva Venta con carga masiva de artículos
const articulosNvaVenta = ref([]);
const errorStock = ref(null);

const agregarFilaArticulo = () => {
  articulosNvaVenta.value.push({ id_articulo: null, cantidad: 1, precio_unitario: 0, total: 0, stock_actual: null });
  validarStockMasivo();
};

const quitarFilaArticulo = (index) => {
  articulosNvaVenta.value.splice(index, 1);
  validarStockMasivo();
};

const alCambiarArticuloNvaVenta = (index) => {
  const item = articulosNvaVenta.value[index];
  const art = articulos.value.find(a => Number(a.id) === Number(item.id_articulo));
  if (art) {
    item.precio_unitario = art.precio_actual || 0;
    item.stock_actual = art.stock_actual || 0;
    actualizarTotalItem(index);
    validarStockMasivo();
  }
};

const actualizarTotalItem = (index) => {
  const item = articulosNvaVenta.value[index];
  item.total = ((Number(item.cantidad) || 0) * (Number(item.precio_unitario) || 0)).toFixed(2);
  validarStockMasivo();
};

const totalNuevaVenta = computed(() => {
  return articulosNvaVenta.value.reduce((acc, curr) => acc + Number(curr.total || 0), 0).toFixed(2);
});

const validarStockMasivo = () => {
  errorStock.value = null;
  for (const item of articulosNvaVenta.value) {
    if (item.id_articulo && Number(item.cantidad) > Number(item.stock_actual || 0)) {
       const art = articulos.value.find(a => Number(a.id) === Number(item.id_articulo));
       errorStock.value = `Stock insuficiente para ${art?.nombre || 'artículo'}. Disponible: ${item.stock_actual || 0}`;
       return false;
    }
  }
  return true;
};

const vtaNuevaValida = computed(() => {
  const cabeceraOK = ventaForm.value.fecha && ventaForm.value.id_estado_venta;
  const itemsOK = articulosNvaVenta.value.length > 0 && articulosNvaVenta.value.every(i => i.id_articulo && i.cantidad > 0);
  const stockOK = validarStockMasivo();
  return cabeceraOK && itemsOK && stockOK;
});

const getStockBadgeClass = (stock) => {
  if (stock === null) return 'bg-light text-dark';
  if (stock <= 0) return 'bg-danger text-white';
  if (stock < 5) return 'bg-warning text-dark';
  return 'bg-success text-white';
};

// Modal artículo-venta (para vtas existentes)
const showArticuloVentaModal = ref(false);
const ventaIdParaArticulo = ref(null);
const isSavingArticulo = ref(false);
const avForm = ref({ id_articulo: null, cantidad: null, precio_unitario: null });
const avTotalCalculado = computed(() => {
  return ((Number(avForm.value.cantidad) || 0) * (Number(avForm.value.precio_unitario) || 0)).toFixed(2);
});

// Eliminación venta
const showDeleteVentaModal = ref(false);
const isDeleting = ref(false);
const idVentaToDelete = ref(null);

// Eliminación artículo-venta
const showDeleteAvModal = ref(false);
const isDeletingAv = ref(false);
const idAvToDelete = ref(null);

// Total calculado de la venta expandida
const totalVenta = computed(() => {
  return articulosDeVenta.value
    .reduce((acc, av) => acc + Number(av.total || 0), 0)
    .toFixed(2);
});

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

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  return new Date(fecha).toLocaleDateString('es-AR');
};

const estadoBadgeClass = (idEstado) => {
  const mapa = {
    1: 'bg-warning-subtle text-warning',
    2: 'bg-success-subtle text-success',
    3: 'bg-danger-subtle text-danger',
    4: 'bg-secondary-subtle text-secondary',
  };
  return mapa[idEstado] || 'bg-secondary-subtle text-secondary';
};

const fetchData = async () => {
  loading.value = true;
  try {
    [ventas.value, clientes.value, equipos.value, estadosVenta.value, articulos.value] = await Promise.all([
      ventasService.getVentas(),
      clientesService.getClientes(),
      datosMaestrosService.getEquipos(),
      datosMaestrosService.getEstadosVenta(),
      articulosService.getAllActivos(),  // Trae stock_actual calculado
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar las ventas.', type: 'danger' });
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
    const todos = await ventasService.getArticulosVenta();
    articulosDeVenta.value = todos.filter(av => Number(av.id_venta) === Number(idVenta));
  } catch {
    toast.showToast({ message: 'Error al cargar los artículos de la venta.', type: 'danger' });
  } finally {
    loadingDetalle.value = false;
  }
};

// ---- Modal Venta ----
const openVentaModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    ventaForm.value = { ...item };
    originalVentaForm.value = { ...item };
    articulosNvaVenta.value = []; // No se usa en edición actual (MVP)
  } else {
    isEditing.value = false;
    ventaForm.value = emptyVentaForm();
    articulosNvaVenta.value = [{ id_articulo: null, cantidad: 1, precio_unitario: 0, total: 0, stock_actual: null }];
    errorStock.value = null;
  }
  showVentaModal.value = true;
};

const saveVenta = async () => {
  if (!ventaForm.value.fecha || !ventaForm.value.id_estado_venta) {
    toast.showToast({ message: 'Fecha y estado son obligatorios.', type: 'warning' });
    return;
  }
  if (!isEditing.value && !vtaNuevaValida.value) {
    toast.showToast({ message: errorStock.value || 'Complete todos los datos requeridos.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(ventaForm.value) === JSON.stringify(originalVentaForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showVentaModal.value = false;
    return;
  }
  isSaving.value = true;
  try {
    if (isEditing.value) {
      await ventasService.actualizarVenta(ventaForm.value);
      toast.showToast({ message: 'Venta actualizada correctamente.', type: 'success' });
    } else {
      // Nueva Venta Atlómica (Cabecera + Artículos con Stock)
      const payload = {
        ...ventaForm.value,
        articulos: articulosNvaVenta.value.map(i => ({
          id_articulo: i.id_articulo,
          cantidad: i.cantidad,
          precio_unitario: i.precio_unitario,
          total: i.total
        }))
      };
      await ventasService.crearVenta(payload);
      toast.showToast({ message: 'Venta creada y stock descontado con éxito.', type: 'success' });
    }
    showVentaModal.value = false;
    fetchData();
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Error al guardar la venta.';
    toast.showToast({ message: errorMsg, type: 'danger' });
  } finally {
    isSaving.value = false;
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
    toast.showToast({ message: 'Venta eliminada correctamente.', type: 'success' });
    showDeleteVentaModal.value = false;
    if (ventaExpandida.value === idVentaToDelete.value) {
      ventaExpandida.value = null;
    }
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar la venta.', type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

// ---- Modal Artículo-Venta ----
const openArticuloVentaModal = (idVenta) => {
  ventaIdParaArticulo.value = idVenta;
  avForm.value = { id_articulo: null, cantidad: null, precio_unitario: null };
  showArticuloVentaModal.value = true;
};

const autocompletarPrecio = () => {
  if (avForm.value.id_articulo) {
    const art = articulos.value.find(a => Number(a.id) === Number(avForm.value.id_articulo));
    if (art && !avForm.value.precio_unitario) {
      avForm.value.precio_unitario = art.precio_actual;
    }
  }
};

const saveArticuloVenta = async () => {
  if (!avForm.value.id_articulo || !avForm.value.cantidad || !avForm.value.precio_unitario) {
    toast.showToast({ message: 'Todos los campos son obligatorios.', type: 'warning' });
    return;
  }
  isSavingArticulo.value = true;
  try {
    await ventasService.crearArticuloVenta({
      id_articulo: avForm.value.id_articulo,
      id_venta: ventaIdParaArticulo.value,
      cantidad: avForm.value.cantidad,
      precio_unitario: avForm.value.precio_unitario,
      total: avTotalCalculado.value,
    });
    toast.showToast({ message: 'Artículo agregado a la venta.', type: 'success' });
    showArticuloVentaModal.value = false;
    await toggleDetalle(ventaExpandida.value);
    await toggleDetalle(ventaExpandida.value);
  } catch {
    toast.showToast({ message: 'Error al agregar el artículo.', type: 'danger' });
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
    toast.showToast({ message: 'Artículo quitado de la venta.', type: 'success' });
    showDeleteAvModal.value = false;
    const idVenta = ventaExpandida.value;
    ventaExpandida.value = null;
    await toggleDetalle(idVenta);
  } catch {
    toast.showToast({ message: 'Error al quitar el artículo.', type: 'danger' });
  } finally {
    isDeletingAv.value = false;
  }
};

onMounted(fetchData);
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }
.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}
.btn-outline-primary-modern {
  color: var(--color-primary);
  border-color: var(--color-primary);
  font-size: 0.8rem;
  padding: 4px 12px;
}
.btn-outline-primary-modern:hover {
  background-color: var(--color-primary);
  color: white;
}
</style>
