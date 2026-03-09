<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">

    <!-- ── Encabezado ─────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">VENTAS</h1>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted small d-none d-md-inline">Presioná <kbd>N</kbd> para nueva venta</span>
        <button @click="openVentaModal()" class="btn-primary-modern d-flex align-items-center">
          <i class="bi bi-plus-circle-fill fs-6 me-2"></i> Nueva Venta
        </button>
      </div>
    </div>

    <!-- ── Filtros ───────────────────────────────────────────── -->
    <div class="row g-3 mb-4 align-items-end">
      <div class="col-12 col-md-4">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Buscador</label>
        <FuzzySearch v-model="searchQuery" placeholder="Cliente, equipo o descripción..." />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Desde</label>
        <input type="date" v-model="filterDateFrom" class="form-control" />
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted text-uppercase fw-bold mb-1">Hasta</label>
        <input type="date" v-model="filterDateTo" class="form-control" />
      </div>
      <div class="col-12 col-md-2 d-flex gap-1">
        <button @click="resetFilters" class="btn btn-outline-secondary w-100" title="Limpiar filtros">
          <i class="bi bi-arrow-counterclockwise"></i>
        </button>
      </div>
    </div>

    <!-- ── VENTAS ABIERTAS ────────────────────────────────────── -->
    <div class="mb-4">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="ventas-section-badge ventas-section-badge--abierta">
          <i class="bi bi-folder2-open me-1"></i> ABIERTAS
        </span>
        <span class="badge rounded-pill bg-warning-subtle text-warning fw-bold">{{ ventasAbiertas.length }}</span>
      </div>

      <div v-if="loading" class="text-center py-4">
        <div class="spinner-border text-primary-custom" role="status" style="width:2.5rem;height:2.5rem">
          <span class="visually-hidden">Cargando...</span>
        </div>
      </div>

      <div v-else-if="ventasAbiertas.length === 0" class="venta-empty-state">
        <i class="bi bi-inbox"></i>
        <span>No hay ventas abiertas</span>
      </div>

      <div v-else class="ventas-grid">
        <div
          v-for="venta in ventasAbiertas"
          :key="venta.id"
          class="venta-card venta-card--abierta cursor-pointer"
          :class="{ 'venta-card--expanded': ventaExpandida === venta.id }"
          @click="openVentaModal(venta)"
        >
          <!-- Cabecera de la tarjeta -->
          <div class="venta-card__header">
            <div class="d-flex align-items-start gap-3 flex-grow-1 min-w-0">
              <div class="venta-card__icon-container">
                <i class="bi bi-cart3"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="venta-card__id text-uppercase">Venta Nro: {{ venta.id }}-{{ venta.simbolo }}</span>
                </div>
                <div class="fw-bold text-dark lh-sm text-truncate mb-1" style="font-size: 1.05rem;">
                  {{ venta.cliente_nombre || 'Consumidor Final' }}
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                  <span v-if="venta.equipo_nombre" class="badge-equipo">
                    <i class="bi bi-person-workspace me-1"></i>{{ venta.equipo_nombre }}
                  </span>
                  <span class="text-muted" style="font-size:0.8rem">
                    <i class="bi bi-calendar3 me-1"></i>{{ formatFecha(venta.fecha) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="d-flex flex-column align-items-end gap-2 flex-shrink-0 ms-2" @click.stop>
              <div class="d-flex align-items-center gap-1">
                <button @click="iniciarCierreVenta(venta)" class="btn btn-sm btn-success-modern d-flex align-items-center px-2 py-1" title="Cerrar y Cobrar">
                  <i class="bi bi-wallet2 me-1"></i>
                  <span class="d-none d-sm-inline">Cobrar</span>
                </button>
                <button @click="prepareDeleteVenta(venta.id)" class="btn btn-link link-danger p-0 ms-1" title="Eliminar venta">
                  <i class="bi bi-trash3 fs-5"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Detalle expandido -->
          <div v-if="ventaExpandida === venta.id" class="venta-card__detalle">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-primary-custom fw-bold small">
                <i class="bi bi-box-seam me-1"></i>Artículos
              </span>
              <button @click="openVentaModal(venta)" class="btn btn-sm btn-outline-primary-modern">
                <i class="bi bi-plus me-1"></i>Agregar artículo
              </button>
            </div>
            <div v-if="loadingDetalle" class="text-center py-2">
              <div class="spinner-border spinner-border-sm text-primary-custom"></div>
            </div>
            <table v-else class="table table-sm align-middle mb-1">
              <thead>
                <tr>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1">Artículo</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Cant.</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">P.Unit.</th>
                  <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Total</th>
                  <th class="py-1"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="av in articulosDeVenta" :key="av.id_articulo_venta">
                  <td class="fw-medium small">{{ av.articulo_nombre }}</td>
                  <td class="text-end small">{{ av.cantidad }}</td>
                  <td class="text-end small text-muted">${{ Number(av.precio_unitario).toFixed(2) }}</td>
                  <td class="text-end small fw-semibold">${{ Number(av.total).toFixed(2) }}</td>
                  <td class="text-end">
                    <button @click="prepareDeleteArticuloVenta(av.id_articulo_venta)" class="btn btn-link link-danger p-0">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </td>
                </tr>
                <tr v-if="articulosDeVenta.length === 0">
                  <td colspan="5" class="text-center text-muted py-2 small">Sin artículos cargados aún.</td>
                </tr>
              </tbody>
              <tfoot v-if="articulosDeVenta.length > 0">
                <tr class="border-top">
                  <td colspan="3" class="text-end fw-bold small py-1">Total:</td>
                  <td class="text-end fw-bold small py-1">${{ totalDetalleVenta }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>

            <!-- Cerrar venta rápido -->
            <div class="d-flex justify-content-end mt-2">
              <button
                @click="iniciarCierreVenta(venta)"
                class="btn btn-sm btn-success-modern d-flex align-items-center gap-1"
                :disabled="articulosDeVenta.length === 0"
              >
                <i class="bi bi-check2-circle"></i> Cerrar venta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── VENTAS CERRADAS ────────────────────────────────────── -->
    <div class="mt-4">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="ventas-section-badge ventas-section-badge--cerrada">
          <i class="bi bi-check-circle me-1"></i> CERRADAS
        </span>
        <span class="badge rounded-pill bg-success-subtle text-success fw-bold">{{ ventasCerradas.length }}</span>
        <button class="btn btn-link link-secondary p-0 ms-1 small" @click="mostrarCerradas = !mostrarCerradas" style="font-size:0.8rem">
          {{ mostrarCerradas ? 'Ocultar' : 'Mostrar' }}
        </button>
      </div>

      <div v-if="mostrarCerradas">
        <div v-if="ventasCerradas.length === 0 && !loading" class="venta-empty-state">
          <i class="bi bi-archive"></i>
          <span>No hay ventas cerradas</span>
        </div>
        <div v-else-if="ventasCerradas.length > 0" class="card shadow-sm border-0 rounded-3 overflow-hidden">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <SortableTableHead :columns="columnsCerradas" :sort-key="sortKey" :sort-dir="sortDir" @sort="handleSort" />
              <tbody class="bg-white">
                <template v-for="venta in ventasCerradas" :key="venta.id">
                  <tr :class="{ 'table-active': ventaExpandida === venta.id }">
                    <td class="ps-4 text-muted fw-bold small">{{ venta.id }}-{{ venta.simbolo }}</td>
                    <td class="text-muted small">{{ formatFecha(venta.fecha) }}</td>
                    <td class="fw-medium text-dark small">{{ venta.cliente_nombre || '—' }}</td>
                    <td class="small">
                      <span v-if="venta.equipo_nombre" class="badge bg-primary-subtle text-primary-custom rounded-pill px-2">
                        {{ venta.equipo_nombre }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="text-muted small">
                      {{ venta.descripcion_cliente || '—' }}
                    </td>
                    <td class="text-muted small">
                      <span v-if="venta.medio_cobro_nombre" class="badge bg-info-subtle text-info border border-info-subtle px-2">
                        {{ venta.medio_cobro_nombre }}
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="pe-4 text-end">
                      <button @click="toggleDetalle(venta.id)" class="btn btn-link link-secondary p-1 me-1" :title="ventaExpandida === venta.id ? 'Ocultar' : 'Ver'">
                        <i class="bi fs-5" :class="ventaExpandida === venta.id ? 'bi-chevron-up' : 'bi-list-ul'"></i>
                      </button>
                      <button @click="prepareDeleteVenta(venta.id)" class="btn btn-link link-danger p-1" title="Eliminar">
                        <i class="bi bi-trash3 fs-5"></i>
                      </button>
                    </td>
                  </tr>
                  <!-- Detalle expandido de cerrada -->
                  <tr v-if="ventaExpandida === venta.id" class="bg-light">
                    <td colspan="6" class="px-4 py-3">
                      <div v-if="loadingDetalle" class="text-center py-2">
                        <div class="spinner-border spinner-border-sm text-primary-custom"></div>
                      </div>
                      <table v-else class="table table-sm align-middle mb-0">
                        <thead>
                          <tr>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1">Artículo</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Cant.</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">P.Unit.</th>
                            <th class="text-uppercase fs-xs text-secondary fw-bold py-1 text-end">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="av in articulosDeVenta" :key="av.id_articulo_venta">
                            <td class="fw-medium small">{{ av.articulo_nombre }}</td>
                            <td class="text-end small">{{ av.cantidad }}</td>
                            <td class="text-end small text-muted">${{ Number(av.precio_unitario).toFixed(2) }}</td>
                            <td class="text-end small fw-semibold">${{ Number(av.total).toFixed(2) }}</td>
                          </tr>
                          <tr v-if="articulosDeVenta.length === 0">
                            <td colspan="4" class="text-center text-muted py-2 small">Sin artículos.</td>
                          </tr>
                          <tr v-if="articulosDeVenta.length > 0" class="border-top">
                            <td colspan="3" class="text-end fw-bold small py-1">Total:</td>
                            <td class="text-end fw-bold small py-1">${{ totalDetalleVenta }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
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

    <!-- Modal de Venta Unificado -->
    <VentaModal
      v-model="showVentaModal"
      :is-editing="isEditing"
      :is-loading="isSaving"
      :initial-form="ventaForm"
      :clientes="clientes"
      :equipos="equipos"
      :estados-venta="estadosVenta"
      :medios-cobro="mediosCobro"
      :articulos="articulos"
      :id-estado-cerrada="ID_ESTADO_CERRADA"
      :id-cuenta-corriente="ID_CUENTA_CORRIENTE"
      :simbolo-dia="simboloDia"
      @save="handleSaveVenta"
    />

    <!-- Contenedor de Ticket para Impresión -->
    <div v-if="ventaParaImprimir" class="ticket-impresion">
      <div class="ticket-header">
        <h2 class="ticket-title">IL CALCIO CAMP</h2>
        <p>Ticket de Venta #{{ ventaParaImprimir.id }}-{{ ventaParaImprimir.simbolo }}</p>
        <p>Fecha: {{ formatFecha(ventaParaImprimir.fecha) }}</p>
      </div>
      
      <div class="ticket-info">
        <p><strong>Cliente:</strong> {{ ventaParaImprimir.cliente_nombre || 'Consumidor Final' }}</p>
        <p v-if="ventaParaImprimir.equipo_nombre"><strong>Equipo:</strong> {{ ventaParaImprimir.equipo_nombre }}</p>
      </div>

      <table class="ticket-table">
        <thead>
          <tr>
            <th>Cant.</th>
            <th>Descripción</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in articulosDeVentaParaImprimir" :key="item.id_articulo_venta">
            <td>{{ item.cantidad }}</td>
            <td>{{ item.articulo_nombre }}</td>
            <td class="text-end">${{ Number(item.total).toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>

      <div class="ticket-total">
        <div class="total-row">
          <span>TOTAL:</span>
          <span class="total-amount">${{ totalVentaParaImprimir }}</span>
        </div>
        <p v-if="ventaParaImprimir.medio_cobro_nombre" class="mt-2 text-center">
          <strong>Pago:</strong> {{ ventaParaImprimir.medio_cobro_nombre }}
        </p>
      </div>

      <div class="ticket-footer">
        <p>¡Gracias por su compra!</p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import * as qz from 'qz-tray';
import { KJUR, hextob64 } from 'jsrsasign';
import ConfirmModal from '@/components/ConfirmModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import SortableTableHead, { useSorting } from '@/components/SortableTableHead.vue';
import VentaModal from "@/components/VentaModal.vue";
import ventasService from '@/services/ventasService';
import clientesService from '@/services/clientesService';
import articulosService from '@/services/articulosService';
import datosMaestrosService from '@/services/datosMaestrosService';
import configuracionService from '@/services/configuracionService';
import { useToastStore } from '@/stores/toastStore';

const toast = useToastStore();
const { sortKey, sortDir, handleSort, sortItems } = useSorting('id', 'desc');

// Configuración de seguridad para que QZ Tray habilite el "Recordar"
qz.security.setCertificatePromise((resolve) => {
    resolve("-----BEGIN CERTIFICATE-----\n" +
            "MIIECzCCAvOgAwIBAgIGAZzT1LtVMA0GCSqGSIb3DQEBCwUAMIGiMQswCQYDVQQG\n" +
            "EwJVUzELMAkGA1UECAwCTlkxEjAQBgNVBAcMCUNhbmFzdG90YTEbMBkGA1UECgwS\n" +
            "UVogSW5kdXN0cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMx\n" +
            "HDAaBgkqhkiG9w0BCQEWDXN1cHBvcnRAcXouaW8xGjAYBgNVBAMMEVFaIFRyYXkg\n" +
            "RGVtbyBDZXJ0MB4XDTI2MDMwODE4MjEwMFoXDTQ2MDMwODE4MjEwMFowgaIxCzAJ\n" +
            "BgNVBAYTAlVTMQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYD\n" +
            "VQQKDBJRWiBJbmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMs\n" +
            "IExMQzEcMBoGCSqGSIb3DQEJARYNc3VwcG9ydEBxei5pbzEaMBgGA1UEAwwRUVog\n" +
            "VHJheSBEZW1vIENlcnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7\n" +
            "BPga2LBxlozHJJ4cXUMYnoP4K2GT9+5yk1GVp+T59GtS8wN32i1TSh1XBzPtJi2i\n" +
            "18AKrGpcuLXPlGo4qtdeXn4goXVd70d1Y7Q/mgyFFVi0G+uYvEyXaMbbAUBHV6i0\n" +
            "JJ/2Os5zbPUbEla1vPEv0J4iPxMYvn2iDpneycX6VKlrPXQNwGcVTtsG0xF9JI/u\n" +
            "A5lxrhqeIYdl6kQ7sTzyCNBdpJLcoCn7RL0K4ntc8aQ4aM+2Ob6IyFDzt83orOdQ\n" +
            "e945BX9/2NbOPhts67fRgf2H4BgIe9xagc0nguR82wrCcqt2RASziQ19C2FozIrF\n" +
            "XAeB8R/4LKHkD+qzmFbvAgMBAAGjRTBDMBIGA1UdEwEB/wQIMAYBAf8CAQEwDgYD\n" +
            "VR0PAQH/BAQDAgEGMB0GA1UdDgQWBBT9y3FGXDjXVpdzoIEQZM3xLotUyDANBgkq\n" +
            "hkiG9w0BAQsFAAOCAQEAHelzhR5AFTYfGxklytf3MqbsM04ZkMn1cie6c0iWGwBG\n" +
            "DJUQy+7pYEjAUMYZdSQhVmcceH/Ab/d/1tKtW8HvzBQvKe6gtD+DhLd7YZtPVQbz\n" +
            "dsOTutGGhDrg2CM0mAfs2gA0ZNT66k1INPUJLuShRwMO9CqrMLh/Ykr+2c/XGW4i\n" +
            "JKYUCwdpBYTqgPS5gstawIgeBWQ+qx/Pjy8NjJUv9CxZcplTjwTDPjtrR2kOghPO\n" +
            "HdCX9yx+nFOCRx9TVHZ3XuBZJuD/I5/z4zBfsYAYeuJYrt41AoOX4AJ4+HutQN4Y\n" +
            "0msH99Pi4m5ZdGFToZ5kayfCSsxwdUqFHzPrv0Z1XQ==\n" +
            "-----END CERTIFICATE-----");
});

qz.security.setSignaturePromise((toSign) => {
    return (resolve, reject) => {
        try {
            const pk = "-----BEGIN PRIVATE KEY-----\n" +
                "MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7BPga2LBxlozH\n" +
                "JJ4cXUMYnoP4K2GT9+5yk1GVp+T59GtS8wN32i1TSh1XBzPtJi2i18AKrGpcuLXP\n" +
                "lGo4qtdeXn4goXVd70d1Y7Q/mgyFFVi0G+uYvEyXaMbbAUBHV6i0JJ/2Os5zbPUb\n" +
                "Ela1vPEv0J4iPxMYvn2iDpneycX6VKlrPXQNwGcVTtsG0xF9JI/uA5lxrhqeIYdl\n" +
                "6kQ7sTzyCNBdpJLcoCn7RL0K4ntc8aQ4aM+2Ob6IyFDzt83orOdQe945BX9/2NbO\n" +
                "Phts67fRgf2H4BgIe9xagc0nguR82wrCcqt2RASziQ19C2FozIrFXAeB8R/4LKHk\n" +
                "D+qzmFbvAgMBAAECggEAJCMcK9fWFETCdBKDyLhOqDmtB22ef8CHHzWPLKtSB+hu\n" +
                "OouBjo2md3MZQ0E9i+P2KoKk9YsGTF9WpkMn2UZNskrw9S4tpxZ+yNSYtjd2ltqe\n" +
                "lsLUXeF4rUMONbBCsuZhz1lKXYJUdSJHJFGBVsGpGxOlErn8XyojzYYjvlRfwHR5\n" +
                "7TeaVg3gOS1r8GVCGPFE7bi0kBDxKRVSN0VqsS0togRaotcD1zf7l5t8hkYx5kHv\n" +
                "3WGUbDrivz4EfSx9Qj7r9+Vhdfa10aXuqokMAHXVf5QsxNxXX5vdPENMF7ofJy5u\n" +
                "A59DmDMoG8cOkE+m6KDrxvlG7G/fZ/f836pVOiMVHQKBgQDdWrsBsKn/NioPOVFz\n" +
                "LgquGQ4zdD2tFLwKI1Ioz5Z3XRKXTpmcj2wJEFDYYMWltttqATpMcrj2RYbZCaWc\n" +
                "IYNoONLQ4pRNvbMZHWmsbNsoR9uVNZ+8QtnTWA+b9c0t2yFPjZDn174m7UZ2CR8C\n" +
                "bMcTXlcfgSwKl7N633nxVFSfAwKBgQDYSn9ShVpIt+cBPHnLjCJ3XeBB2/jg4uQk\n" +
                "2wRXkmU/9ors9Aq3Q8GkN1XvpXqUr91HE4rxPYNUdIafCaiKAQ5NWw1TA4jccP87\n" +
                "6t/ijWe9wJUV+O/OBXgM8vM9wfa/1XScOaMU03E1uUIW5P6r+tzGPDYp4Zn/Rjm4\n" +
                "F4ik5E6epQKBgEZ/5Dm4k5wmGyU4Iznk+x/R+RToO9CJXw53i25WF10y9n3cWc5k\n" +
                "W4tTd/xCbhDGeYF8nJ3GmCRPppAvo2BjyB+EoZhH4eYUuhsQpBx3myFsKYKPTq2+\n" +
                "OPQ4AtiwY8XsGeLlerZsnzJ0tdFYPFkgXhNMI8Fz+ZvyDwbecE8thboTAoGBAIVt\n" +
                "Z6ATjb+gW0xC72um9jgm3EokliK9NTqbNdGECRvtToSgg9/MV6+jR0tADR+eYeYP\n" +
                "4z2w0cyO2eFQRv1ja1xDGDQm0Q4UUw+2dAjBbMb8/7t/RwgUDZwHYBCwEDUFTBt3\n" +
                "3ufhDEy1DVUsTQLxDbLowA0UFDkLLF4pfm0iPnHVAoGAdoKELYjpDNE1rpr8BNIf\n" +
                "6q1qCr50tvNkyEXwIsqsq0/B6v7RZqyzApJx+sI2CNI1LYvxwn3wiX2ORB3W8dYF\n" +
                "mwJgd8gI0eI4m12TFHzyQfASJuIW8/OaS59UmSOlF2anow4u3RDQeZHGWf3HFq0f\n" +
                "CQpbR8VVgM0tLd17KTfWkKQ=\n" +
                "-----END PRIVATE KEY-----";

            const sig = new KJUR.crypto.Signature({ alg: 'SHA1withRSA' });
            sig.init(pk);
            sig.updateString(toSign);
            const hex = sig.sign();
            resolve(hextob64(hex));
        } catch (err) {
            console.error('Error signing request:', err);
            reject(err);
        }
    };
});

// Estados para impresión
const ventaParaImprimir = ref(null);
const articulosDeVentaParaImprimir = ref([]);
const totalVentaParaImprimir = computed(() =>
  articulosDeVentaParaImprimir.value.reduce((acc, av) => acc + Number(av.total || 0), 0).toFixed(2)
);

const ID_ESTADO_ABIERTA = computed(() =>
  estadosVenta.value.find(e => e.descripcion?.toLowerCase().includes('abierta'))?.id ?? 1
);
const ID_ESTADO_CERRADA = computed(() =>
  estadosVenta.value.find(e => e.descripcion?.toLowerCase().includes('cerrada'))?.id ?? 2
);
const ID_CUENTA_CORRIENTE = computed(() =>
  mediosCobro.value.find(m => m.descripcion?.toLowerCase().includes('cuenta corriente'))?.id ?? null
);

const columnsCerradas = [
  { key: 'id',            label: 'Nro Venta',     sortable: true,  thClass: 'ps-4 py-3 text-uppercase fs-xs fw-bold text-secondary', thStyle: 'width:100px' },
  { key: 'fecha',         label: 'Fecha',       sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'cliente_nombre',label: 'Cliente',     sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'equipo_nombre', label: 'Equipo',      sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'descripcion_cliente', label: 'Descripción', sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'medio_cobro_nombre',  label: 'Medio pago',  sortable: true,  thClass: 'py-3 text-uppercase fs-xs fw-bold text-secondary' },
  { key: 'acciones',      label: '',            sortable: false, thClass: 'pe-4 py-3 text-end' },
];

const ventas          = ref([]);
const clientes        = ref([]);
const equipos         = ref([]);
const estadosVenta    = ref([]);
const mediosCobro     = ref([]);
const articulos       = ref([]);
const loading         = ref(false);
const searchQuery     = ref('');
const mostrarCerradas = ref(true);

const simbolosRotativos = ['$', '#', '&', '@', '€', '£', '¥', 'Δ', 'Ω', 'Σ'];
const simboloDia = computed(() => {
  const diaDelAnio = Math.floor((new Date() - new Date(new Date().getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24));
  return simbolosRotativos[diaDelAnio % simbolosRotativos.length];
});

// Filtros de fecha
const getYesterday = () => {
  const d = new Date();
  d.setDate(d.getDate() - 1);
  return d.toISOString().split('T')[0];
};
const getToday = () => new Date().toISOString().split('T')[0];

const filterDateFrom = ref(getYesterday());
const filterDateTo   = ref(getToday());

const resetFilters = () => {
  searchQuery.value = '';
  filterDateFrom.value = getYesterday();
  filterDateTo.value = getToday();
};

const ventaExpandida    = ref(null);
const articulosDeVenta  = ref([]);
const loadingDetalle    = ref(false);
const totalesVenta      = ref({});

const showVentaModal = ref(false);
const isEditing      = ref(false);
const isSaving       = ref(false);

const emptyVentaForm = () => ({
  id: null,
  fecha: new Date().toISOString().split('T')[0],
  id_cliente: null,
  id_equipo: null,
  id_estado_venta: 3,
  descripcion_cliente: '',
  tipo_vta: 1,
  simbolo: simboloDia.value,
  id_medio_cobro: 1,
  articulos: [],
});

const ventaForm = ref(emptyVentaForm());

const showArticuloVentaModal = ref(false);
const ventaIdParaArticulo    = ref(null);
const isSavingArticulo       = ref(false);
const avForm = ref({ id_articulo: null, cantidad: null, precio_unitario: null });
const avTotalCalculado = computed(() =>
  ((Number(avForm.value.cantidad) || 0) * (Number(avForm.value.precio_unitario) || 0)).toFixed(2)
);

const showDeleteVentaModal = ref(false);
const isDeleting           = ref(false);
const idVentaToDelete      = ref(null);
const showDeleteAvModal    = ref(false);
const isDeletingAv         = ref(false);
const idAvToDelete         = ref(null);

const totalDetalleVenta = computed(() =>
  articulosDeVenta.value.reduce((acc, av) => acc + Number(av.total || 0), 0).toFixed(2)
);

const ventasFiltradas = computed(() => {
  let items = (ventas.value || []).map(v => {
    const medio = (mediosCobro.value || []).find(m => Number(m.id) === Number(v.id_medio_cobro));
    return {
      ...v,
      medio_cobro_nombre: medio ? medio.descripcion : (v.medio_cobro_nombre || '—')
    };
  });
  
  // Filtrar fuera a los que no tienen el estado correcto para evitar errores si algo falla
  items = items.filter(v => v.id_estado_venta);
  
  // Filtro de texto
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(v =>
      v.cliente_nombre?.toLowerCase().includes(q) ||
      v.equipo_nombre?.toLowerCase().includes(q) ||
      v.estado_descripcion?.toLowerCase().includes(q) ||
      v.descripcion_cliente?.toLowerCase().includes(q)
    );
  }

  // Filtro de fechas
  if (filterDateFrom.value) {
    items = items.filter(v => v.fecha >= filterDateFrom.value);
  }
  if (filterDateTo.value) {
    items = items.filter(v => v.fecha <= filterDateTo.value);
  }

  return sortItems(items);
});

const ventasAbiertas = computed(() =>
  ventasFiltradas.value.filter(v => Number(v.id_estado_venta) === Number(ID_ESTADO_ABIERTA.value))
);
const ventasCerradas = computed(() =>
  ventasFiltradas.value.filter(v => Number(v.id_estado_venta) === Number(ID_ESTADO_CERRADA.value))
);

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const part = String(fecha).split('T')[0];
  const [y, m, d] = part.split('-');
  return `${d}/${m}/${y}`;
};

const fetchData = async () => {
  loading.value = true;
  try {
    const [ventasRes, clientesRes, equiposRes, estadosRes, mediosRes, articulosRes] = await Promise.all([
      ventasService.getVentas(),
      clientesService.getClientes(),
      datosMaestrosService.getEquipos(),
      datosMaestrosService.getEstadosVenta(),
      datosMaestrosService.getMediosCobro(),
      articulosService.getAllActivos(),
    ]);
    ventas.value = ventasRes;
    clientes.value = clientesRes;
    equipos.value = equiposRes;
    estadosVenta.value = estadosRes;
    mediosCobro.value = mediosRes;
    articulos.value = articulosRes;

    console.log('Ventas:', ventas.value);
    console.log('Medios Cobro:', mediosCobro.value);
  } catch {
    toast.showToast({ message: 'Error al cargar datos.', type: 'danger' });
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
    articulosDeVenta.value = await ventasService.getArticulosDeVenta(idVenta);
    totalesVenta.value[idVenta] = articulosDeVenta.value.reduce((acc, av) => acc + Number(av.total || 0), 0).toFixed(2);
  } catch {
    toast.showToast({ message: 'Error al cargar artículos.', type: 'danger' });
  } finally {
    loadingDetalle.value = false;
  }
};

const openVentaModal = async (item = null, forceCierre = false) => {
  if (item) {
    // MODO EDICIÓN / AGREGAR ARTÍCULOS
    isEditing.value = true;
    loadingDetalle.value = true;
    try {
      // Cargamos los artículos actuales de la venta para que aparezcan en el modal
      const articulosExistentes = await ventasService.getArticulosDeVenta(item.id);
      ventaForm.value = { 
        ...item, 
        simbolo: item.simbolo || simboloDia.value,
        id_medio_cobro: item.id_medio_cobro || 1, // Por defecto ID 1 al editar/cerrar
        articulos: articulosExistentes.map(av => ({
          id_articulo: av.id_articulo,
          nombre: av.articulo_nombre,
          cantidad: av.cantidad,
          precio_unitario: av.precio_unitario,
          total: av.total,
          stock_actual: av.stock_actual // Asegurar que el componente lo reciba si está disponible
        })),
        forceCierre: forceCierre
      };
      
      if (forceCierre) {
        ventaForm.value.id_estado_venta = ID_ESTADO_CERRADA.value;
      }
    } catch (e) {
      toast.showToast({ message: 'Error al cargar detalles de la venta.', type: 'danger' });
      return;
    } finally {
      loadingDetalle.value = false;
    }
  } else {
    // MODO NUEVA VENTA
    isEditing.value = false;
    ventaForm.value = { 
      ...emptyVentaForm(), 
      id_estado_venta: ID_ESTADO_CERRADA.value,
      simbolo: simboloDia.value,
      id_medio_cobro: 1, // Por defecto ID 1
      articulos: [],
      forceCierre: false
    };
  }
  showVentaModal.value = true;
};

const iniciarCierreVenta = (venta) => {
  openVentaModal(venta, true);
};

const handleSaveVenta = async ({ venta, articulos: articulosCarrito }) => {
  if (isSaving.value) return; // Evitar múltiples clicks
  isSaving.value = true;
  try {
    const esCerrada = Number(venta.id_estado_venta) === Number(ID_ESTADO_CERRADA.value);
    const medioCobroDesc = mediosCobro.value.find(m => m.id === venta.id_medio_cobro)?.descripcion ?? '';
    const payload = {
      ...venta,
      tipo_vta: esCerrada ? medioCobroDesc : '',
      id_estado_cerrada: ID_ESTADO_CERRADA.value, // Enviamos el ID para lógica backend
      articulos: (articulosCarrito || []).map(i => ({
        id_articulo: i.id_articulo,
        cantidad: i.cantidad,
        precio_unitario: i.precio_unitario,
        total: i.total,
      })),
    };

    let idVenta = null;
    if (isEditing.value) {
      await ventasService.actualizarVenta(payload);
      toast.showToast({ message: 'Venta actualizada.', type: 'success' });
      idVenta = venta.id;
    } else {
      const resp = await ventasService.crearVenta(payload);
      toast.showToast({ message: 'Venta creada.', type: 'success' });
      idVenta = resp?.id ?? null;
    }
    showVentaModal.value = false;
    await fetchData();

    if (esCerrada && idVenta) {
      // await descargarTicketVenta(idVenta); // Quitamos la descarga automática de PDF por ahorro de clics
      await imprimirTicketDirecto(idVenta);
    }
  } catch (err) {
    const msg = err?.response?.data?.message || 'Error al guardar.';
    toast.showToast({ message: msg, type: 'danger' });
  } finally {
    isSaving.value = false;
  }
};

const descargarTicketVenta = async (idVenta) => {
  try {
    const blob = await ventasService.descargarTicketVenta(idVenta);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ticket_venta_${idVenta}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  } catch {
    toast.showToast({ message: 'Venta guardada. No se pudo generar el ticket.', type: 'warning' });
  }
};

const imprimirTicketDirecto = async (idVenta) => {
  try {
    const venta = ventas.value.find(v => v.id === idVenta);
    if (!venta) return;

    const articulos = await ventasService.getArticulosDeVenta(idVenta);
    
    // Conectar a QZ Tray si no está activo
    if (!qz.websocket.isActive()) {
      try {
        await qz.websocket.connect();
      } catch (err) {
        toast.showToast({ 
          message: 'QZ Tray no detectado. Asegúrate de que la aplicación esté abierta.', 
          type: 'danger' 
        });
        return;
      }
    }

    const config = qz.configs.create("POS-80C");
    
    // Comandos ESC/POS para papel de 80mm
    const data = [
      '\x1B\x40',          // Inicializar impresora
      '\x1B\x61\x01',      // Centrar
      '\x1B\x21\x30',      // Texto grande (doble ancho y alto)
      'IL CALCIO CAMP\x0A',
      '\x1B\x21\x00',      // Texto normal
      `Venta #${venta.id}-${venta.simbolo}\x0A`,
      `Fecha: ${formatFecha(venta.fecha)}\x0A`,
      '------------------------------------------------\x0A', // 48 guiones (aprox 80mm)
      '\x1B\x61\x00',      // Alinear a la izquierda
      'CANT  DESCRIPCION                     TOTAL\x0A',
      '------------------------------------------------\x0A',
      ...articulos.map(a => {
        const cant = String(a.cantidad).padEnd(6, ' ');
        const desc = String(a.articulo_nombre).substring(0, 26).padEnd(27, ' ');
        const total = `$${Number(a.total).toFixed(2)}`.padStart(15, ' ');
        return `${cant}${desc}${total}\x0A`;
      }),
      '------------------------------------------------\x0A',
      '\x1B\x61\x02',      // Alinear a la derecha
      '\x1B\x21\x18',      // Negrita + Doble alto
      `TOTAL: $${Number(totalDetalleVenta.value || 0).toFixed(2)}\x0A`,
      '\x1B\x21\x00',      // Normal
      `Pago: ${venta.medio_cobro_nombre || 'Efectivo'}\x0A\x0A`,
      '\x1B\x61\x01',      // Centrar
      '¡Gracias por su compra!\x0A',
      '\x1B\x64\x05',      // Feed (avance de 5 líneas)
      '\x1D\x56\x00'       // Comando alternativo de corte (Full Cut)
    ];

    await qz.print(config, data);
    toast.showToast({ message: 'Ticket enviado a la tiquetera.', type: 'success' });

  } catch (err) {
    console.error('Error al imprimir ticket:', err);
    const errorMsg = err.message || 'Error desconocido';
    toast.showToast({ message: `Error en la impresora: ${errorMsg}`, type: 'danger' });
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
    toast.showToast({ message: 'Venta eliminada.', type: 'success' });
    showDeleteVentaModal.value = false;
    if (ventaExpandida.value === idVentaToDelete.value) ventaExpandida.value = null;
    await fetchData();
  } catch {
    toast.showToast({ message: 'Error al eliminar.', type: 'danger' });
  } finally {
    isDeleting.value = false;
  }
};

const openArticuloVentaModal = (idVenta) => {
  ventaIdParaArticulo.value = idVenta;
  avForm.value = { id_articulo: null, cantidad: null, precio_unitario: null };
  showArticuloVentaModal.value = true;
};
const autocompletarPrecio = () => {
  if (avForm.value.id_articulo) {
    const art = articulos.value.find(a => Number(a.id) === Number(avForm.value.id_articulo));
    if (art && !avForm.value.precio_unitario) avForm.value.precio_unitario = art.precio_actual;
  }
};
const saveArticuloVenta = async () => {
  isSavingArticulo.value = true;
  try {
    await ventasService.crearArticuloVenta({
      id_articulo: avForm.value.id_articulo,
      id_venta: ventaIdParaArticulo.value,
      cantidad: avForm.value.cantidad,
      precio_unitario: avForm.value.precio_unitario,
      total: avTotalCalculado.value,
    });
    toast.showToast({ message: 'Artículo agregado.', type: 'success' });
    showArticuloVentaModal.value = false;
    const idV = ventaExpandida.value;
    ventaExpandida.value = null;
    await toggleDetalle(idV);
  } catch {
    toast.showToast({ message: 'Error.', type: 'danger' });
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
    toast.showToast({ message: 'Artículo quitado.', type: 'success' });
    showDeleteAvModal.value = false;
    const idV = ventaExpandida.value;
    ventaExpandida.value = null;
    await toggleDetalle(idV);
  } catch {
    toast.showToast({ message: 'Error.', type: 'danger' });
  } finally {
    isDeletingAv.value = false;
  }
};

const onKeydown = (e) => {
  if (showVentaModal.value || showArticuloVentaModal.value) return;
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
  if (e.key === 'n' || e.key === 'N') { e.preventDefault(); openVentaModal(); }
};

onMounted(async () => { 
    fetchData(); 
    window.addEventListener('keydown', onKeydown);
    
    // Configurar QZ Tray para que use la seguridad
    try {
        if (!qz.websocket.isActive()) {
            await qz.websocket.connect();
        }
        // Al conectar con las promesas de seguridad ya seteadas, 
        // QZ debería pedir permiso una única vez.
    } catch (e) {
        console.warn("QZ Tray no detectado al inicio.");
    }
});

onUnmounted(() => { 
    window.removeEventListener('keydown', onKeydown); 
    // Opcional: desconectar para limpiar
    // if (qz.websocket.isActive()) qz.websocket.disconnect();
});
</script>

<style scoped>
/* ── Estilos Generales y Profundidad ────────────────────────── */
.venta-card {
  background-color: #fff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  position: relative;
  overflow: hidden;
}

.venta-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
  border-color: var(--primary-color-subtle, rgba(0, 85, 140, 0.2));
}

.venta-card--abierta {
  border-left: 5px solid #ffc107;
}

.venta-card--expanded {
  transform: scale(1.005);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border-color: rgba(0, 0, 0, 0.15);
}

.venta-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.25rem;
}

.venta-card__icon-container {
  width: 48px;
  height: 48px;
  background-color: #f8f9fa;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #6c757d;
  flex-shrink: 0;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.venta-card--abierta .venta-card__icon-container {
  background-color: #fff9e6;
  color: #ffc107;
}

.venta-card__id {
  font-family: 'Monaco', 'Consolas', monospace;
  font-size: 0.75rem;
  color: #adb5bd;
  letter-spacing: 0.5px;
  font-weight: 800;
}

.badge-equipo {
  background-color: #e7f1ff;
  color: #0d6efd;
  padding: 0.25rem 0.6rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  border: 1px solid #cfe2ff;
}

.venta-card__detalle {
  padding: 1.25rem;
  border-top: 1px dashed #e9ecef;
  background-color: #fcfcfc;
}

/* ── Tablas con Profundidad ────────────────────────────────── */
.card-table-container {
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table thead th {
  background-color: #f8f9fa;
  text-transform: uppercase;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: #6c757d;
  padding: 1rem 0.75rem;
  border-bottom: 2px solid #edf2f7;
}

.table tbody td {
  padding: 1rem 0.75rem;
  border-bottom: 1px solid #edf2f7;
}

.table-hover tbody tr:hover {
  background-color: rgba(248, 249, 250, 0.5);
}

.venta-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: #f8f9fa;
  border-radius: 16px;
  border: 2px dashed #dee2e6;
  color: #6c757d;
}

.venta-empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.ventas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.ventas-section-badge {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.6px;
  text-transform: uppercase;
  padding: 3px 10px;
  border-radius: 20px;
}

.ventas-section-badge--abierta {
  background: #fff8e1;
  color: #856404;
  border: 1px solid #ffe082;
}

.ventas-section-badge--cerrada {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.btn-success-modern {
  background-color: #198754;
  border: none;
  border-radius: 9px;
  color: white;
  font-weight: 600;
  font-size: 0.85rem;
  padding: 7px 16px;
  transition: all 0.2s;
}

.btn-success-modern:hover {
  background-color: #157347;
  box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);
  transform: translateY(-1px);
}

.btn-outline-primary-modern {
  color: var(--color-primary);
  border-color: #e2e8f0;
  background: #f8fafc;
  font-size: 0.85rem;
  padding: 7px 16px;
  border-radius: 9px;
  font-weight: 600;
}

.btn-outline-primary-modern:hover {
  border-color: var(--color-primary);
  background-color: var(--color-primary, #00558c);
  color: white;
}

.fs-xs { font-size: 0.75rem; }
.cursor-pointer { cursor: pointer; }

/* Animaciones */
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 576px) {
  .ventas-grid {
    grid-template-columns: 1fr;
  }
}

/* ── Estilos de Impresión (Ticket 80mm) ────────────────────────── */
@media print {
  /* Ocultar todo el contenedor de la aplicación y modales */
  #app > *:not(.ticket-impresion),
  .modal, .modal-backdrop, .toast-container, 
  nav, .btn, header, footer, .container-fluid {
    display: none !important;
  }

  /* Asegurar que el ticket sea lo único visible */
  .ticket-impresion {
    display: block !important;
    position: absolute;
    left: 0;
    top: 0;
    width: 80mm;
    margin: 0;
    padding: 2mm 4mm;
    font-family: 'Courier New', Courier, monospace;
    font-size: 12px;
    line-height: 1.2;
    color: #000;
  }

  .ticket-header {
    text-align: center;
    margin-bottom: 4mm;
    border-bottom: 1pt dashed #000;
    padding-bottom: 2mm;
  }

  .ticket-title {
    font-size: 16px;
    font-weight: bold;
    margin: 0;
    text-transform: uppercase;
  }

  .ticket-info {
    margin-bottom: 3mm;
    font-size: 11px;
  }

  .ticket-info p {
    margin: 0.5mm 0;
  }

  .ticket-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4mm;
    font-size: 11px;
  }

  .ticket-table th {
    text-align: left;
    border-bottom: 1pt solid #000;
    padding-bottom: 1mm;
    text-transform: uppercase;
  }

  .ticket-table td {
    padding: 1mm 0;
    vertical-align: top;
  }

  .ticket-total {
    border-top: 1pt solid #000;
    padding-top: 2mm;
    margin-top: 1mm;
  }

  .total-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    font-weight: bold;
  }

  .ticket-footer {
    text-align: center;
    margin-top: 6mm;
    font-size: 10px;
    border-top: 1pt dashed #000;
    padding-top: 2mm;
  }

  /* Ajustes para navegadores */
  @page {
    size: 80mm auto;
    margin: 0;
  }
}

/* Ocultar ticket en pantalla normal */
@media screen {
  .ticket-impresion {
    display: none;
  }
}
</style>
