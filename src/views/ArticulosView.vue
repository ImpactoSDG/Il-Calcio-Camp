<template>
  <div class="container-fluid p-4 bg-body-tertiary min-vh-100 animate-fade-in">

    <!-- ── Encabezado ── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center gap-3">
        <button @click="$router.back()" class="btn-back-arrow" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <div>
          <h1 class="h5 fw-bold mb-0">Artículos</h1>
          <p class="text-muted small mb-0">Gestión del catálogo de productos</p>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <button
          @click="openBulkStatusModal(true)"
          class="btn-bulk-text btn-bulk-text--success"
          :class="{ 'btn-bulk-text--disabled': seleccionados.length === 0 }"
          title="Activar seleccionados"
        >
          <i class="bi bi-check-circle-fill me-1"></i> Activar
        </button>
        <button
          @click="openBulkStatusModal(false)"
          class="btn-bulk-text btn-bulk-text--danger"
          :class="{ 'btn-bulk-text--disabled': seleccionados.length === 0 }"
          title="Desactivar seleccionados"
        >
          <i class="bi bi-x-circle-fill me-1"></i> Desactivar
        </button>
        <div class="bulk-divider mx-1"></div>
        <button
          @click="openBulkModal"
          class="btn-bulk-trigger d-flex align-items-center gap-2"
          :class="{ 'btn-bulk-trigger--disabled': seleccionados.length === 0 }"
        >
          <span v-if="seleccionados.length > 0" class="bulk-trigger__badge">{{ seleccionados.length }}</span>
          <i class="bi bi-tags-fill"></i>
          Actualizar precios
        </button>
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center gap-2">
          <i class="bi bi-plus-circle-fill"></i> Nuevo Artículo
        </button>
      </div>
    </div>

    <!-- ── Buscador ── -->
    <div class="row mb-4">
      <div class="col-md-4 col-lg-3">
        <FuzzySearch v-model="searchQuery" placeholder="Buscar artículos..." />
      </div>
    </div>

    <!-- ── Cargando ── -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary-custom" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <!-- ── Sin resultados ── -->
    <div v-else-if="articulosAgrupados.length === 0" class="text-center py-5 text-muted bg-white rounded-4 border">
      <i class="bi bi-search fs-1 opacity-25"></i>
      <p class="mt-2 mb-0">No se encontraron artículos.</p>
    </div>

    <!-- ── Acordeones por Categoría ── -->
    <div v-else class="d-flex flex-column gap-3">
      <div
        v-for="(grupo, idx) in articulosAgrupados"
        :key="grupo.categoria"
        class="card-categoria"
        :class="{ 'card-categoria--open': categoriasAbiertas.has(idx) }"
      >
        <div
          class="card-categoria__header"
          @click="toggleCategoria(idx)"
        >
          <div class="d-flex align-items-center gap-3">
            <div class="cat-icon">
              <i class="bi bi-collection-fill"></i>
            </div>
            <div>
              <div class="fw-bold text-dark">{{ grupo.categoria }}</div>
              <div class="fs-xs text-muted mt-1">
                <span class="cat-count-badge">
                  {{ grupo.articulos.length }} {{ grupo.articulos.length === 1 ? 'artículo' : 'artículos' }}
                </span>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="cat-stats d-none d-md-flex">
              <span class="cat-stat">
                <i class="bi bi-check-circle-fill text-success me-1"></i>
                {{ grupo.articulos.filter(a => a.activo).length }} activos
              </span>
              <span class="cat-stat-divider">·</span>
              <span class="cat-stat">
                <i class="bi bi-x-circle text-secondary me-1"></i>
                {{ grupo.articulos.filter(a => !a.activo).length }} inactivos
              </span>
            </div>
          
            <div class="d-flex align-items-center gap-2 cat-select-all" @click.stop>
              <input
                type="checkbox"
                class="form-check-input mt-0"
                :checked="isCategoryAllSelected(grupo.articulos)"
                :indeterminate.prop="isCategoryPartialSelected(grupo.articulos)"
                @change="toggleCategorySelection(grupo.articulos, $event)"
                title="Seleccionar todos en esta categoría"
              />
              <label class="fs-xs text-muted" style="cursor:pointer">Sel. todos</label>
            </div>
          
            <div class="cat-chevron-wrap" :class="{ 'cat-chevron-wrap--open': categoriasAbiertas.has(idx) }">
              <i class="bi bi-chevron-down"></i>
            </div>
          </div>
        </div>
      
        <Transition name="accordion">
          <div v-if="categoriasAbiertas.has(idx)" class="card-categoria__body">
            <table class="table-articulos w-100">
              <colgroup>
                <col style="width: 44px">    <col style="width: 60px">    <col style="width: auto">    <col style="width: 110px">   <col style="width: 110px">   <col style="width: 150px">   <col style="width: 100px">   <col style="width: 100px">   </colgroup>
              <thead>
                <tr>
                  <th></th>
                  <th></th>
                  <th>Nombre</th>
                  <th class="text-end">Precio</th>
                  <th class="text-end">Costo</th>
                  <th>Cód. Barra</th>
                  <th class="text-center">Estado</th>
                  <th class="text-end pe-4">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in grupo.articulos"
                  :key="item.id"
                  class="row-articulo"
                  :class="{ 'row-articulo--selected': seleccionados.includes(item.id) }"
                >
                  <td class="ps-3">
                    <input
                      type="checkbox"
                      class="form-check-input mt-0"
                      :value="item.id"
                      v-model="seleccionados"
                    />
                  </td>
                  <td>
                    <div class="articulo-img-thumb shadow-sm border overflow-hidden rounded-2 d-flex align-items-center justify-content-center bg-light" style="width: 40px; height: 40px;">
                      <img v-if="item.url_imagen" :src="`${apiBaseUrl}/${item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                      <i v-else class="bi bi-image text-muted opacity-25" style="font-size: 1.2rem;"></i>
                    </div>
                  </td>
                  <td>
                    <span class="fw-semibold text-dark">{{ item.nombre }}</span>
                  </td>
                  <td class="text-end">
                    <span class="precio-val">
                      {{ item.precio_actual != null ? `$${formatNum(item.precio_actual)}` : '—' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <span class="precio-val precio-val--costo">
                      {{ item.costo_actual != null ? `$${formatNum(item.costo_actual)}` : '—' }}
                    </span>
                  </td>
                  <td class="text-muted small font-monospace">
                    <i class="bi bi-upc me-1 opacity-50"></i>{{ item.cod_barra || '—' }}
                  </td>
                  <td class="text-center">
                    <span :class="item.activo ? 'status-pill--active' : 'status-pill--inactive'" class="status-pill">
                      {{ item.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="pe-4 text-end">
                    <div class="d-flex justify-content-end gap-1">
                      <button @click="openModal(item)" class="btn-action btn-action--edit" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                      </button>
                      <button @click="prepareDelete(item.id)" class="btn-action btn-action--delete" title="Eliminar">
                        <i class="bi bi-trash3-fill"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </Transition>
      </div>
    </div>

    <!-- ── Modal: Actualización masiva de precios ── -->
    <Teleport to="body">
      <div v-if="showBulkModal" class="modal-backdrop-custom">
        <div class="modal-panel modal-panel--bulk animate-modal-in">
          <div class="modal-panel__header modal-panel__header--bulk">
            <div class="d-flex align-items-center gap-3">
              <span class="modal-icon-wrap modal-icon-wrap--bulk">
                <i class="bi bi-tags-fill"></i>
              </span>
              <div>
                <h5 class="mb-0 fw-bold">Actualizar precios en lote</h5>
                <p class="mb-0 text-muted small">
                  Modificando
                  <strong>{{ seleccionados.length }}</strong>
                  artículo{{ seleccionados.length !== 1 ? 's' : '' }}
                </p>
              </div>
            </div>
            <button class="btn-close" @click="closeBulkModal"></button>
          </div>

          <div class="modal-panel__body">
            <!-- Columna a modificar -->
            <div class="bulk-section">
              <label class="bulk-section__label">Campo a modificar</label>
              <div class="btn-group-custom">
                <button
                  v-for="opt in opcionesBase"
                  :key="opt.value"
                  @click="bulkCampo = opt.value; calcularPrevisualizacion()"
                  class="btn-choice"
                  :class="{ 'btn-choice--active': bulkCampo === opt.value }"
                >
                  <i class="bi me-1" :class="opt.icon"></i>{{ opt.label }}
                </button>
              </div>
            </div>

            <!-- Input porcentaje -->
            <div class="bulk-section mt-3">
              <label class="bulk-section__label">
                Porcentaje de ajuste (%)
              </label>
              <div class="input-group" style="max-width: 220px;">
                <span class="input-group-text fw-bold">%</span>
                <input
                  v-model.number="bulkValor"
                  type="number"
                  step="0.01"
                  class="form-control"
                  placeholder="Ej: 10"
                  @input="calcularPrevisualizacion"
                />
              </div>
              <p v-if="bulkValor" class="text-muted fs-xs mt-1">
                Se aplicará un incremento del {{ bulkValor }}% sobre el {{ bulkCampo === 'precio_actual' ? 'Precio Final' : 'Precio Costo' }} actual
              </p>
            </div>

            <!-- Previsualización -->
            <div class="bulk-preview mt-4">
              <div class="bulk-preview__header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-eye me-1"></i> Previsualización de cambios</span>
                <span class="badge bg-secondary opacity-75">{{ seleccionados.length }} ítems</span>
              </div>
              <div class="bulk-preview__chips">
                <div
                  v-for="item in previsualizacion"
                  :key="item.id"
                  class="preview-chip"
                >
                  <span class="preview-chip__name">{{ item.nombre }}</span>
                  <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="preview-chip__old">${{ formatNum(item.valorActual) }}</span>
                    <i class="bi bi-arrow-right preview-chip__arrow"></i>
                    <span class="preview-chip__new" :class="{ 'text-danger': item.valorNuevo < item.valorActual }">
                      ${{ formatNum(item.valorNuevo) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-panel__footer">
            <button @click="closeBulkModal" type="button" class="btn btn-light px-4">Cancelar</button>
            <button
              @click="aplicarBulk"
              class="btn-primary-modern px-4 d-flex align-items-center gap-2"
              :disabled="isApplying || bulkValor === null || bulkValor === ''"
            >
              <span v-if="isApplying" class="spinner-border spinner-border-sm"></span>
              <i v-else class="bi bi-check2-all"></i>
              Aplicar cambios
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Modal: Formulario artículo ── -->
    <Teleport to="body">
      <div v-if="showFormModal" class="modal-backdrop-custom modal-backdrop-custom--scrollable">
        <div class="modal-panel animate-modal-in my-4">
          <div class="modal-panel__header">
            <div class="d-flex align-items-center gap-3">
              <span class="modal-icon-wrap">
                <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
              </span>
              <div>
                <h5 class="mb-0 fw-bold">{{ isEditing ? 'Editar Artículo' : 'Nuevo Artículo' }}</h5>
                <p class="mb-0 text-muted small">{{ isEditing ? 'Modificar los datos del producto' : 'Completá los campos para agregar un producto' }}</p>
              </div>
            </div>
            <button class="btn-close" @click="showFormModal = false"></button>
          </div>

          <form @submit.prevent>
            <div class="modal-panel__body">
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label">Nombre <span class="text-danger">*</span></label>
                  <input 
                    v-model.trim="form.nombre" 
                    type="text" 
                    class="form-control" 
                    placeholder="Ej: Coca Cola 500 ml" 
                    required 
                    @keydown.enter.prevent
                  />
                </div>
                
                <!-- Subida de Imagen -->
                <div class="col-12">
                  <label class="form-label d-block text-uppercase fw-bold small text-secondary">Imagen del Producto</label>
                  <div class="d-flex flex-column align-items-center gap-3 p-4 border rounded-4 bg-white shadow-sm overflow-hidden">
                    <div class="position-relative" style="width: 180px; height: 180px;">
                      <div class="w-100 h-100 rounded-4 bg-light border-2 border-dashed border-primary-subtle d-flex align-items-center justify-content-center overflow-hidden shadow-sm">
                        <img v-if="previewImagen" :src="previewImagen" class="w-100 h-100 object-fit-cover animate-fade-in" />
                        <img v-else-if="form.url_imagen" :src="`${apiBaseUrl}/${form.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                        <div v-else class="text-center">
                          <i class="bi bi-cloud-arrow-up fs-1 text-primary opacity-50"></i>
                          <p class="small text-muted mb-0">Sin imagen</p>
                        </div>
                      </div>
                      <input 
                        type="file" 
                        ref="fileInput" 
                        @change="onFileChange" 
                        accept=".jpg,.jpeg,.png,.webp" 
                        class="d-none" 
                      />
                      <button 
                        v-if="form.url_imagen || previewImagen" 
                        type="button" 
                        @click="clearImage" 
                        class="btn btn-danger btn-sm rounded-circle position-absolute top-0 end-0 m-2 shadow"
                        style="width: 28px; height: 28px; padding: 0; transform: translate(30%, -30%); z-index: 5;"
                      >
                        <i class="bi bi-x fs-5"></i>
                      </button>
                    </div>
                    <div class="text-center">
                      <p class="small text-muted mb-3 lh-sm" style="max-width: 300px;">Formatos: <strong>JPG, PNG o WEBP</strong>. Se recomienda una relación de aspecto 1:1.</p>
                      <button 
                        type="button" 
                        @click="$refs.fileInput.click()" 
                        class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm"
                        :disabled="isSaving"
                      >
                        <i class="bi bi-camera-fill me-2"></i>
                        {{ form.url_imagen || previewImagen ? 'Cambiar imagen' : 'Seleccionar foto' }}
                      </button>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Categoría</label>
                  <select v-model.number="form.id_categoria_articulo" class="form-select">
                    <option :value="null">Sin categoría</option>
                    <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.descripcion }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Código de Barras</label>
                  <input 
                    v-model.trim="form.cod_barra" 
                    type="text" 
                    class="form-control" 
                    placeholder="Ej: 7790001234567" 
                    @keydown.enter.prevent
                  />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Precio Actual ($)</label>
                  <CustomNumberInput v-model="form.precio_actual" :decimals="2" placeholder="0.00" @keydown.enter.prevent />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Costo Actual ($)</label>
                  <CustomNumberInput v-model="form.costo_actual" :decimals="2" placeholder="0.00" @keydown.enter.prevent />
                </div>
                <!-- Nuevo campo ROP -->
                <div class="col-md-6">
                  <label class="form-label">Stock Mínimo (ROP)</label>
                  <input 
                    v-model.number="form.ROP" 
                    type="number" 
                    class="form-control" 
                    placeholder="1" 
                    min="0" 
                    @keydown.enter.prevent
                  />
                  <div class="form-text fs-xs">Nivel de reabastecimiento</div>
                </div>
                <!-- Sección de Estado: Solo visible al editar -->
                <div v-if="isEditing" class="col-12 mt-3">
                  <label class="form-label d-block text-uppercase fw-bold small text-secondary">Estado del Artículo</label>
                  <div class="d-flex align-items-center gap-3 p-3 bg-light border border-light-subtle rounded-3">
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-3">
                      <input 
                        v-model="form.activo" 
                        class="form-check-input flex-shrink-0" 
                        type="checkbox" 
                        role="switch" 
                        id="chkActivo" 
                        style="width: 2.8em; height: 1.5em; cursor: pointer; margin-left: 0;" 
                      />
                      <label class="form-check-label fw-bold mb-0" for="chkActivo" :class="form.activo ? 'text-success' : 'text-danger'" style="cursor: pointer; min-width: 70px;">
                        {{ form.activo ? 'Activo' : 'Inactivo' }}
                      </label>
                    </div>
                    <div class="vr mx-1 opacity-25" style="height: 24px;"></div>
                    <span class="text-muted small lh-sm">
                      {{ form.activo ? 'El artículo aparecerá en el catálogo de ventas.' : 'El artículo no estará disponible para nuevas ventas.' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-panel__footer">
              <button @click="showFormModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
              <button type="button" @click="save" class="btn-primary-modern px-4" :disabled="isSaving">
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                {{ isEditing ? 'Actualizar' : 'Guardar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Artículo"
      message="¿Estás seguro de eliminar este artículo? Será retirado del sistema y no aparecerá en ningún listado. Las ventas históricas no se verán afectadas."
      confirm-button-text="Eliminar"
      variant="danger"
      :is-loading="isDeleting"
      @confirm="confirmDelete"
    />

    <ConfirmModal
      v-model="showBulkStatusModal"
      :title="targetStatus ? 'Activar Artículos' : 'Desactivar Artículos'"
      :message="`¿Estás seguro de que deseas ${targetStatus ? 'activar' : 'desactivar'} los ${seleccionados.length} artículos seleccionados?`"
      :confirm-button-text="targetStatus ? 'Activar' : 'Desactivar'"
      :variant="targetStatus ? 'success' : 'danger'"
      :is-loading="isApplyingStatus"
      @confirm="executeBulkStatusUpdate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import CustomNumberInput from '@/components/CustomNumberInput.vue';
import articulosService from '@/services/articulosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';
import { formatNumber } from '@/utils/formatters';

const toast = useToastStore();

// ── Datos ────────────────────────────────────────────────────────
const articulos   = ref([]);
const categorias  = ref([]);
const loading     = ref(false);
const searchQuery = ref('');

// ── Modal individual ─────────────────────────────────────────────
const showFormModal   = ref(false);
const showDeleteModal = ref(false);
const showBulkStatusModal = ref(false);
const isEditing       = ref(false);
const isSaving        = ref(false);
const isDeleting      = ref(false);
const isApplyingStatus = ref(false);
const idToDelete      = ref(null);
const targetStatus    = ref(true);

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';
const fileInput = ref(null);
const previewImagen = ref(null);
const fileToUpload = ref(null);

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  const allowed = ['image/jpeg', 'image/png', 'image/webp'];
  if (!allowed.includes(file.type)) {
    toast.showToast({ message: 'Formato no permitido. Use JPG, PNG o WEBP.', type: 'danger' });
    return;
  }

  fileToUpload.value = file;
  previewImagen.value = URL.createObjectURL(file);
};

const clearImage = () => {
  fileToUpload.value = null;
  previewImagen.value = null;
  form.value.url_imagen = null;
  if (fileInput.value) fileInput.value.value = '';
};

const emptyForm = () => ({ 
  nombre: '', 
  precio_actual: null, 
  costo_actual: null, 
  cod_barra: '', 
  id_categoria_articulo: null, 
  activo: true, 
  url_imagen: null, 
  ROP: 1 
});
const form         = ref(emptyForm());
const originalForm = ref({});

// ── Acordeones ───────────────────────────────────────────────────
const categoriasAbiertas = ref(new Set());
const toggleCategoria = (idx) => {
  const s = new Set(categoriasAbiertas.value);
  s.has(idx) ? s.delete(idx) : s.add(idx);
  categoriasAbiertas.value = s;
};

// ── Selección múltiple ───────────────────────────────────────────
const seleccionados = ref([]);

const isCategoryAllSelected     = (arts) => arts.length > 0 && arts.every(a => seleccionados.value.includes(a.id));
const isCategoryPartialSelected = (arts) => arts.some(a => seleccionados.value.includes(a.id)) && !isCategoryAllSelected(arts);

const toggleCategorySelection = (arts, event) => {
  const ids = arts.map(a => a.id);
  if (event.target.checked) {
    seleccionados.value = [...new Set([...seleccionados.value, ...ids])];
  } else {
    seleccionados.value = seleccionados.value.filter(id => !ids.includes(id));
  }
  calcularPrevisualizacion();
};

const limpiarSeleccion = () => {
  seleccionados.value = [];
  previsualizacionMap.value = {};
};

// ── Bulk update ──────────────────────────────────────────────────
const showBulkModal = ref(false);
const bulkCampo     = ref('precio_actual');
const bulkValor     = ref(null);
const isApplying    = ref(false);
const previsualizacionMap = ref({});

const opcionesBase = [
  { value: 'precio_actual', label: 'Precio Final',  icon: 'bi-tag-fill' },
  { value: 'costo_actual',  label: 'Precio Costo',  icon: 'bi-box-seam' },
];

const previsualizacion = computed(() =>
  seleccionados.value.map(id => {
    const art = articulos.value.find(a => a.id === id);
    const valorActual = bulkCampo.value === 'precio_actual' ? (art?.precio_actual ?? 0) : (art?.costo_actual ?? 0);
    let valorNuevo = valorActual;
    
    if (bulkValor.value !== null && bulkValor.value !== '') {
      valorNuevo = parseFloat((Number(valorActual) * (1 + Number(bulkValor.value) / 100)).toFixed(2));
    }

    return {
      id: Number(id),
      nombre: art?.nombre ?? '?',
      valorActual,
      valorNuevo,
    };
  })
);

const calcularPrevisualizacion = () => {
  const mapa = {};
  previsualizacion.value.forEach(item => {
    mapa[item.id] = item.valorNuevo;
  });
  previsualizacionMap.value = mapa;
};

watch([bulkCampo, bulkValor], calcularPrevisualizacion);

const openBulkModal = () => {
  if (seleccionados.value.length === 0) {
    toast.showToast({ message: 'Debe seleccionar al menos un producto para actualizar precios.', type: 'warning' });
    return;
  }
  showBulkModal.value = true;
};

const closeBulkModal = () => {
  showBulkModal.value  = false;
  bulkValor.value      = null;
  previsualizacionMap.value = {};
};

const aplicarBulk = async () => {
  if (!seleccionados.value.length || bulkValor.value === null || bulkValor.value === '') return;
  calcularPrevisualizacion();
  isApplying.value = true;
  try {
    const result = await articulosService.bulkUpdatePrecios(bulkCampo.value, previsualizacionMap.value);
    toast.showToast({ message: result.message || 'Precios actualizados.', type: 'success' });
    closeBulkModal();
    limpiarSeleccion();
    await fetchData();
  } catch {
    toast.showToast({ message: 'Error al aplicar los cambios.', type: 'danger' });
  } finally {
    isApplying.value = false;
  }
};

const openBulkStatusModal = (status) => {
  if (seleccionados.value.length === 0) {
    toast.showToast({ message: `Debe seleccionar al menos un producto para ${status ? 'activar' : 'desactivar'} en lote.`, type: 'warning' });
    return;
  }
  targetStatus.value = status;
  showBulkStatusModal.value = true;
};

const executeBulkStatusUpdate = async () => {
  isApplyingStatus.value = true;
  try {
    const result = await articulosService.bulkUpdateStatus(seleccionados.value, targetStatus.value);
    toast.showToast({ message: result.message || 'Estados actualizados.', type: 'success' });
    
    // Actualización optimista
    articulos.value = articulos.value.map(a => {
      if (seleccionados.value.includes(a.id)) {
        return { ...a, activo: targetStatus.value ? 1 : 0 };
      }
      return a;
    });
    
    showBulkStatusModal.value = false;
    limpiarSeleccion();
  } catch {
    toast.showToast({ message: 'Error al actualizar los estados.', type: 'danger' });
  } finally {
    isApplyingStatus.value = false;
  }
};

// ── Computados ───────────────────────────────────────────────────
const articulosFiltrados = computed(() => {
  if (!searchQuery.value) return articulos.value;
  const q = searchQuery.value.toLowerCase();
  return articulos.value.filter(a =>
    a.nombre?.toLowerCase().includes(q) ||
    a.cod_barra?.toLowerCase().includes(q) ||
    a.categoria_descripcion?.toLowerCase().includes(q)
  );
});

const articulosAgrupados = computed(() => {
  const grupos = {};
  articulosFiltrados.value.forEach(item => {
    const cat = item.categoria_descripcion || 'Sin Categoría';
    if (!grupos[cat]) grupos[cat] = [];
    grupos[cat].push(item);
  });
  return Object.keys(grupos).sort().map(cat => ({ categoria: cat, articulos: grupos[cat] }));
});

watch(articulosAgrupados, (grupos) => {
  categoriasAbiertas.value = new Set(grupos.map((_, i) => i));
}, { immediate: false });

// ── CRUD ─────────────────────────────────────────────────────────
const fetchData = async () => {
  loading.value = true;
  try {
    [articulos.value, categorias.value] = await Promise.all([
      articulosService.getArticulos(),
      datosMaestrosService.getCategorias(),
    ]);
  } catch {
    toast.showToast({ message: 'Error al cargar los artículos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  previewImagen.value = null;
  fileToUpload.value = null;
  if (item) {
    isEditing.value = true;
    form.value = { ...item, activo: Boolean(Number(item.activo)) };
    originalForm.value = { ...form.value };
  } else {
    isEditing.value = false;
    form.value = emptyForm();
  }
  showFormModal.value = true;
};

const save = async () => {
  if (!form.value.nombre) {
    toast.showToast({ message: 'El nombre es obligatorio.', type: 'warning' });
    return;
  }
  
  const hasFormChanges = JSON.stringify(form.value) !== JSON.stringify(originalForm.value);
  const hasFileToUpload = !!fileToUpload.value;
  
  if (isEditing.value && !hasFormChanges && !hasFileToUpload) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }
  
  isSaving.value = true;
  try {
    let savedArticulo = null;
    if (isEditing.value) {
      await articulosService.actualizarArticulo(form.value);
      savedArticulo = form.value;
      toast.showToast({ message: 'Artículo actualizado.', type: 'success' });
    } else {
      const resp = await articulosService.crearArticulo(form.value);
      savedArticulo = { ...form.value, id: resp.id };
      toast.showToast({ message: 'Artículo creado.', type: 'success' });
    }

    // Subir imagen si hay un archivo seleccionado
    if (fileToUpload.value && savedArticulo.id) {
      await articulosService.subirImagen(savedArticulo.id, savedArticulo.nombre, fileToUpload.value);
    }
    
    showFormModal.value = false;
    previewImagen.value = null;
    fileToUpload.value = null;
    fetchData();
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al guardar el artículo.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const prepareDelete = (id) => { idToDelete.value = id; showDeleteModal.value = true; };

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    await articulosService.eliminarArticulo(idToDelete.value);
    toast.showToast({ message: 'Artículo eliminado del sistema.', type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar el artículo.', type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

const formatNum = (val) => formatNumber(val, 2, true);

onMounted(async () => {
  await fetchData();
  categoriasAbiertas.value = new Set(articulosAgrupados.value.map((_, i) => i));
});
</script>

<style scoped>
.fs-xs { font-size: 0.75rem; }

/* ── Botón "Actualizar precios" flotante ── */
.btn-bulk-trigger {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 1rem;
  background: linear-gradient(135deg, #169c9f 0%, #0e7c7f 100%);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(22, 156, 159, 0.35);
  transition: all 0.2s;
}
.btn-bulk-trigger:hover:not(.btn-bulk-trigger--disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(22, 156, 159, 0.45);
}

.btn-bulk-text {
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}
.btn-bulk-text--success { background: #d4f0e2; color: #0d6e35; }
.btn-bulk-text--success:hover:not(.btn-bulk-text--disabled) { background: #0d6e35; color: #fff; transform: translateY(-1px); }
.btn-bulk-text--danger { background: #fdeaea; color: #dc3545; }
.btn-bulk-text--danger:hover:not(.btn-bulk-text--disabled) { background: #dc3545; color: #fff; transform: translateY(-1px); }

.btn-bulk-text--disabled {
  background: #f1f3f5 !important;
  color: #adb5bd !important;
  box-shadow: none !important;
  cursor: default !important;
  transform: none !important;
}

/* ── Botones de acción rápida en lote ── */
.btn-bulk-action {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
}
.btn-bulk-action--success { background: #d4f0e2; color: #0d6e35; }
.btn-bulk-action--success:hover { background: #0d6e35; color: #fff; transform: scale(1.1); }
.btn-bulk-action--danger { background: #fdeaea; color: #dc3545; }
.btn-bulk-action--danger:hover { background: #dc3545; color: #fff; transform: scale(1.1); }

.bulk-divider {
  width: 1px;
  height: 24px;
  background: #dee2e6;
}

.btn-bulk-trigger--disabled {
  background: #e9ecef !important;
  color: #adb5bd !important;
  box-shadow: none !important;
  cursor: default !important;
  transform: none !important;
}
.bulk-trigger__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(255,255,255,0.25);
  font-size: 0.75rem;
  font-weight: 800;
}

/* ── Card de categoría ── */
.card-categoria {
  background: #fff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 0 0 1px rgba(0,0,0,0.04);
  transition: box-shadow 0.2s, transform 0.2s;
}
.card-categoria:hover {
  box-shadow: 0 4px 16px rgba(0,0,0,0.09), 0 0 0 1px rgba(0,85,140,0.08);
}
.card-categoria--open {
  box-shadow: 0 6px 24px rgba(0,85,140,0.12), 0 0 0 1.5px rgba(0,85,140,0.15);
}

.card-categoria__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  cursor: pointer;
  background: linear-gradient(to right, #f5f9fc 0%, #ffffff 60%);
  border-left: 5px solid var(--color-primary, #00558c);
  transition: background 0.15s;
  user-select: none;
  position: relative;
}
.card-categoria__header::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(to right, rgba(0,85,140,0.12), transparent);
}
.card-categoria--open .card-categoria__header {
  background: linear-gradient(to right, #eef4fb 0%, #f8fbff 60%);
  border-left-color: var(--color-primary, #00558c);
  border-left-width: 6px;
}

.cat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #ddeaf4 0%, #c5dcec 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary, #00558c);
  font-size: 1.05rem;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0,85,140,0.15);
}

.cat-count-badge {
  display: inline-block;
  background: linear-gradient(135deg, #e8f0f7, #d5e6f0);
  color: var(--color-primary, #00558c);
  font-size: 0.7rem;
  font-weight: 700;
  padding: 1px 8px;
  border-radius: 20px;
  letter-spacing: 0.02em;
}

.cat-stats {
  gap: 0.4rem;
  font-size: 0.78rem;
  color: #6c757d;
  align-items: center;
}
.cat-stat { display: flex; align-items: center; }
.cat-stat-divider { color: #ced4da; font-weight: 300; }

.cat-select-all {
  padding: 4px 8px;
  border-radius: 8px;
  transition: background 0.15s;
}
.cat-select-all:hover { background: rgba(0,85,140,0.06); }

.cat-chevron-wrap {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0f4f8;
  color: #8898aa;
  transition: all 0.25s ease;
}
.cat-chevron-wrap--open {
  background: var(--color-primary, #00558c);
  color: #fff;
  transform: rotate(-180deg);
}

/* ── Tabla ── */
.card-categoria__body { border-top: none; overflow: hidden; }

.table-articulos {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.table-articulos thead th {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: #8898aa;
  padding: 0.55rem 0.75rem;
  background: #f8fafc;
  border-bottom: 1px solid #eef1f5;
  border-top: 2px solid #eef4fb;
}

.table-articulos tbody td {
  padding: 0.7rem 0.75rem;
  font-size: 0.875rem;
  border-bottom: 1px solid #f5f7f9;
  vertical-align: middle;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.row-articulo { transition: background 0.1s; }
.row-articulo:last-child td { border-bottom: none; }
.row-articulo:hover { background: #f5f9ff; }
.row-articulo--selected { background: #eef4fb !important; }
.row-articulo--selected td:first-child { border-left: 3px solid var(--color-primary, #00558c); }

/* ── Precios ── */
.precio-val { font-weight: 600; color: #2d3a45; }
.precio-val--costo { color: #6c757d; font-weight: 500; }

/* ── Pills de estado ── */
.status-pill {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.2rem 0.65rem;
  border-radius: 50px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  display: inline-block;
}
.status-pill--active   { background: #d4f0e2; color: #0d6e35; }
.status-pill--inactive { background: #e9ecef; color: #6c757d; }

/* ── Botones acción ── */
.btn-action {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 7px;
  border: none;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-action--edit   { background: #eef4f8; color: var(--color-primary, #00558c); }
.btn-action--edit:hover { background: var(--color-primary, #00558c); color: #fff; transform: scale(1.05); }
.btn-action--delete { background: #fdeaea; color: #dc3545; }
.btn-action--delete:hover { background: #dc3545; color: #fff; transform: scale(1.05); }

/* ── Modal bulk ── */
.modal-panel--bulk { max-width: 680px; }

.modal-panel__header--bulk {
  background: linear-gradient(135deg, #f0fafa 0%, #e8f7f7 100%);
  border-bottom: 1px solid #d5eff0;
}

.modal-icon-wrap--bulk {
  background: linear-gradient(135deg, #c5ecec, #a8e0e1);
  color: #0e7c7f;
}

.bulk-section__label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.btn-group-custom { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.btn-choice {
  padding: 0.4rem 0.9rem;
  border-radius: 8px;
  border: 1.5px solid #dee2e6;
  background: #fff;
  font-size: 0.83rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.15s;
  display: flex;
  align-items: center;
}
.btn-choice:hover { border-color: var(--color-primary, #00558c); color: var(--color-primary, #00558c); background: #f0f6fb; }
.btn-choice--active {
  background: var(--color-primary, #00558c);
  color: #fff;
  border-color: var(--color-primary, #00558c);
  box-shadow: 0 2px 8px rgba(0,85,140,0.25);
}
.btn-choice--active-sec {
  background: #169c9f;
  color: #fff;
  border-color: #169c9f;
  box-shadow: 0 2px 8px rgba(22,156,159,0.25);
}

/* ── Previsualización ── */
.bulk-preview {
  border-radius: 12px;
  background: #f8fafb;
  border: 1px solid #e8eef3;
  overflow: hidden;
}
.bulk-preview__header {
  padding: 0.6rem 1rem;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  background: #f0f4f8;
  border-bottom: 1px solid #e8eef3;
}
.bulk-preview__chips {
  padding: 0.75rem 1rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  max-height: 180px;
  overflow-y: auto;
}

.preview-chip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  background: #fff;
  border: 1px solid #e0e8f0;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.85rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.preview-chip__name  { font-weight: 600; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 1rem; }
.preview-chip__old   { color: #adb5bd; text-decoration: none; font-size: 0.8rem; }
.preview-chip__new   { color: #0d6e35; font-weight: 700; width: 85px; text-align: right; }
.preview-chip__arrow { color: #ced4da; font-size: 0.75rem; }

/* ── Modal base ── */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1055;
  padding: 1rem;
}

/* Permitir scroll cuando el contenido es muy alto */
.modal-backdrop-custom--scrollable {
  display: block;
  overflow-y: auto;
  padding: 2rem 1rem;
}

.modal-backdrop-custom--scrollable .modal-panel {
  margin: 0 auto;
}

.modal-panel {
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.modal-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem;
  border-bottom: 1px solid #f0f0f0;
}

.modal-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #eef4f8, #d9e8f0);
  color: var(--color-primary, #00558c);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.modal-panel__body   { padding: 1.5rem; }
.modal-panel__footer {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #f0f0f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

/* ── Transiciones ── */
.accordion-enter-active,
.accordion-leave-active { transition: all 0.25s ease; overflow: hidden; }
.accordion-enter-from,
.accordion-leave-to { opacity: 0; max-height: 0; }
.accordion-enter-to,
.accordion-leave-from { opacity: 1; max-height: 2000px; }

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96) translateY(10px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modal-in { animation: modalIn 0.2s ease forwards; }

.text-primary-custom { color: var(--color-primary, #00558c); }
</style>