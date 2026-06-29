<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">JUGADORES</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
        </button>
      </div>
    </div>

    <div class="mb-3">
      <FuzzySearch v-model="searchQuery" placeholder="Buscar por nombre, apellido o DNI..." />
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
            <tr v-for="item in jugadoresFiltrados" :key="item.id">
              <td class="ps-4 fw-medium text-dark">{{ item.apellido }}, {{ item.nombre }}</td>
              <td class="text-muted">{{ item.dni || '-' }}</td>
              <td class="text-muted">{{ item.equipo_nombre || '-' }}</td>
              <td class="text-muted">{{ formatDate(item.fecha_nac) }}</td>
              <td class="text-muted">{{ formatDate(item.fecha_alta) }}</td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <div class="d-flex gap-1 justify-content-end flex-nowrap">
                  <button @click="openDetalle(item)" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-2 py-1" title="Ver detalle">
                    <i class="bi bi-eye fs-6"></i>
                    <span class="small fw-bold">Ver</span>
                  </button>
                  <button @click="openModal(item)" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 px-2 py-1" title="Editar">
                    <i class="bi bi-pencil fs-6"></i>
                    <span class="small fw-bold">Editar</span>
                  </button>
                  <button @click="prepareDelete(item.id)" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 px-2 py-1" title="Eliminar">
                    <i class="bi bi-trash3 fs-6"></i>
                    <span class="small fw-bold">Eliminar</span>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="jugadoresFiltrados.length === 0 && !loading">
              <td colspan="7" class="text-center py-5 text-muted">
                No hay jugadores que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de detalle del jugador -->
    <Teleport to="body">
      <div v-if="showDetalleModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);" @click.self="showDetalleModal = false">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-person-lines-fill me-2"></i>
                {{ jugadorDetalle?.apellido }}, {{ jugadorDetalle?.nombre }}
              </h5>
              <button type="button" class="btn-close" @click="showDetalleModal = false"></button>
            </div>
            <div class="modal-body">

              <!-- Datos del jugador -->
              <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                <i class="bi bi-person me-1"></i> Datos personales
              </h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">DNI</label>
                  <div class="fw-medium">{{ jugadorDetalle?.dni || '-' }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Fecha de nacimiento</label>
                  <div class="fw-medium">{{ formatDate(jugadorDetalle?.fecha_nac) }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Fecha de alta</label>
                  <div class="fw-medium">{{ formatDate(jugadorDetalle?.fecha_alta) }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Equipo actual</label>
                  <div class="fw-medium">{{ jugadorDetalle?.equipo_nombre || 'Sin asignar' }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Email</label>
                  <div class="fw-medium">
                    <a v-if="jugadorDetalle?.email" :href="`mailto:${jugadorDetalle.email}`" class="text-decoration-none text-dark">{{ jugadorDetalle.email }}</a>
                    <span v-else class="text-muted">-</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Teléfono</label>
                  <div class="fw-medium">
                    <a v-if="jugadorDetalle?.telefono" :href="`tel:${jugadorDetalle.telefono}`" class="text-decoration-none text-dark">{{ jugadorDetalle.telefono }}</a>
                    <span v-else class="text-muted">-</span>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Estado</label>
                  <div>
                    <span :class="jugadorDetalle?.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                      {{ jugadorDetalle?.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </div>
                </div>
              </div>

              <hr class="my-3" />

              <!-- Historial de documentación -->
              <h6 class="fw-bold text-secondary text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                <i class="bi bi-file-earmark-text me-1"></i> Historial de documentación
              </h6>

              <div v-if="loadingDocumentos" class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary-custom" role="status"></div>
                <span class="ms-2 text-muted small">Cargando documentos...</span>
              </div>

              <div v-else-if="documentos.length === 0" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                No hay documentos cargados para este jugador.
              </div>

              <div v-else class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3 py-2 text-uppercase" style="font-size: 0.72rem; color: #6c757d;">#</th>
                      <th class="py-2 text-uppercase" style="font-size: 0.72rem; color: #6c757d;">Archivo</th>
                      <th v-if="columnasDocumento.includes('fecha_carga')" class="py-2 text-uppercase" style="font-size: 0.72rem; color: #6c757d;">Fecha carga</th>
                      <th class="pe-3 py-2 text-end text-uppercase" style="font-size: 0.72rem; color: #6c757d;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(doc, index) in documentos" :key="doc.id">
                      <td class="ps-3 text-muted small">{{ index + 1 }}</td>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <i :class="iconoDocumento(doc.url)" class="fs-5 text-secondary"></i>
                          <span class="small fw-medium text-truncate" style="max-width: 280px;" :title="doc.url">{{ doc.url }}</span>
                        </div>
                      </td>
                      <td v-if="columnasDocumento.includes('fecha_carga')" class="text-muted small">
                        {{ formatDate(doc.fecha_carga) }}
                      </td>
                      <td class="pe-3 text-end">
                        <button @click="verDocumento(doc.url)" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 px-2 py-1">
                          <i class="bi bi-eye fs-6"></i>
                          <span class="small fw-bold">Ver</span>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>
            <div class="modal-footer">
              <button @click="openModal(jugadorDetalle)" type="button" class="btn btn-outline-success px-3">
                <i class="bi bi-pencil me-1"></i> Editar
              </button>
              <button @click="showDetalleModal = false" type="button" class="btn btn-light px-4">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de formulario (crear/editar) -->
    <Teleport to="body">
      <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                {{ isEditing ? 'Editar Jugador' : 'Nuevo Jugador' }}
              </h5>
              <button type="button" class="btn-close" @click="showFormModal = false"></button>
            </div>
            <form @submit.prevent="save">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Lionel" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Apellido</label>
                    <input v-model.trim="form.apellido" type="text" class="form-control" placeholder="Ej: Messi" required />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">DNI</label>
                    <input v-model.trim="form.dni" type="text" class="form-control" placeholder="Ej: 30123456" maxlength="8" inputmode="numeric" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Fecha de Nacimiento</label>
                    <input v-model="form.fecha_nac" type="date" class="form-control" />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Fecha de Alta</label>
                    <input v-model="form.fecha_alta" type="date" class="form-control" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input v-model.trim="form.email" type="email" class="form-control" placeholder="Ej: jugador@email.com" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input v-model.trim="form.telefono" type="tel" class="form-control" placeholder="Ej: 3516001234" />
                  </div>
                  <div class="col-md-12">
                    <label class="form-label">Equipo actual</label>
                    <select v-model.number="form.id_equipo_actual" class="form-select">
                      <option :value="null">Sin asignar</option>
                      <option v-for="equipo in equipos" :key="equipo.id" :value="Number(equipo.id)">
                        {{ equipo.nombre }}
                        <template v-if="equipo.disciplina"> - {{ equipo.disciplina }}</template>
                      </option>
                    </select>
                  </div>
                  <div class="col-12">
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-2 jugador-switch-row">
                      <input v-model="form.activo" class="form-check-input mt-0" type="checkbox" role="switch" id="chkActivoJugador" />
                      <label class="form-check-label fw-semibold mb-0" for="chkActivoJugador">
                        {{ form.activo ? 'Activo' : 'Inactivo' }}
                      </label>
                    </div>
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

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Jugador"
      message="¿Estás seguro de eliminar este jugador? Si luego se relaciona con equipos o eventos, esta acción puede quedar restringida por la base de datos."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';
import { useUserStore } from '@/stores/userStore';

const toast = useToastStore();
const userStore = useUserStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting();

const columns = [
  { key: 'apellido',      label: 'Jugador',     sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'dni',           label: 'DNI',         sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_nombre', label: 'Equipo',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_nac',     label: 'Fecha Nac.',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'fecha_alta',    label: 'Fecha Alta',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',        label: 'Estado',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'acciones',      label: 'Acciones',    sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
];

const jugadores = ref([]);
const equipos = ref([]);
const searchQuery = ref('');
const jugadoresFiltrados = computed(() => {
  let items = jugadores.value;
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    items = items.filter(item =>
      item.nombre?.toLowerCase().includes(query) ||
      item.apellido?.toLowerCase().includes(query) ||
      item.dni?.toLowerCase().includes(query) ||
      item.equipo_nombre?.toLowerCase().includes(query)
    );
  }
  return sortItems(items);
});

const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const showDetalleModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const idToDelete = ref(null);
const jugadorDetalle = ref(null);
const documentos = ref([]);
const loadingDocumentos = ref(false);

const columnasDocumento = computed(() => {
  if (!documentos.value.length) return [];
  return Object.keys(documentos.value[0]);
});

const form = ref({
  id: null,
  nombre: '',
  apellido: '',
  dni: '',
  fecha_nac: '',
  fecha_alta: '',
  email: '',
  telefono: '',
  id_equipo_actual: null,
  activo: true,
});
const originalForm = ref({});

const normalizeDate = (value) => {
  if (!value) return '';
  return String(value).slice(0, 10);
};

const formatDate = (value) => {
  if (!value) return '-';
  const [year, month, day] = String(value).slice(0, 10).split('-');
  return year && month && day ? `${day}/${month}/${year}` : value;
};

const todayString = () => new Date().toISOString().slice(0, 10);

const iconoDocumento = (url) => {
  const ext = (url || '').split('.').pop().toLowerCase();
  if (ext === 'pdf') return 'bi bi-file-earmark-pdf text-danger';
  if (['jpg', 'jpeg', 'png'].includes(ext)) return 'bi bi-file-earmark-image text-primary';
  return 'bi bi-file-earmark text-secondary';
};

const verDocumento = (nombreArchivo) => {
  const apiUrl = import.meta.env.VITE_API_URL;
  const token = userStore.token;
  const url = `${apiUrl}/fichas/${encodeURIComponent(nombreArchivo)}?token=${encodeURIComponent(token)}`;
  window.open(url, '_blank');
};

const fetchData = async () => {
  loading.value = true;
  try {
    const [jugadoresData, equiposData] = await Promise.all([
      datosMaestrosService.getJugadores(),
      datosMaestrosService.getEquipos(),
    ]);
    jugadores.value = jugadoresData.map(item => ({
      ...item,
      activo: Boolean(Number(item.activo)),
      id_equipo_actual: item.id_equipo_actual ? Number(item.id_equipo_actual) : null,
    }));
    equipos.value = equiposData.filter(item => Boolean(Number(item.activo)));
  } catch {
    toast.showToast({ message: 'Error al cargar los jugadores.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openDetalle = async (item) => {
  jugadorDetalle.value = item;
  documentos.value = [];
  showDetalleModal.value = true;
  loadingDocumentos.value = true;
  try {
    documentos.value = await datosMaestrosService.getDocumentosJugador(item.id);
  } catch {
    toast.showToast({ message: 'Error al cargar los documentos del jugador.', type: 'danger' });
  } finally {
    loadingDocumentos.value = false;
  }
};

const openModal = (item = null) => {
  showDetalleModal.value = false;
  if (item) {
    isEditing.value = true;
    form.value = {
      ...item,
      dni: item.dni ?? '',
      fecha_nac: normalizeDate(item.fecha_nac),
      fecha_alta: normalizeDate(item.fecha_alta),
      email: item.email ?? '',
      telefono: item.telefono ?? '',
      id_equipo_actual: item.id_equipo_actual ? Number(item.id_equipo_actual) : null,
      activo: Boolean(Number(item.activo)),
    };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = {
      id: null,
      nombre: '',
      apellido: '',
      dni: '',
      fecha_nac: '',
      fecha_alta: todayString(),
      email: '',
      telefono: '',
      id_equipo_actual: null,
      activo: true,
    };
  }
  showFormModal.value = true;
};

const buildPayload = () => ({
  ...(isEditing.value ? { id: form.value.id } : {}),
  nombre: form.value.nombre.trim(),
  apellido: form.value.apellido.trim(),
  dni: form.value.dni?.trim() || null,
  fecha_nac: form.value.fecha_nac || null,
  fecha_alta: form.value.fecha_alta || null,
  email: form.value.email?.trim() || null,
  telefono: form.value.telefono?.trim() || null,
  id_equipo_actual: form.value.id_equipo_actual || null,
  activo: Boolean(form.value.activo),
});

const save = async () => {
  if (!form.value.nombre?.trim() || !form.value.apellido?.trim()) {
    toast.showToast({ message: 'Nombre y apellido son obligatorios.', type: 'warning' });
    return;
  }

  if (form.value.dni && !/^\d{8}$/.test(form.value.dni.trim())) {
    toast.showToast({ message: 'El DNI debe tener exactamente 8 numeros.', type: 'warning' });
    return;
  }

  if (isEditing.value && JSON.stringify(buildPayload()) === JSON.stringify({
    id: originalForm.value.id,
    nombre: originalForm.value.nombre,
    apellido: originalForm.value.apellido,
    dni: originalForm.value.dni || null,
    fecha_nac: originalForm.value.fecha_nac || null,
    fecha_alta: originalForm.value.fecha_alta || null,
    email: originalForm.value.email?.trim() || null,
    telefono: originalForm.value.telefono?.trim() || null,
    id_equipo_actual: originalForm.value.id_equipo_actual || null,
    activo: Boolean(originalForm.value.activo),
  })) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await datosMaestrosService.actualizarJugador(buildPayload());
      toast.showToast({ message: 'Jugador actualizado correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearJugador(buildPayload());
      toast.showToast({ message: 'Jugador creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al guardar el jugador.', type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await datosMaestrosService.eliminarJugador(idToDelete.value);
    toast.showToast({ message: 'Jugador eliminado correctamente.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el jugador.', type: 'danger' });
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
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}

.jugador-switch-row .form-check-input {
  margin-left: 0;
}
</style>
