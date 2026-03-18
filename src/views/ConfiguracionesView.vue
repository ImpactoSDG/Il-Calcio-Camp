<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">CONFIGURACIONES</h1>
      </div>
      
      <div class="d-flex gap-2">
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva
        </button>
      </div>
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
            <tr v-for="conf in sortedConfigs" :key="conf.id">
              <td class="ps-4 fw-bold text-primary-custom sticky-col-first">
                <code>{{ conf.clave }}</code>
              </td>
              <td class="text-muted border-start px-3">
                <span class="d-inline-block text-truncate" style="max-width: 400px;" :title="conf.valor">
                  {{ conf.valor }}
                </span>
              </td>
              <td class="small text-secondary border-start px-3">{{ conf.descripcion || '-' }}</td>
              <td class="pe-4 text-end border-start">
                <button @click="openModal(conf)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click="prepareDelete(conf.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="configs.length === 0 && !loading">
              <td colspan="4" class="text-center py-5 text-muted">
                No se encontraron configuraciones registradas.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Configuración' : 'Nueva Configuración' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="saveConfig">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Clave (Snake case sin espacios)</label>
                <input 
                  v-model.trim="form.clave" 
                  type="text" 
                  class="form-control" 
                  :disabled="isEditing"
                  placeholder="ej: ruta_servidor_logos"
                />
                <div class="form-text">Ejemplo: nombre_de_variable</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Valor</label>
                <textarea v-model.trim="form.valor" class="form-control" rows="3" placeholder="Ruta o valor del parámetro"></textarea>
              </div>
              <div class="mb-0">
                <label class="form-label">Descripción / Nota</label>
                <input v-model.trim="form.descripcion" type="text" class="form-control" placeholder="¿Para qué se usa?" />
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
      v-model="showConfirmModal"
      title="Eliminar Configuración"
      message="¿Estás seguro de que deseas eliminar este parámetro global? Esta acción no se puede deshacer."
      confirmButtonText="Eliminar"
      variant="danger"
      :isLoading="isDeleting"
      @confirm="handleDelete"
    />

    <ToastNotification />

    <!-- ── SECCIÓN IMPRESORAS TIQUETERA (CRUD) ──────────────────────── -->
    <div class="mt-5 pt-4 border-top">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-printer-fill fs-4 text-secondary"></i>
          <h2 class="h5 fw-bold mb-0 text-secondary">IMPRESORAS TIQUETERA</h2>
        </div>
        <button @click="openImpresoraModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva
        </button>
      </div>

      <div class="card shadow-sm border-0 rounded-lg overflow-hidden position-relative" :style="{ minHeight: loadingImpresoras ? '200px' : 'auto' }">
        <div v-if="loadingImpresoras" class="loading-overlay-local d-flex flex-column align-items-center justify-content-center">
          <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
              <tr>
                <th class="ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary">Nombre de la impresora</th>
                <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start">Cmd. Corte</th>
                <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start">Avance</th>
                <th class="py-3 text-uppercase fs-xs fw-bold text-secondary border-start">Estado</th>
                <th class="pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary border-start text-end">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white">
              <tr v-for="imp in impresoras" :key="imp.id">
                <td class="ps-4 fw-semibold">
                  <i class="bi bi-printer me-2 text-secondary"></i>{{ imp.nombre }}
                  <div v-if="imp.descripcion" class="small text-muted fw-normal">{{ imp.descripcion }}</div>
                </td>
                <td class="border-start px-3"><code>{{ getCutLabel(imp.comando_corte) }}</code></td>
                <td class="border-start px-3 text-muted">{{ imp.lineas_avance }} líneas</td>
                <td class="border-start px-3">
                  <span v-if="imp.es_default == 1" class="badge bg-success">
                    <i class="bi bi-check-circle-fill me-1"></i>Predeterminada
                  </span>
                  <button v-else @click="setDefaultImpresora(imp)" class="btn btn-sm btn-outline-secondary py-0 px-2">
                    Usar esta
                  </button>
                </td>
                <td class="pe-4 text-end border-start">
                  <button @click="openImpresoraModal(imp)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                    <i class="bi bi-pencil-square fs-4"></i>
                  </button>
                  <button @click="prepareDeleteImpresora(imp.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                    <i class="bi bi-trash3 fs-4"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="impresoras.length === 0 && !loadingImpresoras">
                <td colspan="5" class="text-center py-5 text-muted">
                  No hay impresoras registradas. Agregá una con el botón "Nueva".
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Impresora -->
    <Teleport to="body">
    <div v-if="showImpresoraModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditingImpresora ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditingImpresora ? 'Editar Impresora' : 'Nueva Impresora Tiquetera' }}
            </h5>
            <button type="button" class="btn-close" @click="showImpresoraModal = false"></button>
          </div>
          <form @submit.prevent="saveImpresora">
            <div class="modal-body">

              <!-- Nombre + Detectar -->
              <div class="mb-3">
                <label class="form-label fw-semibold">Nombre de la impresora</label>
                <div class="input-group">
                  <input
                    v-model.trim="impresoraForm.nombre"
                    type="text"
                    class="form-control"
                    placeholder="Ej: POS-80C, EPSON TM-T20, Genérica 80mm..."
                    required
                  />
                  <button @click.prevent="detectarImpresorasEnModal" class="btn btn-outline-secondary" type="button" :disabled="detectando" title="Detectar impresoras vía QZ Tray">
                    <span v-if="detectando" class="spinner-border spinner-border-sm"></span>
                    <span v-else><i class="bi bi-search me-1"></i>Detectar</span>
                  </button>
                </div>
                <div v-if="impresorasDetectadas.length > 0" class="mt-2 p-2 border rounded-3 bg-light">
                  <p class="small text-muted mb-1 fw-bold">Hacé click para seleccionar:</p>
                  <button
                    v-for="nombre in impresorasDetectadas"
                    :key="nombre"
                    type="button"
                    class="btn btn-sm me-1 mb-1"
                    :class="impresoraForm.nombre === nombre ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="impresoraForm.nombre = nombre"
                  >
                    <i class="bi bi-printer me-1"></i>{{ nombre }}
                  </button>
                </div>
                <div class="form-text">El nombre debe coincidir exactamente con el dispositivo en Windows.</div>
              </div>

              <div class="row g-3">
                <!-- Comando corte -->
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Comando de corte (ESC/POS)</label>
                  <select v-model="impresoraForm.comando_corte" class="form-select" required>
                    <option v-for="v in CUT_VARIANTS" :key="v.value" :value="v.value">{{ v.label }}</option>
                  </select>
                  <div class="form-text">Si el papel no se corta, probá otra opción.</div>
                </div>
                <!-- Líneas avance -->
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Líneas de avance</label>
                  <input v-model.number="impresoraForm.lineas_avance" type="number" min="0" max="20" class="form-control" />
                  <div class="form-text">Antes del corte (0–20).</div>
                </div>
                <!-- Predeterminada -->
                <div class="col-md-3 d-flex flex-column justify-content-center">
                  <div class="form-check mt-4">
                    <input v-model="impresoraForm.es_default" class="form-check-input" type="checkbox" id="esDefaultCheck" />
                    <label class="form-check-label fw-semibold" for="esDefaultCheck">Predeterminada</label>
                  </div>
                </div>
              </div>

              <!-- Descripción -->
              <div class="mt-3">
                <label class="form-label">Descripción (opcional)</label>
                <input v-model.trim="impresoraForm.descripcion" type="text" class="form-control" placeholder="Ej: Impresora del mostrador principal" />
              </div>

            </div>
            <div class="modal-footer">
              <button @click="showImpresoraModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="submit" class="btn btn-primary-modern px-4" :disabled="isSavingImpresora">
                <span v-if="isSavingImpresora" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditingImpresora ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </Teleport>

    <ConfirmModal
      v-model="showConfirmImpresoraModal"
      title="Eliminar Impresora"
      message="¿Estás seguro de que deseas eliminar esta impresora tiquetera? Esta acción no se puede deshacer."
      confirmButtonText="Eliminar"
      variant="danger"
      :isLoading="isDeletingImpresora"
      @confirm="handleDeleteImpresora"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import configuracionService from '@/services/configuracionService';
import impresoraTiqueteraService from '@/services/impresoraTiqueteraService';
import ConfirmModal from '@/components/ConfirmModal.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import ToastNotification from '@/components/ToastNotification.vue';
import { useToastStore } from '@/stores/toastStore';
import {
  setupQzSecurity, listarImpresoras,
  savePrinterName, saveCutCmd, saveFeedLines, syncLocalStorage,
  CUT_VARIANTS,
  QZ_CERT, QZ_PK,
} from '@/composables/usePrinterConfig';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'clave',       label: 'Clave',       sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary sticky-col-first' },
  { key: 'valor',       label: 'Valor / Ruta',sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary border-start' },
  { key: 'descripcion', label: 'Descripción', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary border-start' },
  { key: 'acciones',    label: 'Acciones',    sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end border-start' },
]

const configs = ref([]);

const sortedConfigs = computed(() => sortItems(configs.value))
const loading = ref(false);
const showFormModal = ref(false);
const showConfirmModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const configToDelete = ref(null);
const originalForm = ref({});
const form = ref({ id: null, clave: '', valor: '', descripcion: '' });

const fetchConfigs = async () => {
  loading.value = true;
  try {
    configs.value = await configuracionService.getAll();
  } catch (error) {
    toast.showToast({ message: "Error al cargar configuraciones", type: "danger" });
  } finally {
    loading.value = false;
  }
};

const openModal = (conf = null) => {
  if (conf) {
    isEditing.value = true;
    form.value = { ...conf };
    originalForm.value = { ...conf };
  } else {
    isEditing.value = false;
    form.value = { id: null, clave: '', valor: '', descripcion: '' };
  }
  showFormModal.value = true;
};

/**
 * Validaciones de Frontend
 */
const validateForm = () => {
  const { clave, valor } = form.value;

  if (!clave) {
    toast.showToast({ message: "La clave es obligatoria", type: "warning" });
    return false;
  }

  // Verificar duplicado local (solo en creación)
  if (!isEditing.value) {
    const exists = configs.value.some(c => c.clave.toLowerCase() === clave.toLowerCase());
    if (exists) {
      toast.showToast({ message: "Esta clave ya existe en el sistema", type: "danger" });
      return false;
    }
  }

  if (!valor) {
    toast.showToast({ message: "El campo Valor no puede estar vacío", type: "warning" });
    return false;
  }

  // Verificar si hubo cambios reales en edición
  if (isEditing.value) {
    const hasChanged = JSON.stringify(form.value) !== JSON.stringify(originalForm.value);
    if (!hasChanged) {
      toast.showToast({ message: "No se detectaron cambios para actualizar", type: "info" });
      showFormModal.value = false;
      return false;
    }
  }

  return true;
};

const saveConfig = async () => {
  if (!validateForm()) return;

  isSaving.value = true;
  try {
    if (isEditing.value) {
      await configuracionService.update(form.value);
      toast.showToast({ message: "Configuración actualizada con éxito", type: "success" });
    } else {
      await configuracionService.create(form.value);
      toast.showToast({ message: "Parámetro global creado", type: "success" });
    }
    showFormModal.value = false;
    fetchConfigs();
  } catch (error) {
    toast.showToast({ message: "Error al procesar la solicitud en el servidor", type: "danger" });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => {
  configToDelete.value = id;
  showConfirmModal.value = true;
};

const handleDelete = async () => {
  isDeleting.value = true;
  try {
    await configuracionService.delete(configToDelete.value);
    toast.showToast({ message: "Configuración eliminada correctamente", type: "success" });
    showConfirmModal.value = false;
    fetchConfigs();
  } catch (error) {
    toast.showToast({ message: "Error al eliminar el parámetro", type: "danger" });
  } finally {
    isDeleting.value = false;
  }
};

// ──── CRUD Impresoras Tiquetera ───────────────────────────────
const impresoras                = ref([]);
const loadingImpresoras         = ref(false);
const showImpresoraModal        = ref(false);
const isEditingImpresora        = ref(false);
const isSavingImpresora         = ref(false);
const showConfirmImpresoraModal = ref(false);
const isDeletingImpresora       = ref(false);
const impresoraToDelete         = ref(null);
const impresorasDetectadas      = ref([]);
const detectando                = ref(false);

const emptyImpresoraForm = () => ({
  id: null,
  nombre: '',
  comando_corte: '\x1D\x56\x00',
  lineas_avance: 5,
  es_default: false,
  descripcion: '',
});
const impresoraForm = ref(emptyImpresoraForm());

const getCutLabel = (val) => CUT_VARIANTS.find(v => v.value === val)?.label ?? val;

const fetchImpresoras = async () => {
  loadingImpresoras.value = true;
  try {
    impresoras.value = await impresoraTiqueteraService.getAll();
    const def = impresoras.value.find(i => i.es_default == 1 || i.es_default === true);
    if (def) {
      syncLocalStorage(def);
    }
  } catch {
    toast.showToast({ message: 'Error al cargar las impresoras', type: 'danger' });
  } finally {
    loadingImpresoras.value = false;
  }
};

const openImpresoraModal = (imp = null) => {
  impresorasDetectadas.value = [];
  if (imp) {
    isEditingImpresora.value = true;
    impresoraForm.value = { ...imp, es_default: imp.es_default == 1 };
  } else {
    isEditingImpresora.value = false;
    impresoraForm.value = emptyImpresoraForm();
  }
  showImpresoraModal.value = true;
};

const detectarImpresorasEnModal = async () => {
  detectando.value = true;
  try {
    setupQzSecurity(QZ_CERT, QZ_PK);
    impresorasDetectadas.value = await listarImpresoras();
    if (!impresorasDetectadas.value.length) {
      toast.showToast({ message: 'No se encontraron impresoras vía QZ Tray.', type: 'warning' });
    }
  } catch {
    toast.showToast({ message: 'No se pudo conectar a QZ Tray.', type: 'danger' });
  } finally {
    detectando.value = false;
  }
};

const saveImpresora = async () => {
  if (!impresoraForm.value.nombre) {
    toast.showToast({ message: 'El nombre de la impresora es obligatorio.', type: 'warning' });
    return;
  }
  isSavingImpresora.value = true;
  try {
    if (isEditingImpresora.value) {
      await impresoraTiqueteraService.update(impresoraForm.value);
      toast.showToast({ message: 'Impresora actualizada.', type: 'success' });
    } else {
      await impresoraTiqueteraService.create(impresoraForm.value);
      toast.showToast({ message: 'Impresora registrada.', type: 'success' });
    }
    syncLocalStorage(impresoraForm.value);
    showImpresoraModal.value = false;
    fetchImpresoras();
  } catch {
    toast.showToast({ message: 'Error al guardar la impresora.', type: 'danger' });
  } finally {
    isSavingImpresora.value = false;
  }
};

const setDefaultImpresora = async (imp) => {
  try {
    await impresoraTiqueteraService.update({ ...imp, es_default: true });
    syncLocalStorage({ ...imp, es_default: true });
    toast.showToast({ message: `"${imp.nombre}" establecida como predeterminada.`, type: 'success' });
    fetchImpresoras();
  } catch {
    toast.showToast({ message: 'Error al actualizar la impresora.', type: 'danger' });
  }
};

const prepareDeleteImpresora = (id) => {
  impresoraToDelete.value = id;
  showConfirmImpresoraModal.value = true;
};

const handleDeleteImpresora = async () => {
  isDeletingImpresora.value = true;
  try {
    await impresoraTiqueteraService.delete(impresoraToDelete.value);
    toast.showToast({ message: 'Impresora eliminada.', type: 'success' });
    showConfirmImpresoraModal.value = false;
    fetchImpresoras();
  } catch {
    toast.showToast({ message: 'Error al eliminar la impresora.', type: 'danger' });
  } finally {
    isDeletingImpresora.value = false;
  }
};

onMounted(() => {
  fetchConfigs();
  fetchImpresoras();
});
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }
.btn-link { text-decoration: none; }
.loading-overlay-local {
  position: absolute;
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
  display: flex;
}
code {
  background-color: #f8f9fa;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  color: #d63384;
}
</style>