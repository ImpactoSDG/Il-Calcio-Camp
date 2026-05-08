<template>
  <div class="modal fade" :class="{ show: modelValue }" :style="{ display: modelValue ? 'block' : 'none' }" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg ripple-container">
        <!-- Header -->
        <div class="modal-header bg-light border-bottom-0 pb-0">
          <div class="d-flex align-items-center gap-3 w-100">
            <div class="icon-box bg-primary-subtle text-primary rounded-3 p-2">
              <i class="bi bi-receipt fs-4"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                Detalle de Venta #{{ venta?.id }}-{{ venta?.simbolo }}
              </h5>
              <div class="d-flex align-items-center gap-2">
                <p class="text-muted small mb-0">{{ formatFecha(venta?.fecha) }}</p>
                <span v-if="venta?.fecha_edicion" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;" :title="'Editado: ' + formatFechaHora(venta.fecha_edicion)">
                  EDITADO
                </span>
              </div>
            </div>
            <button type="button" class="btn-close ms-auto" @click="close"></button>
          </div>
        </div>

        <div class="modal-body p-4 position-relative">
          <!-- Spinner de carga -->
          <div v-if="props.isLoading" class="position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.85); border-radius: 16px; z-index: 10;">
            <div class="spinner-border text-primary-custom" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>

          <!-- Información General -->
          <div class="row g-4 mb-4">
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 h-100 border border-light-subtle">
                <label class="text-uppercase text-muted fw-bold small mb-2 d-block">Cliente</label>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-circle bg-white border text-primary">
                    <i class="bi bi-person-fill"></i>
                  </div>
                  <span class="fw-semibold text-dark">{{ venta?.cliente_nombre || 'Consumidor Final' }}</span>
                </div>
                <div v-if="venta?.equipo_nombre" class="mt-2">
                  <span class="badge bg-primary-subtle text-primary-custom rounded-pill">
                    <i class="bi bi-people-fill me-1"></i> {{ venta.equipo_nombre }}
                  </span>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="p-3 bg-light rounded-3 h-100 border border-light-subtle">
                <label class="text-uppercase text-muted fw-bold small mb-2 d-block">Información de Pago</label>
                <div class="mb-2">
                  <span class="text-muted small">Estado:</span>
                  <span class="badge bg-success-subtle text-success ms-2 border border-success-subtle">CERRADA</span>
                </div>
                <div>
                  <span class="text-muted small">Medio de Pago:</span>
                  <span v-if="venta?.medio_cobro_nombre" class="badge bg-info-subtle text-info ms-2 border border-info-subtle">
                    {{ venta.medio_cobro_nombre }}
                  </span>
                  <span v-else class="text-muted ms-2">—</span>
                </div>
                <!-- Estado Facturación AFIP -->
                <div v-if="venta?.estado_factura" class="mt-2 pt-2 border-top border-light-subtle">
                  <span class="text-muted small">Facturación ARCA:</span>
                  <div v-if="venta.estado_factura === 'facturada'" class="d-inline-flex align-items-center gap-1 ms-2 py-1 px-2 rounded-2 bg-success-subtle text-success border border-success-subtle shadow-xs animate-fade-in" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill"></i>
                    <span class="fw-bold">Facturada correctamente</span>
                  </div>
                  <div v-else-if="venta.estado_factura === 'error'" class="d-inline-flex flex-column gap-1 ms-2 w-100 mt-1">
                    <div class="d-inline-flex align-items-center gap-1 py-1 px-2 rounded-2 bg-danger-subtle text-danger border border-danger-subtle shadow-xs animate-shake" style="font-size: 0.75rem; align-self: flex-start;">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      <span class="fw-bold">Error en facturación</span>
                    </div>
                    <p class="mb-0 text-danger opacity-75 fw-medium" style="font-size: 0.7rem; line-height: 1.2;">
                      Hubo un error al facturar. Verifique en ARCA si la factura fue emitida correctamente.
                    </p>
                  </div>
                  <div v-else-if="venta.estado_factura === 'rechazada'" class="d-inline-flex flex-column gap-1 ms-2 w-100 mt-1">
                    <div class="d-inline-flex align-items-center gap-1 py-1 px-2 rounded-2 bg-secondary-subtle text-secondary border border-secondary-subtle shadow-xs" style="font-size: 0.75rem; align-self: flex-start;">
                      <i class="bi bi-x-circle-fill"></i>
                      <span class="fw-bold">Factura Rechazada</span>
                    </div>
                    <p class="mb-0 text-muted fw-medium" style="font-size: 0.7rem; line-height: 1.2;">
                      La solicitud fue rechazada por ARCA. No se generó comprobante válido.
                    </p>
                  </div>
                  <!-- Botón Ver Factura -->
                  <div v-if="venta?.estado_factura === 'facturada'" class="mt-2 d-flex gap-2">
                    <button @click="verFacturaPdf" class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 px-2 py-1 shadow-sm" style="font-size: 0.7rem;">
                      <i class="bi bi-file-earmark-pdf"></i>
                      Ver Factura/Ticket
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabla de Artículos -->
          <div class="card border-0 shadow-sm overflow-hidden">
            <div class="bg-primary-custom text-white p-2">
              <h6 class="mb-0 small fw-bold"><i class="bi bi-box-seam me-2"></i>ARTÍCULOS</h6>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 border-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-3 border-0 py-2 text-uppercase fs-xs fw-bold text-secondary" style="width: 60px"></th>
                    <th class="border-0 py-2 text-uppercase fs-xs fw-bold text-secondary">Artículo</th>
                    <th class="border-0 py-2 text-uppercase fs-xs fw-bold text-secondary text-end">Cant.</th>
                    <th class="border-0 py-2 text-uppercase fs-xs fw-bold text-secondary text-end">P.Unit.</th>
                    <th class="pe-3 border-0 py-2 text-uppercase fs-xs fw-bold text-secondary text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in articulos" :key="item.id_articulo_venta">
                    <td class="ps-3 py-2 border-0">
                      <div class="articulo-img-thumb shadow-sm border overflow-hidden rounded-2 d-flex align-items-center justify-content-center bg-white" style="width: 40px; height: 40px;">
                        <img v-if="item.url_imagen" :src="`${apiBaseUrl}/${item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                        <i v-else class="bi bi-box-seam text-muted opacity-50" style="font-size: 1rem;"></i>
                      </div>
                    </td>
                    <td class="py-2 border-0 fw-medium small">{{ item.articulo_nombre }}</td>
                    <td class="py-2 border-0 text-end small">{{ item.cantidad }}</td>
                    <td class="py-2 border-0 text-end small text-muted">${{ formatMoney(item.precio_unitario) }}</td>
                    <td class="pe-3 py-2 border-0 text-end small fw-bold text-dark">${{ formatMoney(item.total) }}</td>
                  </tr>
                  <tr v-if="articulos.length === 0">
                    <td colspan="5" class="text-center text-muted py-4 small">Esta venta no tiene artículos registrados.</td>
                  </tr>
                </tbody>
                <tfoot v-if="articulos.length > 0" class="bg-light-subtle">
                  <tr>
                    <td colspan="4" class="text-end fw-bold py-3 text-secondary">TOTAL FINAL:</td>
                    <td class="text-end pe-3 fw-bold fs-5 py-3 text-primary-custom">${{ totalVenta }}</td>
                  </tr>
                  <tr v-if="venta?.monto_cobrado && Number(venta.monto_cobrado) !== Number(venta.total_venta)">
                    <td colspan="4" class="text-end fw-bold py-1 text-success">MONTO PAGADO:</td>
                    <td class="text-end pe-3 fw-bold py-1 text-success">${{ formatMoney(venta.monto_cobrado) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <div v-if="venta?.descripcion_cliente" class="mt-3 p-2 bg-warning-subtle rounded-2 border border-warning-subtle">
            <p class="small mb-0 text-warning-emphasis">
              <i class="bi bi-info-circle me-1"></i> <strong>Nota:</strong> {{ venta.descripcion_cliente }}
            </p>
          </div>

          <!-- Mensaje de Advertencia por Edición Post-Facturación -->
          <div v-if="mostrarAdvertenciaEdicion" class="mt-3 p-3 bg-warning-subtle rounded-3 border border-warning-subtle shadow-sm animate__animated animate__shakeX">
            <div class="d-flex gap-3">
              <i class="bi bi-exclamation-triangle-fill text-warning fs-4 mt-1"></i>
              <div>
                <p class="fw-bold text-warning mb-1">Venta facturada modificada</p>
                <p class="small mb-0 text-warning-emphasis">
                  Esta venta fue modificada después de emitir la factura. El detalle puede no coincidir con lo que se facturó.
                </p>
                <p class="text-muted mt-2" style="font-size: 0.7rem;">
                  Última edición: {{ formatFechaHora(venta.fecha_edicion) }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-top-0 bg-light-subtle gap-2">
          <button @click="imprimirTicket" class="btn btn-outline-primary d-flex align-items-center gap-2 px-4 shadow-sm" :disabled="isPrinting">
            <i class="bi bi-printer"></i>
            {{ isPrinting ? 'Enviando...' : 'Reimprimir' }}
          </button>
          <button @click="emit('editar', venta)" class="btn btn-outline-warning d-flex align-items-center gap-2 px-4 shadow-sm">
            <i class="bi bi-pencil-square"></i>
            Editar
          </button>
          <button type="button" class="btn btn-secondary px-4" @click="close">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Factura Electrónica -->
  <FacturaPreviewModal
    v-model="showFacturaModal"
    :venta="venta"
    :articulos="articulos"
  />
</template>

<script setup>
import { computed, ref } from 'vue';
import { formatMoney } from '@/utils/formatters';
import FacturaPreviewModal from '@/components/venta/FacturaPreviewModal.vue';

const props = defineProps({
  modelValue: Boolean,
  venta: Object,
  articulos: Array,
  apiBaseUrl: String,
  isLoading: Boolean
});

const emit = defineEmits(['update:modelValue', 'imprimir', 'editar']);

const isPrinting = ref(false);
const showFacturaModal = ref(false);

const close = () => {
  emit('update:modelValue', false);
};

const verFacturaPdf = () => {
  showFacturaModal.value = true;
};

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const part = String(fecha).split('T')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const formatFechaHora = (fecha) => {
  if (!fecha) return '—';
  const d = new Date(fecha);
  return d.toLocaleString('es-AR');
};

const totalVenta = computed(() => {
  return formatMoney(props.articulos.reduce((acc, av) => acc + Number(av.total || 0), 0));
});

const mostrarAdvertenciaEdicion = computed(() => {
  // Si la venta tiene estado (facturada o error) y además tiene fecha_edicion
  return (props.venta?.estado_factura === 'facturada' || props.venta?.estado_factura === 'error') && 
         props.venta?.fecha_edicion;
});

const imprimirTicket = async () => {
  isPrinting.value = true;
  try {
    await emit('imprimir', props.venta.id);
  } finally {
    isPrinting.value = false;
  }
};
</script>

<style scoped>
.modal.show {
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
}

.icon-box {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.fs-xs { font-size: 0.72rem; }

.text-primary-custom {
  color: #00558c;
}

.bg-primary-custom {
  background-color: #00558c;
}

.modal-content {
  border-radius: 20px;
}

.table.border-0 tr th, 
.table.border-0 tr td {
  border: none !important;
}

/* Efecto ripple simple para el modal */
.ripple-container {
  overflow: hidden;
}
</style>
