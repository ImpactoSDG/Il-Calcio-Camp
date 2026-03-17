/**
 * Composable: usePrinterConfig
 * 
 * Centraliza la configuración y el uso de QZ Tray + ESC/POS.
 * El nombre de la impresora y el comando de corte se leen desde localStorage
 * para que cada instalación pueda adaptarse sin tocar el código.
 */

import * as qz from 'qz-tray';
import { KJUR, hextob64 } from 'jsrsasign';

// ── Certificado y clave privada de QZ Tray (Demo cert) ──────
// Centralizado aquí para no repetirlos en cada componente.
export const QZ_CERT = `-----BEGIN CERTIFICATE-----
MIIECzCCAvOgAwIBAgIGAZzT1LtVMA0GCSqGSIb3DQEBCwUAMIGiMQswCQYDVQQG
EwJVUzELMAkGA1UECAwCTlkxEjAQBgNVBAcMCUNhbmFzdG90YTEbMBkGA1UECgwS
UVogSW5kdXN0cmllcywgTExDMRswGQYDVQQLDBJRWiBJbmR1c3RyaWVzLCBMTEMx
HDAaBgkqhkiG9w0BCQEWDXN1cHBvcnRAcXouaW8xGjAYBgNVBAMMEVFaIFRyYXkg
RGVtbyBDZXJ0MB4XDTI2MDMwODE4MjEwMFoXDTQ2MDMwODE4MjEwMFowgaIxCzAJ
BgNVBAYTAlVTMQswCQYDVQQIDAJOWTESMBAGA1UEBwwJQ2FuYXN0b3RhMRswGQYD
VQQKDBJRWiBJbmR1c3RyaWVzLCBMTEMxGzAZBgNVBAsMElFaIEluZHVzdHJpZXMs
IExMQzEcMBoGCSqGSIb3DQEJARYNc3VwcG9ydEBxei5pbzEaMBgGA1UEAwwRUVog
VHJheSBEZW1vIENlcnQwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7
BPga2LBxlozHJJ4cXUMYnoP4K2GT9+5yk1GVp+T59GtS8wN32i1TSh1XBzPtJi2i
18AKrGpcuLXPlGo4qtdeXn4goXVd70d1Y7Q/mgyFFVi0G+uYvEyXaMbbAUBHV6i0
JJ/2Os5zbPUbEla1vPEv0J4iPxMYvn2iDpneycX6VKlrPXQNwGcVTtsG0xF9JI/u
A5lxrhqeIYdl6kQ7sTzyCNBdpJLcoCn7RL0K4ntc8aQ4aM+2Ob6IyFDzt83orOdQ
e945BX9/2NbOPhts67fRgf2H4BgIe9xagc0nguR82wrCcqt2RASziQ19C2FozIrF
XAeB8R/4LKHkD+qzmFbvAgMBAAGjRTBDMBIGA1UdEwEB/wQIMAYBAf8CAQEwDgYD
VR0PAQH/BAQDAgEGMB0GA1UdDgQWBBT9y3FGXDjXVpdzoIEQZM3xLotUyDANBgkq
hkiG9w0BAQsFAAOCAQEAHelzhR5AFTYfGxklytf3MqbsM04ZkMn1cie6c0iWGwBG
DJUQy+7pYEjAUMYZdSQhVmcceH/Ab/d/1tKtW8HvzBQvKe6gtD+DhLd7YZtPVQbz
dsOTutGGhDrg2CM0mAfs2gA0ZNT66k1INPUJLuShRwMO9CqrMLh/Ykr+2c/XGW4i
JKYUCwdpBYTqgPS5gstawIgeBWQ+qx/Pjy8NjJUv9CxZcplTjwTDPjtrR2kOghPO
HdCX9yx+nFOCRx9TVHZ3XuBZJuD/I5/z4zBfsYAYeuJYrt41AoOX4AJ4+HutQN4Y
0msH99Pi4m5ZdGFToZ5kayfCSsxwdUqFHzPrv0Z1XQ==
-----END CERTIFICATE-----`;

export const QZ_PK = `-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7BPga2LBxlozH
JJ4cXUMYnoP4K2GT9+5yk1GVp+T59GtS8wN32i1TSh1XBzPtJi2i18AKrGpcuLXP
lGo4qtdeXn4goXVd70d1Y7Q/mgyFFVi0G+uYvEyXaMbbAUBHV6i0JJ/2Os5zbPUb
Ela1vPEv0J4iPxMYvn2iDpneycX6VKlrPXQNwGcVTtsG0xF9JI/uA5lxrhqeIYdl
6kQ7sTzyCNBdpJLcoCn7RL0K4ntc8aQ4aM+2Ob6IyFDzt83orOdQe945BX9/2NbO
Phts67fRgf2H4BgIe9xagc0nguR82wrCcqt2RASziQ19C2FozIrFXAeB8R/4LKHk
D+qzmFbvAgMBAAECggEAJCMcK9fWFETCdBKDyLhOqDmtB22ef8CHHzWPLKtSB+hu
OouBjo2md3MZQ0E9i+P2KoKk9YsGTF9WpkMn2UZNskrw9S4tpxZ+yNSYtjd2ltqe
lsLUXeF4rUMONbBCsuZhz1lKXYJUdSJHJFGBVsGpGxOlErn8XyojzYYjvlRfwHR5
7TeaVg3gOS1r8GVCGPFE7bi0kBDxKRVSN0VqsS0togRaotcD1zf7l5t8hkYx5kHv
3WGUbDrivz4EfSx9Qj7r9+Vhdfa10aXuqokMAHXVf5QsxNxXX5vdPENMF7ofJy5u
A59DmDMoG8cOkE+m6KDrxvlG7G/fZ/f836pVOiMVHQKBgQDdWrsBsKn/NioPOVFz
LgquGQ4zdD2tFLwKI1Ioz5Z3XRKXTpmcj2wJEFDYYMWltttqATpMcrj2RYbZCaWc
IYNoONLQ4pRNvbMZHWmsbNsoR9uVNZ+8QtnTWA+b9c0t2yFPjZDn174m7UZ2CR8C
bMcTXlcfgSwKl7N633nxVFSfAwKBgQDYSn9ShVpIt+cBPHnLjCJ3XeBB2/jg4uQk
2wRXkmU/9ors9Aq3Q8GkN1XvpXqUr91HE4rxPYNUdIafCaiKAQ5NWw1TA4jccP87
6t/ijWe9wJUV+O/OBXgM8vM9wfa/1XScOaMU03E1uUIW5P6r+tzGPDYp4Zn/Rjm4
F4ik5E6epQKBgEZ/5Dm4k5wmGyU4Iznk+x/R+RToO9CJXw53i25WF10y9n3cWc5k
W4tTd/xCbhDGeYF8nJ3GmCRPppAvo2BjyB+EoZhH4eYUuhsQpBx3myFsKYKPTq2+
OPQ4AtiwY8XsGeLlerZsnzJ0tdFYPFkgXhNMI8Fz+ZvyDwbecE8thboTAoGBAIVt
Z6ATjb+gW0xC72um9jgm3EokliK9NTqbNdGECRvtToSgg9/MV6+jR0tADR+eYeYP
4z2w0cyO2eFQRv1ja1xDGDQm0Q4UUw+2dAjBbMb8/7t/RwgUDZwHYBCwEDUFTBt3
3ufhDEy1DVUsTQLxDbLowA0UFDkLLF4pfm0iPnHVAoGAdoKELYjpDNE1rpr8BNIf
6q1qCr50tvNkyEXwIsqsq0/B6v7RZqyzApJx+sI2CNI1LYvxwn3wiX2ORB3W8dYF
mwJgd8gI0eI4m12TFHzyQfASJuIW8/OaS59UmSOlF2anow4u3RDQeZHGWf3HFq0f
CQpbR8VVgM0tLd17KTfWkKQ=
-----END PRIVATE KEY-----`;

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
