<template>
  <Teleport to="body">
  <div
    v-if="modelValue"
    class="modal-fullscreen-container animate-fade-in"
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
                      <label class="form-label fw-semibold text-secondary small mb-1">Estado de la venta</label>
                      <div class="d-flex gap-1 w-100">
                        <button
                          type="button"
                          @click="cambiarEstado(VENTA_STATES.CERRADA)"
                          class="btn btn-sm flex-fill py-2 fw-bold"
                          :class="esCerrada ? 'btn-dark text-white' : 'btn-outline-secondary'"
                          style="font-size: 0.72rem;"
                          title="Finalizar y cobrar"
                        >
                          <i class="bi bi-check2-circle me-1"></i>Cerrar
                        </button>
                        <button
                          type="button"
                          @click="cambiarEstado(VENTA_STATES.ABIERTA)"
                          class="btn btn-sm flex-fill py-2 fw-bold"
                          :class="esAbierta ? 'btn-primary text-white' : 'btn-outline-primary'"
                          style="font-size: 0.72rem;"
                          title="Dejar abierta (requiere cliente)"
                        >
                          <i class="bi bi-folder2-open me-1"></i>Abierta
                        </button>
                        <button
                          type="button"
                          @click="cambiarEstado(VENTA_STATES.PAUSA)"
                          class="btn btn-sm flex-fill py-2 fw-bold"
                          :class="esPausa ? 'btn-warning text-dark' : 'btn-outline-warning'"
                          style="font-size: 0.72rem;"
                          title="Pausar sin cobrar ni descontar stock"
                        >
                          <i class="bi bi-pause-circle me-1"></i>Pausar
                        </button>
                      </div>
                    </div>

                    <!-- Cliente y Observaciones -->
                    <div class="col-12 mt-2 pt-2 border-top">
                      <div class="row g-2">
                        <div class="col-12" v-if="!isAjuste">
                          <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold text-secondary small mb-0">
                              Cliente 
                              <span v-if="esAbierta || (facturar && tipoFactura === 'A')" class="text-danger">*</span>
                            </label>
                            <button 
                              type="button" 
                              @click="openNewQuickClient" 
                              class="btn btn-link link-primary p-0 small text-decoration-none fw-bold" 
                              style="font-size: 0.65rem;"
                            >
                              + Nuevo
                            </button>
                          </div>
                          <div class="d-flex align-items-center gap-2">
                            <FuzzySearch
                              v-model="queryCliente"
                              :data="activeClients"
                              :keys="['nombre_cliente', 'dni_cliente']"
                              placeholder="Buscar cliente..."
                              @selected="onClienteSelected"
                              class="fuzzy-search-sm flex-grow-1"
                            >
                              <template #default="{ item }">
                                <div class="d-flex flex-column py-1">
                                  <span class="fw-bold small">{{ item.nombre_cliente }}</span>
                                  <div class="d-flex align-items-center gap-1">
                                    <span v-if="item.dni_cliente" class="text-muted" style="font-size: 0.7rem;">DNI: {{ item.dni_cliente }}</span>
                                    <span v-else-if="Number(item.id_condicion_iva_receptor) === 1" class="text-danger fw-bold" style="font-size: 0.65rem;">
                                      <i class="bi bi-exclamation-triangle-fill"></i> DNI REQUERIDO *
                                    </span>
                                    <span v-if="Number(item.id_condicion_iva_receptor) === 1" class="badge bg-info-subtle text-info border border-info-subtle py-0 px-1" style="font-size: 0.6rem;">RI</span>
                                  </div>
                                </div>
                              </template>
                            </FuzzySearch>
                            <button 
                              v-if="form.id_cliente"
                              type="button" 
                              class="btn btn-outline-primary p-0 rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                              style="width: 32px; height: 32px;"
                              @click="openEditQuickClient"
                              title="Editar cliente"
                            >
                              <i class="bi bi-pencil-fill" style="font-size: 0.9rem;"></i>
                            </button>
                          </div>
                          <input type="hidden" v-model="form.id_cliente" :required="esAbierta">
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
                    <!-- Checkbox Facturación -->
                    <div v-if="!isAjuste && esCerrada" class="form-check mb-2">
                      <input 
                        id="chkFacturar" 
                        v-model="facturar" 
                        type="checkbox" 
                        class="form-check-input cursor-pointer" 
                        style="width: 1.5em; height: 1.5em;"
                        :disabled="!puedeFacturar || (isEditing && !!form.estado_factura)"
                        :title="isEditing && !!form.estado_factura ? 'Esta venta ya fue procesada por AFIP' : mensajeFacturacion"
                      />
                      <label class="form-check-label fw-semibold text-secondary small ms-2 cursor-pointer" for="chkFacturar" :class="{ 'opacity-50': !puedeFacturar || (isEditing && !!form.estado_factura) }">
                        Emitir factura AFIP {{ isEditing && !!form.estado_factura ? '(Procesada)' : '' }}
                      </label>
                    </div>

                    <!-- Selector de Tipo de Factura -->
                    <div v-if="facturar && !isAjuste && esCerrada" class="mb-3 pb-3 border-bottom animate-fade-in">
                      <div class="d-flex gap-2">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" v-model="tipoFactura" value="B" id="radFacturaB">
                          <label class="form-check-label small fw-bold text-dark" for="radFacturaB">Factura B</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" v-model="tipoFactura" value="A" id="radFacturaA">
                          <label class="form-check-label small fw-bold text-dark" for="radFacturaA">Factura A</label>
                        </div>
                      </div>
                      <div v-if="tipoFactura === 'A'" class="alert alert-info py-1 px-2 mt-2 mb-0" style="font-size: 0.7rem;">
                        <i class="bi bi-info-circle me-1"></i> Requiere que el cliente sea <strong>Responsable Inscripto</strong> con <strong>CUIT (11 dígitos)</strong>.
                      </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-secondary fw-medium">{{ isAjuste ? 'Items a ajustar' : 'Subtotal' }}</span>
                      <span class="text-dark fw-bold">{{ isAjuste ? articulosCarrito.length : ('$' + formatMoney(subtotal.toFixed(2))) }}</span>
                    </div>
    <div v-if="!isAjuste" class="d-flex justify-content-between align-items-center mb-2">
      <span class="text-secondary fw-medium">IVA (21%)</span>
      <span class="text-dark fw-bold">${{ formatMoney(totalIVA) }}</span>        
    </div>
    <div :class="{ 'border-top border-2 pt-2 mt-2': true, 'd-flex justify-content-between align-items-center': true, 'opacity-50': isAjuste }">
      <span class="text-dark fw-black fs-5">{{ isAjuste ? 'AJUSTE' : 'TOTAL' }}</span>   
      <span class="text-primary fw-black fs-4">{{ isAjuste ? 'STOCK' : ('$' + formatMoney(totalCarrito)) }}</span>  
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

                <!-- Advertencias de Stock Insuficiente -->
                <div v-if="advertenciasStock.length > 0 && !isAjuste" class="rounded-3 py-2 px-3 mb-3 flex-shrink-0 border border-warning-subtle" style="font-size: 0.82rem; background: #fffdf0;">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <span class="fw-bold text-warning-emphasis">Stock insuficiente.</span>
                  </div>
                  <ul class="mb-0 ps-3" style="list-style: disc;">
                    <li v-for="item in advertenciasStock" :key="item.id_articulo" class="text-warning-emphasis">
                      <strong>{{ item.nombre }}</strong>: se piden {{ item.cantidad }}, hay {{ item.stock_actual }} en stock
                    </li>
                  </ul>
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
                          <div class="fw-black text-primary fs-5">${{ formatMoney(res.precio_actual || 0) }}</div>
                          <div v-if="facturar" class="text-muted small" style="font-size: 0.7rem;">IVA Incl. ${{ formatMoney((Number(res.precio_actual || 0) * (0.21 / 1.21)).toFixed(2)) }}</div>
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

                        <td class="py-2 ps-3" :style="{ width: isAjuste ? '80%' : '74%' }">
                          <div class="d-flex align-items-center gap-2">    
                            <div class="bg-primary-subtle rounded-3 overflow-hidden d-flex align-items-center justify-content-center border shadow-sm" style="width: 32px; height: 32px; flex-shrink: 0;">
                              <img v-if="item.imagen || item.url_imagen" :src="`${apiBaseUrl}/${item.imagen || item.url_imagen}`" class="w-100 h-100 object-fit-cover" />
                              <i v-else class="bi bi-basket2 text-primary" style="font-size: 0.8rem;"></i>
                            </div>
                            <div class="lh-sm min-w-0 flex-grow-1 py-1">
                              <div class="fw-bold text-dark small" style="word-break: break-word; line-height: 1.2;">{{ item.nombre }}</div>
                              <div v-if="!isAjuste" class="text-muted" style="font-size: 0.7rem;">${{ formatMoney(item.precio_unitario) }} / un.</div>
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
                      
                        <td v-if="!isAjuste" class="text-end py-2 px-1" style="width: 26%;">
                          <div class="text-end">
                            <div class="fw-black text-dark" style="font-size: 0.85rem;">${{ formatMoney(item.total) }}</div>
                            <div v-if="item.iva" class="text-muted small" style="font-size: 0.7rem;">IVA ${{ formatMoney((Number(item.total) * (0.21 / 1.21)).toFixed(2)) }}</div>
                          </div>
                        </td>
                      
                        <td class="pe-3 text-end" style="width: 5%;">      
                          <button type="button" @click="quitarFilaArticulo(index)" class="btn btn-link link-danger p-0 opacity-50 hover-opacity-100" title="Quitar artículo">
                            <i class="bi bi-x-circle" style="font-size: 1rem;"></i>      
                          </button>
                        </td>

                      </tr>
                    
                      <tr v-if="articulosCarrito.length === 0">
                        <td :colspan="isAjuste ? 3 : 3" class="text-center py-5">
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
            :class="{ 'opacity-50 cursor-not-allowed': !puedeGuardar }"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span>{{ isLoading ? 'GUARDANDO...' : (isAjuste ? 'REGISTRAR AJUSTE' : (esPausa ? 'PAUSAR VENTA' : (isEditing ? 'GUARDAR' : 'CREAR VENTA'))) }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
  </Teleport>

  <QuickClientModal
    v-model="showQuickClientModal"
    :clientes-existentes="activeClients"
    :condiciones-iva="condicionesIva"
    :initial-data="quickClientData"
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
import { formatMoney } from '@/utils/formatters';
import { resolverClienteRapido } from '@/utils/clienteRapido';
import { VENTA_STATES } from '@/constants/states';
import QuickClientModal from '@/components/venta/QuickClientModal.vue';
import QuickAssignTeamModal from '@/components/venta/QuickAssignTeamModal.vue';
import FuzzySearch from '@/components/FuzzySearch.vue';
import facturaService from '@/services/comercial/facturaService';

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
  condicionesIva: Array,
  idEstadoCerrada: Number,
  idEstadoPausa: Number,
  idEstadoAbierta: Number,
  idCuentaCorriente: Number,
  simboloDia: String,
  isAjuste: Boolean
});

const emit = defineEmits(['update:modelValue', 'save', 'client-created', 'assign-team']);

const toastStore = useToastStore();
const form = ref({});
const montoEntregadoRef = ref(null);
const articulosCarrito = ref([]);
const queryCliente = ref('');
const facturar = ref(false);
const tipoFactura = ref('B');

const onClienteSelected = (cliente) => {
  form.value.id_cliente = cliente?.id || null;
  queryCliente.value = cliente?.nombre_cliente || '';
};

const activeClients = computed(() => props.clientes || []);

const clienteSeleccionado = computed(() => {
  if (!form.value.id_cliente) return null;
  return activeClients.value.find(c => c.id === form.value.id_cliente);
});

// Nueva validación para Factura A (Requisitos AFIP)
const validacionFacturaA = computed(() => {
  if (!facturar.value || tipoFactura.value !== 'A') return { valid: true };
  
  if (!form.value.id_cliente) {
    return { valid: false, message: 'Para Factura A se requiere un cliente seleccionado.' };
  }
  
  const cli = clienteSeleccionado.value;
  if (!cli) return { valid: false, message: 'Cliente seleccionado no es válido.' };

  if (Number(cli.id_condicion_iva_receptor) !== 1) {
    return { valid: false, message: 'El cliente debe ser Responsable Inscripto (RI) para emitir Factura A.' };
  }
  
  const cuitLimpio = String(cli.dni_cliente || '').replace(/[^0-9]/g, '');
  if (cuitLimpio.length !== 11) {
    return { valid: false, message: 'Para Factura A se requiere un CUIT válido de 11 dígitos. El cliente seleccionado tiene un DNI o CUIT inválido.' };
  }
  
  if (!cli.nombre_cliente || String(cli.nombre_cliente).trim() === '') {
    return { valid: false, message: 'El cliente debe tener Razón Social / Nombre cargado para Factura A.' };
  }

  return { valid: true };
});

// Cambiar a factura A automáticamente si el cliente es Responsable Inscripto (id_condicion_iva_receptor = 1)
watch(clienteSeleccionado, (nuevo) => {
  if (nuevo && Number(nuevo.id_condicion_iva_receptor) === 1) {
    tipoFactura.value = 'A';
  } else {
    tipoFactura.value = 'B';
  }
});

const showQuickClientModal = ref(false);
const showQuickTeamModal = ref(false);
const quickClientData = ref(null);

const openNewQuickClient = () => {
  quickClientData.value = null;
  showQuickClientModal.value = true;
};

const openEditQuickClient = () => {
  if (form.value.id_cliente) {
    quickClientData.value = clienteSeleccionado.value;
    showQuickClientModal.value = true;
  }
};

const handleQuickClientConfirm = (cliente) => {
  // La decisión de tratar al cliente como nuevo o como edición vive en un helper
  // puro (testeable) para evitar el bug de clasificación por formato de id.
  const { payload } = resolverClienteRapido(cliente);
  emit('client-created', payload);

  // Asignar a la venta inmediatamente
  form.value.id_cliente = cliente.id;
  queryCliente.value = cliente.nombre_cliente; // Sincronizar el buscador con el nombre
  showQuickClientModal.value = false;
  
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

watch(() => facturar.value, (isFacturar) => {
  if (!props.isAjuste) {
    articulosCarrito.value.forEach((item, index) => {
      item.iva = isFacturar;
      actualizarTotalItem(index);
    });
  }
});

watch(() => form.value.id_cliente, (newVal) => {
  if (newVal) {
    const cliente = props.clientes.find(c => c.id === newVal);
    form.value.id_equipo = cliente?.id_equipo || null;
    form.value.tipo_vta = 0; // Si hay cliente, es "A cuenta"
  } else {
    form.value.id_equipo = null;
    // Pausa y cerrada sin cliente: tipo_vta = 1 (Común); solo Abierta es A cuenta
    if (!esPausa.value && !esCerrada.value) {
      form.value.tipo_vta = 0;
    } else {
      form.value.tipo_vta = 1;
    }
  }
});

// Sincronizar form y carrito cuando se abre el modal
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    // Reset the manual override so forceCierre takes effect on next open
    userOverrodeState.value = false;
    form.value = { ...props.initialForm };
    articulosCarrito.value = props.initialForm?.articulos ? [...props.initialForm.articulos] : [];
    
    // Sincronizar nombre del cliente para el buscador difuso
    if (form.value.id_cliente) {
      const cliente = props.clientes.find(c => c.id === form.value.id_cliente);
      queryCliente.value = cliente?.nombre_cliente || '';
    } else {
      queryCliente.value = '';
    }
    
    if (!props.isEditing) {
      form.value.id_estado_venta = VENTA_STATES.CERRADA;
      form.value.tipo_vta = 1; // Común
      if (!form.value.fecha) {
        form.value.fecha = new Date().toISOString().split('T')[0];
      }
    } else {
      // Si estamos editando y el estado actual es ABIERTA, asegurarnos que se vea como cerrada por defecto si es una nueva sesión de edición que forzó a cerrar
      if (form.value.forceCierre) {
        form.value.id_estado_venta = VENTA_STATES.CERRADA;
      }
    }
    
    // Sincronizar estado de facturación basándose en el campo estado_factura de la BD
    if (props.isEditing) {
      facturar.value = form.value.estado_factura !== null;
    } else {
      facturar.value = false;
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
  form.value.id_estado_venta = VENTA_STATES.CERRADA;
  form.value.tipo_vta = 1; // Común
  if (!form.value.fecha) {
    form.value.fecha = new Date().toISOString().split('T')[0];
  }
}

watch(() => props.initialForm, (newVal) => {
  form.value = { ...newVal };
  
  // Si viene con forceCierre, asegurar estado CERRADA independientemente del valor del API
  if (newVal.forceCierre) {
    form.value.id_estado_venta = VENTA_STATES.CERRADA;
  }
  
  articulosCarrito.value = newVal.articulos ? [...newVal.articulos] : [];
  
  // Sincronizar estado de facturación basándose en el campo estado_factura de la BD
  if (props.isEditing) {
    facturar.value = newVal.estado_factura !== null;
  } else {
    facturar.value = false;
  }
  
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
    document.addEventListener('keydown', handleKeydownGlobal);
    nextTick(() => {
      if (form.value.forceCierre) {
        montoEntregadoRef.value?.focus();
        montoEntregadoRef.value?.select();
      } else {
        // En cualquier otro caso (edición o nueva), foco en buscador
        buscadorArticuloRef.value?.focus();
      }
    });
  } else {
    document.removeEventListener('keydown', handleKeydownGlobal);
  }
});

onUnmounted(() => {
  document.body.style.overflow = '';
  document.removeEventListener('keydown', handleKeydownGlobal);
});

// Tracks if the user manually clicked a state button (overrides forceCierre)
const userOverrodeState = ref(false);

// When forceCierre=true and the user hasn't manually changed state → always CERRADA
// This bypasses watcher timing issues entirely (reads props directly at computed time)
const esCerrada = computed(() => {
  if (!userOverrodeState.value && !!props.initialForm?.forceCierre) return true;
  return Number(form.value.id_estado_venta) === VENTA_STATES.CERRADA;
});
const esPausa = computed(() => {
  if (!userOverrodeState.value && props.initialForm?.forceCierre) return false;
  return Number(form.value.id_estado_venta) === VENTA_STATES.PAUSA;
});
const esAbierta = computed(() => {
  if (!userOverrodeState.value && props.initialForm?.forceCierre) return false;
  return Number(form.value.id_estado_venta) === VENTA_STATES.ABIERTA;
});
const esCuentaCorriente = computed(() => form.value.tipo_vta === 0);

const subtotal = computed(() =>
  articulosCarrito.value.reduce((acc, i) => {
    const totalItem = Number(i.total || 0);
    const gravado = i.iva ? (totalItem / 1.21) : totalItem;
    return acc + gravado;
  }, 0)
);

const totalIVA = computed(() => {
  return articulosCarrito.value
    .reduce((acc, i) => acc + (i.iva ? (Number(i.total || 0) * (0.21 / 1.21)) : 0), 0)
    .toFixed(2);
});

const totalCarrito = computed(() => {
  return (articulosCarrito.value.reduce((acc, i) => acc + Number(i.total || 0), 0)).toFixed(2);
});

// esAbierto mantiene compatibilidad: true cuando NO está cerrada (incluye pausa y abierta)
const esAbierto = computed(() => !esCerrada.value);

// Mantenido por compatibilidad legacy — ya no se usa con el nuevo selector de 3 estados
const toggleEstadoVenta = (abierto) => {
  if (abierto) {
    form.value.id_estado_venta = VENTA_STATES.ABIERTA;
    form.value.tipo_vta = 0;
  } else {
    form.value.id_estado_venta = VENTA_STATES.CERRADA;
    form.value.tipo_vta = form.value.id_cliente ? 0 : 1;
  }
};

const cambiarEstado = (id) => {
  // Mark that the user explicitly selected a state, clearing the forceCierre override
  userOverrodeState.value = true;
  if (id !== VENTA_STATES.CERRADA) {
    // Si se selecciona abierta o pausa, forzar a "A Cuenta" (si es abierta) y desactivar factura
    if (id === VENTA_STATES.ABIERTA) {
      form.value.tipo_vta = 0; 
    }
    facturar.value = false;
  }
  form.value.id_estado_venta = id;
};

const cambiarTipoVta = (tipo) => {
  // Ya no es necesario restringir aquí porque la validación de puedeGuardar
  // se encarga de asegurar que si está abierto, tenga cliente.
  form.value.tipo_vta = tipo;
};

const puedeGuardar = computed(() => {
  // Condiciones comunes
  const tieneItems = articulosCarrito.value.length > 0 &&
    articulosCarrito.value.every(i => i.id_articulo && Number(i.cantidad) > 0);

  if (!form.value.fecha || !form.value.id_estado_venta || !tieneItems) return false;
  if (errorStock.value) return false;

  // Validación Factura A (Requisitos AFIP)
  if (!validacionFacturaA.value.valid) return false;

  if (esCerrada.value) {
    // Cerrada: requiere medio de pago, cliente NO obligatorio
    return !!form.value.id_medio_cobro;
  }

  if (esAbierta.value) {
    // Abierta: requiere cliente, pago NO obligatorio
    return !!form.value.id_cliente;
  }

  // Pausa: no requiere ni cliente ni pago
  return true;
});

const puedeFacturar = computed(() => {
  if (props.isAjuste) return false;
  if (!esCerrada.value) return false;
  // Permitimos facturar aunque no haya cliente (Consumidor Final anónimo)
  return true;
});

const mensajeFacturacion = computed(() => {
  if (props.isAjuste) return 'No aplica a ajustes';
  if (!esCerrada.value) return 'Solo en ventas cerradas';
  return '';
});

// Con Transferencia o Mercado Pago, la facturación AFIP se tilda por defecto (Factura B)
const medioPagoRequiereFacturacionAutomatica = (idMedioCobro) => {
  const medio = props.mediosCobro?.find(m => Number(m.id) === Number(idMedioCobro));
  const descripcion = (medio?.descripcion || '').toLowerCase();
  return descripcion.includes('transferencia') || descripcion.includes('mercado pago');
};

watch(() => form.value.id_medio_cobro, (nuevoIdMedio) => {
  if (!puedeFacturar.value) return;
  if (props.isEditing && form.value.estado_factura) return;
  if (medioPagoRequiereFacturacionAutomatica(nuevoIdMedio)) {
    facturar.value = true;
  }
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
  let cantidadAAgregar = Number(art.cantidad_previa) || 1;
  const existente = articulosCarrito.value.find(i => Number(i.id_articulo) === Number(art.id));
  
  const aplicarIva = facturar.value && !props.isAjuste;
  if (existente) {
    existente.cantidad = Number((Number(existente.cantidad) + cantidadAAgregar).toFixed(2));
    const base = existente.cantidad * existente.precio_unitario;
    existente.total = base.toFixed(2); // El precio ya incluye IVA
  } else {
    const baseNuevo = cantidadAAgregar * Number(art.precio_actual || 0);
    // Agregar al principio del array (Unshift) para que el último producto aparezca arriba
    articulosCarrito.value.unshift({
      id_articulo: art.id,
      nombre: art.nombre,
      url_imagen: art.url_imagen,
      stock_actual: art.stock_actual,
      cantidad: cantidadAAgregar,
      precio_unitario: Number(art.precio_actual || 0),
      iva: aplicarIva,
      total: baseNuevo.toFixed(2), // El precio ya incluye IVA
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
  const nueva = Number((Number(item.cantidad) + delta).toFixed(2));
  
  // No permitir cantidad menor o igual a 0
  if (nueva <= 0) return;

  item.cantidad = nueva;
  actualizarTotalItem(index);
};

const actualizarTotalItem = (index) => {
  const item = articulosCarrito.value[index];
  const base = (Number(item.cantidad) || 0) * (Number(item.precio_unitario) || 0);
  item.total = base.toFixed(2); // El precio ya incluye IVA
  validarStockMasivo();
};

const quitarFilaArticulo = (index) => { articulosCarrito.value.splice(index, 1); validarStockMasivo(); };

const advertenciasStock = computed(() =>
  articulosCarrito.value.filter(i =>
    i.stock_actual !== null &&
    i.stock_actual !== undefined &&
    Number(i.cantidad) > Number(i.stock_actual)
  )
);

const validarStockMasivo = () => {
  errorStock.value = null;
  return true;
};

const handleKeydownGlobal = (e) => {
  if (e.key !== 'Enter') return;
  const tag = document.activeElement?.tagName?.toLowerCase();
  const isInputFocused = ['input', 'textarea', 'select', 'button'].includes(tag);
  if (!isInputFocused && puedeGuardar.value && !props.isLoading) {
    e.preventDefault();
    handleSave();
  }
};

const handleSave = async () => {
  // Primero validamos si puede guardar, si no mostramos el toast específico
  if (!puedeGuardar.value) {
    // Si la falla es por Factura A, mostramos el mensaje específico
    if (!validacionFacturaA.value.valid) {
      toastStore.showToast({
        message: validacionFacturaA.value.message,
        type: 'warning'
      });
    } else {
      // Fallas generales (items, fecha, medio de pago, etc)
      toastStore.showToast({
        message: 'Por favor complete todos los datos requeridos antes de guardar.',
        type: 'warning'
      });
    }
    return;
  }

  // Ensure the effective displayed state is always what gets saved
  // (guards against any watcher timing edge cases with forceCierre)
  const ventaToSave = { ...form.value };
  if (esCerrada.value && Number(ventaToSave.id_estado_venta) !== VENTA_STATES.CERRADA) {
    ventaToSave.id_estado_venta = VENTA_STATES.CERRADA;
  }
  // Emitir el evento de guardar venta al padre (VentasView.vue)
  // El padre se encarga de llamar al servicio y devolver el ID creado o editado
  emit('save', { 
    venta: ventaToSave, 
    articulos: articulosCarrito.value,
    facturar: facturar.value && !props.isAjuste, // Pasamos la intención de facturar
    tipo_factura: tipoFactura.value
  });
};

const closeModal = () => {
  facturar.value = false;
  emit('update:modelValue', false);
};
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
.transition-transform { 
  transition: transform 0.1s; 
}

.fuzzy-search-sm {
  width: 100%;
}

.fuzzy-search-sm :deep(.form-control) {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  border-width: 2px;
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.fuzzy-search-sm :deep(.list-group) {
  font-size: 0.875rem;
}

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
