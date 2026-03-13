<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <!-- ── Encabezado ─────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary text-uppercase">Descontar Stock</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small d-none d-md-inline">Registrar salidas por ajuste (daños, vencimientos, etc.)</span>
        <button @click="openAjusteModal" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-dash-circle-fill fs-6 me-2"></i> Nuevo Ajuste
        </button>
      </div>
    </div>

    <!-- ── Filtros ───────────────────────────────────────────── -->
    <div class="row g-3 mb-4 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Buscador</label>
        <FuzzySearch v-model="searchQuery" placeholder="Buscar por ID, producto o motivo..." />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Desde</label>
        <input type="date" v-model="filterDateFrom" class="form-control border-2 shadow-sm" />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Hasta</label>
        <input type="date" v-model="filterDateTo" class="form-control border-2 shadow-sm" />
      </div>
      <div class="col-12 col-md-2 d-flex gap-1">
        <button @click="resetFilters" class="btn btn-outline-secondary w-100 shadow-sm" title="Limpiar filtros">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </div>

    <!-- ── Tabla de Ajustes ──────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light text-secondary text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
            <tr>
              <th class="ps-4 py-3">ID</th>
              <th class="py-3">Fecha</th>
              <th class="py-3">Producto(s) Ajustado(s)</th>
              <th class="py-3">Motivo / Notas</th>
              <th class="text-end pe-4 py-3">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="text-center py-5">
                <div class="spinner-border text-primary-custom" role="status" style="width:2.5rem;height:2.5rem"></div>
              </td>
            </tr>
            <tr v-else-if="ajustesFiltrados.length === 0">
              <td colspan="5" class="text-center py-5 text-muted">
                <div class="opacity-50">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  <span>No se encontraron ajustes de stock</span>
                </div>
              </td>
            </tr>
            <tr v-for="ajuste in ajustesFiltrados" :key="ajuste.id" class="animate-row-in border-bottom bg-white">
              <td class="ps-4 fw-bold text-primary">#{{ ajuste.id }}</td>
              <td class="text-dark fw-medium">{{ formatFecha(ajuste.fecha) }}</td>
              <td>
                <div class="d-flex flex-column gap-1">
                  <div v-for="art in parseArticulos(ajuste.articulos_list)" :key="art.nombre" class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger-subtle text-danger fw-bold" style="font-size: 0.7rem;">-{{ art.cantidad }}</span>
                    <span class="small fw-semibold text-dark text-truncate" style="max-width: 250px;">{{ art.nombre }}</span>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-muted small italic">{{ ajuste.descripcion_cliente || 'Sin motivo especificado' }}</span>
              </td>
              <td class="text-end pe-4">
                <button @click="confirmarEliminarAjuste(ajuste)" class="btn btn-link link-danger p-0" title="Eliminar ajuste (Restaurar stock)">
                  <i class="bi bi-trash3 fs-5"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal de Venta adaptado para Ajuste -->
    <VentaModal
      v-model="showModal"
      :is-editing="false"
      :is-loading="saving"
      :initial-form="form"
      :clientes="[]"
      :equipos="[]"
      :estados-venta="estadosVenta"
      :medios-cobro="mediosCobro"
      :articulos="articulos"
      :id-estado-cerrada="idEstadoCerrada"
      :is-ajuste="true"
      @save="handleSave"
    />

    <!-- Modal de Confirmación de Eliminación -->
    <ConfirmModal 
      v-model="showConfirmDelete"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :is-loading="deleting"
      @confirm="executeDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';
import { useToastStore } from '@/stores/toastStore';
import FuzzySearch from '@/components/FuzzySearch.vue';
import VentaModal from '@/components/venta/VentaModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';
const toastStore = useToastStore();

const ajustes = ref([]);
const articulos = ref([]);
const estadosVenta = ref([]);
const mediosCobro = ref([]);
const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const showModal = ref(false);
const showConfirmDelete = ref(false);
const idEstadoCerrada = ref(null);

const confirmConfig = ref({
  title: '',
  message: '',
  id: null
});

const searchQuery = ref('');
const filterDateFrom = ref('');
const filterDateTo = ref('');

const form = ref({
  fecha: new Date().toISOString().split('T')[0],
  id_estado_venta: null,
  id_medio_cobro: null, // Necesario para la validación aunque sea ajuste
  descripcion_cliente: '',
  articulos: [],
  es_ajuste: 1,
  tipo_vta: 1,
  simbolo: '' // Vacío para ajustes
});

const fetchData = async () => {
  loading.value = true;
  try {
    const [asRes, artRes, evRes, mcRes] = await Promise.all([
      api.get('/ventas'),
      api.get('/articulos?activos=1'),
      api.get('/estados-venta'),
      api.get('/medios-cobro')
    ]);
    
    // Filtrar solo las que son ajustes
    ajustes.value = asRes.data.filter(v => v.es_ajuste == 1);
    articulos.value = artRes.data;
    estadosVenta.value = evRes.data;
    mediosCobro.value = mcRes.data;
    
    // Buscar el estado "Cerrada" o similar para usarlo por defecto
    const cerrada = evRes.data.find(e => e.descripcion.toLowerCase().includes('cerrada')) || evRes.data[0];
    idEstadoCerrada.value = cerrada?.id;
    form.value.id_estado_venta = idEstadoCerrada.value;

    // Buscar un medio de cobro por defecto (el primero) para que pase la validación del modal
    if (mcRes.data.length > 0) {
      form.value.id_medio_cobro = mcRes.data[0].id;
    }

  } catch (error) {
    toastStore.showToast({ message: 'Error al cargar datos', type: 'danger' });
  } finally {
    loading.value = false;
  }
};

const ajustesFiltrados = computed(() => {
  return ajustes.value.filter(a => {
    const matchesSearch = !searchQuery.value || 
      a.id.toString().includes(searchQuery.value) || 
      (a.descripcion_cliente && a.descripcion_cliente.toLowerCase().includes(searchQuery.value.toLowerCase()));
    
    const matchesDateFrom = !filterDateFrom.value || a.fecha >= filterDateFrom.value;
    const matchesDateTo = !filterDateTo.value || a.fecha <= filterDateTo.value;
    
    return matchesSearch && matchesDateFrom && matchesDateTo;
  });
});

const openAjusteModal = () => {
  form.value = {
    fecha: new Date().toISOString().split('T')[0],
    id_estado_venta: idEstadoCerrada.value,
    id_medio_cobro: mediosCobro.value.length > 0 ? mediosCobro.value[0].id : null,
    descripcion_cliente: '',
    articulos: [],
    es_ajuste: 1,
    tipo_vta: 1,
    simbolo: ''
  };
  showModal.value = true;
};

const handleSave = async ({ venta, articulos: items }) => {
  saving.value = true;
  try {
    // Preparar datos para ajuste: monto 0, cobro 0, es_ajuste 1, tipo_vta 1, sin simbolo
    const payload = {
      ...venta,
      es_ajuste: 1,
      tipo_vta: 1, // Asegurar que sea Común
      simbolo: '', // Sin símbolo
      articulos: items.map(i => ({
        ...i,
        precio_unitario: 0,
        total: 0
      })),
      monto_cobrado: 0,
      id_cliente: null,
      id_equipo: null
    };

    await api.post('/ventas', payload);
    toastStore.showToast({ message: 'Ajuste de stock registrado exitosamente', type: 'success' });
    showModal.value = false;
    fetchData();
  } catch (error) {
    toastStore.showToast({ message: error.response?.data?.message || 'Error al guardar ajuste', type: 'danger' });
  } finally {
    saving.value = false;
  }
};

const formatFecha = (fecha) => {
  if (!fecha) return '-';
  const [y, m, d] = fecha.split('-');
  return `${d}/${m}/${y}`;
};

const parseArticulos = (list) => {
  if (!list) return [];
  return list.split('||').map(item => {
    const [cantidad, nombre] = item.split('x ');
    return { cantidad, nombre };
  });
};

const confirmarEliminarAjuste = (ajuste) => {
  confirmConfig.value = {
    id: ajuste.id,
    title: `Eliminar Ajuste #${ajuste.id}`,
    message: `¿Estás seguro de que deseas eliminar este ajuste de stock?\n\nAl hacerlo, se revertirá la salida de mercadería y el stock volverá a sumarse automáticamente en el inventario.`
  };
  showConfirmDelete.value = true;
};

const executeDelete = async () => {
  deleting.value = true;
  try {
    await api.delete('/ventas', { data: { id: confirmConfig.value.id } });
    toastStore.showToast({ message: 'Ajuste eliminado y stock restaurado', type: 'success' });
    showConfirmDelete.value = false;
    fetchData();
  } catch (error) {
    toastStore.showToast({ 
      message: error.response?.data?.message || 'Error al eliminar el ajuste', 
      type: 'danger' 
    });
  } finally {
    deleting.value = false;
  }
};

const resetFilters = () => {
  searchQuery.value = '';
  filterDateFrom.value = '';
  filterDateTo.value = '';
};

onMounted(fetchData);
</script>

<style scoped>
.btn-primary-modern {
  background: #212529;
  color: white;
  border: none;
  padding: 0.6rem 1.2rem;
  border-radius: 10px;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-primary-modern:hover {
  background: #000;
  transform: translateY(-1px);
}
.text-primary-custom { color: #0d6efd; }
.btn-back-arrow {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #6c757d;
  padding: 0;
  cursor: pointer;
}
</style>
