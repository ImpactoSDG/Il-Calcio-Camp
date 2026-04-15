import { defineStore } from 'pinia';
import { ref } from 'vue';
import { isLocalhost } from '@/utils/envUtils';

export const usePrintModeStore = defineStore('printMode', () => {
  const isLocalEnv = isLocalhost();
  
  // Estado del modo impresión
  // null = automático (depende del ambiente)
  // 'print' = modo impresión (a impresora térmica)
  // 'download' = modo descarga (a PDF)
  const printMode = ref(localStorage.getItem('printMode') || 'print');
  
  /**
   * Obtiene el modo de impresión actual
   */
  function getPrintMode() {
    return printMode.value;
  }
  
  /**
   * Establece el modo de impresión
   * @param {string} mode - 'print' o 'download'
   */
  function setPrintMode(mode) {
    if (!isLocalEnv) {
      console.warn('⚠️ No se puede cambiar el modo de impresión fuera de localhost');
      return;
    }
    
    if (!['print', 'download'].includes(mode)) {
      console.error('Modo inválido. Use "print" o "download"');
      return;
    }
    
    printMode.value = mode;
    localStorage.setItem('printMode', mode);
  }
  
  /**
   * Alterna entre modo impresión y descarga
   */
  function togglePrintMode() {
    if (!isLocalEnv) {
      console.warn('⚠️ No se puede cambiar el modo de impresión fuera de localhost');
      return;
    }
    
    const newMode = printMode.value === 'print' ? 'download' : 'print';
    setPrintMode(newMode);
  }
  
  /**
   * Retorna si estamos en modo impresión
   */
  function isPrintMode() {
    return printMode.value === 'print';
  }
  
  /**
   * Retorna si estamos en modo descarga
   */
  function isDownloadMode() {
    return printMode.value === 'download';
  }
  
  /**
   * Retorna si el toggle debe estar visible (solo en localhost)
   */
  function canTogglePrintMode() {
    return isLocalEnv;
  }

  return {
    printMode,
    getPrintMode,
    setPrintMode,
    togglePrintMode,
    isPrintMode,
    isDownloadMode,
    canTogglePrintMode,
    isLocalEnv
  };
});
