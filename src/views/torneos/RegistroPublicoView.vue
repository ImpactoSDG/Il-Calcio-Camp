<template>
  <div class="registro-public-bg min-vh-100 d-flex flex-column">
    <!-- Header público -->
    <header class="public-header py-3 px-4 d-flex align-items-center justify-content-center">
      <div class="d-flex align-items-center gap-3">
        <img src="/il-calcio-camp-logo.jpg" alt="Il Calcio Camp" class="logo-img" />
        <div class="text-center">
          <div class="fw-bold text-white fs-5 lh-1">IL CALCIO CAMP</div>
          <div class="text-white-50 small">Portal de inscripción</div>
        </div>
      </div>
    </header>

    <!-- Contenido -->
    <main class="flex-grow-1 d-flex align-items-start justify-content-center py-4 px-2 px-sm-3">
      <div class="registro-card shadow-lg w-100">

        <!-- Pantalla de éxito -->
        <div v-if="registroExitoso" class="text-center py-5 px-4">
          <div class="success-icon mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
          </div>
          <h2 class="fw-bold mb-2">¡Equipo registrado!</h2>
          <p class="text-muted mb-4">
            Tu equipo <strong>{{ nombreEquipoRegistrado }}</strong> fue enviado correctamente.<br />
            Un administrador revisará la solicitud y lo confirmará próximamente.
          </p>
          <button @click="resetForm" class="btn btn-primary-modern px-5">
            Registrar otro equipo
          </button>
        </div>

        <!-- Formulario de registro -->
        <template v-else>
          <div class="p-4 pb-2 border-bottom">
            <h5 class="fw-bold mb-1">
              <i class="bi bi-people-fill me-2 text-primary-custom"></i>
              Inscripción de Equipo
            </h5>
            <p class="text-muted small mb-0">
              Completá los datos del equipo y de cada jugador para inscribirte.
            </p>
          </div>

          <form @submit.prevent="enviarRegistro" class="p-3 p-sm-4">
            <!-- Datos del equipo -->
            <div class="row g-3 mb-4">
              <div class="col-12 col-sm-6">
                <label class="form-label fw-semibold">Nombre del Equipo <span class="text-danger">*</span></label>
                <input
                  v-model.trim="form.nombre"
                  type="text"
                  class="form-control"
                  placeholder="Ej: Los Leones FC"
                  maxlength="150"
                  required
                />
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label fw-semibold">Disciplina <span class="text-danger">*</span></label>
                <input
                  v-model.trim="form.disciplina"
                  type="text"
                  class="form-control"
                  placeholder="Ej: Fútbol 5"
                  maxlength="100"
                  required
                />
              </div>
            </div>

            <!-- Jugadores -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold text-secondary mb-0">
                <i class="bi bi-person-lines-fill me-1"></i>
                Jugadores <span class="text-danger">*</span>
              </h6>
              <button type="button" class="btn btn-sm btn-outline-primary" @click="agregarJugador">
                <i class="bi bi-plus-lg me-1"></i> Agregar jugador
              </button>
            </div>

            <!-- Jugadores: tabla en desktop, tarjetas en mobile -->
            <!-- Vista desktop (tabla) -->
            <div class="d-none d-md-block table-responsive border rounded mb-3">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-3" style="min-width: 120px">Nombre <span class="text-danger">*</span></th>
                    <th style="min-width: 120px">Apellido <span class="text-danger">*</span></th>
                    <th style="min-width: 110px">DNI <span class="text-danger">*</span></th>
                    <th class="text-center" style="width: 80px">Capitán</th>
                    <th class="text-center" style="width: 80px">Arquero</th>
                    <th class="text-end pe-3" style="width: 60px"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(jugador, idx) in form.jugadores" :key="`jugador-d-${idx}`">
                    <td class="ps-3">
                      <input v-model.trim="jugador.nombre" type="text" class="form-control form-control-sm" placeholder="Nombre" maxlength="100" required />
                    </td>
                    <td>
                      <input v-model.trim="jugador.apellido" type="text" class="form-control form-control-sm" placeholder="Apellido" maxlength="100" required />
                    </td>
                    <td>
                      <input
                        v-model.trim="jugador.dni"
                        type="text"
                        class="form-control form-control-sm"
                        :class="{ 'is-invalid': jugador.dniError }"
                        placeholder="Ej: 38123456"
                        maxlength="9"
                        inputmode="numeric"
                        pattern="\d{7,9}"
                        required
                        @input="jugador.dniError = ''"
                        @blur="validarDni(jugador)"
                      />
                      <div v-if="jugador.dniError" class="invalid-feedback">{{ jugador.dniError }}</div>
                    </td>
                    <td class="text-center">
                      <input :checked="jugador.capitan" class="form-check-input" type="radio" name="capitan-d" :id="`capitan-d-${idx}`" @change="setCapitan(idx)" />
                    </td>
                    <td class="text-center">
                      <input v-model="jugador.arquero" class="form-check-input" type="checkbox" :id="`arquero-d-${idx}`" />
                    </td>
                    <td class="text-end pe-3">
                      <button type="button" class="btn btn-sm btn-link link-danger p-0" :disabled="form.jugadores.length === 1" @click="quitarJugador(idx)" title="Quitar jugador">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Vista mobile (tarjetas) -->
            <div class="d-md-none mb-3">
              <div
                v-for="(jugador, idx) in form.jugadores"
                :key="`jugador-m-${idx}`"
                class="jugador-card mb-3 p-3 border rounded position-relative"
              >
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-semibold text-secondary small">Jugador #{{ idx + 1 }}</span>
                  <button
                    type="button"
                    class="btn btn-sm btn-link link-danger p-0"
                    :disabled="form.jugadores.length === 1"
                    @click="quitarJugador(idx)"
                  >
                    <i class="bi bi-trash3"></i>
                  </button>
                </div>
                <div class="row g-2">
                  <div class="col-6">
                    <label class="form-label form-label-sm mb-1">Nombre <span class="text-danger">*</span></label>
                    <input v-model.trim="jugador.nombre" type="text" class="form-control form-control-sm" placeholder="Nombre" maxlength="100" required />
                  </div>
                  <div class="col-6">
                    <label class="form-label form-label-sm mb-1">Apellido <span class="text-danger">*</span></label>
                    <input v-model.trim="jugador.apellido" type="text" class="form-control form-control-sm" placeholder="Apellido" maxlength="100" required />
                  </div>
                  <div class="col-12">
                    <label class="form-label form-label-sm mb-1">DNI <span class="text-danger">*</span></label>
                    <input
                      v-model.trim="jugador.dni"
                      type="text"
                      class="form-control form-control-sm"
                      :class="{ 'is-invalid': jugador.dniError }"
                      placeholder="Ej: 38123456"
                      maxlength="9"
                      inputmode="numeric"
                      pattern="\d{7,9}"
                      required
                      @input="jugador.dniError = ''"
                      @blur="validarDni(jugador)"
                    />
                    <div v-if="jugador.dniError" class="invalid-feedback">{{ jugador.dniError }}</div>
                  </div>
                  <div class="col-6 d-flex align-items-center gap-2">
                    <input :checked="jugador.capitan" class="form-check-input mt-0" type="radio" name="capitan-m" :id="`capitan-m-${idx}`" @change="setCapitan(idx)" />
                    <label :for="`capitan-m-${idx}`" class="form-check-label small mb-0">Capitán</label>
                  </div>
                  <div class="col-6 d-flex align-items-center gap-2">
                    <input v-model="jugador.arquero" class="form-check-input mt-0" type="checkbox" :id="`arquero-m-${idx}`" />
                    <label :for="`arquero-m-${idx}`" class="form-check-label small mb-0">Arquero</label>
                  </div>
                </div>
              </div>
            </div>
            <!-- Alerta de error -->
            <div v-if="errorMsg" class="alert alert-danger py-2 mb-3" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ errorMsg }}
            </div>

            <div class="d-flex gap-2 justify-content-end">
              <button type="button" class="btn btn-light px-4" @click="resetForm" :disabled="guardando">
                Limpiar
              </button>
              <button type="submit" class="btn btn-primary-modern px-5" :disabled="guardando">
                <span v-if="guardando" class="spinner-border spinner-border-sm me-2"></span>
                {{ guardando ? 'Enviando...' : 'Inscribir equipo' }}
              </button>
            </div>
          </form>
        </template>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import publicApi from '@/services/publicApi.js';

const guardando = ref(false);
const registroExitoso = ref(false);
const nombreEquipoRegistrado = ref('');
const errorMsg = ref('');

const crearJugadorVacio = () => ({
  nombre: '',
  apellido: '',
  dni: '',
  capitan: false,
  arquero: false,
  dniError: '',
});

const form = ref({
  nombre: '',
  disciplina: '',
  jugadores: [crearJugadorVacio()],
});

const agregarJugador = () => {
  form.value.jugadores.push(crearJugadorVacio());
};

const quitarJugador = (idx) => {
  if (form.value.jugadores.length === 1) return;
  const eraCapitan = form.value.jugadores[idx].capitan;
  form.value.jugadores.splice(idx, 1);
  if (eraCapitan && form.value.jugadores.length > 0) {
    form.value.jugadores[0].capitan = true;
  }
};

const setCapitan = (idx) => {
  form.value.jugadores = form.value.jugadores.map((j, i) => ({
    ...j,
    capitan: i === idx,
  }));
};

const validarDni = (jugador) => {
  const dni = jugador.dni.trim();
  if (dni && !/^\d{7,9}$/.test(dni)) {
    jugador.dniError = 'El DNI debe tener entre 7 y 9 dígitos numéricos.';
  }
};

const resetForm = () => {
  form.value = {
    nombre: '',
    disciplina: '',
    jugadores: [crearJugadorVacio()],
  };
  errorMsg.value = '';
  registroExitoso.value = false;
  nombreEquipoRegistrado.value = '';
};

const enviarRegistro = async () => {
  errorMsg.value = '';

  // Validaciones locales
  for (let i = 0; i < form.value.jugadores.length; i++) {
    const j = form.value.jugadores[i];
    const dni = j.dni.trim();
    if (!dni) {
      errorMsg.value = `El DNI del jugador #${i + 1} es obligatorio.`;
      return;
    }
    if (!/^\d{7,9}$/.test(dni)) {
      errorMsg.value = `El DNI del jugador #${i + 1} debe tener entre 7 y 9 dígitos numéricos.`;
      return;
    }
  }

  // Verificar DNIs duplicados dentro del formulario
  const dnis = form.value.jugadores.map(j => j.dni.trim());
  const dnisUnicos = new Set(dnis);
  if (dnisUnicos.size !== dnis.length) {
    errorMsg.value = 'Hay jugadores con el mismo DNI en el formulario. Verificá los datos.';
    return;
  }

  guardando.value = true;
  try {
    const payload = {
      nombre: form.value.nombre,
      disciplina: form.value.disciplina,
      jugadores: form.value.jugadores.map(j => ({
        nombre: j.nombre,
        apellido: j.apellido,
        dni: j.dni.trim(),
        capitan: j.capitan,
        arquero: j.arquero,
      })),
    };

    await publicApi.post('/registro-equipo', payload);

    nombreEquipoRegistrado.value = form.value.nombre;
    registroExitoso.value = true;
  } catch (err) {
    const msg = err?.response?.data?.message || 'Error al enviar la inscripción. Intente nuevamente.';
    errorMsg.value = msg;
  } finally {
    guardando.value = false;
  }
};
</script>

<style scoped>
/* ── Paleta: rojo #c0392b, verde #27ae60, negro #111, blanco #fff ── */

.registro-public-bg {
  background: linear-gradient(160deg, #111 0%, #1a1a1a 40%, #2c0a0a 100%);
  min-height: 100dvh;
}

.public-header {
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  border-bottom: 2px solid #c0392b;
}

.public-header .text-white-50 {
  color: rgba(255,255,255,0.55) !important;
}

.logo-img {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #c0392b;
}

.registro-card {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  overflow: hidden;
  border-top: 4px solid #c0392b;
}

/* Encabezado de la card */
.registro-card .border-bottom {
  border-color: #e9ecef !important;
}

/* Ícono del título: verde */
.text-primary-custom {
  color: #27ae60 !important;
}

/* Botón principal: rojo */
.btn-primary-modern {
  background: #c0392b;
  border: none;
  color: #fff;
  border-radius: 8px;
  font-weight: 600;
  transition: background 0.2s, opacity 0.2s;
}

.btn-primary-modern:hover:not(:disabled) {
  background: #a93226;
  color: #fff;
}

.btn-primary-modern:disabled {
  background: #c0392b;
  opacity: 0.55;
  color: #fff;
}

/* Botón "Agregar jugador": verde */
.btn-outline-primary {
  color: #27ae60 !important;
  border-color: #27ae60 !important;
}

.btn-outline-primary:hover {
  background: #27ae60 !important;
  color: #fff !important;
}

/* Tarjetas mobile de jugadores */
.jugador-card {
  background: #f8f9fa;
  border-color: #dee2e6 !important;
}

/* Radio y checkbox: acento verde */
.form-check-input:checked {
  background-color: #27ae60;
  border-color: #27ae60;
}

.form-check-input:focus {
  box-shadow: 0 0 0 0.2rem rgba(39, 174, 96, 0.25);
}

/* Focus en inputs */
.form-control:focus {
  border-color: #c0392b;
  box-shadow: 0 0 0 0.2rem rgba(192, 57, 43, 0.2);
}

/* Ícono de éxito: verde */
.success-icon {
  animation: bounceIn 0.5s ease;
}

.success-icon .bi {
  color: #27ae60 !important;
}

@keyframes bounceIn {
  0%   { transform: scale(0.5); opacity: 0; }
  70%  { transform: scale(1.1); }
  100% { transform: scale(1);   opacity: 1; }
}

/* ── Responsive ajustes ── */
@media (max-width: 575.98px) {
  .registro-card {
    border-radius: 12px;
    border-top-width: 3px;
  }

  .logo-img {
    width: 40px;
    height: 40px;
  }

  .public-header {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
  }
}
</style>
