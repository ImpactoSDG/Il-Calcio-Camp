<!-- Se usa cuando el usuario guarda una venta con el modo de impresión configurado como "descargar_pdf"
En ese caso, en lugar de enviar directamente a la impresora, abre el modal con el PDF del ticket simple de venta
Permite al usuario previsualizar el PDF antes de imprimir o descargarlo -->
<template>
  <div v-if="modelValue" class="pdf-viewer-overlay animate-fade-in" @click.self="close">
    <div class="pdf-viewer-container shadow-2xl">
      <!-- Header Estilizado -->
      <div class="pdf-viewer-header">
        <div class="d-flex align-items-center gap-2">
          <div class="pdf-icon-badge">
            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
          </div>
          <div>
            <h6 class="mb-0 fw-bold text-white">{{ title }}</h6>
            <small class="text-white-50">Vista previa del documento</small>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button @click="imprimir" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 rounded-pill" :disabled="loading">
            <i class="bi bi-printer-fill"></i>
            <span>{{ loading ? 'Procesando...' : 'Imprimir' }}</span>
          </button>
          <button @click="close" class="btn-close-pdf" title="Cerrar">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>

      <!-- Cuerpo con Iframe -->
      <div class="pdf-viewer-body bg-dark">
        <iframe 
          v-if="url"
          :src="url" 
          class="pdf-iframe"
          title="PDF Viewer"
        ></iframe>
        <div v-else class="text-center text-white py-5">
           <div class="spinner-border text-primary mb-3"></div>
           <p>Cargando documento...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: Boolean,
  url: String,
  title: {
    type: String,
    default: 'Previsualización de PDF'
  },
  loading: Boolean
});

const emit = defineEmits(['update:modelValue', 'imprimir']);

const close = () => {
  emit('update:modelValue', false);
};

const imprimir = () => {
  emit('imprimir');
};
</script>

<style scoped>
.pdf-viewer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(5px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.pdf-viewer-container {
  width: 100%;
  max-width: 900px;
  height: 90vh;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.pdf-viewer-header {
  background: #2c3e50;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pdf-icon-badge {
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.btn-close-pdf {
  background: transparent;
  border: none;
  color: #fff;
  font-size: 1.2rem;
  padding: 5px;
  line-height: 1;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.btn-close-pdf:hover {
  opacity: 1;
}

.pdf-viewer-body {
  flex-grow: 1;
  position: relative;
}

.pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 768px) {
  .pdf-viewer-container {
    height: 100vh;
    max-width: none;
    border-radius: 0;
  }
}
</style>
