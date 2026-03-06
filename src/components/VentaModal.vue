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
            {{ isEditing ? `Venta #${form.id}` : 'Nueva Venta' }}
          </h5>
        </div>
        <button type="button" class="btn-close shadow-none p-2" @click="closeModal"></button>
      </div>

      <form @submit.prevent="handleSave" class="flex-grow-1 d-flex flex-column overflow-hidden">
        <div class="modal-body p-0 flex-grow-1 overflow-hidden">
          <div class="container-fluid h-100 p-0">
            <div class="row g-0 h-100">
              <!-- COLUMNA 1: CONFIGURACIÓN (1/4) -->
              <div class="col-lg-3 bg-light-subtle border-end p-4 d-flex flex-column overflow-y-auto custom-scrollbar">
                <div class="section-container mb-4">
                  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                      1. DATOS DEL PEDIDO
                    </h6>
                  </div>

                  <div class="row g-3">
                    <div class="col-12" v-if="isEditing">
                      <label class="form-label fw-semibold text-secondary small mb-1">Fecha <span class="text-danger">*</span></label>
                      <input v-model="form.fecha" type="date" class="form-control border-2 shadow-sm py-2 px-3 rounded-3 fw-bold" required />
                    </div>

                    <div class="col-12">
                      <label class="form-label fw-semibold text-secondary small mb-1">Estado de la Venta</label>
                      <div class="d-flex gap-2">
                        <button 
                          v-for="ev in estadosVenta" 
                          :key="ev.id"
                          type="button"
                          class="btn flex-fill py-2 fw-bold border-2 animate-fade-in"
                          :class="form.id_estado_venta === ev.id ? (ev.id === idEstadoCerrada ? 'btn-success border-success' : 'btn-warning border-warning') : 'btn-outline-secondary'"
                          @click="cambiarEstado(ev.id)"
                        >
                          <i :class="ev.id === idEstadoCerrada ? 'bi bi-check-circle-fill' : 'bi bi-clock-history'" class="me-2"></i>
                          {{ ev.descripcion }}
                        </button>
                      </div>
                    </div>

                    <div class="col-12 mt-2">
                      <label class="form-label fw-semibold text-secondary small mb-1">Tipo de Venta</label>
                      <div class="d-flex gap-2">
                        <button 
                          type="button"
                          class="btn flex-fill py-2 fw-bold border-2"
                          :class="form.tipo_vta === 1 ? 'btn-primary border-primary' : 'btn-outline-secondary'"
                          @click="cambiarTipoVta(1)"
                        >
                          <i class="bi bi-shop me-2"></i>Común
                        </button>
                        <button 
                          type="button"
                          class="btn flex-fill py-2 fw-bold border-2"
                          :class="form.tipo_vta === 0 ? 'btn-primary border-primary' : 'btn-outline-secondary'"
                          @click="cambiarTipoVta(0)"
                        >
                          <i class="bi bi-person-lines-fill me-2"></i>A Cuenta
                        </button>
                      </div>
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
                      <span class="text-secondary fw-medium">Subtotal</span>
                      <span class="text-dark fw-bold">${{ Number(totalCarrito).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-secondary fw-medium">IVA (Total)</span>
                      <span class="text-dark fw-bold">$ 0,00</span>        
                    </div>
                    <div class="border-top border-2 pt-2 mt-2 d-flex justify-content-between align-items-center">
                      <span class="text-dark fw-black fs-5">TOTAL</span>   
                      <span class="text-primary fw-black fs-4">${{ Number(totalCarrito).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>  
                    </div>
                  </div>
                </div>
              </div>

              <!-- COLUMNA 2: CLIENTE Y PAGO (1/4) -->
              <div class="col-lg-3 bg-white border-end p-4 d-flex flex-column overflow-y-auto custom-scrollbar">
                <div class="section-container mb-4">
                  <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                    <h6 class="fw-bold mb-0 text-dark text-uppercase letter-spacing-1" style="font-size: 0.85rem;">
                      ASIGNACIÓN Y PAGO
                    </h6>
                  </div>

                  <div class="row g-3">
                    <div class="col-12" v-if="esAbierto || form.tipo_vta === 0">
                      <label class="form-label fw-semibold text-secondary small mb-1">Cliente</label>
                      <select v-model.number="form.id_cliente" class="form-select border-2 shadow-sm py-2 px-3 rounded-3" :required="form.tipo_vta === 0">
                        <option :value="null">Sin cliente (Venta Mostrador)</option>
                        <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_cliente }}</option>
                      </select>
                    </div>

                    <div class="col-12" v-if="esAbierto || form.tipo_vta === 0">
                      <label class="form-label fw-semibold text-secondary small mb-1">Mesa / Equipo</label>
                      <select v-model.number="form.id_equipo" class="form-select border-2 shadow-sm py-2 px-3 rounded-3">
                        <option :value="null">Sin mesa</option>
                        <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                      </select>
                    </div>

                    <!-- SECCIÓN PAGO (Solo si es cerrada) -->
                    <div v-if="!esAbierto" class="col-12 mt-2 pt-2 animate-fade-in">
                      <div>
                        <label class="form-label fw-semibold text-secondary small mb-1">Medio de Pago <span class="text-danger">*</span></label>
                        <select v-model.number="form.id_medio_cobro" class="form-select border-2 shadow-sm py-2 rounded-3" :required="!esAbierto">
                          <option :value="null" disabled>-- Seleccionar --</option>
                          <option v-for="m in mediosCobro" :key="m.id" :value="m.id">{{ m.descripcion }}</option>
                        </select>
                      </div>

                      <div class="mt-3">
                        <label class="form-label fw-semibold text-secondary small mb-1">Monto Entregado</label>
                        <div class="input-group">
                          <span class="input-group-text bg-white border-2 border-end-0">$</span>
                          <input 
                            ref="montoEntregadoRef"
                            v-model.number="form.monto_cobrado" 
                            type="number" 
                            step="0.01" 
                            class="form-control border-2 border-start-0 py-2 fw-bold" 
                            :placeholder="totalCarrito" 
                          />
                        </div>
                      </div>
                    </div>

                    <div class="col-12 mt-3 pt-3 border-top">
                      <label class="form-label fw-semibold text-secondary small mb-1">Observaciones</label>
                      <textarea v-model.trim="form.descripcion_cliente" class="form-control border-2 shadow-sm rounded-3" rows="3" placeholder="Notas internas..."></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- COLUMNA 3: ARTÍCULOS (2/4) -->
              <div class="col-lg-6 bg-white p-4 d-flex flex-column overflow-hidden">
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
                      <div class="bg-light rounded-3 p-2 text-primary">    
                        <i class="bi bi-box-seam fs-4"></i>
                      </div>
                      <div class="flex-grow-1 text-start">
                        <div class="fw-bold text-dark fs-6">{{ res.nombre }}</div>
                        <div class="text-muted small">
                          Stock: <strong :class="stockColor(res.stock_actual)">{{ res.stock_actual }}</strong> | Cat: {{ res.categoria_descripcion || 'N/A' }}
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
                        <div class="text-end ms-2">
                          <div class="fw-black text-primary fs-5">${{ Number(res.precio_actual || 0).toLocaleString() }}</div>
                        </div>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Tabla de Carrito -->
                <div class="table-responsive flex-grow-1 custom-scrollbar overflow-y-auto" style="min-height: 200px;">  
                  <table class="table table-hover align-middle mb-0">      
                    <tbody>
                      <tr v-for="(item, index) in articulosCarrito" :key="index" class="animate-row-in">
                        <td class="py-3" style="width: 50%;">
                          <div class="d-flex align-items-center gap-3">    
                            <div class="bg-primary-subtle rounded-circle p-2 text-primary" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                              <i class="bi bi-basket2"></i>
                            </div>
                            <div>
                              <div class="fw-bold text-dark">{{ item.nombre }}</div>
                              <div class="text-muted small">${{ Number(item.precio_unitario).toLocaleString() }} / unidad</div>
                            </div>
                          </div>
                        </td>
                        <td class="text-center py-3">
                          <input
                            v-model.number="item.cantidad"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="form-control form-control-sm text-center fw-bold"
                            style="width: 80px; margin: 0 auto; border: 2px solid #dee2e6; border-radius: 8px; color: #000; font-size: 1rem;"
                            @input="actualizarTotalItem(index)"
                          />
                        </td>
                        <td class="text-end py-3 fw-black text-dark fs-6" style="width: 25%;">
                          ${{ Number(item.total).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                        </td>
                        <td class="pe-3 text-end" style="width: 5%;">      
                          <button type="button" @click="quitarFilaArticulo(index)" class="btn btn-link link-danger p-0 opacity-75">
                            <i class="bi bi-x-circle-fill fs-5"></i>       
                          </button>
                        </td>
                      </tr>

                      <!-- Empty State (Imagen) -->
                      <tr v-if="articulosCarrito.length === 0">
                        <td colspan="4" class="text-center py-5">
                          <div class="container-empty-state opacity-50">   
                            <i class="bi bi-cart-x border rounded-circle p-4 fs-1 d-inline-block mb-3"></i>
                            <h5 class="fw-bold text-muted">Aún no hay artículos en la venta</h5>
                            <p class="text-muted small">Busca y selecciona artículos arriba para agregarlos</p>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Error de Stock -->
                <div v-if="errorStock" class="alert alert-danger border-0 rounded-4 mt-4 shadow-sm animate-shake d-flex align-items-center py-3 flex-shrink-0">
                  <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                  <div>
                    <h6 class="mb-0 fw-bold">Stock insuficiente</h6>       
                    <small>{{ errorStock }}</small>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- Footer Moderno -->
        <div class="modal-footer border-0 p-4 bg-white justify-content-between flex-shrink-0">
          <button @click="closeModal" type="button" class="btn btn-link text-secondary text-decoration-none fw-bold px-4">CANCELAR</button>
          <button
            type="submit"
            class="btn btn-primary-modern btn-lg px-5 py-3 shadow-lg rounded-pill d-flex align-items-center gap-3 border-0 transition-transform active-scale"
            :disabled="isLoading || !puedeGuardar"
          >
            <div v-if="isLoading" class="spinner-border spinner-border-sm"></div>
            <i v-else class="bi bi-check-lg fs-4 fw-bold"></i>
            <span class="fw-black text-uppercase letter-spacing-1" style="font-size: 0.9rem;">{{ isEditing ? 'Guardar Cambios' : 'Confirmar Venta' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, nextTick, watch, onUnmounted } from 'vue';
import { useToastStore } from '@/stores/toastStore';

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
  simboloDia: String
});

const emit = defineEmits(['update:modelValue', 'save']);

const toastStore = useToastStore();
const form = ref({});
const montoEntregadoRef = ref(null);
const articulosCarrito = ref([]);

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

const cambiarEstado = (id) => {
  if (id !== props.idEstadoCerrada) {
    // Si se selecciona abierta, forzar a "A Cuenta"
    form.value.tipo_vta = 0; 
  }
  form.value.id_estado_venta = id;
};

const cambiarTipoVta = (tipo) => {
  if (tipo === 1 && esAbierto.value) {
    toastStore.showToast({ 
      message: 'Las ventas abiertas deben estar asociadas a un cliente o un equipo.', 
      type: 'warning' 
    });
    return;
  }
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
  const clienteOK = !esCuentaCorriente.value || !!form.value.id_cliente;
  
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
  const cantidadAAgregar = Number(art.cantidad_previa) || 1;
  const existente = articulosCarrito.value.find(i => Number(i.id_articulo) === Number(art.id));
  if (existente) {
    existente.cantidad = Number((Number(existente.cantidad) + cantidadAAgregar).toFixed(2));
    existente.total = (existente.cantidad * existente.precio_unitario).toFixed(2);
  } else {
    articulosCarrito.value.push({
      id_articulo: art.id,
      nombre: art.nombre,
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

const bajarSeleccion = () => { if (resultadosBusqueda.value.length) indexSeleccionado.value = (indexSeleccionado.value + 1) % resultadosBusqueda.value.length; };
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
  const nueva = Number((Number(item.cantidad) + delta).toFixed(2));
  if (nueva > 0) { item.cantidad = nueva; actualizarTotalItem(index); }      
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
      errorStock.value = "Stock insuficiente para \"" + item.nombre + "\". Disponible: " + (item.stock_actual ?? 0);
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
