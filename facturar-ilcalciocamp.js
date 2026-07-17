import { Arca } from "@arcasdk/core";
import fs from "fs";
import path from "path";
import { fileURLToPath } from 'url';

// Configuración para módulos ES (necesario para __dirname)
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 1. Leer datos desde PHP (argumento de consola en Base64)
const args = process.argv.slice(2);
if (args.length === 0) {
    console.log(JSON.stringify({ success: false, error: "No se recibieron datos" }));
    process.exit(1);
}

// 2. Decodificar el Base64 que viene de PHP
try {
    const decodedData = Buffer.from(args[0], 'base64').toString('utf-8');
    var datos = JSON.parse(decodedData);
} catch (e) {
    console.log(JSON.stringify({ success: false, error: "Error al decodificar Base64: " + e.message }));
    process.exit(1);
}

// --- VARIABLES DINÁMICAS ---
const CUIT_EMISOR = parseInt(datos.cuit); 
const PTO_VTA = parseInt(datos.ptoVta);
const TIPO_CBTE = parseInt(datos.tipoCbte); // 1=A, 6=B, 11=C
const MONTO = parseFloat(datos.monto);
const DOC_TIPO = parseInt(datos.docTipo || 99);
const DOC_NRO = parseFloat(datos.docNro || 0); // parseFloat para permitir CUITs grandes
const CONDICION_IVA = parseInt(datos.condicionIva || 5);

// Deducir tipo de letra del tipo de comprobante AFIP
let TIPO_LETRA = 'C'; // Default
if (TIPO_CBTE === 1) TIPO_LETRA = 'A';      // Factura A
else if (TIPO_CBTE === 6) TIPO_LETRA = 'B'; // Factura B
else if (TIPO_CBTE === 11) TIPO_LETRA = 'C'; // Factura C

// Validación básica
if (isNaN(CUIT_EMISOR) || isNaN(PTO_VTA) || isNaN(TIPO_CBTE) || isNaN(MONTO)) {
    console.log(JSON.stringify({ 
        success: false, 
        error: `Datos inválidos recibidos: CUIT=${datos.cuit}, PTO=${datos.ptoVta}, TIPO=${datos.tipoCbte}, MONTO=${datos.monto}` 
    }));
    process.exit(1);
}

async function facturar() {
    try {
        // 3. Configuración de ARCA
        // ⚠️ RUTAS ACTUALIZADAS PARA IL-CALCIO-CAMP
        const arca = new Arca({
            cuit: CUIT_EMISOR,
            // Certificados específicos de Il-Calcio-Camp
            cert: fs.readFileSync(path.join(__dirname, "ilcalciocamp_48644b2d9483b5d5.crt"), "utf8"),
            key: fs.readFileSync(path.join(__dirname, "clave_privada_ilcalcio.key"), "utf8"),
            production: true, // TRUE = Producción real
            useHttpsAgent: true // <--- ¡CRUCIAL PARA CORREGIR EL ERROR 'DH KEY TOO SMALL'!
        });

        // 4. Obtener último comprobante
        let ultimoCbte;
        try {
            ultimoCbte = await arca.electronicBillingService.getLastVoucher(PTO_VTA, TIPO_CBTE);
        } catch (err) {
            throw new Error(`Error conectando con AFIP (GetLastVoucher): ${err.message}`);
        }

        // Parseo robusto del último número
        let nroUltimo = 0;
        if (typeof ultimoCbte === 'object' && ultimoCbte !== null) {
            nroUltimo = parseInt(ultimoCbte.cbteNro || 0);
        } else {
            nroUltimo = parseInt(ultimoCbte || 0);
        }

        const proximoCbte = nroUltimo + 1;

        // 5. Cálculos Matemáticos (Neto e IVA)
        let importeNeto, importeIVA;
        
        if (TIPO_LETRA === 'A' || TIPO_LETRA === 'B') { 
            // Factura A o B (Responsable Inscripto)
            // Se asume IVA 21%. Si vendes al 10.5% debes ajustar esto.
            importeNeto = parseFloat((MONTO / 1.21).toFixed(2));
            importeIVA = parseFloat((MONTO - importeNeto).toFixed(2));
        } else { 
            // Factura C (Monotributo)
            importeNeto = MONTO;
            importeIVA = 0;
        }

        // 6. Armado del objeto Factura
        const voucherData = {
            CantReg: 1,
            PtoVta: PTO_VTA,
            CbteTipo: TIPO_CBTE,
            Concepto: parseInt(datos.concepto || 1), // 1=Productos, 2=Servicios
            DocTipo: DOC_TIPO, 
            DocNro: DOC_NRO,
            CbteDesde: proximoCbte,
            CbteFch: parseInt(datos.cbteFch || new Date().toISOString().slice(0, 10).replace(/-/g, "")),
            CbteHasta: proximoCbte,
            ImpTotal: MONTO,
            ImpTotConc: 0,
            ImpNeto: importeNeto,
            ImpOpEx: 0,
            ImpIVA: importeIVA,
            ImpTrib: 0,
            MonId: "PES",
            MonCotiz: 1,
        };

        // Si es Servicios (Concepto 2 o 3), requiere fechas
        if (voucherData.Concepto > 1) {
            const hoyInt = parseInt(new Date().toISOString().slice(0, 10).replace(/-/g, ""));
            voucherData.FchServDesde = parseInt(datos.fechaDesde || hoyInt);
            voucherData.FchServHasta = parseInt(datos.fechaHasta || hoyInt);
            voucherData.FchVtoPago = parseInt(datos.fechaVto || hoyInt);
        }

        // Agregar Array de IVA solo si NO es Factura C
        if (TIPO_LETRA !== 'C') {
            voucherData.Iva = [
                {
                    Id: 5, // 5 = 21%
                    BaseImp: importeNeto,
                    Importe: importeIVA,
                },
            ];
        }

        // 7. Enviar a AFIP
        const invoice = await arca.electronicBillingService.createVoucher(voucherData);

        // 8. Procesar Respuesta - DEBUGGING (a stderr, no a stdout)
        process.stderr.write("[DEBUG ARCA RESPONSE] Estructura completa: " + JSON.stringify(invoice, null, 2) + "\n");

        // Arca SDK a veces devuelve la respuesta cruda o procesada, unificamos:
        const resp = invoice.response || invoice.res || invoice;
        const detResp = resp.FeDetResp || resp; 
        
        process.stderr.write("[DEBUG DETRESP] " + JSON.stringify(detResp, null, 2) + "\n");
        
        // Intentar extraer CAE de los distintos lugares posibles
        let cae = null;
        let vto = null;
        
        // Intenta varias estructuras posibles
        if (detResp.FECAEDetResponse && Array.isArray(detResp.FECAEDetResponse) && detResp.FECAEDetResponse[0]) {
            cae = detResp.FECAEDetResponse[0].CAE;
            vto = detResp.FECAEDetResponse[0].CAEFchVto;
        } else if (invoice.cae) {
            cae = invoice.cae;
            vto = invoice.caeFchVto;
        } else if (resp.CAE) {
            cae = resp.CAE;
            vto = resp.CAEFchVto;
        }
        
        process.stderr.write("[DEBUG EXTRACTED] " + JSON.stringify({ cae, vto }) + "\n");
        
        // Éxito - SOLO ESTO VA A STDOUT
        console.log(JSON.stringify({
            success: true,
            cae: cae || "",
            vto: vto || "",
            nro: proximoCbte,
            tipo: TIPO_CBTE,
            ptovta: PTO_VTA,
            data: voucherData // Devolvemos lo que mandamos por si acaso
        }));

    } catch (error) {
        // Error
        console.log(JSON.stringify({ 
            success: false, 
            error: error.message || "Error desconocido en Node.js"
        }));
    }
}

facturar();
