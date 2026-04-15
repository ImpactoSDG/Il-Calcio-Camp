import { usePrintModeStore } from '@/stores/printModeStore';

/**
 * Composable para manejar la lógica de impresión
 * Proporciona métodos para imprimir o descargar basándose en el modo configurado
 */
export function usePrinter() {
  const printStore = usePrintModeStore();

  /**
   * Imprime o descarga basándose en el modo configurado
   * @param {HTMLElement} element - Elemento a imprimir
   * @param {Object} config - Configuración adicional
   */
  const handlePrint = (element, config = {}) => {
    const {
      filename = 'documento',
      title = 'Documento',
      onSuccess = null,
      onError = null
    } = config;

    try {
      if (printStore.isPrintMode()) {
        printToPrinter(element, title);
      } else {
        downloadAsPDF(element, filename);
      }
      
      if (onSuccess) onSuccess();
    } catch (error) {
      console.error('Error en impresión:', error);
      if (onError) onError(error);
    }
  };

  /**
   * Imprime directamente en la impresora térmica
   * @param {HTMLElement} element - Elemento a imprimir
   * @param {string} title - Título para la ventana de impresión
   */
  const printToPrinter = (element, title = 'Impresión') => {
    const printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write(`
      <html>
        <head>
          <title>${title}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
            @media print {
              body { margin: 0; }
            }
          </style>
          <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        </head>
        <body>
          ${element.innerHTML}
          <script>
            window.print();
            window.close();
          </script>
        </body>
      </html>
    `);
    printWindow.document.close();
  };

  /**
   * Descarga el contenido como PDF
   * Nota: Requiere una librería como html2pdf o pdfmake
   * @param {HTMLElement} element - Elemento a descargar
   * @param {string} filename - Nombre del archivo
   */
  const downloadAsPDF = (element, filename = 'documento') => {
    // Verificar si html2pdf está disponible
    if (typeof html2pdf === 'undefined') {
      console.warn('⚠️ html2pdf no está disponible. Implementa la descarga con tu librería preferida.');
      // Fallback: copiar al portapapeles o mostrar modal
      alert('Modo descarga activo, pero html2pdf no está configurado');
      return;
    }

    const options = {
      margin: 5,
      filename: `${filename}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
    };

    html2pdf().set(options).from(element).save();
  };

  /**
   * Retorna el modo actual
   */
  const getPrintMode = () => {
    return printStore.getPrintMode();
  };

  /**
   * Retorna si podemos alternar el modo
   */
  const canTogglePrintMode = () => {
    return printStore.canTogglePrintMode();
  };

  return {
    handlePrint,
    printToPrinter,
    downloadAsPDF,
    getPrintMode,
    canTogglePrintMode,
    isPrintMode: () => printStore.isPrintMode(),
    isDownloadMode: () => printStore.isDownloadMode()
  };
}
