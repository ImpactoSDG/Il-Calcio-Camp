/**
 * Composable: usePrinterConfig
 *
 * Centraliza la configuración y el uso de QZ Tray + ESC/POS.
 * El nombre de la impresora y el comando de corte se leen desde localStorage
 * para que cada instalación pueda adaptarse sin tocar el código.
 *
 * Los certificados de QZ Tray se cargan dinámicamente desde la API
 * según el machine_id (UUID único por navegador/máquina).
 */

import * as qz from 'qz-tray';
import { KJUR, hextob64 } from 'jsrsasign';

// ── Claves de identificación y preferencia por máquina ──────
const LS_MACHINE_ID                 = 'qz_machine_id';
const LS_MACHINE_PREFERRED_PRINTER  = 'qz_machine_printer';

/**
 * Persistencia híbrida para el Machine ID usando Cookies (10 años) y LocalStorage.
 * Esto evita que se pierda al limpiar caché rápida o actualizar.
 */
function setMachineIdCookie(id) {
  const d = new Date();
  d.setTime(d.getTime() + (3650 * 24 * 60 * 60 * 1000)); // 10 años
  const expires = "expires=" + d.toUTCString();
  document.cookie = `${LS_MACHINE_ID}=${id};${expires};path=/;SameSite=Strict`;
}

function getMachineIdCookie() {
  const name = LS_MACHINE_ID + "=";
  const decodedCookie = decodeURIComponent(document.cookie);
  const ca = decodedCookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') c = c.substring(1);
    if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
  }
  return null;
}

/**
 * Devuelve (o genera la primera vez) un UUID único para esta máquina/navegador.
 * Se persiste en localStorage y Cookies para máxima redundancia.
 */
export function getMachineId() {
  let id = localStorage.getItem(LS_MACHINE_ID) || getMachineIdCookie();

  if (!id) {
    // Genera un UUID v4 compatible con todos los navegadores modernos
    id = crypto.randomUUID
      ? crypto.randomUUID()
      : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
          const r = (Math.random() * 16) | 0;
          return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
        });
  }

  // Asegurar consistencia en ambos almacenamientos
  localStorage.setItem(LS_MACHINE_ID, id);
  setMachineIdCookie(id);

  return id;
}

/**
 * Permite cambiar el ID manualmente (por si se quiere restaurar uno anterior).
 */
export function saveMachineId(newId) {
  const id = newId.trim();
  if (id) {
    localStorage.setItem(LS_MACHINE_ID, id);
    setMachineIdCookie(id);
  }
}

// ── Impresora preferida por máquina (localStorage + Cookie) ─
function setMachinePreferredPrinterCookie(jsonStr) {
  const d = new Date();
  d.setTime(d.getTime() + (3650 * 24 * 60 * 60 * 1000)); // 10 años
  document.cookie = `${LS_MACHINE_PREFERRED_PRINTER}=${encodeURIComponent(jsonStr)};expires=${d.toUTCString()};path=/;SameSite=Strict`;
}

function getMachinePreferredPrinterCookie() {
  const name = LS_MACHINE_PREFERRED_PRINTER + '=';
  for (const c of document.cookie.split(';')) {
    const trimmed = c.trim();
    if (trimmed.startsWith(name)) {
      try { return decodeURIComponent(trimmed.substring(name.length)); } catch { return null; }
    }
  }
  return null;
}

/**
 * Devuelve la impresora preferida para esta máquina (localStorage -> Cookie).
 * Retorna un objeto { id, nombre, comando_corte, lineas_avance } o null.
 */
export function getMachinePreferredPrinter() {
  const raw = localStorage.getItem(LS_MACHINE_PREFERRED_PRINTER) || getMachinePreferredPrinterCookie();
  if (!raw) return null;
  try { return JSON.parse(raw); } catch { return null; }
}

/**
 * Guarda la impresora elegida para esta máquina y la aplica al localStorage activo.
 */
export function saveMachinePreferredPrinter(imp) {
  if (!imp) return;
  const data = JSON.stringify({
    id:            imp.id,
    nombre:        imp.nombre,
    comando_corte: imp.comando_corte || '\x1D\x56\x00',
    lineas_avance: imp.lineas_avance || 5,
  });
  localStorage.setItem(LS_MACHINE_PREFERRED_PRINTER, data);
  setMachinePreferredPrinterCookie(data);
  // Aplicar inmediatamente al localStorage activo
  savePrinterName(imp.nombre || '');
  saveCutCmd(imp.comando_corte || '\x1D\x56\x00');
  saveFeedLines(imp.lineas_avance || 5);
}

/**
 * Elimina la preferencia específica de esta máquina.
 * Al eliminarla, se volverá a usar el predeterminado global.
 */
export function clearMachinePreferredPrinter() {
  localStorage.removeItem(LS_MACHINE_PREFERRED_PRINTER);
  document.cookie = `${LS_MACHINE_PREFERRED_PRINTER}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Strict`;
}

// ── Claves de localStorage ───────────────────────────────────
export const LS_PRINTER_NAME = 'qz_printer_name';
export const LS_CUT_CMD      = 'qz_cut_cmd';
export const LS_FEED_LINES   = 'qz_feed_lines';

// Variantes de corte conocidas; se muestran en la UI de configuración
export const CUT_VARIANTS = [
  { label: 'Full Cut  (GS V 0)  — más compatible',  value: '\x1D\x56\x00' },
  { label: 'Partial Cut (GS V 1)',                   value: '\x1D\x56\x01' },
  { label: 'Full Cut  (GS V A 0)',                   value: '\x1D\x56\x41\x00' },
  { label: 'ESC i  — Epson TM series',               value: '\x1B\x69' },
  { label: 'ESC m  — Bixolon / Star',                value: '\x1B\x6D' },
];

// ── Configurar QZ Security una única vez (no hace falta hacerlo en cada componente) ──
let _securityConfigured = false;

/**
 * Aplica el certificado y la clave privada a QZ Tray.
 * Usar resetQzSecurity() antes de llamar de nuevo si los certs cambiaron.
 */
export function setupQzSecurity(cert, pk) {
  if (_securityConfigured) return;
  _securityConfigured = true;

  qz.security.setCertificatePromise((resolve) => resolve(cert));

  qz.security.setSignaturePromise((toSign) => {
    return (resolve, reject) => {
      try {
        const sig = new KJUR.crypto.Signature({ alg: 'SHA1withRSA' });
        sig.init(pk);
        sig.updateString(toSign);
        resolve(hextob64(sig.sign()));
      } catch (err) {
        reject(err);
      }
    };
  });
}

/**
 * Permite volver a configurar los certificados (útil tras subir un nuevo cert).
 */
export function resetQzSecurity() {
  _securityConfigured = false;
}

// ── Getters/setters de localStorage ─────────────────────────
export function getPrinterName() {
  return localStorage.getItem(LS_PRINTER_NAME) || '';
}
export function savePrinterName(name) {
  localStorage.setItem(LS_PRINTER_NAME, name.trim());
}
export function getCutCmd() {
  return localStorage.getItem(LS_CUT_CMD) || '\x1D\x56\x00';
}
export function saveCutCmd(cmd) {
  localStorage.setItem(LS_CUT_CMD, cmd);
}
export function getFeedLines() {
  return parseInt(localStorage.getItem(LS_FEED_LINES) || '5', 10);
}
export function saveFeedLines(lines) {
  localStorage.setItem(LS_FEED_LINES, String(lines));
}

// ── Sincronizar objeto impresora con localStorage ─────────────
export function syncLocalStorage(imp) {
  if (!imp) return;
  // Sincronizamos si es la default o si forzamos la sincronización
  if (imp.es_default == 1 || imp.es_default === true) {
    savePrinterName(imp.nombre || '');
    saveCutCmd(imp.comando_corte || '\x1D\x56\x00');
    saveFeedLines(imp.lineas_avance || 5);
  }
}

// ── Conexión QZ ──────────────────────────────────────────────
export async function ensureQzConnected() {
  if (!qz.websocket.isActive()) {
    await qz.websocket.connect();
  }
}

// ── Listar impresoras disponibles via QZ ─────────────────────
export async function listarImpresoras() {
  await ensureQzConnected();
  const lista = await qz.printers.find();
  return Array.isArray(lista) ? lista : [lista];
}

// ── Función principal: imprimir ticket ESC/POS ───────────────
export async function imprimirTicketEscPos({ venta, articulos, nombreLocal = 'IL CALCIO CAMP' }) {
  const printerName = getPrinterName();
  if (!printerName) {
    throw new Error('No hay impresora configurada. Ir a Configuraciones > Impresora.');
  }

  await ensureQzConnected();

  const config = qz.configs.create(printerName);
  const feed   = '\x1B\x64' + String.fromCharCode(getFeedLines());
  const cut    = getCutCmd();

  const totalGeneral = articulos.reduce((a, i) => a + Number(i.total || 0), 0).toFixed(2);

  const formatFechaLocal = (fecha) => {
    if (!fecha) return '—';
    const [y, m, d] = String(fecha).split('T')[0].split('-');
    return `${d}/${m}/${y}`;
  };

  const SEP = '------------------------------------------------\x0A'; // 48 chars

  // Símbolo del día (ya es un carácter como %, !, #, etc.)
  const simbolo = venta.simbolo || '';

  const lineasArticulos = articulos.map(a => {
    const cant  = String(a.cantidad).padEnd(6, ' ');
    const desc  = String(a.articulo_nombre).substring(0, 26).padEnd(27, ' ');
    const total = `$${Number(a.total).toFixed(2)}`.padStart(15, ' ');
    return `${cant}${desc}${total}\x0A`;
  });

  const data = [
    '\x1B\x40',            // Inicializar
    '\x1B\x74\x02',        // Seleccionar tabla PC850 (Latin-1) -> \x02 es común para 850 en muchos modelos
    '\x1B\x61\x01',      // Centrar
    '\x1B\x21\x30',      // Texto grande
    `${nombreLocal}\x0A`,
    '\x1B\x21\x00',      // Normal
    `Venta N\xF8 ${venta.id} - ${simbolo}\x0A`,
    `Fecha: ${formatFechaLocal(venta.fecha)}\x0A`,
    SEP,
    '\x1B\x61\x00',      // Izquierda
    'CANT  DESCRIPCI\xA2N                     TOTAL\x0A',
    SEP,
    ...lineasArticulos,
    SEP,
    '\x1B\x61\x02',      // Derecha
    '\x1B\x21\x18',      // Negrita + doble alto
    `TOTAL: $${totalGeneral}\x0A`,
    '\x1B\x21\x00',      // Normal
    `Pago: ${venta.medio_cobro_nombre || 'No registrado'}\x0A\x0A`,
    '\x1B\x61\x01',      // Centrar
    '\xADGracias por su compra!\x0A',
    SEP,
    feed,                // Avance configurable
    cut,                 // Corte configurable
  ];

  await qz.print(config, data);
}

/**
 * Genera HTML del ticket para impresión o descarga como PDF
 * Compatible con impresoras térmicas (80mm de ancho)
 */
export function generarHtmlTicket({ venta, articulos, nombreLocal = 'IL CALCIO CAMP' }) {
  const totalGeneral = articulos.reduce((a, i) => a + Number(i.total || 0), 0).toFixed(2);

  const formatFechaLocal = (fecha) => {
    if (!fecha) return '—';
    const [y, m, d] = String(fecha).split('T')[0].split('-');
    return `${d}/${m}/${y}`;
  };

  const simbolo = venta.simbolo || '';

  const lineasArticulos = articulos.map(a => `
    <tr>
      <td style="width: 10%; text-align: center;">${a.cantidad}</td>
      <td style="width: 60%; text-align: left;">${a.articulo_nombre}</td>
      <td style="width: 30%; text-align: right;">$${Number(a.total).toFixed(2)}</td>
    </tr>
  `).join('');

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Ticket Venta ${venta.id}</title>
      <style>
        * { margin: 0; padding: 0; }
        body {
          font-family: 'Courier New', monospace; 
          width: 80mm;
          padding: 5mm;
          background: white;
        }
        .ticket { text-align: center; }
        .header { 
          font-weight: bold; 
          font-size: 14px;
          margin-bottom: 5px;
        }
        .fecha { 
          font-size: 11px; 
          margin-bottom: 10px;
          color: #666;
        }
        .separador { 
          border-top: 1px dashed #000; 
          margin: 8px 0;
        }
        table { 
          width: 100%; 
          font-size: 11px;
          margin: 8px 0;
        }
        th { 
          border-bottom: 1px dashed #000;
          padding: 3px 0;
          font-size: 10px;
        }
        td { padding: 2px 0; }
        .total { 
          font-weight: bold; 
          font-size: 14px;
          margin: 10px 0;
        }
        .pago { 
          font-size: 11px;
          margin: 5px 0;
        }
        .footer { 
          font-size: 12px;
          margin-top: 10px;
          font-weight: bold;
        }
        @media print {
          body { width: 80mm; margin: 0; padding: 0; }
          .no-print { display: none; }
        }
      </style>
    </head>
    <body>
      <div class="ticket">
        <div class="header">${nombreLocal}</div>
        <div>Venta Nº ${venta.id} ${simbolo}</div>
        <div class="fecha">Fecha: ${formatFechaLocal(venta.fecha)}</div>
        <div class="separador"></div>
        
        <table>
          <tr>
            <th style="text-align: center; width: 10%">CANT.</th>
            <th style="text-align: left; width: 60%">DESCRIPCIÓ N</th>
            <th style="text-align: right; width: 30%">TOTAL</th>
          </tr>
          <tr><td colspan="3" style="border-bottom: 1px dashed #000; height: 2px;"></td></tr>
          ${lineasArticulos}
        </table>
        
        <div class="separador"></div>
        <div class="total">TOTAL: $${totalGeneral}</div>
        <div class="pago">Pago: ${venta.medio_cobro_nombre || 'No registrado'}</div>
        <div class="separador"></div>
        <div class="footer">¡Gracias por su compra!</div>
      </div>
    </body>
    </html>
  `;
}

/**
 * Formatea fecha del CAE desde formato genérico
 * Maneja: "YYYYMMDD", "DD/MM/YYYY", etc.
 * Retorna: "DD/MM/YYYY"
 */
function formatearFechaCAE(f) {
  if (!f) return '--';
  const s = String(f).replace(/\D/g, '');
  if (s.length === 8) return `${s.slice(6)}/${s.slice(4, 6)}/${s.slice(0, 4)}`;
  return String(f);
}

/**
 * Formatea CUIT en formato XX-XXXXXXXX-X
 */
function formatearCuit(cuit) {
  if (!cuit) return '--';
  const solo_numeros = String(cuit).replace(/\D/g, '').padStart(11, '0');
  return `${solo_numeros.slice(0, 2)}-${solo_numeros.slice(2, 10)}-${solo_numeros.slice(10)}`;
}

/**
 * Formatea fecha desde múltiples formatos posibles
 * Maneja: "YYYY-MM-DD", "DD HH:MM:SS/MM/YYYY", "DD/MM/YYYY", etc.
 * Retorna: "DD/MM/YYYY"
 */
function formatearFechaSimple(f) {
  if (!f) return '--';
  const str = String(f).trim();
  
  // Si tiene barra pero empieza con dígito y espacio (formato "DD HH:MM:SS/MM/YYYY")
  if (/^\d{1,2}\s+\d{2}:\d{2}:\d{2}\/\d{1,2}\/\d{4}$/.test(str)) {
    const match = str.match(/^(\d{1,2})\s+\d{2}:\d{2}:\d{2}\/(\d{1,2})\/(\d{4})$/);
    if (match) {
      const [, dd, mm, yyyy] = match;
      return `${String(dd).padStart(2, '0')}/${String(mm).padStart(2, '0')}/${yyyy}`;
    }
  }
  
  // Formato ISO: "YYYY-MM-DD" o "YYYY-MM-DDTHH:MM:SS"
  if (str.includes('T')) {
    const [datePart] = str.split('T');
    const [yyyy, mm, dd] = datePart.split('-');
    return `${dd}/${mm}/${yyyy}`;
  }
  
  if (str.includes('-') && str.length >= 10) {
    const [yyyy, mm, dd] = str.substring(0, 10).split('-');
    return `${dd}/${mm}/${yyyy}`;
  }
  
  // Formato "DD/MM/YYYY" ya está correcto
  if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(str)) {
    const [dd, mm, yyyy] = str.split('/');
    return `${String(dd).padStart(2, '0')}/${String(mm).padStart(2, '0')}/${yyyy}`;
  }
  
  return '--';
}

/**
 * Formatea fecha y hora desde múltiples formatos
 * Retorna: "DD/MM/YYYY HH:MM:SS"
 */
function formatearFechaHora(f) {
  if (!f) return '--';
  const str = String(f).trim();
  
  // Formato "DD HH:MM:SS/MM/YYYY"
  if (/^\d{1,2}\s+\d{2}:\d{2}:\d{2}\/\d{1,2}\/\d{4}$/.test(str)) {
    const match = str.match(/^(\d{1,2})\s+(\d{2}:\d{2}:\d{2})\/(\d{1,2})\/(\d{4})$/);
    if (match) {
      const [, dd, hora, mm, yyyy] = match;
      return `${String(dd).padStart(2, '0')}/${String(mm).padStart(2, '0')}/${yyyy} ${hora}`;
    }
  }
  
  // Formato ISO: "YYYY-MM-DDTHH:MM:SS"
  if (str.includes('T')) {
    const [datePart, timePart] = str.split('T');
    const [yyyy, mm, dd] = datePart.split('-');
    const hora = timePart.substring(0, 8); // HH:MM:SS
    return `${dd}/${mm}/${yyyy} ${hora}`;
  }
  
  return formatearFechaSimple(f);
}

/**
 * Genera los comandos ESC/POS para imprimir un QR nativo en impresoras térmicas.
 * Usa GS ( k (Modelo 2), compatible con la mayoría de impresoras térmicas modernas.
 */
function generarQrEscPos(texto, tamano = 6) {
  const bytes = Array.from(texto, c => c.charCodeAt(0));
  const dataLen = bytes.length + 3; // +3 por cabecera (0x31 0x50 0x30)
  const pL = dataLen & 0xFF;
  const pH = (dataLen >> 8) & 0xFF;
  const storeCmd = [0x1d, 0x28, 0x6b, pL, pH, 0x31, 0x50, 0x30, ...bytes];
  return [
    '\x1d\x28\x6b\x04\x00\x31\x41\x32\x00',                         // Modelo 2
    `\x1d\x28\x6b\x03\x00\x31\x43${String.fromCharCode(tamano)}`,    // Tamaño
    '\x1d\x28\x6b\x03\x00\x31\x45\x31',                              // Error correction M
    String.fromCharCode(...storeCmd),                                  // Almacenar datos
    '\x1d\x28\x6b\x03\x00\x31\x51\x30',                              // Imprimir
  ];
}

/**
 * Imprime la factura electrónica AFIP completa vía ESC/POS (QZ Tray).
 * Incluye emisor, receptor, artículos, IVA, CAE y QR nativo.
 */
export async function imprimirTicketDetalleFactura({ venta, articulos, factura, nombreLocal = 'IL CALCIO CAMP' }) {
  const printerName = getPrinterName();
  if (!printerName) {
    throw new Error('No hay impresora configurada. Ir a Configuraciones > Impresora.');
  }

  await ensureQzConnected();

  const config = qz.configs.create(printerName);
  const feed   = '\x1B\x64' + String.fromCharCode(getFeedLines());
  const cut    = getCutCmd();

  const fmt = (v) => parseFloat(v || 0).toFixed(2);

  const SEP = '------------------------------------------------\x0A';
  const B   = '\x1B\x45\x01'; // bold on
  const NB  = '\x1B\x45\x00'; // bold off
  const CTR = '\x1B\x61\x01'; // center
  const LFT = '\x1B\x61\x00'; // left
  const RGT = '\x1B\x61\x02'; // right
  const BIG = '\x1B\x21\x30'; // double height+width
  const NRM = '\x1B\x21\x00'; // normal

  const nroCbte = factura?.nro_comprobante ? String(factura.nro_comprobante).padStart(8, '0') : '--------';
  const pto     = factura?.pto_venta ? String(factura.pto_venta).padStart(4, '0') : '----';
  const tipoLetra = (factura?.tipo_letra || '?').toUpperCase();

  // Calcular total sumando artículos (a.total ya incluye IVA si corresponde)
  const total = articulos.reduce((sum, a) => sum + Number(a.total || 0), 0);

  const lineasDetalle = articulos.map(a => {
    const cant = String(a.cantidad).padEnd(6, ' ');
    const desc = String(a.articulo_nombre).substring(0, 22).padEnd(23, ' ');
    const total = `$${Number(a.total).toFixed(2)}`.padStart(17, ' ');
    return `${cant}${desc}${total}\x0A`;
  });

  const fechaAfip = (factura?.fecha_emision || venta.fecha || '');
  let fechaFormateada = '';
  if (fechaAfip.includes('T')) {
    fechaFormateada = fechaAfip.split('T')[0];
  } else if (fechaAfip.includes('-')) {
    fechaFormateada = fechaAfip.substring(0, 10);
  } else if (fechaAfip.includes('/')) {
    const [d, m, y] = fechaAfip.split(' ')[0].split('/');
    fechaFormateada = `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
  }

  const qrDataObj = {
    ver: 1,
    fecha: fechaFormateada,
    cuit: parseInt(String(factura?.emisor_cuit || '0').replace(/\D/g, ''), 10),
    ptoVta: parseInt(factura?.pto_venta || 1, 10),
    tipoCmp: parseInt(factura?.codigo_afip_comprobante || 0, 10),
    nroCmp: parseInt(factura?.nro_comprobante || 0, 10),
    importe: total,
    moneda: 'PES',
    ctz: 1,
    tipoDocRec: tipoLetra === 'A' ? 80 : 96, // Factura A exige CUIT (80) del receptor, no DNI (96)
    nroDocRec: parseInt(String(factura?.cuit_dni_receptor || '0').replace(/\D/g, ''), 10),
    tipoCodAut: 'E',
    codAut: parseInt(factura?.cae || 0, 10),
  };
  const qrData = JSON.stringify(qrDataObj);
  const qrUrl = `https://www.afip.gob.ar/fe/qr/?p=${btoa(qrData)}`;

  const data = [
    '\x1B\x40',            // Init
    '\x1B\x74\x02',        // Seleccionar tabla PC850 (Latin-1)
    CTR, BIG,
    `${factura?.emisor_nombre || nombreLocal}\x0A`,
    NRM,
    `CUIT: ${formatearCuit(factura?.emisor_cuit)}\x0A`,
    `IVA: ${factura?.emisor_condicion_iva || ''}\x0A`,
    SEP,
    LFT, `Ingresos Brutos: ${factura?.emisor_condicion_iibb || ''}\x0A`,
    `Inicio de actividades: ${formatearFechaSimple(factura?.emisor_fecha_inicio_acts)}\x0A`,
    `Domicilio:\x0A${factura?.emisor_direccion || ''}\x0A`,
    SEP,
    CTR, B, `FACTURA ELECTR\xA2NICA ${tipoLetra}\x0A`, NB,
    `N\xF8 ${pto}-${nroCbte}\x0A`,
    `Fecha: ${formatearFechaHora(factura?.fecha_emision || venta.fecha)}\x0A`,
    SEP,
    LFT,
    `Sres.: ${factura?.nombre_receptor || factura?.cliente_nombre || 'Consumidor Final'}\x0A`,
    `DNI/CUIT: ${factura?.cuit_dni_receptor || '--'}\x0A`,
    `Cond. IVA: ${factura?.condicion_iva_receptor_nombre || '--'}\x0A`,
    SEP,
    LFT,
    'CANT  DESCRIPCI\xA2N             TOTAL\x0A',
    SEP,
    ...lineasDetalle,
    SEP,
  ];

  if (tipoLetra === 'A') {
    const neto = total / 1.21;
    const iva = total - neto;
    data.push(RGT, `Neto Gravado: $${fmt(neto)}\x0A`);
    data.push(RGT, `IVA 21%: $${fmt(iva)}\x0A`);
  }

  data.push(
    RGT, B, `\x1B\x21\x18TOTAL: $${fmt(total)}\x0A`, NRM, NB,
    LFT,
    SEP,
    B, `CAE: ${factura?.cae || '--'}\x0A`, NB,
    `Vto. CAE: ${formatearFechaCAE(factura?.vto_cae)}\x0A`,
    CTR,
    ...generarQrEscPos(qrUrl, 3),
    '\x0A',
    SEP,
    '\xADGracias por su compra!\x0A',
    feed,
    cut,
  );

  await qz.print(config, data);
}

/**
 * Genera HTML de la factura electrónica AFIP para visualización/impresión manual.
 * Incluye: datos del emisor, receptor, detalle de artículos, IVA, CAE y QR.
 */
export function generarHtmlDetalleFactura({ factura, articulos }) {
  const fmt = (v) => parseFloat(v || 0).toFixed(2);

  // Tipo de comprobante y número formateado
  const tipoLetra = (factura.tipo_letra || '').toUpperCase();
  const nroCbte = factura.nro_comprobante
    ? String(factura.nro_comprobante).padStart(8, '0')
    : '00000000';
  const pto = factura.pto_venta
    ? String(factura.pto_venta).padStart(4, '0')
    : '0001';
  const nroCompleto = `${pto}-${nroCbte}`;

  // QR AFIP: https://www.afip.gob.ar/fe/qr/?p=BASE64_JSON
  const fechaSimple = formatearFechaSimple(factura.fecha_emision || factura.fecha_venta);
  const [dd, mm, yyyy] = fechaSimple.split('/');
  const qrData = {
    ver: 1,
    fecha: `${yyyy}-${mm}-${dd}`,
    cuit: parseInt(String(factura.emisor_cuit || '0').replace(/\D/g, ''), 10),
    ptoVta: parseInt(factura.pto_venta || 1, 10),
    tipoCmp: parseInt(factura.codigo_afip_comprobante || 0, 10),
    nroCmp: parseInt(factura.nro_comprobante || 0, 10),
    importe: parseFloat(factura.importe_total || 0),
    moneda: 'PES',
    ctz: 1,
    tipoDocRec: tipoLetra === 'A' ? 80 : 96, // Factura A exige CUIT (80) del receptor, no DNI (96)
    nroDocRec: parseInt(String(factura.cuit_dni_receptor || '0').replace(/\D/g, ''), 10),
    tipoCodAut: 'E',
    codAut: parseInt(factura.cae || 0, 10),
  };
  const qrBase64 = btoa(JSON.stringify(qrData));
  const qrUrl = `https://www.afip.gob.ar/fe/qr/?p=${qrBase64}`;
  const qrImgUrl = `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(qrUrl)}`;

  // Detalle artículos - mostrar total tal como viene (ya incluye IVA si corresponde)
  const lineasHtml = articulos.map(a => `
    <tr>
      <td class="c">${a.cantidad}</td>
      <td>${a.articulo_nombre}</td>
      <td class="r">$${fmt(a.total)}</td>
    </tr>`).join('');

  // Discriminación de IVA para Factura A
  let discriminacionIvaHtml = '';
  if (tipoLetra === 'A') {
    const total = parseFloat(factura.importe_total || 0);
    const neto = total / 1.21;
    const iva = total - neto;
    discriminacionIvaHtml = `
      <tr class="small-text"><td>Neto Gravado</td><td class="r">$${fmt(neto)}</td></tr>
      <tr class="small-text"><td>IVA 21%</td><td class="r">$${fmt(iva)}</td></tr>
    `;
  }

  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Factura Nº ${nroCompleto}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Courier New', monospace; font-size: 11px; width: 80mm; padding: 4mm; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: bold; }
    .sep { border-top: 1px dashed #000; margin: 4px 0; }
    .sep-solid { border-top: 1px solid #000; margin: 4px 0; }
    .tipo-badge {
      display: inline-block; border: 2px solid #000;
      font-size: 18px; font-weight: 900; width: 26px; height: 26px;
      line-height: 22px; text-align: center; margin: 2px 0;
    }
    table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 4px 0; }
    th { border-bottom: 1px solid #000; padding: 2px 0; font-size: 9px; text-align: left; }
    td { padding: 2px 0; vertical-align: top; }
    td.c { text-align: center; }
    td.r { text-align: right; }
    .totales td { padding: 1px 0; }
    .total-final { font-size: 13px; font-weight: 900; }
    .small-text { font-size: 9px; }
    .cae-block { font-size: 10px; margin-top: 4px; }
    .footer-legal { font-size: 9px; color: #333; text-align: center; margin-top: 4px; }
    .qr-wrap { text-align: center; margin-top: 6px; }
    @media print { body { width: 80mm; margin: 0; padding: 0; } }
  </style>
</head>
<body>

  <div class="center bold" style="font-size:13px;">${factura.emisor_nombre || 'IL CALCIO CAMP'}</div>
  <div class="center">CUIT: ${formatearCuit(factura.emisor_cuit)}</div>
  <div class="center">IVA: ${factura.emisor_condicion_iva || '—'}</div>
  <div class="center" style="font-size: 10px; margin-top: 2px;">
    Ingresos Brutos: ${factura.emisor_condicion_iibb || '—'}<br>
    Inicio de actividades: ${formatearFechaSimple(factura.emisor_fecha_inicio_acts)}
  </div>
  <div class="center" style="font-size: 10px; margin-top: 2px;">
    <strong>Domicilio:</strong><br>
    ${factura.emisor_direccion || '—'}<br>
    ${factura.emisor_ciudad || ''}
  </div>

  <div class="sep-solid"></div>

  <div class="center">
    <span class="tipo-badge">${tipoLetra}</span>
  </div>
  <div class="center bold" style="font-size:12px;">FACTURA ELECTRÓNICA</div>
  <div class="center">Nº ${nroCompleto}</div>
  <div class="center">Fecha: ${formatearFechaHora(factura.fecha_emision || factura.fecha_venta)}</div>

  <div class="sep"></div>

  <div><strong>Cliente:</strong> ${factura.nombre_receptor || factura.cliente_nombre || 'Consumidor Final'}</div>
  <div><strong>DNI/CUIT:</strong> ${factura.cuit_dni_receptor || '—'}</div>
  <div><strong>Cond. IVA:</strong> ${factura.condicion_iva_receptor_nombre || '—'}</div>
  ${factura.Direccion_receptor ? `<div><strong>Dirección:</strong> ${factura.Direccion_receptor}</div>` : ''}

  <div class="sep"></div>

  <table>
    <thead>
      <tr>
        <th style="width:8%; text-align:center;">Cant</th>
        <th style="width:60%;">Descripción</th>
        <th style="width:32%; text-align:right;">Total</th>
      </tr>
    </thead>
    <tbody>
      ${lineasHtml}
    </tbody>
  </table>

  <div class="sep-solid"></div>
  <table class="totales">
    ${discriminacionIvaHtml}
    <tr class="total-final"><td>TOTAL VENTA</td><td class="r">$${fmt(factura.importe_total)}</td></tr>
  </table>

  <div class="sep"></div>

  <div class="cae-block">
    <div><strong>CAE:</strong> ${factura.cae || '—'}</div>
    <div><strong>Vto. CAE:</strong> ${formatearFechaCAE(factura.vto_cae)}</div>
  </div>

  <div class="qr-wrap">
    <img src="${qrImgUrl}" width="100" height="100" alt="QR AFIP" />
  </div>


</body>
</html>`;
}

/**
 * Imprime ticket respetando el modo configurado (QZ Tray o PDF)
 * tipo: 'COMUN' | 'DETALLE_FACTURA'
 * factura: datos completos de la factura (solo para DETALLE_FACTURA)
 */
export async function imprimirTicketConModo({ venta, articulos, nombreLocal = 'IL CALCIO CAMP', tipo = 'COMUN', factura = null }) {
  const { usePrintModeStore } = await import('@/stores/printModeStore');
  const printStore = usePrintModeStore();
  const esModoImpresion = printStore.isPrintMode();

  if (tipo === 'DETALLE_FACTURA') {
    if (esModoImpresion) {
      await imprimirTicketDetalleFactura({ venta, articulos, factura, nombreLocal });
    } else {
      const html = generarHtmlDetalleFactura({ venta, articulos, factura, nombreLocal });
      const ventana = window.open('', '', 'height=700,width=500');
      ventana.document.write(html);
      ventana.document.close();
      ventana.onload = () => ventana.print();
    }
  } else {
    // TICKET COMUN
    if (esModoImpresion) {
      await imprimirTicketEscPos({ venta, articulos, nombreLocal });
    } else {
      const html = generarHtmlTicket({ venta, articulos, nombreLocal });
      const ventana = window.open('', '', 'height=600,width=800');
      ventana.document.write(html);
      ventana.document.close();
      ventana.onload = () => ventana.print();
    }
  }
}

