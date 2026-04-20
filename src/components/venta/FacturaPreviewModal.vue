<template>
  <div v-if="modelValue" class="modal fade show d-block" tabindex="-1" style="background-color:rgba(0,0,0,0.65); z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content border-0 shadow-lg d-flex flex-column" style="border-radius: 12px; overflow: hidden; max-height: 90vh;">

        <!-- Header -->
        <div class="modal-header text-white py-2 px-4 shadow-sm" style="background: #1e293b; z-index: 10;">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-receipt text-warning fs-5"></i>
            <h6 class="mb-0 fw-bold text-dark">Factura Electrónica</h6>
          </div>
          <button type="button" class="btn-close btn-close-dark" @click="close"></button>
        </div>

        <!-- Loading -->
        <div v-if="cargando" class="modal-body text-center py-5">
          <div class="spinner-border text-primary mb-3" role="status"></div>
          <p class="text-muted">Cargando comprobante...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="modal-body text-center py-5">
          <i class="bi bi-exclamation-triangle text-warning fs-1"></i>
          <p class="text-muted mt-2">{{ error }}</p>
        </div>

        <!-- Ticket AFIP en iframe srcdoc (con zoom para mejor visualización) -->
        <div v-else class="modal-body p-0 bg-dark d-flex justify-content-center align-items-start flex-grow-1" style="overflow-y: auto; padding: 20px !important;">
          <div style="transform: scale(1.4); transform-origin: top center; margin-bottom: 250px;">
            <iframe
              :srcdoc="htmlTicket"
              class="border-0 shadow-lg"
              style="width: 320px; height: 800px; background: white;"
              scrolling="no"
            ></iframe>
          </div>
        </div>

        <!-- Footer (Sticky) -->
        <div class="modal-footer py-2 px-4 bg-light border-top shadow-sm" style="z-index: 10; margin-top: auto;">
          <button
            v-if="!error && !cargando"
            @click="reimprimir"
            :disabled="imprimiendo"
            class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
          >
            <i class="bi bi-printer"></i>
            {{ imprimiendo ? "Enviando..." : "Reimprimir" }}
          </button>
          <button @click="close" class="btn btn-secondary btn-sm">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import facturaService from "@/services/comercial/facturaService";
import { generarHtmlDetalleFactura, imprimirTicketConModo } from "@/composables/usePrinterConfig";

const props = defineProps({
  modelValue: Boolean,
  venta: Object,
  articulos: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue"]);

const cargando  = ref(false);
const error     = ref("");
const htmlTicket = ref("");
const imprimiendo = ref(false);

const close = () => emit("update:modelValue", false);

const reimprimir = async () => {
  if (!props.venta || imprimiendo.value) return;
  imprimiendo.value = true;
  try {
    await imprimirTicketConModo({
      venta: props.venta,
      articulos: props.articulos,
      tipo: "DETALLE_FACTURA",
      factura: _factura.value,
    });
  } catch (e) {
    alert("Error al imprimir: " + (e?.message || e));
  } finally {
    imprimiendo.value = false;
  }
};

const _factura = ref(null);

watch(() => props.modelValue, async (open) => {
  if (!open || !props.venta?.id) return;

  cargando.value = true;
  error.value = "";
  htmlTicket.value = "";
  _factura.value = null;

  try {
    const factura = await facturaService.getFacturaPorVenta(props.venta.id);
    if (!factura) {
      error.value = "No se encontró una factura electrónica para esta venta.";
      return;
    }
    _factura.value = factura;
    htmlTicket.value = generarHtmlDetalleFactura({ factura, articulos: props.articulos });
  } catch {
    error.value = "Error al cargar el comprobante electrónico.";
  } finally {
    cargando.value = false;
  }
}, { immediate: false });
</script>

<style scoped>
.factura-document {
  font-family: Arial, sans-serif;
  font-size: 0.85rem;
  background: #fff;
}
</style>