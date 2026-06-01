<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CLIENTES</h1>
      </div>
      <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
        <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
      </button>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por nombre, dirección o condición IVA..." />
    </div>

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
            <tr v-for="item in clientesFiltrados" :key="item.id" :class="{ 'cliente-inactivo': Number(item.activo) === 0 }">
              <td class="ps-4 fw-medium text-dark">{{ item.nombre_cliente }}</td>
              <td class="text-muted">
                <span v-if="item.condicion_iva_descripcion" class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">
                  {{ item.condicion_iva_descripcion }}
                </span>
                <span v-else class="text-muted">—</span>
              </td>
              <td class="text-muted">{{ item.provincia_nombre || '—' }}</td>
              <td class="text-muted">{{ item.direccion || '—' }}</td>
              <td class="text-end fw-bold" :class="Number(item.saldo_pendiente) > 0 ? 'text-danger' : 'text-success'">
                ${{ formatMoney(item.saldo_pendiente) }}
              </td>
              <td class="pe-4 text-end">
                <div class="d-flex gap-1 justify-content-end flex-nowrap">
                  <button @click="verSaldo(item)" class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1 px-2 py-1" title="Ver Saldo">
                    <i class="bi bi-wallet2 fs-6"></i>
                    <span class="small fw-bold">Saldo</span>
                  </button>
                  <button @click="openModal(item)" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1" title="Editar">
                    <i class="bi bi-pencil fs-6"></i>
                    <span class="small fw-bold">Editar</span>
                  </button>
                  <button @click="toggleActivo(item)" :class="Number(item.activo) === 1 ? 'btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 px-2 py-1' : 'btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1'" :title="Number(item.activo) === 1 ? 'Desactivar' : 'Activar'">
                    <span v-if="togglingId === item.id" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i v-if="Number(item.activo) === 1" class="bi bi-person-dash fs-6"></i>
                    <i v-else class="bi bi-person-check fs-6"></i>
                    <span class="small fw-bold">{{ Number(item.activo) === 1 ? 'Desactivar' : 'Activar' }}</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="clientesFiltrados.length === 0 && !loading">
              <td colspan="6" class="text-center py-5 text-muted">No hay clientes que coincidan con la búsqueda.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-12">
                  <label class="form-label">Nombre del Cliente <span class="text-danger">*</span></label>
                  <input v-model.trim="form.nombre_cliente" type="text" class="form-control" placeholder="Ej: Juan Pérez" required />
                </div>
                <div class="col-md-12">
                  <label class="form-label">
                    DNI / CUIT
                    <span v-if="Number(form.id_condicion_iva_receptor) === 1" class="text-danger">*</span>
                  </label>
                  <input v-model.trim="form.cuit_dni" type="text" class="form-control" placeholder="Ej: 23456789 (DNI)" :required="Number(form.id_condicion_iva_receptor) === 1" />
                </div>
                <div class="col-md-12">
                  <label class="form-label">Condición IVA</label>
                  <select v-model="form.id_condicion_iva_receptor" class="form-select shadow-sm border-2">
                    <option v-for="c in condicionesIva" :key="c.id" :value="c.id">{{ c.descripcion_condicion }}</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Dirección</label>
                  <input v-model.trim="form.direccion" type="text" class="form-control" placeholder="Ej: Av. Principal 123" />
                </div>
                <div class="col-md-12">
                  <label class="form-label">Teléfono</label>
                  <input v-model.trim="form.telefono" type="text" class="form-control" placeholder="Ej: 351-1234567" />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </Teleport>

    <!-- Modal Detalle de Saldo -->
    <Teleport to="body">
      <div v-if="showSaldoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-wallet2 me-2"></i>
                Detalle de Movimientos: {{ clienteSeleccionado?.nombre_cliente }}
              </h5>
              <button type="button" class="btn-close" @click="showSaldoModal = false"></button>
            </div>
            <div class="modal-body p-0">
              <div v-if="loadingMovimientos" class="py-5 text-center">
                <div class="spinner-border text-primary-custom" role="status"></div>
                <div class="mt-2 text-muted">Cargando movimientos...</div>
              </div>
              <div v-else class="table-responsive" style="max-height: 400px;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="bg-light sticky-top">
                    <tr>
                      <th class="ps-4">Fecha</th>
                      <th>Tipo</th>
                      <th>Descripción</th>
                      <th class="text-end pe-4">Monto</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(mov, index) in movimientos" :key="index">
                      <td class="ps-4">{{ new Date(mov.fecha).toLocaleDateString() }}</td>
                      <td>
                        <span :class="mov.tipo === 'VENTA' ? 'badge bg-danger-subtle text-danger' : 'badge bg-success-subtle text-success'">
                          {{ mov.tipo }}
                        </span>
                      </td>
                      <td class="text-muted small">{{ mov.descripcion || '—' }}</td>
                      <td class="text-end pe-4 fw-bold" :class="mov.tipo === 'VENTA' ? 'text-danger' : 'text-success'">
                        {{ mov.tipo === 'VENTA' ? '+' : '-' }} ${{ formatMoney(mov.monto) }}
                      </td>
                    </tr>
                    <tr v-if="movimientos.length === 0">
                      <td colspan="4" class="text-center py-4 text-muted">No hay movimientos registrados.</td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-light sticky-bottom fw-bold">
                    <tr>
                      <td colspan="3" class="ps-4 text-end">Saldo Final:</td>
                      <td class="text-end pe-4" :class="Number(clienteSeleccionado?.saldo_pendiente) > 0 ? 'text-danger' : 'text-success'">
                        ${{ formatMoney(clienteSeleccionado?.saldo_pendiente) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showSaldoModal = false" type="button" class="btn btn-secondary px-4">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Botón activar/desactivar maneja soft-delete; no se usa modal de eliminación aquí -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
// Import ConfirmModal removed — no physical deletes allowed; use activar/desactivar
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import clientesService from '@/services/comercial/clientesService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';
import { formatMoney } from '@/utils/formatters';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'nombre_cliente',            label: 'Nombre',        sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'condicion_iva_descripcion', label: 'Condición IVA', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'provincia_nombre',          label: 'Provincia',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'direccion',                 label: 'Dirección',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'saldo_pendiente',           label: 'Saldo',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-end', thStyle: 'width: 120px' },
  { key: 'acciones',                  label: 'Acciones',      sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const clientes = ref([]);
const condicionesIva = ref([]);
const provincias = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const showFormModal = ref(false);
const togglingId = ref(null);
const showSaldoModal = ref(false);
const loadingMovimientos = ref(false);
const clienteSeleccionado = ref(null);
const movimientos = ref([]);
const isEditing = ref(false);
const isSaving = ref(false);
// isDeleting/idToDelete removed (no hard deletes)

const emptyForm = () => ({ 
  id: null, 
  nombre_cliente: '', 
  id_condicion_iva_receptor: 2, 
  direccion: '', 
  id_provinica: 1,
  telefono: '',
  cuit_dni: ''
});
const form = ref(emptyForm());
const originalForm = ref({});

const clientesFiltrados = computed(() => {
  let items = clientes.value;
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(c =>
      c.nombre_cliente?.toLowerCase().includes(q) ||
      c.condicion_iva_descripcion?.toLowerCase().includes(q) ||
      c.direccion?.toLowerCase().includes(q) ||
      String(c.id).includes(q)
    );
  }
  return sortItems(items);
});

const fetchData = async () => {
  loading.value = true;
  try {
    const [c, ci, p] = await Promise.all([
      clientesService.getClientes(),
      datosMaestrosService.getCondicionesIva(),
      datosMaestrosService.getProvincias(),
    ]);
    clientes.value = c;
    condicionesIva.value = ci;
    provincias.value = p;
  } catch (err) {
    console.error(err);
    toast.showToast({ message: 'Error al cargar los clientes.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const verSaldo = async (item) => {
  clienteSeleccionado.value = item;
  showSaldoModal.value = true;
  loadingMovimientos.value = true;
  try {
    movimientos.value = await clientesService.getMovimientos(item.id);
  } catch (err) {
    console.error(err);
    toast.showToast({ message: 'Error al cargar los movimientos.', type: 'danger' });
  } finally {
    loadingMovimientos.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = { ...item };
    originalForm.value = { ...item };
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.nombre_cliente) {
    toast.showToast({ message: 'El nombre del cliente es obligatorio.', type: 'warning' });
    return;
  }
  if (isEditing.value && JSON.stringify(form.value) === JSON.stringify(originalForm.value)) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }
  if (!isEditing.value) {
    const existe = clientes.value.some(c => Number(c.id) === Number(form.value.id));
    if (existe) {
      toast.showToast({ message: 'Ya existe un cliente con ese ID.', type: 'danger' });
      return;
    }
  }

  isSaving.value = true;
  try {
    // Sanitizar CUIT/DNI: dejar solo números (evita errores en bigint)
    if (form.value.cuit_dni) {
      form.value.cuit_dni = String(form.value.cuit_dni).replace(/[^0-9]/g, '');
    }

    if (isEditing.value) {
      await clientesService.actualizarCliente(form.value);
      toast.showToast({ message: 'Cliente actualizado correctamente.', type: 'success' });
    } else {
      await clientesService.crearCliente(form.value);
      toast.showToast({ message: 'Cliente creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el cliente.', type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const toggleActivo = async (item) => {
  const nuevo = Number(item.activo) === 1 ? 0 : 1;
  togglingId.value = item.id;
  try {
    await clientesService.setActivo(item.id, nuevo);
    // actualizar lista local
    const idx = clientes.value.findIndex(c => c.id === item.id);
    if (idx !== -1) clientes.value[idx].activo = nuevo;
    toast.showToast({ message: nuevo === 1 ? 'Cliente activado.' : 'Cliente desactivado.', type: 'success' });
  } catch (err) {
    console.error(err);
    toast.showToast({ message: 'Error al actualizar estado del cliente.', type: 'danger' });
  } finally {
    togglingId.value = null;
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
/* Primero, le damos posicionamiento relativo a la fila */
.cliente-inactivo {
  position: relative;
}

/* Luego, creamos la película gris superpuesta */
.cliente-inactivo::after {
  content: ''; /* Requerido para pseudo-elementos */
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  /* Aquí definimos el color y la transparencia (alpha) de la película */
  background-color: rgba(255, 255, 255, 0.5); 
  pointer-events: none; /* ¡CRUCIAL! Esto permite que los clics pasen a través y los botones funcionen */
  z-index: 1; /* Coloca la película sobre el contenido */
}
</style>
