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

// ── Clave de localStorage para el identificador de máquina ──
const LS_MACHINE_ID = 'qz_machine_id';

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
    '\x1B\x40',          // Inicializar
    '\x1B\x61\x01',      // Centrar
    '\x1B\x21\x30',      // Texto grande
    `${nombreLocal}\x0A`,
    '\x1B\x21\x00',      // Normal
    `Venta N\xF8 ${venta.id} - ${simbolo}\x0A`,
    `Fecha: ${formatFechaLocal(venta.fecha)}\x0A`,
    SEP,
    '\x1B\x61\x00',      // Izquierda
    'CANT  DESCRIPCION                     TOTAL\x0A',
    SEP,
    ...lineasArticulos,
    SEP,
    '\x1B\x61\x02',      // Derecha
    '\x1B\x21\x18',      // Negrita + doble alto
    `TOTAL: $${totalGeneral}\x0A`,
    '\x1B\x21\x00',      // Normal
    `Pago: ${venta.medio_cobro_nombre || 'Efectivo'}\x0A\x0A`,
    '\x1B\x61\x01',      // Centrar
    '¡Gracias por su compra!\x0A',
    SEP,
    feed,                // Avance configurable
    cut,                 // Corte configurable
  ];

  await qz.print(config, data);
}
