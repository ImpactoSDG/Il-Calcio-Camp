<template>
  <Teleport to="body">
  <div
    v-if="modelValue"
    class="modal-fullscreen-container animate-fade-in"
    @click.self="closeModal"
  >
    <div class="modal-fullscreen-content shadow-2xl overflow-hidden d-flex flex-column">
      <!-- Header Minimalista -->
      <div class="modal-header border-0 bg-white p-4 pb-0 d-flex align-items-center justify-content-between flex-shrink-0">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px;">
            <i class="bi bi-cart-plus fs-4"></i>
          </div>
          <h5 class="modal-title fw-black text-dark text-uppercase letter-spacing-1 mb-0" style="font-size: 1.1rem;">
            {{ isAjuste ? 'Nuevo Ajuste de Stock' : (isEditing ? `Venta #${form.id}` : 'Nueva Venta') }}
          </h5>
        </div>
        <button type="button" class="btn-close shadow-none p-2" @click="closeModal"></button>
      </div>

      <form @submit.prevent="handleSave" class="flex-grow-1 d-flex flex-column overflow-hidden">
        <div class="modal-body p-0 flex-grow-1 overflow-hidden">
          <div class="container-fluid h-100 p-0">
            <div class="row g-0 h-100">
              <!-- COLUMNA 1: CONFIGURACIÓN, CLIENTE Y PAGO (1/3) -->
              <div class="col-lg-4 bg-light-subtle border-end p-4 d-flex flex-column overflow-y-auto custom-scrollbar">
                <div class="section-container mb-4">
                  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                      1. DATOS Y ASIGNACIÓN
                    </h6>
                  </div>

                  <div class="row g-2">
                    <div class="col-12" v-if="isEditing || isAjuste">
                      <label class="form-label fw-semibold text-secondary small mb-1">Fecha <span class="text-danger">*</span></label>
                      <input v-model="form.fecha" type="date" class="form-control border-2 shadow-sm py-1 px-3 rounded-3 fw-bold small" required />
                    </div>

                    <div class="col-12" v-if="!isAjuste">
                      <div class="form-check form-switch p-0 d-flex align-items-center gap-3 bg-white p-2 border-2 border rounded-3 shadow-sm">
                        <label class="form-check-label fw-bold text-dark mb-0 order-2 small" for="checkPedidoAbierto" style="cursor: pointer;">
                          Dejar pedido abierto
                        </label>
                        <div class="form-check p-0 mb-0 d-flex align-items-center order-1 ms-2">
                          <input 
                            class="form-check-input ms-0 mt-0" 
                            type="checkbox" 
                            id="checkPedidoAbierto" 
                            style="width: 2rem; height: 1rem; cursor: pointer;"
                            :checked="esAbierto"
                            @change="toggleEstadoVenta($event.target.checked)"
                          >
                        </div>
                      </div>
                    </div>

                    <!-- Cliente y Observaciones -->
                    <div class="col-12 mt-2 pt-2 border-top">
                      <div class="row g-2">
                        <div class="col-12" v-if="!isAjuste">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold text-secondary small mb-0">
                              Cliente <span v-if="esAbierto" class="text-danger">*</span>
                            </label>
                            <button 
                              v-if="esAbierto" 
                              type="button" 
                              @click="showQuickClientModal = true" 
                              class="btn btn-link link-primary p-0 small text-decoration-none fw-bold" 
                              style="font-size: 0.65rem;"
                            >
                              + Nuevo
                            </button>
                          </div>
                          <select 
                            v-model.number="form.id_cliente" 
                            class="form-select border-2 shadow-sm py-1 px-2 rounded-3 small" 
                            :class="{ 'border-primary': !!form.id_cliente }"
                            :required="esAbierto"
                          >
                            <option :value="null">-- Sin cliente --</option>
                            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_cliente }}</option>
                          </select>
                        </div>
                        
                        <div class="col-12">
                          <div class="d-flex align-items-center mb-1" style="min-height: 20px;">
                            <label class="form-label fw-semibold text-secondary small mb-0">{{ isAjuste ? 'Motivo del Ajuste' : 'Observaciones' }}</label>
                          </div>
                          
                          <textarea 
                            v-model.trim="form.descripcion_cliente" 
                            class="form-control border-2 shadow-sm rounded-3 py-1 px-2 small" 
                            rows="2"
                            style="min-height: 60px; resize: vertical;" 
                            :placeholder="isAjuste ? 'Ej: Productos dañados, vencidos...' : 'Notas...'"
                          ></textarea>
                        </div>
                      </div>
                    </div>

                    <!-- Medio de Pago y Monto Cobrado -->
                    <div v-if="!esAbierto && !isAjuste" class="col-12 mt-2 pt-2 border-top animate-fade-in">
                      <div class="row g-2">
                        <div class="col-6">
                          <label class="form-label fw-semibold text-secondary small mb-1">Medio de Pago <span class="text-danger">*</span></label>
                          <select v-model.number="form.id_medio_cobro" class="form-select border-2 shadow-sm py-1 px-2 rounded-3 small" :required="!esAbierto">
                            <option :value="null" disabled>-- Pago --</option>
                            <option v-for="m in mediosCobro" :key="m.id" :value="m.id">{{ m.descripcion }}</option>
                          </select>
                        </div>
                        <div class="col-6">
                          <label class="form-label fw-semibold text-secondary small mb-1">Monto Cobrado</label>
                          <div class="input-group">
                            <span class="input-group-text bg-white border-2 border-end-0 py-0 px-2 small text-muted" style="font-size: 0.75rem;">$</span>
                            <input 
                              ref="montoEntregadoRef"
                              v-model.number="form.monto_cobrado" 
                              type="number" 
                              step="0.01" 
                              class="form-control border-2 border-start-0 py-1 px-2 fw-bold small" 
                              :placeholder="totalCarrito" 
                            />
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Equipo -->
                    <div v-if="form.id_cliente && !isAjuste" class="col-12 mt-2">
                      <label class="form-label fw-semibold text-secondary small mb-1">Equipo Asociado</label>
                      <div v-if="form.id_equipo" class="bg-white p-1 px-2 rounded-3 border-2 border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <i class="bi bi-people text-primary" style="font-size: 0.8rem;"></i>
                          <span class="fw-bold text-dark text-truncate" style="font-size: 0.75rem;">{{ clienteSeleccionado?.equipo_nombre }}</span>
                        </div>
                        <button type="button" @click="showQuickTeamModal = true" class="btn btn-sm btn-link link-secondary p-0 text-decoration-none" style="font-size: 0.65rem;">Cambiar</button>
                      </div>
                      <button v-else type="button" @click="showQuickTeamModal = true" class="btn btn-sm btn-outline-warning w-100 py-1 fw-bold" style="font-size: 0.7rem;">ASOCIAR EQUIPO</button>
                    </div>
                  </div>
                </div>

                <!-- RESUMEN -->
                <div class="section-container mt-auto pt-4 border-top">
                  <h6 class="fw-bold mb-3 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                    RESUMEN
                  </h6>

                  <div class="bg-white border-2 rounded-4 p-3 border shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-secondary fw-medium">{{ isAjuste ? 'Items a ajustar' : 'Subtotal' }}</span>
                      <span class="text-dark fw-bold">{{ isAjuste ? articulosCarrito.length : ('$' + Number(totalCarrito).toLocaleString(undefined, { minimumFractionDigits: 2 })) }}</span>
                    </div>
                    <div v-if="!isAjuste" class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-secondary fw-medium">IVA (Total)</span>
                      <span class="text-dark fw-bold">$ 0,00</span>        
                    </div>
                    <div :class="{ 'border-top border-2 pt-2 mt-2': true, 'd-flex justify-content-between align-items-center': true, 'opacity-50': isAjuste }">
                      <span class="text-dark fw-black fs-5">{{ isAjuste ? 'AJUSTE' : 'TOTAL' }}</span>   
                      <span class="text-primary fw-black fs-4">{{ isAjuste ? 'STOCK' : ('$' + Number(totalCarrito).toLocaleString(undefined, { minimumFractionDigits: 2 })) }}</span>  
                    </div>
                  </div>
                </div>
              </div>

              <!-- COLUMNA 2: ARTÍCULOS (2/3) -->
              <div class="col-lg-8 bg-white p-4 d-flex flex-column overflow-hidden">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-shrink-0">
                  <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                    2. ARTÍCULOS DEL PEDIDO
                  </h6>
                  <div class="text-muted small d-none d-lg-block">
                    <i class="bi bi-info-circle me-1"></i> Usa <kbd class="bg-light text-dark shadow-none border">↓</kbd><kbd class="bg-light text-dark shadow-none border border-start-0">↑</kbd> y <kbd class="bg-light text-dark shadow-none border">Enter</kbd> para buscar rápido
                  </div>
                </div>

                <!-- Buscador de Artículos Moderno -->
                <div class="position-relative mb-4 flex-shrink-0">     
                  <label class="form-label fw-semibold text-secondary small mb-1">Buscar Artículo</label>
                  <div class="input-group group-search-modern">
                    <input
                      ref="buscadorArticuloRef"
                      v-model="queryArticulo"
                      type="text"
                      class="form-control border-2 py-3 px-3 rounded-start-3 fs-6"
                      placeholder="Escriba para buscar un artículo..."     
                      @keydown.enter.prevent="agregarDesdeSearch"
                      @keydown.down.prevent="bajarSeleccion"
                      @keydown.up.prevent="subirSeleccion"                      @keydown.left.prevent="decrementarSeleccionado"
                      @keydown.right.prevent="incrementarSeleccionado"                      @keydown.esc="limpiarBusqueda"
                      @input="onQueryArticulo"
                      autocomplete="off"
                    />
                    <button v-if="queryArticulo" @click="limpiarBusqueda" class="btn btn-outline-secondary border-2 border-start-0 rounded-end-3 px-3" type="button">
                      <i class="bi bi-x fs-4"></i>
                    </button>
                    <span v-else class="input-group-text border-2 border-start-0 bg-white rounded-end-3 px-3 text-muted">
                      <i class="bi bi-search"></i>
                    </span>
                  </div>

                  <!-- Dropdown de resultados (Flotante) -->
                  <div v-if="resultadosBusqueda.length && queryArticulo" 
                       ref="dropdownResultadosRef"
                       class="articulo-dropdown-floating shadow-2xl border-0 rounded-4 animate-fade-in py-2 mt-2 custom-scrollbar">
                    <button
                      v-for="(res, i) in resultadosBusqueda"
                      :key="res.id"
                      :ref="el => { if (el) itemRefs[i] = el }"
                      type="button"
                      class="articulo-dropdown-item-modern d-flex align-items-center gap-3 py-3 px-4 border-bottom border-light"
                      :class="{ 'active': i === indexSeleccionado }"       
                      @click="seleccionarArticuloDeSearch(res)"
                    >
                      <div class="bg-light rounded-3 overflow-hidden d-flex align-items-center justify-content-center shadow-sm border" style="width: 56px; height: 56px; flex-shrink: 0;">    
                        <img v-if="res.url_imagen" :src="`${apiBaseUrl}/${res.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                        <i v-else class="bi bi-box-seam fs-4 text-primary"></i>
                      </div>
                      <div class="flex-grow-1 text-start">
                        <div class="fw-bold text-dark fs-6">{{ res.nombre }}</div>
                        <div class="text-muted small">
                          Stock: <strong :class="stockColor(res.stock_actual)">{{ res.stock_actual }}</strong> | Cód: {{ res.cod_barra || 'N/A' }}
                        </div>
                      </div>
                      <div class="d-flex align-items-center gap-2" @click.stop>
                        <input
                          v-model.number="res.cantidad_previa"
                          type="number"
                          class="form-control form-control-sm text-center fw-bold"
                          style="width: 70px; font-size: 1rem; color: #000; border: 2px solid #dee2e6; border-radius: 8px;"
                          min="1"
                          @click.stop
                        />
                        <div v-if="!isAjuste" class="text-end ms-2">
                          <div class="fw-black text-primary fs-5">${{ Number(res.precio_actual || 0).toLocaleString() }}</div>
                        </div>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Tabla de Carrito -->
                <div class="table-responsive flex-grow-1 custom-scrollbar overflow-y-auto mt-2" style="max-height: calc(100vh - 400px); min-height: 150px; border: 1px solid #f1f3f5; border-radius: 12px; background: #fafbfc;">  
                  <table class="table table-hover align-middle mb-0">      
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="(item, index) in articulosCarrito" :key="index" class="animate-row-in border-bottom bg-white">

                        <td class="py-2 ps-3" :style="{ width: isAjuste ? '80%' : '65%' }">
                          <div class="d-flex align-items-center gap-2">    
                            <div class="bg-primary-subtle rounded-3 overflow-hidden d-flex align-items-center justify-content-center border shadow-sm" style="width: 32px; height: 32px; flex-shrink: 0;">
                              <img v-if="item.imagen || item.url_imagen" :src="`${apiBaseUrl}/${item.imagen || item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                              <i v-else class="bi bi-basket2 text-primary" style="font-size: 0.8rem;"></i>
                            </div>
                            <div class="lh-sm min-w-0 flex-grow-1 py-1">
                              <div class="fw-bold text-dark small" style="word-break: break-word; line-height: 1.2;">{{ item.nombre }}</div>
                              <div v-if="!isAjuste" class="text-muted" style="font-size: 0.7rem;">${{ Number(item.precio_unitario).toLocaleString() }} / un.</div>
                            </div>
                          </div>
                        </td>
                      
                        <td class="text-center py-2 px-1" style="width: 12%;">
                          <input
                            :ref="el => { if (el) inputCantidadesRefs[index] = el }"
                            v-model.number="item.cantidad"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control text-center fw-bold px-1 hide-arrows minimalist-qty-input"
                            style="width: 70px; margin: 0 auto; border: 1px solid #ced4da; border-radius: 8px; color: #000; font-size: 0.9rem; height: 36px; padding: 0 !important;"
                            @input="actualizarTotalItem(index)"
                            @keydown.up.prevent="focusAnterior(index)"
                            @keydown.down.prevent="focusSiguiente(index)"
                            @keydown.left.prevent="cambiarCantidad(index, -1)"
                            @keydown.right.prevent="cambiarCantidad(index, 1)"
                          />
                        </td>
                      
                        <td v-if="!isAjuste" class="text-end py-2 fw-black text-dark px-1" style="width: 18%; font-size: 0.8rem;">
                          ${{ Number(item.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </td>
                      
                        <td class="pe-3 text-end" style="width: 5%;">      
                          <button type="button" @click="quitarFilaArticulo(index)" class="btn btn-link link-danger p-0 opacity-50 hover-opacity-100" title="Quitar artículo">
                            <i class="bi bi-x-circle" style="font-size: 1rem;"></i>      
                          </button>
                        </td>

                      </tr>
                    
                      <tr v-if="articulosCarrito.length === 0">
                        <td :colspan="isAjuste ? 3 : 4" class="text-center py-5">
                          <div class="container-empty-state opacity-50">   
                            <i class="bi bi-cart-x border rounded-circle p-4 fs-1 d-inline-block mb-3 text-muted"></i>
                            <h5 class="fw-bold text-muted">Aún no hay artículos {{ isAjuste ? 'agregados' : 'en la venta' }}</h5>
                            <p class="text-muted small">Busca y selecciona artículos arriba para agregarlos</p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Error de Stock -->
                <div v-if="errorStock" class="alert alert-danger border-0 rounded-3 mt-2 shadow-sm animate-shake d-flex align-items-center py-2 px-3 flex-shrink-0 mb-0" style="font-size: 0.85rem;">
                  <i class="bi bi-exclamation-triangle-fill fs-6 me-2"></i>
                  <div class="flex-grow-1">
                    <span class="fw-bold">Stock insuficiente:</span> {{ errorStock }}
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Footer Moderno -->
        <div class="modal-footer border-0 p-4 bg-white justify-content-end gap-2 flex-shrink-0">
          <button 
            @click="closeModal" 
            type="button" 
            class="btn btn-outline-secondary px-4 py-2 rounded-3 fw-bold"
            :disabled="isLoading"
          >
            CANCELAR
          </button>
          <button
            type="submit"
            class="btn btn-primary px-4 py-2 rounded-3 fw-bold d-flex align-items-center gap-2"
            :disabled="isLoading || !puedeGuardar"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span>{{ isLoading ? 'GUARDANDO...' : (isAjuste ? 'REGISTRAR AJUSTE' : (isEditing ? 'GUARDAR' : 'CREAR VENTA')) }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
  </Teleport>

  <QuickClientModal
    v-model="showQuickClientModal"
    :clientes-existentes="clientes"
    @confirm="handleQuickClientConfirm"
  />

  <QuickAssignTeamModal
    v-model="showQuickTeamModal"
    :cliente-id="form.id_cliente"
    :cliente-nombre="clienteSeleccionado?.nombre_cliente"
    :equipos="equipos"
    @confirm="handleQuickTeamConfirm"
  />
</template>

<script setup>
import { ref, computed, nextTick, watch, onUnmounted } from 'vue';
import { useToastStore } from '@/stores/toastStore';
import QuickClientModal from './QuickClientModal.vue';
import QuickAssignTeamModal from './QuickAssignTeamModal.vue';

const apiBaseUrl = import.meta.env.VITE_API_URL || 'http://localhost/Il-Calcio-Camp/api';

const props = defineProps({
  modelValue: Boolean,
  isEditing: Boolean,
  isLoading: Boolean,
  initialForm: Object,
  clientes: Array,
  equipos: Array,
  estadosVenta: Array,
  mediosCobro: Array,
  articulos: Array,
  idEstadoCerrada: Number,
  idCuentaCorriente: Number,
  simboloDia: String,
  isAjuste: Boolean
});

const emit = defineEmits(['update:modelValue', 'save', 'client-created', 'assign-team']);

const toastStore = useToastStore();
const form = ref({});
const montoEntregadoRef = ref(null);
const articulosCarrito = ref([]);

const showQuickClientModal = ref(false);
const showQuickTeamModal = ref(false);

const handleQuickClientConfirm = (cliente) => {
  if (cliente.isNew) {
    // Si es nuevo, emitir evento para que el padre lo maneje después
    emit('client-created', cliente);
  }
  
  // Asignar a la venta inmediatamente
  form.value.id_cliente = cliente.id;
  
  toastStore.showToast({
    message: `Cliente ${cliente.nombre_cliente} asignado.`,
    type: 'success'
  });
};

const handleQuickTeamConfirm = (equipo) => {
  // Asignar temporalmente al objeto del cliente en el frontend
  const cliente = props.clientes.find(c => c.id === form.value.id_cliente);
  if (cliente) {
    cliente.id_equipo = equipo.id;
    cliente.equipo_nombre = equipo.nombre;
  }
  
  // Asignar al formulario
  form.value.id_equipo = equipo.id;
  
  // Emitir para que el padre guarde en DB si desea
  emit('assign-team', { 
    id_cliente: form.value.id_cliente, 
    id_equipo: equipo.id 
  });
  
  showQuickTeamModal.value = false;
  toastStore.showToast({
    message: `Equipo ${equipo.nombre} asociado.`,
    type: 'success'
  });
};

const clienteSeleccionado = computed(() => {
  if (!form.value.id_cliente) return null;
  return props.clientes.find(c => c.id === form.value.id_cliente);
});

watch(() => form.value.id_cliente, (newVal) => {
  if (newVal) {
    const cliente = props.clientes.find(c => c.id === newVal);
    form.value.id_equipo = cliente?.id_equipo || null;
    form.value.tipo_vta = 0; // Si hay cliente, es "A cuenta"
  } else {
    form.value.id_equipo = null;
    // Si no hay cliente, solo es "A cuenta" (tipo 0) si el pedido está abierto
    form.value.tipo_vta = esAbierto.value ? 0 : 1;
  }
});

// Sincronizar form y carrito cuando se abre el modal
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    form.value = { ...props.initialForm };
    articulosCarrito.value = props.initialForm?.articulos ? [...props.initialForm.articulos] : [];
    
    if (!props.isEditing) {
      form.value.id_estado_venta = props.idEstadoCerrada;
      form.value.tipo_vta = 1; // Común
      if (!form.value.fecha) {
        form.value.fecha = new Date().toISOString().split('T')[0];
      }
    }
    
    // Si es cierre forzado, asegurar el foco
    if (form.value.forceCierre) {
      nextTick(() => {
        montoEntregadoRef.value?.focus();
      });
    }
  }
});

const errorStock = ref(null);
const queryArticulo = ref('');
const resultadosBusqueda = ref([]);
const indexSeleccionado = ref(-1);
const dropdownResultadosRef = ref(null);
const itemRefs = ref({});
const inputCantidadesRefs = ref({});

const buscadorArticuloRef = ref(null);

watch(indexSeleccionado, (newIndex) => {
  if (newIndex >= 0 && itemRefs.value[newIndex]) {
    itemRefs.value[newIndex].scrollIntoView({
      behavior: 'smooth',
      block: 'nearest'
    });
  }
});

watch(() => queryArticulo.value, () => {
  itemRefs.value = {};
});

// Asegurar que el objeto de refs se limpie cuando cambie el carrito
watch(() => articulosCarrito.value.length, () => {
  inputCantidadesRefs.value = {};
});

// Asegurar valores por defecto para nuevas ventas
if (!props.isEditing) {
  form.value.id_estado_venta = props.idEstadoCerrada;
  form.value.tipo_vta = 1; // Común
  if (!form.value.fecha) {
    form.value.fecha = new Date().toISOString().split('T')[0];
  }
}

watch(() => props.initialForm, (newVal) => {
  form.value = { ...newVal };
  articulosCarrito.value = newVal.articulos ? [...newVal.articulos] : [];
  
  if (props.modelValue && newVal.forceCierre) {
    nextTick(() => {
      montoEntregadoRef.value?.focus();
      montoEntregadoRef.value?.select();
    });
  }
}, { deep: true });

watch(() => props.modelValue, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : '';
  if (isOpen) {
    nextTick(() => {
      if (form.value.forceCierre) {
        montoEntregadoRef.value?.focus();
        montoEntregadoRef.value?.select();
      } else {
        // En cualquier otro caso (edición o nueva), foco en buscador
        buscadorArticuloRef.value?.focus();
      }
    });
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
});

const esCerrada = computed(() => form.value.id_estado_venta === props.idEstadoCerrada);
const esCuentaCorriente = computed(() => form.value.tipo_vta === 0);

const totalCarrito = computed(() =>
  articulosCarrito.value.reduce((acc, i) => acc + Number(i.total || 0), 0).toFixed(2)
);

const esAbierto = computed(() => form.value.id_estado_venta !== props.idEstadoCerrada);

const toggleEstadoVenta = (abierto) => {
  if (abierto) {
    // Si el usuario marca "Dejar pedido abierto", buscamos un ID de estado que no sea el de "Cerrada"
    const estadoAbierto = props.estadosVenta.find(ev => ev.id !== props.idEstadoCerrada);
    form.value.id_estado_venta = estadoAbierto ? estadoAbierto.id : null;
    form.value.tipo_vta = 0; // A cuenta obligatoriamente
  } else {
    // Si desmarca, vuelve a estado cerrada
    form.value.id_estado_venta = props.idEstadoCerrada;
    // Si no tiene cliente, vuelve a tipo_vta = 1 (Común), si tiene cliente sigue siendo 0 (A cuenta)
    form.value.tipo_vta = form.value.id_cliente ? 0 : 1;
  }
};

const cambiarEstado = (id) => {
  if (id !== props.idEstadoCerrada) {
    // Si se selecciona abierta, forzar a "A Cuenta"
    form.value.tipo_vta = 0; 
  }
  form.value.id_estado_venta = id;
};

const cambiarTipoVta = (tipo) => {
  // Ya no es necesario restringir aquí porque la validación de puedeGuardar
  // se encarga de asegurar que si está abierto, tenga cliente.
  form.value.tipo_vta = tipo;
};

const puedeGuardar = computed(() => {
  if (props.isEditing) {
    if (!esAbierto.value && !form.value.id_medio_cobro) return false;
    if (esCuentaCorriente.value && !form.value.id_cliente) return false;
    return !!form.value.fecha && !!form.value.id_estado_venta;
  }
  const cabeceraOK = !!form.value.fecha && !!form.value.id_estado_venta;     
  const itemsOK = articulosCarrito.value.length > 0 &&
    articulosCarrito.value.every(i => i.id_articulo && Number(i.cantidad) > 0);
  const stockOK = !errorStock.value;
  const pagoOK = esAbierto.value || !!form.value.id_medio_cobro;
  
  // Cliente obligatorio si está abierto, sino es opcional
  const clienteOK = esAbierto.value ? !!form.value.id_cliente : true;
  
  return cabeceraOK && itemsOK && stockOK && pagoOK && clienteOK;
});

const onQueryArticulo = () => {
  indexSeleccionado.value = -1;
  if (!queryArticulo.value.trim()) {
    resultadosBusqueda.value = [];
    return;
  }
  const q = queryArticulo.value.toLowerCase();
  resultadosBusqueda.value = props.articulos
    .filter(a =>
      a.nombre?.toLowerCase().includes(q) ||
      a.categoria_descripcion?.toLowerCase().includes(q) ||
      a.cod_barra?.toLowerCase().includes(q)
    )
    .map(a => ({ ...a, cantidad_previa: 1 })); // Quitamos el .slice(0, 8) para mostrar todos
};

const seleccionarArticuloDeSearch = (art) => {
  const stockDisponible = Number(art.stock_actual || 0);
  
  // Si un producto no tiene stock disponible (stock = 0), no debe poder agregarse a la venta.
  if (stockDisponible <= 0) {
    toastStore.showToast({
      message: `El artículo "${art.nombre}" no tiene stock disponible.`,
      type: 'warning'
    });
    return;
  }

  let cantidadAAgregar = Number(art.cantidad_previa) || 1;
  const existente = articulosCarrito.value.find(i => Number(i.id_articulo) === Number(art.id));
  
  if (existente) {
    const totalDespuesDeAgregar = Number((Number(existente.cantidad) + cantidadAAgregar).toFixed(2));
    
    // Si la suma supera el stock, ajustamos para que quede el máximo disponible
    if (totalDespuesDeAgregar > stockDisponible) {
      const cantidadRealmenteAgregada = Number((stockDisponible - existente.cantidad).toFixed(2));
      
      if (cantidadRealmenteAgregada > 0) {
        existente.cantidad = stockDisponible;
        existente.total = (existente.cantidad * existente.precio_unitario).toFixed(2);
        toastStore.showToast({
          message: `Se ajustó la cantidad al máximo disponible (${stockDisponible}) para "${art.nombre}".`,
          type: 'warning'
        });
      } else {
        toastStore.showToast({
          message: `Ya tienes todo el stock disponible en el carrito para "${art.nombre}".`,
          type: 'warning'
        });
      }
      limpiarBusqueda();
      return;
    }
    
    existente.cantidad = totalDespuesDeAgregar;
    existente.total = (existente.cantidad * existente.precio_unitario).toFixed(2);
  } else {
    // Si la cantidad inicial es mayor al stock, capamos al máximo disponible
    if (cantidadAAgregar > stockDisponible) {
      cantidadAAgregar = stockDisponible;
      toastStore.showToast({
        message: `Cantidad ajustada al stock disponible (${stockDisponible}) para "${art.nombre}".`,
        type: 'warning'
      });
    }

    // Agregar al principio del array (Unshift) para que el último producto aparezca arriba
    articulosCarrito.value.unshift({
      id_articulo: art.id,
      nombre: art.nombre,
      url_imagen: art.url_imagen,
      stock_actual: art.stock_actual,
      cantidad: cantidadAAgregar,
      precio_unitario: Number(art.precio_actual || 0),
      total: (cantidadAAgregar * Number(art.precio_actual || 0)).toFixed(2),
    });
  }
  // Resetear cantidad previa para la próxima búsqueda
  art.cantidad_previa = 1;
  limpiarBusqueda();
  validarStockMasivo();
  nextTick(() => buscadorArticuloRef.value?.focus());
};

const agregarDesdeSearch = () => {
  const lista = resultadosBusqueda.value;
  if (lista.length === 0) return;
  const seleccionado = indexSeleccionado.value >= 0 ? lista[indexSeleccionado.value] : lista[0];
  seleccionarArticuloDeSearch(seleccionado);
};

const bajarSeleccion = () => { 
  if (resultadosBusqueda.value.length) {
    indexSeleccionado.value = (indexSeleccionado.value + 1) % resultadosBusqueda.value.length; 
  } else if (articulosCarrito.value.length > 0) {
    // Si no hay resultados de búsqueda pero hay artículos en el carrito, foco al primero
    nextTick(() => {
      inputCantidadesRefs.value[0]?.focus();
      inputCantidadesRefs.value[0]?.select();
    });
  }
};

const focusAnterior = (index) => {
  if (index === 0) {
    buscadorArticuloRef.value?.focus();
    buscadorArticuloRef.value?.select();
  } else {
    inputCantidadesRefs.value[index - 1]?.focus();
    inputCantidadesRefs.value[index - 1]?.select();
  }
};

const focusSiguiente = (index) => {
  if (index < articulosCarrito.value.length - 1) {
    inputCantidadesRefs.value[index + 1]?.focus();
    inputCantidadesRefs.value[index + 1]?.select();
  }
};

const subirSeleccion = () => { if (resultadosBusqueda.value.length) indexSeleccionado.value = (indexSeleccionado.value - 1 + resultadosBusqueda.value.length) % resultadosBusqueda.value.length; };

const incrementarSeleccionado = () => {
  if (resultadosBusqueda.value.length && indexSeleccionado.value >= 0) {
    const art = resultadosBusqueda.value[indexSeleccionado.value];
    art.cantidad_previa = (Number(art.cantidad_previa) || 1) + 1;
  }
};

const decrementarSeleccionado = () => {
  if (resultadosBusqueda.value.length && indexSeleccionado.value >= 0) {
    const art = resultadosBusqueda.value[indexSeleccionado.value];
    art.cantidad_previa = Math.max(1, (Number(art.cantidad_previa) || 1) - 1);
  }
};

const limpiarBusqueda = () => { queryArticulo.value = ''; resultadosBusqueda.value = []; indexSeleccionado.value = -1; };

const stockColor = (stock) => {
  const s = Number(stock);
  if (s <= 0) return 'text-danger';
  if (s <= 5) return 'text-warning';
  return 'text-success';
};

const cambiarCantidad = (index, delta) => {
  const item = articulosCarrito.value[index];
  const stockDisponible = Number(item.stock_actual || 0);
  let nueva = Number((Number(item.cantidad) + delta).toFixed(2));
  
  // No permitir cantidad menor o igual a 0
  if (nueva <= 0) return;

  // Si con el delta superamos el stock, capamos al stock disponible
  if (nueva > stockDisponible) {
    nueva = stockDisponible;
    toastStore.showToast({
      message: `Solo hay ${stockDisponible} unidades disponibles para "${item.nombre}".`,
      type: 'warning'
    });
  }

  item.cantidad = nueva;
  actualizarTotalItem(index);
};

const actualizarTotalItem = (index) => {
  const item = articulosCarrito.value[index];
  item.total = ((Number(item.cantidad) || 0) * (Number(item.precio_unitario) || 0)).toFixed(2);
  validarStockMasivo();
};

const quitarFilaArticulo = (index) => { articulosCarrito.value.splice(index, 1); validarStockMasivo(); };

const validarStockMasivo = () => {
  errorStock.value = null;
  for (const item of articulosCarrito.value) {
    if (item.id_articulo && Number(item.cantidad) > Number(item.stock_actual ?? 0)) {
      errorStock.value = "Cantidad mayor al stock disponible para \"" + item.nombre + "\". Disponible: " + (item.stock_actual ?? 0);
      return false;
    }
  }
  return true;
};

const handleSave = () => emit('save', { venta: form.value, articulos: articulosCarrito.value });
const closeModal = () => emit('update:modelValue', false);
</script>

<style scoped>
.modal-fullscreen-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.4); /* Gris semitransparente */
  backdrop-filter: blur(4px);
  z-index: 99999; /* Asegurar que tape el header */
  display: flex;
  align-items: flex-start; /* Cambio: alinear al inicio para permitir scroll natural */
  justify-content: center;
  padding: 2rem 0; /* Espacio arriba y abajo */
  margin: 0;
  overflow-y: auto; /* Cambio: permitir scroll en el contenedor principal */
}

.modal-fullscreen-content {
  width: 90vw; /* Cambio: un poco más ancho */
  min-height: 90vh; /* Cambio: altura mínima mayor */
  background: white;
  display: flex;
  flex-direction: column;
  border-radius: 1.5rem; /* Bordes redondeados para que se note que flota */
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  margin-bottom: 2rem; /* Espacio extra al final */
}

/* EN RESOLUCIONES GRANDES */
@media (min-width: 1200px) {
  .modal-fullscreen-container {
    padding: 3rem 0;
  }
  
  .modal-fullscreen-content {
    width: 85vw;
    min-height: 85vh;
    border-radius: 1.5rem;
  }
}

/* REST OF STYLES... */
.fw-black { font-weight: 900; }
.letter-spacing-1 { letter-spacing: 1px; }
.shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }

.bg-light-subtle { background-color: #f8fbff; }
.group-search-modern .form-control:focus { border-color: #0d6efd; box-shadow: none; }

.articulo-dropdown-floating {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  z-index: 1200;
  max-height: 400px;
  overflow-y: auto;
  border: 1px solid #dee2e6;
}

.articulo-dropdown-item-modern {
  width: 100%;
  background: none;
  border: none;
  transition: all 0.2s;
}
.articulo-dropdown-item-modern:hover, .articulo-dropdown-item-modern.active {
  background: #f1f7ff;
}
.articulo-dropdown-item-modern.active { border-left: 4px solid #0d6efd; }    

.btn-qty-circle {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #dee2e6;
  background: #fff;
  transition: all 0.2s;
}
.btn-qty-circle:hover { background: #e9ecef; }

.animate-fade-in { animation: fadeIn 0.2s ease-out; }
.animate-row-in { animation: slideIn 0.3s ease-out; }

@keyframes fadeIn { 
  from { opacity: 0; } 
  to { opacity: 1; } 
}

@keyframes slideIn { 
  from { opacity: 0; transform: translateX(10px); } 
  to { opacity: 1; transform: translateX(0); } 
}

.active-scale:active { transform: scale(0.98); }
.transition-transform { transition: transform 0.1s; }

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #999;
}
</style>
