<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">EQUIPOS</h1>
      </div>
      <div class="d-flex gap-2">
        <button @click="openBulkModal()" class="btn btn-outline-primary d-flex align-items-center">
          <i class="bi bi-people-fill fs-6 me-2"></i> Equipo + Jugadores
        </button>
        <button @click="openModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nuevo
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
            <tr v-for="item in sortedEquipos" :key="item.id" class="row-clickable" @click="openDetalleEquipo(item)">
              <td class="ps-4 text-center">
                <img v-if="item.escudo" :src="resolveEscudoUrl(item.escudo)" alt="escudo" class="escudo-thumb" />
                <span v-else class="text-muted">-</span>
              </td>
              <td class="fw-medium text-dark">{{ item.nombre }}</td>
              <td class="text-muted">
                <span class="badge bg-primary-subtle text-primary-custom rounded-pill px-3">{{ item.disciplina }}</span>
              </td>
              <td class="text-center">
                <span :class="item.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill px-3">
                  {{ item.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="pe-4 text-end">
                <button @click.stop="openModal(item)" class="btn btn-link link-secondary p-1 me-2" title="Editar">
                  <i class="bi bi-pencil-square fs-4"></i>
                </button>
                <button @click.stop="prepareDelete(item.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                  <i class="bi bi-trash3 fs-4"></i>
                </button>
              </td>
            </tr>
            <tr v-if="sortedEquipos.length === 0 && !loading">
              <td colspan="5" class="text-center py-5 text-muted">
                No hay equipos registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Formulario -->
    <Teleport to="body">
    <div v-if="showFormModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
      <div class="modal-dialog modal-dialog-centered" :class="isEditing ? 'modal-xl' : 'modal-lg'">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi me-2" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
              {{ isEditing ? 'Editar Equipo' : 'Nuevo Equipo' }}
            </h5>
            <button type="button" class="btn-close" @click="showFormModal = false"></button>
          </div>
          <form @submit.prevent="save">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">Nombre del Equipo</label>
                <input v-model.trim="form.nombre" type="text" class="form-control" placeholder="Ej: Sub-15 Fútbol" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Disciplina</label>
                <input v-model.trim="form.disciplina" type="text" class="form-control" placeholder="Ej: Fútbol" required />
              </div>
              <div class="mb-3">
                <label class="form-label">Escudo (imagen/logo)</label>
                <input
                  type="file"
                  class="form-control"
                  accept="image/*,.svg"
                  @change="onEscudoFileChange"
                />
                <div v-if="form.escudo" class="form-text d-flex align-items-center gap-2 mt-2">
                  <img :src="resolveEscudoUrl(form.escudo)" alt="escudo" class="escudo-thumb" />
                  <a :href="resolveEscudoUrl(form.escudo)" target="_blank" rel="noopener noreferrer">Ver escudo actual</a>
                </div>
              </div>
              <div class="mb-0">
                <div class="form-text">El equipo se registra en estado activo por defecto.</div>
              </div>

              <template v-if="isEditing">
                <hr class="my-4" />
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0 fw-bold text-secondary">Jugadores del Equipo</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="addEditJugadorRow">
                    <i class="bi bi-plus-lg me-1"></i> Agregar jugador
                  </button>
                </div>

                <div v-if="loadingRoster" class="py-3 text-center text-muted">
                  <div class="spinner-border spinner-border-sm text-primary-custom me-2"></div>
                  Cargando jugadores...
                </div>
                <div v-else class="table-responsive border rounded">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3">Nombre</th>
                        <th>Apellido</th>
                        <th class="text-center" style="width: 120px">Capitán</th>
                        <th class="text-center" style="width: 120px">Arquero</th>
                        <th class="text-end pe-3" style="width: 100px">Quitar</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(jugador, idx) in editRoster" :key="jugador.localId">
                        <td class="ps-3">
                          <input v-model.trim="jugador.nombre" type="text" class="form-control form-control-sm" placeholder="Nombre" required />
                        </td>
                        <td>
                          <input v-model.trim="jugador.apellido" type="text" class="form-control form-control-sm" placeholder="Apellido" required />
                        </td>
                        <td class="text-center">
                          <input
                            :checked="jugador.capitan"
                            class="form-check-input"
                            type="radio"
                            name="edit-capitan"
                            :id="`edit-capitan-${idx}`"
                            @change="setEditCapitan(idx)"
                          />
                        </td>
                        <td class="text-center">
                          <input
                            v-model="jugador.arquero"
                            class="form-check-input"
                            type="checkbox"
                            :id="`edit-arquero-${idx}`"
                          />
                        </td>
                        <td class="text-end pe-3">
                          <button type="button" class="btn btn-sm btn-link link-danger" @click="removeEditJugadorRow(idx)">
                            <i class="bi bi-trash3"></i>
                          </button>
                        </td>
                      </tr>
                      <tr v-if="editRoster.length === 0">
                        <td colspan="5" class="text-center py-3 text-muted">Este equipo no tiene jugadores cargados.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="form-text mt-2">Podés agregar jugadores nuevos, quitar jugadores actuales, cambiar el capitán y marcar arqueros.</div>
              </template>
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

    <Teleport to="body">
      <div v-if="showBulkModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-people-fill me-2"></i>
                Nuevo Equipo con Jugadores
              </h5>
              <button type="button" class="btn-close" @click="showBulkModal = false"></button>
            </div>
            <form @submit.prevent="saveBulkTeamWithPlayers">
              <div class="modal-body">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nombre del Equipo</label>
                    <input v-model.trim="bulkForm.nombre" type="text" class="form-control" placeholder="Ej: Primera Futsal" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Disciplina</label>
                    <input v-model.trim="bulkForm.disciplina" type="text" class="form-control" placeholder="Ej: Fútbol" required />
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Escudo (opcional)</label>
                    <input type="file" class="form-control" accept="image/*,.svg" @change="onBulkEscudoFileChange" />
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <div class="form-text pb-2">Se crea activo por defecto.</div>
                  </div>
                </div>

                <hr class="my-4" />

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="mb-0 fw-bold text-secondary">Integrantes del Equipo</h6>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="addBulkJugadorRow">
                    <i class="bi bi-plus-lg me-1"></i> Agregar jugador
                  </button>
                </div>

                <div class="table-responsive border rounded">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th class="ps-3" style="width: 65%">Nombre completo del jugador</th>
                        <th class="text-center" style="width: 12%">Capitán</th>
                        <th class="text-center" style="width: 12%">Arquero</th>
                        <th class="text-end pe-3" style="width: 11%">Acción</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(jugador, idx) in bulkForm.jugadores" :key="`bulk-jugador-${idx}`">
                        <td class="ps-3">
                          <input
                            v-model.trim="jugador.nombre_completo"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="Ej: Juan Pérez"
                            required
                          />
                        </td>
                        <td class="text-center">
                          <input
                            :checked="jugador.capitan"
                            class="form-check-input"
                            type="radio"
                            name="bulk-capitan"
                            :id="`capitan-${idx}`"
                            @change="setBulkCapitan(idx)"
                          />
                        </td>
                        <td class="text-center">
                          <input
                            v-model="jugador.arquero"
                            class="form-check-input"
                            type="checkbox"
                            :id="`arquero-${idx}`"
                          />
                        </td>
                        <td class="text-end pe-3">
                          <button
                            type="button"
                            class="btn btn-sm btn-link link-danger"
                            :disabled="bulkForm.jugadores.length === 1"
                            @click="removeBulkJugadorRow(idx)"
                          >
                            <i class="bi bi-trash3"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="form-text mt-2">Marcá un capitán con el selector circular y los arqueros con checkbox.</div>
              </div>
              <div class="modal-footer">
                <button @click="showBulkModal = false" type="button" class="btn btn-light px-4">Cancelar</button>
                <button type="submit" class="btn btn-primary-modern px-4" :disabled="isBulkSaving">
                  <span v-if="isBulkSaving" class="spinner-border spinner-border-sm me-2"></span>
                  Guardar equipo e integrantes
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showDetailModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="bi bi-info-circle-fill me-2"></i>
                Detalle del Equipo
              </h5>
              <button type="button" class="btn-close" @click="showDetailModal = false"></button>
            </div>
            <div class="modal-body" v-if="selectedEquipo">
              <div class="d-flex align-items-center gap-3 mb-3">
                <img v-if="selectedEquipo.escudo" :src="resolveEscudoUrl(selectedEquipo.escudo)" alt="escudo" class="escudo-thumb escudo-thumb-lg" />
                <div>
                  <h6 class="mb-1 fw-bold">{{ selectedEquipo.nombre }}</h6>
                  <div class="text-muted small">{{ selectedEquipo.disciplina }}</div>
                  <span :class="selectedEquipo.activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'" class="badge rounded-pill mt-2">
                    {{ selectedEquipo.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </div>

              <h6 class="fw-bold text-secondary">Integrantes</h6>
              <div v-if="loadingDetail" class="py-4 text-center text-muted">
                <div class="spinner-border spinner-border-sm text-primary-custom me-2"></div>
                Cargando integrantes...
              </div>
              <div v-else class="table-responsive border rounded">
                <table class="table table-sm mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3">Nombre</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="integrante in integrantesEquipo" :key="integrante.id_cliente_equipo">
                      <td class="ps-3">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                          <span>{{ integrante.cliente_nombre }}</span>
                          <div class="d-inline-flex gap-2 align-items-center">
                          <span v-if="integrante.capitan" class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 role-badge">
                            <img src="/simbolos/capitan.png" alt="capitán" class="role-badge-icon" />
                            <span>Capitán</span>
                          </span>
                          <span v-if="integrante.arquero" class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 role-badge">
                            <img src="/simbolos/guantes.png" alt="guantes" class="role-badge-icon" />
                            <span>Arquero</span>
                          </span>
                          <span v-if="!integrante.capitan && !integrante.arquero" class="badge bg-light text-secondary rounded-pill px-3">Jugador</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="integrantesEquipo.length === 0">
                      <td colspan="1" class="text-center py-4 text-muted">Este equipo no tiene integrantes asociados.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="showDetailModal = false" type="button" class="btn btn-secondary px-4">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      v-model="showDeleteModal"
      title="Eliminar Equipo"
      message="Esta acción dará de baja el equipo y dejará a sus jugadores sin equipo asignado."
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
import datosMaestrosService from '@/services/datosMaestrosService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();

const { sortKey, sortDir, handleSort, sortItems } = useSorting()

const columns = [
  { key: 'escudo',     label: '',           sortable: false, thClass: 'ps-4 py-3', thStyle: 'width: 70px' },
  { key: 'nombre',     label: 'Nombre',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'disciplina', label: 'Disciplina', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'activo',     label: 'Estado',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary text-center', thStyle: 'width: 120px' },
  { key: 'acciones',   label: 'Acciones',   sortable: false, thClass: 'pe-4 py-3 text-uppercase fs-xs fw-bold text-secondary text-end' },
]

const equipos = ref([]);

const equiposActivos = computed(() => equipos.value.filter(item => Number(item.activo) !== 0))
const sortedEquipos = computed(() => sortItems(equiposActivos.value))
const loading = ref(false);
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const showBulkModal = ref(false);
const showDetailModal = ref(false);
const isEditing = ref(false);
const isSaving = ref(false);
const isDeleting = ref(false);
const isBulkSaving = ref(false);
const loadingDetail = ref(false);
const loadingRoster = ref(false);
const idToDelete = ref(null);
const selectedEquipo = ref(null);
const integrantesEquipo = ref([]);
const editRoster = ref([]);
const originalRoster = ref([]);
let rosterLocalId = 0;

const form = ref({ id: null, nombre: '', disciplina: '', activo: true, escudo: null, escudoFile: null });
const originalForm = ref({});

const emptyBulkForm = () => ({
  nombre: '',
  disciplina: '',
  escudo: null,
  escudoFile: null,
  jugadores: [{ nombre_completo: '', capitan: false, arquero: false }],
});

const bulkForm = ref(emptyBulkForm());

const toBoolFlag = (value) => {
  if (typeof value === 'boolean') return value;
  if (typeof value === 'number') return value === 1;
  if (typeof value === 'string') {
    const normalized = value.trim().toLowerCase();
    return normalized === '1' || normalized === 'true' || normalized === 't' || normalized === 'si' || normalized === 's';
  }
  return false;
};

const fetchData = async () => {
  loading.value = true;
  try {
    equipos.value = await datosMaestrosService.getEquipos();
  } catch {
    toast.showToast({ message: 'Error al cargar los equipos.', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true;
    form.value = { ...item, activo: Boolean(Number(item.activo)), escudoFile: null };
    originalForm.value = { ...form.value };
    loadEquipoRoster(item.id);
  } else {
    isEditing.value = false;
    form.value = { id: null, nombre: '', disciplina: '', activo: true, escudo: null, escudoFile: null };
    editRoster.value = [];
    originalRoster.value = [];
  }
  showFormModal.value = true;
};

const createRosterRow = (jugador = {}) => ({
  localId: `roster-${rosterLocalId++}`,
  id: jugador.id ?? null,
  nombre: jugador.nombre ?? '',
  apellido: jugador.apellido ?? '',
  dni: jugador.dni ?? null,
  fecha_nac: jugador.fecha_nac ?? null,
  fecha_alta: jugador.fecha_alta ?? null,
  activo: jugador.activo !== undefined ? toBoolFlag(jugador.activo) : true,
  capitan: toBoolFlag(jugador.capitan),
  arquero: toBoolFlag(jugador.arquero),
});

const loadEquipoRoster = async (idEquipo) => {
  loadingRoster.value = true;
  try {
    const jugadores = await datosMaestrosService.getJugadoresByEquipo(idEquipo);
    originalRoster.value = jugadores.map(jugador => ({
      id: jugador.id,
      nombre: jugador.nombre ?? '',
      apellido: jugador.apellido ?? '',
      dni: jugador.dni ?? null,
      fecha_nac: jugador.fecha_nac ?? null,
      fecha_alta: jugador.fecha_alta ?? null,
      activo: toBoolFlag(jugador.activo),
      capitan: toBoolFlag(jugador.capitan),
      arquero: toBoolFlag(jugador.arquero),
    }));
    editRoster.value = originalRoster.value.map(jugador => createRosterRow(jugador));
  } catch {
    editRoster.value = [];
    originalRoster.value = [];
    toast.showToast({ message: 'No se pudieron cargar los jugadores del equipo.', type: 'danger' });
  } finally {
    loadingRoster.value = false;
  }
};

const addEditJugadorRow = () => {
  editRoster.value.push(createRosterRow());
};

const removeEditJugadorRow = (index) => {
  const removedWasCaptain = Boolean(editRoster.value[index]?.capitan);
  editRoster.value.splice(index, 1);
  if (removedWasCaptain && editRoster.value.length > 0) {
    editRoster.value[0].capitan = true;
  }
};

const setEditCapitan = (index) => {
  editRoster.value = editRoster.value.map((jugador, idx) => ({
    ...jugador,
    capitan: idx === index,
  }));
};

const hasTeamChanges = () => JSON.stringify({ ...form.value, escudoFile: null }) !== JSON.stringify({ ...originalForm.value, escudoFile: null });

const hasRosterChanges = () => {
  if (!isEditing.value) {
    return false;
  }

  const current = editRoster.value.map(jugador => ({
    id: jugador.id ?? null,
    nombre: jugador.nombre?.trim() || '',
    apellido: jugador.apellido?.trim() || '',
    dni: jugador.dni || null,
    fecha_nac: jugador.fecha_nac || null,
    fecha_alta: jugador.fecha_alta || null,
    activo: Boolean(jugador.activo),
    capitan: Boolean(jugador.capitan),
    arquero: Boolean(jugador.arquero),
  }));

  const original = originalRoster.value.map(jugador => ({
    id: jugador.id ?? null,
    nombre: jugador.nombre?.trim() || '',
    apellido: jugador.apellido?.trim() || '',
    dni: jugador.dni || null,
    fecha_nac: jugador.fecha_nac || null,
    fecha_alta: jugador.fecha_alta || null,
    activo: Boolean(jugador.activo),
    capitan: Boolean(jugador.capitan),
    arquero: Boolean(jugador.arquero),
  }));

  return JSON.stringify(current) !== JSON.stringify(original);
};

const syncRosterChanges = async (idEquipo) => {
  const roster = editRoster.value.map(jugador => ({
    ...jugador,
    nombre: jugador.nombre?.trim() || '',
    apellido: jugador.apellido?.trim() || '',
  }));

  if (roster.some(jugador => !jugador.nombre || !jugador.apellido)) {
    throw new Error('Todos los jugadores del plantel deben tener nombre y apellido.');
  }

  const originalesPorId = new Map(originalRoster.value.map(jugador => [Number(jugador.id), jugador]));
  const idsActuales = new Set(roster.filter(jugador => jugador.id).map(jugador => Number(jugador.id)));

  for (const jugador of roster) {
    if (jugador.id) {
      const original = originalesPorId.get(Number(jugador.id));
      await datosMaestrosService.actualizarJugador({
        id: jugador.id,
        nombre: jugador.nombre,
        apellido: jugador.apellido,
        dni: original?.dni || null,
        fecha_nac: original?.fecha_nac || null,
        fecha_alta: original?.fecha_alta || null,
        activo: original?.activo ?? true,
        id_equipo_actual: idEquipo,
        capitan: Boolean(jugador.capitan),
        arquero: Boolean(jugador.arquero),
      });
    } else {
      await datosMaestrosService.crearJugador({
        nombre: jugador.nombre,
        apellido: jugador.apellido,
        dni: null,
        fecha_nac: null,
        fecha_alta: null,
        activo: true,
        id_equipo_actual: idEquipo,
        capitan: Boolean(jugador.capitan),
        arquero: Boolean(jugador.arquero),
      });
    }
  }

  for (const jugadorOriginal of originalRoster.value) {
    if (!idsActuales.has(Number(jugadorOriginal.id))) {
      await datosMaestrosService.actualizarJugador({
        id: jugadorOriginal.id,
        nombre: jugadorOriginal.nombre,
        apellido: jugadorOriginal.apellido,
        dni: jugadorOriginal.dni || null,
        fecha_nac: jugadorOriginal.fecha_nac || null,
        fecha_alta: jugadorOriginal.fecha_alta || null,
        activo: jugadorOriginal.activo ?? true,
        id_equipo_actual: null,
        capitan: false,
        arquero: false,
      });
    }
  }
};

const openBulkModal = () => {
  bulkForm.value = emptyBulkForm();
  showBulkModal.value = true;
};

const onBulkEscudoFileChange = (event) => {
  const file = event?.target?.files?.[0] || null;
  bulkForm.value.escudoFile = file;
};

const addBulkJugadorRow = () => {
  bulkForm.value.jugadores.push({ nombre_completo: '', capitan: false, arquero: false });
};

const removeBulkJugadorRow = (index) => {
  if (bulkForm.value.jugadores.length === 1) {
    return;
  }
  const removedWasCaptain = Boolean(bulkForm.value.jugadores[index]?.capitan);
  bulkForm.value.jugadores.splice(index, 1);
  if (removedWasCaptain && bulkForm.value.jugadores.length > 0) {
    bulkForm.value.jugadores[0].capitan = true;
  }
};

const setBulkCapitan = (index) => {
  bulkForm.value.jugadores = bulkForm.value.jugadores.map((jugador, idx) => ({
    ...jugador,
    capitan: idx === index,
  }));
};

const onEscudoFileChange = (event) => {
  const file = event?.target?.files?.[0] || null;
  form.value.escudoFile = file;
};

const resolveEscudoUrl = (escudo) => {
  if (!escudo) return '';

  const value = String(escudo).trim();
  if (value === '') return '';
  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) return value;

  const apiBase = import.meta.env.VITE_API_URL;
  if (!apiBase) return value;

  try {
    const apiUrl = new URL(apiBase, window.location.origin);
    const apiPath = String(apiUrl.pathname || '').replace(/\/+$/, '');
    const projectBasePath = apiPath.endsWith('/api') ? apiPath.slice(0, -4) : '';

    if (value.startsWith('/')) {
      if (projectBasePath && !value.startsWith(projectBasePath + '/')) {
        return `${apiUrl.origin}${projectBasePath}${value}`;
      }
      return `${apiUrl.origin}${value}`;
    }

    if (projectBasePath && value.startsWith('uploads/')) {
      return `${apiUrl.origin}${projectBasePath}/${value}`;
    }

    return new URL(value, apiUrl.href).toString();
  } catch {
    return value;
  }
};

const save = async () => {
  if (!form.value.nombre || !form.value.disciplina) {
    toast.showToast({ message: 'Nombre y disciplina son obligatorios.', type: 'warning' });
    return;
  }
  const hayArchivoNuevo = Boolean(form.value.escudoFile);
  if (isEditing.value && !hayArchivoNuevo && !hasTeamChanges() && !hasRosterChanges()) {
    toast.showToast({ message: 'No se detectaron cambios.', type: 'info' });
    showFormModal.value = false;
    return;
  }

  isSaving.value = true;
  try {
    let escudoPath = form.value.escudo || null;
    if (form.value.escudoFile) {
      const formData = new FormData();
      formData.append('escudo', form.value.escudoFile);
      const uploadResp = await datosMaestrosService.subirEscudoEquipo(formData);
      escudoPath = uploadResp?.escudo || escudoPath;
    }

    const payload = {
      id: form.value.id,
      nombre: form.value.nombre,
      disciplina: form.value.disciplina,
      activo: isEditing.value ? form.value.activo : true,
      escudo: escudoPath,
    };

    if (isEditing.value) {
      await datosMaestrosService.actualizarEquipo(payload);
      await syncRosterChanges(form.value.id);
      toast.showToast({ message: 'Equipo actualizado correctamente.', type: 'success' });
    } else {
      await datosMaestrosService.crearEquipo(payload);
      toast.showToast({ message: 'Equipo creado correctamente.', type: 'success' });
    }
    showFormModal.value = false;
    fetchData();
  } catch (err) {
    const msg = err?.response?.data?.message || 'Error al guardar el equipo.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const parseNombreCompleto = (value) => {
  const partes = String(value || '').trim().split(/\s+/).filter(Boolean);
  if (partes.length < 2) {
    return null;
  }

  return {
    nombre: partes.slice(0, -1).join(' '),
    apellido: partes[partes.length - 1],
  };
};

const saveBulkTeamWithPlayers = async () => {
  if (!bulkForm.value.nombre?.trim() || !bulkForm.value.disciplina?.trim()) {
    toast.showToast({ message: 'Nombre y disciplina del equipo son obligatorios.', type: 'warning' });
    return;
  }

  const jugadoresValidos = bulkForm.value.jugadores
    .map(j => ({ ...j, nombre_completo: j.nombre_completo?.trim() || '' }))
    .filter(j => j.nombre_completo);

  if (jugadoresValidos.length === 0) {
    toast.showToast({ message: 'Debe cargar al menos un jugador para el equipo.', type: 'warning' });
    return;
  }

  const jugadoresParseados = jugadoresValidos.map(jugador => ({
    ...jugador,
    datos: parseNombreCompleto(jugador.nombre_completo),
  }));

  if (jugadoresParseados.some(jugador => !jugador.datos)) {
    toast.showToast({ message: 'Cada jugador debe tener nombre y apellido.', type: 'warning' });
    return;
  }

  isBulkSaving.value = true;
  try {
    let escudoPath = bulkForm.value.escudo || null;
    if (bulkForm.value.escudoFile) {
      const formData = new FormData();
      formData.append('escudo', bulkForm.value.escudoFile);
      const uploadResp = await datosMaestrosService.subirEscudoEquipo(formData);
      escudoPath = uploadResp?.escudo || escudoPath;
    }

    const teamResp = await datosMaestrosService.crearEquipo({
      nombre: bulkForm.value.nombre.trim(),
      disciplina: bulkForm.value.disciplina.trim(),
      activo: true,
      escudo: escudoPath,
    });

    const idEquipo = Number(teamResp?.id);
    if (!idEquipo) {
      throw new Error('No se pudo obtener el ID del equipo creado.');
    }

    for (const jugador of jugadoresParseados) {
      await datosMaestrosService.crearJugador({
        nombre: jugador.datos.nombre,
        apellido: jugador.datos.apellido,
        dni: null,
        fecha_nac: null,
        fecha_alta: null,
        activo: true,
        id_equipo_actual: idEquipo,
        capitan: Boolean(jugador.capitan),
        arquero: Boolean(jugador.arquero),
      });
    }

    toast.showToast({ message: 'Equipo y jugadores cargados correctamente.', type: 'success' });
    showBulkModal.value = false;
    await fetchData();
  } catch (err) {
    const msg = err?.response?.data?.message || err?.message || 'Error al guardar equipo y jugadores.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isBulkSaving.value = false;
  }
};

const openDetalleEquipo = async (equipo) => {
  selectedEquipo.value = {
    ...equipo,
    activo: Boolean(Number(equipo.activo)),
  };
  integrantesEquipo.value = [];
  showDetailModal.value = true;
  loadingDetail.value = true;

  try {
    const integrantes = await datosMaestrosService.getJugadoresByEquipo(equipo.id);
    integrantesEquipo.value = integrantes
      .map(item => ({
        ...item,
        cliente_nombre: [item.apellido, item.nombre].filter(Boolean).join(', ') || item.nombre || '-',
        id_cliente_equipo: item.id_jugador_equipo || item.id,
        capitan: Boolean(Number(item.capitan)),
        arquero: Boolean(Number(item.arquero)),
      }))
      .sort((a, b) => Number(b.capitan) - Number(a.capitan) || Number(b.arquero) - Number(a.arquero));
  } catch {
    toast.showToast({ message: 'No se pudo cargar el detalle del equipo.', type: 'danger' });
  } finally {
    loadingDetail.value = false;
  }
};

const prepareDelete = (id) => {
  idToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    const idEquipo = idToDelete.value;
    const response = await datosMaestrosService.bajaLogicaEquipo(idEquipo);
    const cantidad = Number(response?.jugadores_desasignados || 0);
    const mensaje = cantidad > 0
      ? `Equipo dado de baja. ${cantidad} jugador(es) quedaron sin equipo asignado.`
      : 'Equipo dado de baja. No había jugadores activos asignados.';
    toast.showToast({ message: mensaje, type: 'success' });
    showDeleteModal.value = false;
    fetchData();
  } catch (err) {
    const msg = err?.response?.data?.message || 'No se pudo dar de baja el equipo. Intentá nuevamente.';
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
  top: 0; left: 0; width: 100%; height: 100%;
  background-color: rgba(255, 255, 255, 0.85);
  z-index: 10;
}

.escudo-thumb {
  width: 28px;
  height: 28px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
}

.escudo-thumb-lg {
  width: 54px;
  height: 54px;
}

.row-clickable {
  cursor: pointer;
}

.row-clickable:hover td {
  background-color: #f8fafc;
}

.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.role-badge-icon {
  width: 16px;
  height: 16px;
  object-fit: contain;
}
</style>
