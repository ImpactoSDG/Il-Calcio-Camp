<template>
  <div class="container-fluid p-4 bg-white min-vh-100 position-relative animate-fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
      <div class="d-flex align-items-center">
        <button @click="$router.back()" class="btn-back-arrow me-2" title="Volver">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h1 class="h5 fw-bold mb-0 text-secondary">GESTION DE TORNEOS</h1>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-lg mb-4">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-bold text-secondary mb-0">Torneos</h2>
          <small class="text-muted">Haz click en un torneo para gestionarlo</small>
        </div>

        <div v-if="loadingTorneos" class="text-center py-3 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span>
          Cargando torneos...
        </div>

        <div v-else-if="torneos.length === 0" class="alert alert-warning mb-0">
          No hay torneos disponibles.
        </div>

        <div v-else class="torneo-grid">
          <button
            v-for="torneo in torneos"
            :key="torneo.id"
            type="button"
            class="torneo-card"
            :class="{ active: Number(idTorneoSeleccionado) === Number(torneo.id) }"
            @click="cargarDetalle(Number(torneo.id))"
            :disabled="loadingDetalle"
          >
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-semibold text-start">{{ torneo.nombre }}</div>
                <div class="small text-muted text-start">{{ torneo.disciplina_nombre || 'Sin disciplina' }}</div>
              </div>
              <span class="badge rounded-pill text-bg-light">#{{ torneo.id }}</span>
            </div>
            <div class="text-start mt-2">
              <span
                class="badge rounded-pill torneo-estado-pill"
                :class="getEstadoTorneoBadgeClass(torneo.estado_torneo_descripcion)"
              >
                {{ torneo.estado_torneo_descripcion || 'Sin estado' }}
              </span>
            </div>
            <div
              v-if="loadingDetalle && Number(idTorneoSeleccionado) === Number(torneo.id)"
              class="small mt-1 text-primary text-start"
            >
              Cargando...
            </div>
          </button>

          <button
            type="button"
            class="torneo-card torneo-card-create"
            title="Crear nuevo torneo"
            @click="$router.push('/plantorneo')"
          >
            <div class="torneo-card-create-content" aria-hidden="true">
              <i class="bi bi-plus-lg torneo-card-create-icon"></i>
            </div>
          </button>
        </div>
      </div>
    </div>

    <div v-if="idTorneoSeleccionado && !detalle && !loadingDetalle" class="alert alert-info">
      Selecciona un torneo del mosaico para ver su información.
    </div>

    <div v-if="detalle" class="card shadow-sm border-0 rounded-lg">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="h6 fw-bold text-secondary mb-0">Gestión del torneo: {{ detalle.torneo.nombre }}</h2>
          <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary" @click="cargarDetalle()" :disabled="loadingDetalle">Recargar</button>

            <div class="position-relative">
              <button class="btn btn-outline-secondary px-2" @click="showAccionesMenu = !showAccionesMenu" title="Acciones torneo">
                <i class="bi bi-three-dots-vertical"></i>
              </button>

              <div v-if="showAccionesMenu" class="acciones-menu shadow-sm">
                <button class="acciones-item acciones-item-danger" @click="abrirEliminarTorneoModal">
                  Eliminar torneo
                </button>
              </div>
            </div>
          </div>
        </div>

        <ul class="nav torneo-tabs mb-4">
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'resumen' }" @click="tabActiva = 'resumen'">
              <i class="bi bi-grid me-1"></i>Resumen
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'inscripciones' }" @click="tabActiva = 'inscripciones'">
              <i class="bi bi-person-plus me-1"></i>Inscripciones
              <span class="badge ms-1" :class="tabActiva === 'inscripciones' ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary'">
                {{ detalle.inscripciones?.total || 0 }}
              </span>
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'asignaciones' }" @click="tabActiva = 'asignaciones'">
              <i class="bi bi-diagram-3 me-1"></i>Asignaciones
              <span class="badge ms-1" :class="tabActiva === 'asignaciones' ? 'bg-info text-white' : 'bg-info-subtle text-info'">
                {{ detalle.inscripciones?.asignadas || 0 }}
              </span>
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'programacion' }" @click="tabActiva = 'programacion'">
              <i class="bi bi-calendar3 me-1"></i>Programación
            </button>
          </li>
          <li class="nav-item">
            <button class="torneo-tab" :class="{ active: tabActiva === 'calendario' }" @click="tabActiva = 'calendario'">
              <i class="bi bi-calendar-week me-1"></i>Calendario
            </button>
          </li>
        </ul>

        <template v-if="tabActiva === 'resumen'">
          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="resumen-box h-100">
                <p class="mb-1"><strong>{{ detalle.torneo.nombre }}</strong></p>
                <p class="mb-1 text-muted">Disciplina: {{ detalle.torneo.disciplina_nombre || '-' }}</p>
                <p class="mb-1 text-muted">Estado: {{ detalle.torneo.estado_nombre || '-' }}</p>
                <p class="mb-0 text-muted">Formato: {{ detalle.torneo.formato_manual || '-' }}</p>
              </div>
            </div>
            <div class="col-12 col-lg-6">
              <div class="resumen-box h-100">
                <p class="mb-1 text-muted">Eventos: {{ detalle.eventos.total }} (zonas: {{ detalle.eventos.zona }}, eliminación: {{ detalle.eventos.eliminacion }})</p>
                <p class="mb-1 text-muted">Partidos programados: {{ detalle.eventos.programados || 0 }} de {{ detalle.eventos.total || 0 }}</p>
                <p class="mb-1 text-muted">Partidos finalizados: {{ detalle.eventos.finalizados || 0 }}</p>
                <p class="mb-1 text-muted">Inscripciones: {{ detalle.inscripciones?.total || 0 }}</p>
                <p class="mb-1 text-muted">Cobros por partido: {{ pagosPartidoResumen.pagados }} de {{ pagosPartidoResumen.total }}</p>
                <p class="mb-0 text-muted">Equipos asignados: {{ detalle.inscripciones?.asignadas || 0 }}</p>
              </div>
            </div>
          </div>
        </template>

        <template v-if="tabActiva === 'inscripciones'">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <p class="mb-1 text-muted">Listado de equipos inscriptos al torneo.</p>
              <div class="d-flex align-items-center gap-2 small">
                <span class="text-secondary">Inscriptos:</span>
                <span class="badge rounded-pill" :class="inscripcionesCompletas ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                  {{ detalle.inscripciones?.total || 0 }}/{{ totalInscriptosObjetivo }}
                </span>
              </div>
              <div v-if="cupoLleno" class="small text-danger mt-1">
                Cupo completo: no se permiten nuevas inscripciones.
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-outline-success" @click="abrirBulkInscripcionModal" :disabled="cupoLleno" :title="cupoLleno ? 'Cupo completo' : ''">
                <i class="bi bi-check2-all me-1"></i>Inscribir varios
              </button>
              <button class="btn btn-outline-primary" @click="abrirInscripcionesModal" :disabled="cupoLleno" :title="cupoLleno ? 'Cupo completo' : ''">Agregar nueva inscripción</button>
            </div>
          </div>

          <div v-if="!detalle.inscriptos?.length" class="alert alert-info mb-0">
            Este torneo aún no tiene equipos inscriptos.
          </div>

          <div v-else class="table-responsive">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Equipo</th>
                  <th>Fecha inscripción</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in detalle.inscriptos" :key="item.id">
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img v-if="item.escudo" :src="resolveEscudoUrl(item.escudo)" alt="escudo" class="escudo-thumb" />
                      <span>{{ item.equipo_nombre }}</span>
                    </div>
                  </td>
                  <td>{{ item.fecha_inscripcion || '-' }}</td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-2">
                      <button class="btn btn-sm btn-outline-danger" @click="eliminarInscripcion(item)">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="h6 mb-0">Pagos por partido</h3>
              <span class="small text-muted">Monto por equipo: {{ formatMoney(detalle?.torneo?.valor_inscripcion) }}</span>
            </div>
            <p class="small text-muted mb-3">
              Registrá el pago por equipo para cada próximo partido y adjuntá comprobante si corresponde.
            </p>

            <div v-if="!eventosPagoPartido.length" class="alert alert-info mb-0">
              No hay próximos partidos con equipos asignados para gestionar pagos.
            </div>

            <div v-else class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Partido</th>
                    <th>Fecha/Hora</th>
                    <th>Equipo local</th>
                    <th>Equipo visitante</th>
                    <th>Estado pagos</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ev in eventosPagoPartido" :key="`pago-evento-${ev.id}`">
                    <td>
                      <div class="fw-semibold">{{ ev.titulo || `Partido ${ev.id}` }}</div>
                      <div class="small text-muted">Estado: {{ ev.estado_evento_descripcion || '-' }}</div>
                    </td>
                    <td>{{ formatearFechaHora(ev.fecha_hora_inicio) }}</td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img v-if="ev.equipo_local_escudo" :src="resolveEscudoUrl(ev.equipo_local_escudo)" alt="escudo" class="escudo-thumb" />
                        <span>{{ ev.equipo_local_nombre || '-' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <img v-if="ev.equipo_visitante_escudo" :src="resolveEscudoUrl(ev.equipo_visitante_escudo)" alt="escudo" class="escudo-thumb" />
                        <span>{{ ev.equipo_visitante_nombre || '-' }}</span>
                      </div>
                    </td>
                    <td>
                      <div class="d-flex flex-column gap-1">
                        <span
                          class="badge rounded-pill align-self-start"
                          :class="Number(ev.pago_local_realizado || 0) === 1 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'"
                        >
                          Local: {{ Number(ev.pago_local_realizado || 0) === 1 ? 'Pagado' : 'Pendiente' }}
                        </span>
                        <span
                          class="badge rounded-pill align-self-start"
                          :class="Number(ev.pago_visitante_realizado || 0) === 1 ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'"
                        >
                          Visitante: {{ Number(ev.pago_visitante_realizado || 0) === 1 ? 'Pagado' : 'Pendiente' }}
                        </span>
                      </div>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary" @click="abrirPagoEventoModal(ev)">
                        Gestionar
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <template v-if="tabActiva === 'asignaciones'">
          <ul class="nav nav-pills mb-3 asignaciones-subtabs">
            <li class="nav-item">
              <button class="nav-link" :class="{ active: subTabAsignaciones === 'zonas' }" @click="subTabAsignaciones = 'zonas'">
                Asignaciones de zonas
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link" :class="{ active: subTabAsignaciones === 'cruces' }" @click="subTabAsignaciones = 'cruces'">
                Asignaciones de cruces
              </button>
            </li>
          </ul>

          <template v-if="subTabAsignaciones === 'zonas'">
            <div v-if="!detalle.grupos?.length" class="alert alert-warning mb-0">
              Este torneo no tiene grupos configurados (fase de zonas).
            </div>

            <template v-else-if="asignacionesCompletas">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success rounded-pill"><i class="bi bi-check-circle me-1"></i>Asignaciones completas</span>
                    <span v-if="hayPartidosJugados" class="badge bg-danger-subtle text-danger rounded-pill">
                      <i class="bi bi-lock me-1"></i>Hay partidos jugados
                    </span>
                  </div>
                  <p class="small text-muted mb-0">Los equipos ya quedaron distribuidos en sus grupos.</p>
                </div>
                <button
                  class="btn btn-outline-danger btn-sm"
                  :disabled="hayPartidosJugados || savingEliminarAsignaciones"
                  :title="hayPartidosJugados ? 'No se puede eliminar: hay partidos finalizados' : 'Eliminar todas las asignaciones'"
                  @click="eliminarAsignaciones"
                >
                  <span v-if="savingEliminarAsignaciones" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="bi bi-trash me-1"></i>
                  Eliminar asignaciones
                </button>
              </div>

              <div class="row g-3">
                <div v-for="grupo in asignacionesPorGrupo" :key="grupo.id" class="col-12 col-md-6">
                  <div class="group-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h3 class="h6 mb-0">{{ grupo.nombre }}</h3>
                      <small class="text-muted">{{ grupo.equipos.length }}/{{ grupo.cantidad_equipos_objetivo }}</small>
                    </div>
                    <div v-if="!grupo.equipos.length" class="text-muted small">Sin equipos asignados.</div>
                    <ul v-else class="list-unstyled mb-0">
                      <li
                        v-for="asig in grupo.equipos"
                        :key="asig.id_equipo"
                        class="d-flex align-items-center gap-2 py-1 border-bottom"
                      >
                        <span class="text-muted small" style="width:1.4rem;text-align:right;">{{ asig.posicion_inicial }}.</span>
                        <img v-if="asig.escudo" :src="resolveEscudoUrl(asig.escudo)" alt="escudo" class="escudo-thumb" />
                        <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                        <span class="fw-semibold">{{ asig.equipo_nombre }}</span>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </template>

            <template v-else>
              <div class="table-responsive mb-3">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Grupo</th>
                      <th class="text-center">Asignados</th>
                      <th class="text-center">Cupo</th>
                      <th class="text-center">Estado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="grupo in detalle.grupos" :key="grupo.id">
                      <td>{{ grupo.nombre }}</td>
                      <td class="text-center">{{ grupo.asignados }}</td>
                      <td class="text-center">{{ grupo.cantidad_equipos_objetivo }}</td>
                      <td class="text-center">
                        <span class="badge rounded-pill" :class="Number(grupo.asignados) >= Number(grupo.cantidad_equipos_objetivo) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                          {{ Number(grupo.asignados) >= Number(grupo.cantidad_equipos_objetivo) ? 'Completo' : 'Pendiente' }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="pool-box mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Equipos para sorteo</strong>
                  <small class="text-muted">{{ selectedPool.length }}/{{ totalSlots }} seleccionados</small>
                </div>
                <div class="row g-2">
                  <div v-for="equipo in poolTeams" :key="equipo.id" class="col-12 col-md-6">
                    <label class="pool-item">
                      <input type="checkbox" :value="Number(equipo.id)" v-model="selectedPool" />
                      <img v-if="equipo.escudo" :src="resolveEscudoUrl(equipo.escudo)" alt="escudo" class="escudo-thumb" />
                      <span class="me-auto">{{ equipo.nombre }}</span>
                      <span v-if="equipo.asignado" class="badge rounded-pill bg-info-subtle text-info">Asignado</span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="row g-3">
                <div v-for="grupo in detalle.grupos" :key="grupo.id" class="col-12 col-md-6">
                  <div class="group-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <h3 class="h6 mb-0">{{ grupo.nombre }}</h3>
                      <small class="text-muted">{{ grupo.asignados }}/{{ grupo.cantidad_equipos_objetivo }}</small>
                    </div>

                    <div v-for="pos in Number(grupo.cantidad_equipos_objetivo || 0)" :key="`${grupo.id}-${pos}`" class="mb-2">
                      <label class="form-label small mb-1">Posición {{ pos }}</label>
                      <select class="form-select form-select-sm" :value="getSelected(grupo.id, pos)" @change="setSelected(grupo.id, pos, $event.target.value)">
                        <option value="">Sin asignar</option>
                        <option v-for="equipo in getAvailableOptions(grupo.id, pos)" :key="equipo.id" :value="equipo.id">
                          {{ equipo.nombre }}
                        </option>
                      </select>
                      <div v-if="getSelectedTeam(grupo.id, pos)?.escudo" class="mt-1 small text-muted d-flex align-items-center gap-2">
                        <img :src="resolveEscudoUrl(getSelectedTeam(grupo.id, pos).escudo)" alt="escudo" class="escudo-thumb" />
                        Escudo cargado
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between mt-3">
                <div class="d-flex gap-2">
                  <button class="btn btn-outline-secondary" @click="limpiarAsignacion" :disabled="savingAsignacion">Limpiar</button>
                  <button class="btn btn-outline-primary" @click="asignarAleatorio" :disabled="savingAsignacion">Asignar aleatorio</button>
                </div>
                <button class="btn btn-success" @click="guardarAsignaciones" :disabled="savingAsignacion">
                  <span v-if="savingAsignacion" class="spinner-border spinner-border-sm me-2"></span>
                  Guardar asignación
                </button>
              </div>
            </template>
          </template>

          <template v-else>
            <div v-if="!crucesHabilitados" class="alert alert-warning mb-3">
              Los cruces se habilitan cuando la fase de zonas está concretada (todos los partidos de zona finalizados) o cuando el torneo no tiene fase de zonas.
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="small text-muted">
                Cruces definidos: <strong>{{ crucesConEquiposDefinidos }}</strong>/<strong>{{ eventosCruceProgramacion.length }}</strong>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill" :class="crucesHabilitados ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                  {{ crucesHabilitados ? 'Cruces habilitados' : 'Cruces bloqueados' }}
                </span>
                <button
                  class="btn btn-sm btn-outline-primary"
                  @click="asignarCruces"
                  :disabled="savingAsignacionCruces || !crucesHabilitados || !eventosCruceProgramacion.length"
                >
                  <span v-if="savingAsignacionCruces" class="spinner-border spinner-border-sm me-2"></span>
                  Asignar equipos a cruces
                </button>
              </div>
            </div>

            <div v-if="!eventosCruceProgramacion.length" class="alert alert-info mb-0">
              No hay cruces configurados para este torneo.
            </div>

            <div v-else class="cruce-bracket-box mb-3">
              <div class="small fw-semibold text-secondary mb-2">Visualización de llave</div>
              <div class="cruce-bracket-scroll">
                <div class="cruce-bracket-grid" :style="{ '--round-count': Math.max(1, cruceRounds.length) }">
                  <div v-for="(ronda, roundIndex) in cruceRounds" :key="`${ronda.nombre}-${roundIndex}`" class="cruce-round-column">
                    <h3 class="cruce-round-title">{{ ronda.nombre }}</h3>

                    <div class="cruce-round-track" :style="getCruceRoundTrackStyle(roundIndex, ronda.partidos.length)">
                      <div
                        v-for="(partido, partidoIndex) in ronda.partidos"
                        :key="partido.id"
                        class="cruce-match-wrapper"
                        :style="getCruceMatchStyle(roundIndex, partidoIndex)"
                      >
                        <div
                          class="cruce-match-card"
                          :class="{
                            'has-next': roundIndex < cruceRounds.length - 1,
                            'has-prev': roundIndex > 0,
                          }"
                        >
                          <div class="cruce-team-line">
                            <img v-if="partido.local.escudo" :src="resolveEscudoUrl(partido.local.escudo)" alt="escudo local" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.local.nombre || 'Por definir' }}</span>
                          </div>
                          <div class="cruce-team-line">
                            <img v-if="partido.visitante.escudo" :src="resolveEscudoUrl(partido.visitante.escudo)" alt="escudo visitante" class="escudo-thumb" />
                            <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                            <span class="team-name">{{ partido.visitante.nombre || 'Por definir' }}</span>
                          </div>
                          <div v-if="partido.resultado !== null" class="cruce-score-pill">
                            {{ partido.resultado }}
                          </div>
                        </div>
                      </div>

                      <div
                        v-if="roundIndex < cruceRounds.length - 1"
                        v-for="pairIndex in Math.floor(ronda.partidos.length / 2)"
                        :key="`cruce-merge-${roundIndex}-${pairIndex}`"
                        class="cruce-round-merge"
                        :style="getCruceMergeStyle(roundIndex, pairIndex - 1)"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="eventosCruceProgramacion.length" class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Cruce</th>
                    <th>Local</th>
                    <th>Visitante</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ev in eventosCruceProgramacion" :key="ev.id">
                    <td>{{ ev.titulo }}</td>
                    <td>
                      <template v-if="isEditingCruceManual(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getCruceDraft(ev.id).id_equipo_local ?? ''"
                          @change="setCruceDraftField(ev.id, 'id_equipo_local', $event.target.value)"
                          :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                        >
                          <option value="">Por definir</option>
                          <option v-for="equipo in equiposCruceOptions" :key="`l-${ev.id}-${equipo.id}`" :value="equipo.id">
                            {{ equipo.nombre }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getCruceEquipoDisplay(ev, 'local').nombre || 'Por definir' }}</span>
                      </template>
                    </td>
                    <td>
                      <template v-if="isEditingCruceManual(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getCruceDraft(ev.id).id_equipo_visitante ?? ''"
                          @change="setCruceDraftField(ev.id, 'id_equipo_visitante', $event.target.value)"
                          :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                        >
                          <option value="">Por definir</option>
                          <option v-for="equipo in equiposCruceOptions" :key="`v-${ev.id}-${equipo.id}`" :value="equipo.id">
                            {{ equipo.nombre }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getCruceEquipoDisplay(ev, 'visitante').nombre || 'Por definir' }}</span>
                      </template>
                    </td>
                    <td>
                      <span class="badge rounded-pill" :class="ev.equipo_local_nombre && ev.equipo_visitante_nombre ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">
                        {{ ev.equipo_local_nombre && ev.equipo_visitante_nombre ? 'Definido' : 'Pendiente' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-2">
                        <button
                          v-if="!isEditingCruceManual(ev.id)"
                          class="btn btn-sm btn-outline-primary"
                          @click="editarCruceManual(ev)"
                          :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                        >
                          Editar
                        </button>

                        <template v-else>
                          <button
                            class="btn btn-sm btn-outline-secondary"
                            @click="cancelarEdicionCruceManual(ev)"
                            :disabled="isSavingCruceManual(ev.id)"
                          >
                            Cancelar
                          </button>
                          <button
                            class="btn btn-sm btn-success"
                            @click="guardarAsignacionCruceManual(ev)"
                            :disabled="!crucesHabilitados || isSavingCruceManual(ev.id)"
                          >
                            <span v-if="isSavingCruceManual(ev.id)" class="spinner-border spinner-border-sm me-1"></span>
                            Guardar
                          </button>
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </template>

        <template v-if="tabActiva === 'programacion'">
          <div v-if="loadingProgramacion" class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Cargando datos de programación...
          </div>

          <template v-else>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="small text-muted">
                Total partidos: <strong>{{ programacionEventos.length }}</strong> · Seleccionados: <strong>{{ selectedProgramacionIds.length }}</strong>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-danger" @click="deshacerProgramacion" :disabled="savingProgramacion">
                  <span v-if="savingProgramacion" class="spinner-border spinner-border-sm me-2"></span>
                  Deshacer programación seleccionados
                </button>
                <button class="btn btn-primary" @click="abrirModalProgramacionSeleccionados" :disabled="!selectedProgramacionIds.length || savingProgramacion">
                  <i class="bi bi-calendar-plus me-1"></i>Programar seleccionados
                </button>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th style="width: 42px" class="text-center">
                      <input
                        type="checkbox"
                        class="form-check-input"
                        :checked="allProgramacionSelected"
                        :indeterminate.prop="hasSomeProgramacionSelected && !allProgramacionSelected"
                        @change="toggleSeleccionTodosProgramacion"
                      />
                    </th>
                    <th>Partido</th>
                    <th>Equipo local</th>
                    <th>Equipo visitante</th>
                    <th>Estado</th>
                    <th>Fecha/Hora</th>
                    <th>Cancha</th>
                    <th>Árbitro</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="ev in programacionEventos"
                    :key="ev.id"
                    :class="{ 'programacion-row-selected': selectedProgramacionIds.includes(Number(ev.id)) }"
                  >
                    <td class="text-center">
                      <input type="checkbox" class="form-check-input" :value="Number(ev.id)" v-model="selectedProgramacionIds" />
                    </td>
                    <td>{{ ev.titulo }}</td>
                    <td>{{ ev.equipo_local_nombre || 'Por definir' }}</td>
                    <td>{{ ev.equipo_visitante_nombre || 'Por definir' }}</td>
                    <td>{{ ev.estado_evento_descripcion || '-' }}</td>
                    <td>
                      <template v-if="isEditingProgramacion(ev.id)">
                        <input
                          type="datetime-local"
                          class="form-control form-control-sm"
                          :value="getProgramacionDraft(ev.id).fecha_hora_inicio"
                          @change="setProgramacionDraftField(ev.id, 'fecha_hora_inicio', $event.target.value)"
                        />
                      </template>
                      <template v-else>
                        <span>{{ formatearFechaHora(ev.fecha_hora_inicio) }}</span>
                      </template>
                    </td>
                    <td>
                      <template v-if="isEditingProgramacion(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getProgramacionDraft(ev.id).id_cancha"
                          @change="setProgramacionDraftField(ev.id, 'id_cancha', $event.target.value ? Number($event.target.value) : null)"
                        >
                          <option :value="null">Seleccionar cancha</option>
                          <option v-for="c in (programacionData?.canchas || [])" :key="c.id" :value="Number(c.id)">
                            {{ c.nombre }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getProgramacionCanchaLabel(ev) }}</span>
                      </template>
                    </td>
                    <td>
                      <template v-if="isEditingProgramacion(ev.id)">
                        <select
                          class="form-select form-select-sm"
                          :value="getProgramacionDraft(ev.id).id_arbitro"
                          @change="setProgramacionDraftField(ev.id, 'id_arbitro', $event.target.value ? Number($event.target.value) : null)"
                        >
                          <option :value="null">Seleccionar árbitro</option>
                          <option v-for="a in (programacionData?.arbitros || [])" :key="a.id" :value="Number(a.id)">
                            {{ a.nombre_completo || `${a.apellido || ''} ${a.nombre || ''}`.trim() }}
                          </option>
                        </select>
                      </template>
                      <template v-else>
                        <span>{{ getProgramacionArbitroLabel(ev) }}</span>
                      </template>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-2">
                        <button
                          v-if="!isEditingProgramacion(ev.id)"
                          class="btn btn-sm btn-outline-primary"
                          @click="editarProgramacionEvento(ev)"
                          :disabled="savingProgramacion"
                        >
                          Editar
                        </button>

                        <template v-else>
                          <button
                            class="btn btn-sm btn-outline-secondary"
                            @click="cancelarEdicionProgramacionEvento(ev)"
                            :disabled="savingProgramacion"
                          >
                            Cancelar
                          </button>
                          <button
                            class="btn btn-sm btn-success"
                            @click="guardarProgramacionEvento(ev.id)"
                            :disabled="savingProgramacion"
                          >
                            Guardar
                          </button>
                        </template>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!programacionEventos.length">
                    <td colspan="9" class="text-center text-muted py-3">No hay partidos para mostrar.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </template>

        <template v-if="tabActiva === 'calendario'">
          <div class="small text-muted mb-3">
            Vista mensual de partidos programados del torneo. Este componente es reusable para otras pantallas.
          </div>
          <TorneoCalendar :eventos="eventosCalendario" :torneo-nombre="detalle?.torneo?.nombre || ''" />
        </template>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="showProgramacionModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Programar partidos seleccionados</h5>
              <button type="button" class="btn-close" @click="showProgramacionModal = false"></button>
            </div>

            <div class="modal-body">
              <div class="small text-muted mb-3">
                Se programarán <strong>{{ selectedProgramacionIds.length }}</strong> partido(s).
              </div>

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label small mb-1">Fecha inicio</label>
                  <input type="date" class="form-control" v-model="programacionForm.fecha_inicio" />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label small mb-1">Duración por partido (min)</label>
                  <input type="number" min="20" max="240" step="5" class="form-control" v-model.number="programacionForm.duracion_minutos" />
                </div>
              </div>

              <div class="mt-3">
                <div class="small fw-semibold mb-2">Franjas por día</div>
                <div class="row g-2">
                  <div v-for="franja in programacionForm.franjas" :key="franja.dia_semana" class="col-12 col-lg-6">
                    <div class="franja-row">
                      <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" v-model="franja.activa" />
                        <span class="small fw-semibold">{{ franja.nombre }}</span>
                      </div>
                      <div class="d-flex align-items-center gap-2">
                        <input type="time" class="form-control form-control-sm" style="max-width: 120px" v-model="franja.hora_inicio" :disabled="!franja.activa" />
                        <span class="small text-muted">a</span>
                        <input type="time" class="form-control form-control-sm" style="max-width: 120px" v-model="franja.hora_fin" :disabled="!franja.activa" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-12 col-lg-6">
                  <div class="small fw-semibold mb-2">Canchas</div>
                  <div class="selector-box">
                    <label v-for="c in (programacionData?.canchas || [])" :key="c.id" class="selector-item">
                      <input type="checkbox" :value="Number(c.id)" v-model="programacionForm.id_canchas" />
                      <span>{{ c.nombre }}</span>
                    </label>
                  </div>
                </div>
                <div class="col-12 col-lg-6">
                  <div class="small fw-semibold mb-2">Árbitros</div>
                  <div class="selector-box">
                    <label v-for="a in (programacionData?.arbitros || [])" :key="a.id" class="selector-item">
                      <input type="checkbox" :value="Number(a.id)" v-model="programacionForm.id_arbitros" />
                      <span>{{ a.nombre_completo || `${a.apellido || ''} ${a.nombre || ''}`.trim() }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" @click="showProgramacionModal = false">Cancelar</button>
              <button class="btn btn-primary" @click="programarSeleccionados" :disabled="savingProgramacion || !selectedProgramacionIds.length">
                <span v-if="savingProgramacion" class="spinner-border spinner-border-sm me-2"></span>
                Programar {{ selectedProgramacionIds.length }} partido(s)
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showBulkInscripcionModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><i class="bi bi-people-fill me-2 text-success"></i>Inscribir varios equipos</h5>
              <button type="button" class="btn-close" @click="showBulkInscripcionModal = false"></button>
            </div>

            <div class="modal-body">
              <div v-if="!equiposSinInscribir.length" class="alert alert-info mb-0">
                Todos los equipos disponibles ya están inscriptos en este torneo.
              </div>

              <template v-else>
                <p class="small text-muted mb-3">Seleccioná los equipos a inscribir en el torneo.</p>

                <!-- Seleccionar todos -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label class="form-check-label small fw-semibold">
                    <input
                      type="checkbox"
                      class="form-check-input me-2"
                      :checked="bulkSeleccionados.length === equiposSinInscribir.length && equiposSinInscribir.length > 0"
                      :indeterminate.prop="bulkSeleccionados.length > 0 && bulkSeleccionados.length < equiposSinInscribir.length"
                      @change="toggleBulkTodos"
                    />
                    Seleccionar todos ({{ equiposSinInscribir.length }})
                  </label>
                  <span class="badge bg-primary-subtle text-primary rounded-pill">
                    {{ bulkSeleccionados.length }} seleccionado(s)
                  </span>
                </div>

                <!-- Lista de equipos -->
                <div class="bulk-equipo-list">
                  <label
                    v-for="equipo in equiposSinInscribir"
                    :key="equipo.id"
                    class="bulk-equipo-row"
                    :class="{ 'bulk-equipo-row--selected': bulkSeleccionados.includes(equipo.id) }"
                  >
                    <input
                      type="checkbox"
                      class="form-check-input flex-shrink-0"
                      :value="equipo.id"
                      v-model="bulkSeleccionados"
                    />
                    <img v-if="equipo.escudo" :src="resolveEscudoUrl(equipo.escudo)" alt="escudo" class="escudo-thumb" />
                    <span v-else class="escudo-thumb-placeholder"><i class="bi bi-shield"></i></span>
                    <span class="fw-semibold">{{ equipo.nombre }}</span>
                  </label>
                </div>
              </template>
            </div>

            <div class="modal-footer d-flex justify-content-between align-items-center">
              <small class="text-muted">Se inscribirán {{ bulkSeleccionados.length }} equipo(s) en un solo envío.</small>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" @click="showBulkInscripcionModal = false">Cancelar</button>
                <button
                  class="btn btn-success"
                  :disabled="savingBulkInscripciones || !bulkSeleccionados.length"
                  @click="guardarBulkInscripciones"
                >
                  <span v-if="savingBulkInscripciones" class="spinner-border spinner-border-sm me-2"></span>
                  Inscribir {{ bulkSeleccionados.length > 0 ? bulkSeleccionados.length : '' }} equipo(s)
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showEliminarTorneoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Eliminar torneo</h5>
              <button type="button" class="btn-close" @click="cancelarEliminarTorneo"></button>
            </div>
            <div class="modal-body">
              <p class="text-muted small mb-2">
                Esta acción hará borrado lógico del torneo. Para confirmar, escribe el nombre exacto del torneo.
              </p>
              <p class="fw-semibold mb-2">{{ detalle?.torneo?.nombre }}</p>

              <label class="form-label small">Confirmación por nombre</label>
              <input type="text" class="form-control mb-3" v-model="confirmNombreEliminar" placeholder="Escribe el nombre del torneo" />

              <label class="form-label small">Motivo (opcional)</label>
              <input type="text" class="form-control" v-model="motivoBajaTorneo" placeholder="Motivo de baja" />
            </div>
            <div class="modal-footer">
              <button class="btn btn-outline-secondary" @click="cancelarEliminarTorneo">Cancelar</button>
              <button class="btn btn-danger" @click="eliminarTorneoSeleccionado" :disabled="savingEliminarTorneo || !nombreEliminarCoincide">
                <span v-if="savingEliminarTorneo" class="spinner-border spinner-border-sm me-2"></span>
                Eliminar torneo
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showInscripcionModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Cargar inscripciones al torneo</h5>
              <button type="button" class="btn-close" @click="showInscripcionModal = false"></button>
            </div>

            <div class="modal-body">
              <p class="small text-muted mb-3">Seleccioná un equipo para inscribirlo en el torneo.</p>

              <div v-if="!equiposParaInscripcion.length" class="alert alert-info mb-0">
                No hay equipos disponibles para gestionar inscripciones.
              </div>

              <div v-else class="row g-3">
                <div class="col-12">
                  <label class="form-label">Equipo</label>
                  <select class="form-select" v-model="inscripcionForm.id_equipo" @change="onEquipoInscripcionChange">
                    <option value="">Seleccionar equipo</option>
                    <option v-for="equipo in equiposParaInscripcion" :key="equipo.id" :value="String(equipo.id)">
                      {{ equipo.nombre }}{{ equipo.ya_inscripto ? ' (ya inscripto)' : '' }}
                    </option>
                  </select>
                </div>
                <div class="col-12">
                  <div class="alert alert-light border mb-0 small">
                    <div class="fw-semibold mb-1">Importante</div>
                    El pago de inscripción ahora se registra por partido y por equipo.
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer d-flex justify-content-between">
              <small class="text-muted">La carga se registra de a un equipo por vez.</small>
              <button class="btn btn-success" @click="guardarInscripciones" :disabled="savingInscripciones || !inscripcionForm.id_equipo">
                <span v-if="savingInscripciones" class="spinner-border spinner-border-sm me-2"></span>
                Guardar inscripción
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div v-if="showPagoEventoModal && pagoEventoSeleccionado" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header">
              <div>
                <h5 class="modal-title mb-0">Gestionar pago por partido</h5>
                <p class="small text-muted mb-0 mt-1">{{ pagoEventoSeleccionado.titulo || `Partido ${pagoEventoSeleccionado.id}` }} · {{ formatearFechaHora(pagoEventoSeleccionado.fecha_hora_inicio) }}</p>
              </div>
              <button type="button" class="btn-close" @click="cerrarPagoEventoModal"></button>
            </div>

            <div class="modal-body">
              <div class="alert alert-light border py-2 mb-3 d-flex justify-content-between align-items-center">
                <span class="small text-muted mb-0">Monto de inscripción por equipo</span>
                <span class="fw-semibold">{{ formatMoney(detalle?.torneo?.valor_inscripcion) }}</span>
              </div>

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="pago-lado-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <img v-if="pagoEventoSeleccionado.equipo_local_escudo" :src="resolveEscudoUrl(pagoEventoSeleccionado.equipo_local_escudo)" alt="escudo" class="escudo-thumb" />
                      <h6 class="mb-0">Local: {{ pagoEventoSeleccionado.equipo_local_nombre || '-' }}</h6>
                    </div>

                    <label class="form-check form-switch mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :checked="getPagoEventoDraft(pagoEventoSeleccionado.id, 'local').pago_realizado"
                        @change="setPagoEventoDraftField(pagoEventoSeleccionado.id, 'local', 'pago_realizado', $event.target.checked)"
                      />
                      <span class="form-check-label small">Pago realizado</span>
                    </label>

                    <input
                      type="file"
                      class="form-control form-control-sm"
                      accept=".pdf,image/*"
                      @change="onPagoEventoFileChange(pagoEventoSeleccionado.id, 'local', $event)"
                    />

                    <div v-if="getPagoEventoDraft(pagoEventoSeleccionado.id, 'local').url_comprobante_pago" class="small mt-2">
                      <a :href="getPagoEventoDraft(pagoEventoSeleccionado.id, 'local').url_comprobante_pago" target="_blank" rel="noopener noreferrer">Ver comprobante actual</a>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="pago-lado-card">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <img v-if="pagoEventoSeleccionado.equipo_visitante_escudo" :src="resolveEscudoUrl(pagoEventoSeleccionado.equipo_visitante_escudo)" alt="escudo" class="escudo-thumb" />
                      <h6 class="mb-0">Visitante: {{ pagoEventoSeleccionado.equipo_visitante_nombre || '-' }}</h6>
                    </div>

                    <label class="form-check form-switch mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        :checked="getPagoEventoDraft(pagoEventoSeleccionado.id, 'visitante').pago_realizado"
                        @change="setPagoEventoDraftField(pagoEventoSeleccionado.id, 'visitante', 'pago_realizado', $event.target.checked)"
                      />
                      <span class="form-check-label small">Pago realizado</span>
                    </label>

                    <input
                      type="file"
                      class="form-control form-control-sm"
                      accept=".pdf,image/*"
                      @change="onPagoEventoFileChange(pagoEventoSeleccionado.id, 'visitante', $event)"
                    />

                    <div v-if="getPagoEventoDraft(pagoEventoSeleccionado.id, 'visitante').url_comprobante_pago" class="small mt-2">
                      <a :href="getPagoEventoDraft(pagoEventoSeleccionado.id, 'visitante').url_comprobante_pago" target="_blank" rel="noopener noreferrer">Ver comprobante actual</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button
                type="button"
                class="btn btn-success"
                :disabled="isSavingPagoEventoPartido(pagoEventoSeleccionado.id)"
                @click="guardarPagoEventoPartido(pagoEventoSeleccionado)"
              >
                <span v-if="isSavingPagoEventoPartido(pagoEventoSeleccionado.id)" class="spinner-border spinner-border-sm me-1"></span>
                Guardar cambios
              </button>
              <button type="button" class="btn btn-outline-secondary" @click="cerrarPagoEventoModal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import datosMaestrosService from '@/services/datosMaestrosService'
import planTorneoService from '@/services/planTorneoService'
import { useToastStore } from '@/stores/toastStore'
import TorneoCalendar from '@/components/torneos/TorneoCalendar.vue'

const toast = useToastStore()
const route = useRoute()

const torneos = ref([])
const idTorneoSeleccionado = ref(null)
const detalle = ref(null)
const equiposDisponibles = ref([])
const loadingTorneos = ref(false)
const loadingDetalle = ref(false)
const savingAsignacion = ref(false)
const savingAsignacionCruces = ref(false)
const savingAsignacionCruceIds = ref([])
const savingEliminarAsignaciones = ref(false)
const savingInscripciones = ref(false)
const savingEliminarTorneo = ref(false)
const savingProgramacion = ref(false)
const loadingProgramacion = ref(false)
const selected = ref({})
const selectedPool = ref([])
const cruceDrafts = ref({})
const pagoEventoDrafts = ref({})
const savingPagoEventoKeys = ref([])
const showPagoEventoModal = ref(false)
const idPagoEventoSeleccionado = ref(null)
const showInscripcionModal = ref(false)
const showEliminarTorneoModal = ref(false)
const showAccionesMenu = ref(false)
const confirmNombreEliminar = ref('')
const motivoBajaTorneo = ref('')
const programacionData = ref(null)
const selectedProgramacionIds = ref([])
const showProgramacionModal = ref(false)
const programacionDrafts = ref({})
const editingProgramacionIds = ref([])
const editingCruceManualIds = ref([])
const programacionForm = ref({
  fase_programar: 'todas',
  fecha_inicio: new Date().toISOString().slice(0, 10),
  duracion_minutos: 70,
  max_dias_busqueda: 365,
  id_canchas: [],
  id_arbitros: [],
  id_eventos: [],
  franjas: [
    { dia_semana: 1, nombre: 'Lunes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 2, nombre: 'Martes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 3, nombre: 'Miércoles', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 4, nombre: 'Jueves', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 5, nombre: 'Viernes', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 6, nombre: 'Sábado', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
    { dia_semana: 7, nombre: 'Domingo', activa: true, hora_inicio: '13:00', hora_fin: '18:00' },
  ],
})
const inscripcionForm = ref({
  id_equipo: '',
})
const showBulkInscripcionModal = ref(false)
const savingBulkInscripciones = ref(false)
const bulkSeleccionados = ref([])
const tabActiva = ref('resumen')
const subTabAsignaciones = ref('zonas')

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const getEstadoTorneoBadgeClass = (estado) => {
  const key = String(estado || '').trim().toUpperCase()

  if (key === 'PLANIFICADO') return 'bg-secondary-subtle text-secondary'
  if (key === 'EN CURSO') return 'bg-success-subtle text-success'
  if (key === 'FINALIZADO') return 'bg-dark-subtle text-dark'
  if (key === 'CANCELADO') return 'bg-danger-subtle text-danger'

  return 'bg-light text-muted'
}

const formatMoney = (value) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(amount)
}

const formatearFechaHora = (value) => {
  if (!value) return '-'
  const dt = new Date(String(value).replace(' ', 'T'))
  if (Number.isNaN(dt.getTime())) return String(value)
  return dt.toLocaleString('es-AR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const CRUCE_BRACKET_CARD_HEIGHT = 86
const CRUCE_BRACKET_BASE_SLOT = 118

const toDateTimeLocal = (value) => {
  if (!value) return ''
  const txt = String(value).trim().replace(' ', 'T')
  return txt.length >= 16 ? txt.slice(0, 16) : txt
}

const buildProgramacionDrafts = () => {
  const next = {}
  for (const ev of (programacionData.value?.eventos || [])) {
    const id = Number(ev.id)
    next[id] = {
      fecha_hora_inicio: toDateTimeLocal(ev.fecha_hora_inicio),
      id_cancha: ev.id_cancha ? Number(ev.id_cancha) : null,
      id_arbitro: ev.id_arbitro ? Number(ev.id_arbitro) : null,
    }
  }
  programacionDrafts.value = next
  const permitidos = new Set(Object.keys(next).map(Number))
  selectedProgramacionIds.value = selectedProgramacionIds.value.filter(id => permitidos.has(Number(id)))
  editingProgramacionIds.value = editingProgramacionIds.value.filter(id => permitidos.has(Number(id)))
}

const buildCruceDrafts = () => {
  const next = {}
  for (const ev of (programacionData.value?.eventos || [])) {
    const titulo = String(ev?.titulo || '').trim()
    if (/^zona\s/i.test(titulo)) continue
    const id = Number(ev.id)
    next[id] = {
      id_equipo_local: ev?.id_equipo_local ? Number(ev.id_equipo_local) : null,
      id_equipo_visitante: ev?.id_equipo_visitante ? Number(ev.id_equipo_visitante) : null,
    }
  }
  cruceDrafts.value = next
  const permitidos = new Set(Object.keys(next).map(Number))
  editingCruceManualIds.value = editingCruceManualIds.value.filter(id => permitidos.has(Number(id)))
}

const buildPagoEventoDrafts = () => {
  const next = {}
  for (const ev of (detalle.value?.eventos_partido || [])) {
    const id = Number(ev.id)
    next[id] = {
      local: {
        pago_realizado: Number(ev?.pago_local_realizado || 0) === 1,
        url_comprobante_pago: ev?.url_comprobante_pago_local || '',
        comprobante_file: null,
      },
      visitante: {
        pago_realizado: Number(ev?.pago_visitante_realizado || 0) === 1,
        url_comprobante_pago: ev?.url_comprobante_pago_visitante || '',
        comprobante_file: null,
      },
    }
  }
  pagoEventoDrafts.value = next
}

const isEventoPendienteProgramacion = (ev) => {
  const estado = Number(ev?.id_estado_evento || 0)
  if (estado === 1) return true
  return !ev?.fecha_hora_inicio || !ev?.id_cancha || !ev?.id_arbitro
}

const resolveEscudoUrl = (escudo) => {
  if (!escudo) return ''

  const value = String(escudo).trim()
  if (value === '') return ''
  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) return value

  const apiBase = import.meta.env.VITE_API_URL
  if (!apiBase) return value

  try {
    const apiUrl = new URL(apiBase, window.location.origin)
    const apiPath = String(apiUrl.pathname || '').replace(/\/+$/, '')
    const projectBasePath = apiPath.endsWith('/api') ? apiPath.slice(0, -4) : ''

    if (value.startsWith('/')) {
      if (projectBasePath && !value.startsWith(projectBasePath + '/')) {
        return `${apiUrl.origin}${projectBasePath}${value}`
      }
      return `${apiUrl.origin}${value}`
    }

    if (projectBasePath && value.startsWith('uploads/')) {
      return `${apiUrl.origin}${projectBasePath}/${value}`
    }

    return new URL(value, apiUrl.href).toString()
  } catch {
    return value
  }
}

const cargarTorneos = async () => {
  loadingTorneos.value = true
  try {
    torneos.value = await datosMaestrosService.getTorneos()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron cargar los torneos.'), type: 'danger' })
  } finally {
    loadingTorneos.value = false
  }
}

const keyOf = (idGrupo, pos) => `${idGrupo}-${pos}`

const cargarDetalle = async (idTorneo = null) => {
  if (idTorneo !== null) {
    idTorneoSeleccionado.value = Number(idTorneo)
    tabActiva.value = 'resumen'
  }

  if (!idTorneoSeleccionado.value) return
  loadingDetalle.value = true
  loadingProgramacion.value = true
  try {
    const [detalleData, disponibles, dataProgramacion] = await Promise.all([
      planTorneoService.getDetalleGestion(idTorneoSeleccionado.value),
      planTorneoService.getEquiposDisponibles(idTorneoSeleccionado.value),
      planTorneoService.getProgramacionData(idTorneoSeleccionado.value, programacionForm.value.fase_programar),
    ])

    detalle.value = detalleData
    equiposDisponibles.value = disponibles
    programacionData.value = dataProgramacion
    buildProgramacionDrafts()
    buildCruceDrafts()
    buildPagoEventoDrafts()

    const next = {}
    for (const item of (detalleData.asignaciones || [])) {
      next[keyOf(item.id_grupo_torneo, item.posicion_inicial)] = Number(item.id_equipo)
    }
    selected.value = next
    selectedPool.value = (detalleData.inscriptos || []).map(i => Number(i.id_equipo))

    programacionForm.value.id_canchas = (dataProgramacion?.canchas || []).map(c => Number(c.id))
    programacionForm.value.id_arbitros = (dataProgramacion?.arbitros || []).map(a => Number(a.id))
  } catch (error) {
    detalle.value = null
    programacionData.value = null
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar el detalle del torneo.'), type: 'danger' })
  } finally {
    loadingDetalle.value = false
    loadingProgramacion.value = false
  }
}

const cargarProgramacionData = async () => {
  if (!idTorneoSeleccionado.value) return
  loadingProgramacion.value = true
  try {
    const dataProgramacion = await planTorneoService.getProgramacionData(
      idTorneoSeleccionado.value,
      programacionForm.value.fase_programar,
    )
    programacionData.value = dataProgramacion
    buildProgramacionDrafts()
    buildCruceDrafts()
    if (programacionForm.value.fase_programar === 'seleccionados') {
      const permitidos = new Set(eventosPendientesProgramacion.value.map(ev => Number(ev.id)))
      programacionForm.value.id_eventos = (programacionForm.value.id_eventos || [])
        .map(Number)
        .filter(id => permitidos.has(id))
    }
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo cargar la programación.'), type: 'danger' })
  } finally {
    loadingProgramacion.value = false
  }
}

const onFaseProgramarChange = async () => {
  if (programacionForm.value.fase_programar !== 'seleccionados') {
    programacionForm.value.id_eventos = []
  }
  await cargarProgramacionData()
}

const getProgramacionDraft = (idEvento) => {
  const id = Number(idEvento)
  if (!programacionDrafts.value[id]) {
    programacionDrafts.value[id] = {
      fecha_hora_inicio: '',
      id_cancha: null,
      id_arbitro: null,
    }
  }
  return programacionDrafts.value[id]
}

const setProgramacionDraftField = (idEvento, field, value) => {
  const draft = getProgramacionDraft(idEvento)
  draft[field] = value
}

const isEditingProgramacion = (idEvento) => editingProgramacionIds.value.includes(Number(idEvento))

const resetProgramacionDraftFromEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  programacionDrafts.value[id] = {
    fecha_hora_inicio: toDateTimeLocal(evento?.fecha_hora_inicio),
    id_cancha: evento?.id_cancha ? Number(evento.id_cancha) : null,
    id_arbitro: evento?.id_arbitro ? Number(evento.id_arbitro) : null,
  }
}

const editarProgramacionEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetProgramacionDraftFromEvento(evento)
  if (!isEditingProgramacion(id)) {
    editingProgramacionIds.value = [...editingProgramacionIds.value, id]
  }
}

const cancelarEdicionProgramacionEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetProgramacionDraftFromEvento(evento)
  editingProgramacionIds.value = editingProgramacionIds.value.filter(item => item !== id)
}

const getCanchaNombreById = (idCancha) => {
  const id = Number(idCancha || 0)
  if (!id) return ''
  const cancha = (programacionData.value?.canchas || []).find(item => Number(item.id) === id)
  return cancha?.nombre || ''
}

const getArbitroNombreById = (idArbitro) => {
  const id = Number(idArbitro || 0)
  if (!id) return ''
  const arbitro = (programacionData.value?.arbitros || []).find(item => Number(item.id) === id)
  return arbitro?.nombre_completo || `${arbitro?.apellido || ''} ${arbitro?.nombre || ''}`.trim()
}

const getProgramacionCanchaLabel = (evento) => {
  const draft = getProgramacionDraft(evento?.id)
  return getCanchaNombreById(draft?.id_cancha || evento?.id_cancha) || 'Sin definir'
}

const getProgramacionArbitroLabel = (evento) => {
  const draft = getProgramacionDraft(evento?.id)
  return getArbitroNombreById(draft?.id_arbitro || evento?.id_arbitro) || 'Sin definir'
}

const toggleSeleccionTodosProgramacion = () => {
  if (allProgramacionSelected.value) {
    selectedProgramacionIds.value = []
    return
  }
  selectedProgramacionIds.value = programacionEventos.value.map(ev => Number(ev.id))
}

const abrirModalProgramacionSeleccionados = () => {
  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido.', type: 'warning' })
    return
  }
  showProgramacionModal.value = true
}

const allTeamsMap = computed(() => {
  const map = {}
  for (const item of (detalle.value?.inscriptos || [])) {
    const id = Number(item.id_equipo)
    map[id] = {
      id,
      nombre: item.equipo_nombre,
      escudo: item.escudo || null,
      asignado: Number(item.asignado || 0) === 1,
    }
  }
  return map
})

const poolTeams = computed(() =>
  Object.values(allTeamsMap.value)
    .map(item => ({ ...item, id: Number(item.id) }))
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

const equiposDisponiblesSorted = computed(() =>
  [...equiposDisponibles.value].sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

const equiposParaInscripcion = computed(() => {
  const map = {}
  for (const equipo of equiposDisponiblesSorted.value) {
    map[Number(equipo.id)] = { ...equipo, ya_inscripto: false }
  }

  for (const item of (detalle.value?.inscriptos || [])) {
    const id = Number(item.id_equipo)
    map[id] = {
      id,
      nombre: item.equipo_nombre,
      escudo: item.escudo || null,
      ya_inscripto: true,
    }
  }

  return Object.values(map).sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
})

const equiposSinInscribir = computed(() => {
  const inscriptosIds = new Set((detalle.value?.inscriptos || []).map(i => Number(i.id_equipo)))
  return equiposDisponiblesSorted.value.filter(e => !inscriptosIds.has(Number(e.id)))
})

const asignacionesCompletas = computed(() => {
  const grupos = detalle.value?.grupos || []
  if (!grupos.length) return false
  return grupos.every(g => Number(g.asignados) >= Number(g.cantidad_equipos_objetivo))
})

const hayPartidosJugados = computed(() => {
  return Number(detalle.value?.eventos?.finalizados || 0) > 0
})

const asignacionesPorGrupo = computed(() => {
  const grupos = detalle.value?.grupos || []
  const items = detalle.value?.asignaciones || []
  return grupos.map(g => ({
    ...g,
    equipos: items
      .filter(a => Number(a.id_grupo_torneo) === Number(g.id))
      .sort((a, b) => Number(a.posicion_inicial) - Number(b.posicion_inicial)),
  }))
})

const totalInscriptosObjetivo = computed(() => {
  const cupo = Number(detalle.value?.torneo?.cupo_equipos || 0)
  if (cupo > 0) return cupo
  return totalSlots.value
})

const inscripcionesCompletas = computed(() => {
  const total = Number(detalle.value?.inscripciones?.total || 0)
  const objetivo = Number(totalInscriptosObjetivo.value || 0)
  if (objetivo <= 0) return false
  return total >= objetivo
})

const cupoLleno = computed(() => {
  const objetivo = Number(totalInscriptosObjetivo.value || 0)
  const total = Number(detalle.value?.inscripciones?.total || 0)
  if (objetivo <= 0) return false
  return total >= objetivo
})

const vacantesDisponibles = computed(() => {
  const objetivo = Number(totalInscriptosObjetivo.value || 0)
  const total = Number(detalle.value?.inscripciones?.total || 0)
  if (objetivo <= 0) return Number.MAX_SAFE_INTEGER
  return Math.max(0, objetivo - total)
})

const nombreEliminarCoincide = computed(() => {
  const actual = String(detalle.value?.torneo?.nombre || '').trim()
  const confirm = String(confirmNombreEliminar.value || '').trim()
  return actual !== '' && actual === confirm
})

const totalSlots = computed(() => {
  let total = 0
  for (const g of (detalle.value?.grupos || [])) {
    total += Number(g.cantidad_equipos_objetivo || 0)
  }
  return total
})

const eventosPendientesProgramacion = computed(() =>
  (programacionData.value?.eventos || []).filter(isEventoPendienteProgramacion)
)

const programacionEventos = computed(() =>
  (programacionData.value?.eventos || []).map(ev => ({ ...ev, id: Number(ev.id) }))
)

const eventosZonaProgramacion = computed(() =>
  programacionEventos.value.filter(ev => /^zona\s/i.test(String(ev.titulo || '').trim()))
)

const eventosCruceProgramacion = computed(() =>
  programacionEventos.value.filter(ev => !/^zona\s/i.test(String(ev.titulo || '').trim()))
)

const eventosPagoPartido = computed(() => {
  const allEventos = (detalle.value?.eventos_partido || []).map(ev => ({ ...ev, id: Number(ev.id) }))
  const now = new Date()
  return allEventos
    .filter(ev => Number(ev.id_equipo_local || 0) > 0 && Number(ev.id_equipo_visitante || 0) > 0)
    .filter(ev => Number(ev.id_estado_evento || 0) !== 4)
    .filter(ev => {
      if (!ev.fecha_hora_inicio) return false
      const dt = new Date(String(ev.fecha_hora_inicio).replace(' ', 'T'))
      return !Number.isNaN(dt.getTime()) && dt >= now
    })
})

const eventosCalendario = computed(() =>
  (detalle.value?.eventos_partido || [])
    .map(ev => ({ ...ev, id: Number(ev.id) }))
    .filter(ev => {
      if (!ev.fecha_hora_inicio) return false
      const dt = new Date(String(ev.fecha_hora_inicio).replace(' ', 'T'))
      return !Number.isNaN(dt.getTime())
    })
)

const pagoEventoSeleccionado = computed(() => {
  const id = Number(idPagoEventoSeleccionado.value || 0)
  if (!id) return null
  return eventosPagoPartido.value.find(ev => Number(ev.id) === id) || null
})

const pagosPartidoResumen = computed(() => {
  let pagados = 0
  let total = 0
  for (const ev of eventosPagoPartido.value) {
    if (Number(ev.id_equipo_local || 0) > 0) {
      total++
      if (Number(ev.pago_local_realizado || 0) === 1) pagados++
    }
    if (Number(ev.id_equipo_visitante || 0) > 0) {
      total++
      if (Number(ev.pago_visitante_realizado || 0) === 1) pagados++
    }
  }
  return { pagados, total }
})

const getPagoEventoDraft = (idEvento, lado) => {
  const id = Number(idEvento)
  if (!pagoEventoDrafts.value[id]) {
    pagoEventoDrafts.value[id] = {
      local: { pago_realizado: false, url_comprobante_pago: '', comprobante_file: null },
      visitante: { pago_realizado: false, url_comprobante_pago: '', comprobante_file: null },
    }
  }
  if (!pagoEventoDrafts.value[id][lado]) {
    pagoEventoDrafts.value[id][lado] = { pago_realizado: false, url_comprobante_pago: '', comprobante_file: null }
  }
  return pagoEventoDrafts.value[id][lado]
}

const setPagoEventoDraftField = (idEvento, lado, field, value) => {
  const draft = getPagoEventoDraft(idEvento, lado)
  draft[field] = value
  if (field === 'pago_realizado' && !value) {
    draft.url_comprobante_pago = ''
    draft.comprobante_file = null
  }
}

const onPagoEventoFileChange = (idEvento, lado, event) => {
  const file = event?.target?.files?.[0] || null
  const draft = getPagoEventoDraft(idEvento, lado)
  draft.comprobante_file = file
}

const getPagoEventoKey = (idEvento, lado) => `${Number(idEvento)}-${lado}`
const isSavingPagoEvento = (idEvento, lado) => savingPagoEventoKeys.value.includes(getPagoEventoKey(idEvento, lado))
const isSavingPagoEventoPartido = (idEvento) =>
  isSavingPagoEvento(idEvento, 'local') || isSavingPagoEvento(idEvento, 'visitante')

const getNombreRondaCruce = (titulo, fallback) => {
  const value = String(titulo || '').trim()
  if (!value) return fallback
  const cleaned = value.replace(/\s*-\s*partido\s*\d+\s*$/i, '').trim()
  return cleaned || fallback
}

const getCruceEquipoDisplay = (ev, side) => {
  const fieldId = side === 'local' ? 'id_equipo_local' : 'id_equipo_visitante'
  const fieldNombre = side === 'local' ? 'equipo_local_nombre' : 'equipo_visitante_nombre'
  const fieldEscudo = side === 'local' ? 'equipo_local_escudo' : 'equipo_visitante_escudo'

  const draft = getCruceDraft(ev.id)
  const idDraft = Number(draft?.[fieldId] || 0)
  if (idDraft > 0 && allTeamsMap.value[idDraft]) {
    return {
      id: idDraft,
      nombre: allTeamsMap.value[idDraft]?.nombre || '',
      escudo: allTeamsMap.value[idDraft]?.escudo || null,
    }
  }

  return {
    id: Number(ev?.[fieldId] || 0) || null,
    nombre: String(ev?.[fieldNombre] || ''),
    escudo: ev?.[fieldEscudo] || null,
  }
}

const cruceRounds = computed(() => {
  const byFecha = new Map()
  const sorted = [...eventosCruceProgramacion.value]
    .sort((a, b) => {
      const fa = Number(a?.numero_fecha || 0)
      const fb = Number(b?.numero_fecha || 0)
      if (fa !== fb) return fa - fb
      return Number(a?.id || 0) - Number(b?.id || 0)
    })

  for (const ev of sorted) {
    const fecha = Number(ev?.numero_fecha || 0)
    const key = Number.isFinite(fecha) ? fecha : 0
    if (!byFecha.has(key)) {
      byFecha.set(key, {
        fecha: key,
        nombre: getNombreRondaCruce(ev?.titulo, `Ronda ${byFecha.size + 1}`),
        partidos: [],
      })
    }

    const row = byFecha.get(key)
    const resultadoDisponible = ev?.resultado_local !== null
      && ev?.resultado_local !== undefined
      && ev?.resultado_visitante !== null
      && ev?.resultado_visitante !== undefined

    row.partidos.push({
      id: Number(ev.id),
      local: getCruceEquipoDisplay(ev, 'local'),
      visitante: getCruceEquipoDisplay(ev, 'visitante'),
      resultado: resultadoDisponible ? `${ev.resultado_local} - ${ev.resultado_visitante}` : null,
    })
  }

  return Array.from(byFecha.values())
})

const getCruceRoundTrackStyle = (roundIndex, matchCount) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  return {
    height: `${slot * matchCount}px`,
  }
}

const getCruceMatchStyle = (roundIndex, matchIndex) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) - (CRUCE_BRACKET_CARD_HEIGHT / 2) + (matchIndex * slot)
  return {
    top: `${top}px`,
  }
}

const getCruceMergeStyle = (roundIndex, pairIndex) => {
  const slot = CRUCE_BRACKET_BASE_SLOT * (2 ** roundIndex)
  const top = (slot / 2) + (pairIndex * 2 * slot)
  return {
    top: `${top}px`,
    height: `${slot}px`,
  }
}

const zonasConcretadas = computed(() => {
  const hayFaseDeZonas = Boolean(detalle.value?.grupos?.length)
  if (!hayFaseDeZonas) return true
  if (!eventosZonaProgramacion.value.length) return false
  return eventosZonaProgramacion.value.every(ev => [4, 7].includes(Number(ev.id_estado_evento)))
})

const crucesHabilitados = computed(() => !detalle.value?.grupos?.length || zonasConcretadas.value)

const crucesConEquiposDefinidos = computed(() =>
  eventosCruceProgramacion.value.filter(ev => ev.equipo_local_nombre && ev.equipo_visitante_nombre).length
)

const equiposCruceOptions = computed(() =>
  Object.values(allTeamsMap.value)
    .map(item => ({ id: Number(item.id), nombre: item.nombre }))
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
)

const allProgramacionSelected = computed(() =>
  programacionEventos.value.length > 0 && selectedProgramacionIds.value.length === programacionEventos.value.length
)

const hasSomeProgramacionSelected = computed(() =>
  selectedProgramacionIds.value.length > 0 && selectedProgramacionIds.value.length < programacionEventos.value.length
)

const selectedIds = computed(() => {
  const ids = []
  for (const value of Object.values(selected.value)) {
    const id = Number(value)
    if (id > 0) ids.push(id)
  }
  return ids
})

const getSelected = (idGrupo, pos) => selected.value[keyOf(idGrupo, pos)] ?? ''

const setSelected = (idGrupo, pos, value) => {
  const key = keyOf(idGrupo, pos)
  if (!value) {
    delete selected.value[key]
    return
  }
  selected.value[key] = Number(value)
}

const getSelectedTeam = (idGrupo, pos) => {
  const id = Number(getSelected(idGrupo, pos))
  return allTeamsMap.value[id] || null
}

const getAvailableOptions = (idGrupo, pos) => {
  const currentId = Number(getSelected(idGrupo, pos) || 0)
  const selectedSet = new Set(selectedIds.value.filter(id => id !== currentId))
  return Object.values(allTeamsMap.value)
    .filter(e => !selectedSet.has(Number(e.id)) || Number(e.id) === currentId)
    .sort((a, b) => String(a.nombre).localeCompare(String(b.nombre), 'es'))
}

const limpiarAsignacion = () => {
  selected.value = {}
}

const asignarAleatorio = () => {
  const grupos = detalle.value?.grupos || []
  if (!grupos.length) return

  const needed = totalSlots.value
  const pool = Array.from(new Set(selectedPool.value.map(Number).filter(v => v > 0)))

  if (pool.length < needed) {
    toast.showToast({
      message: `Debes seleccionar al menos ${needed} equipos para sortear.`,
      type: 'warning',
    })
    return
  }

  const shuffled = [...pool]
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1))
    ;[shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]]
  }

  const next = {}
  let cursor = 0
  for (const grupo of grupos) {
    const cupo = Number(grupo.cantidad_equipos_objetivo || 0)
    for (let pos = 1; pos <= cupo; pos++) {
      next[keyOf(grupo.id, pos)] = Number(shuffled[cursor])
      cursor++
    }
  }

  selected.value = next
  toast.showToast({ message: 'Asignación aleatoria generada. Puedes editarla manualmente.', type: 'info' })
}

const guardarAsignaciones = async () => {
  if (!detalle.value?.grupos?.length) return

  const asignaciones = []
  for (const grupo of detalle.value.grupos) {
    const cupo = Number(grupo.cantidad_equipos_objetivo || 0)
    for (let pos = 1; pos <= cupo; pos++) {
      const idEquipo = Number(getSelected(grupo.id, pos) || 0)
      if (idEquipo > 0) {
        asignaciones.push({
          id_grupo_torneo: Number(grupo.id),
          id_equipo: idEquipo,
          posicion_inicial: pos,
        })
      }
    }
  }

  if (!asignaciones.length) {
    toast.showToast({ message: 'No hay equipos seleccionados para asignar.', type: 'warning' })
    return
  }

  savingAsignacion.value = true
  try {
    await planTorneoService.asignarEquipos({
      id_torneo: idTorneoSeleccionado.value,
      asignaciones,
    })
    toast.showToast({ message: 'Asignación guardada correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo guardar la asignación.'), type: 'danger' })
  } finally {
    savingAsignacion.value = false
  }
}

const asignarCruces = async () => {
  if (!idTorneoSeleccionado.value) return
  if (!crucesHabilitados.value) {
    toast.showToast({ message: 'Los cruces aún están bloqueados. Debes finalizar la fase de zonas.', type: 'warning' })
    return
  }
  if (!eventosCruceProgramacion.value.length) {
    toast.showToast({ message: 'No hay cruces configurados para este torneo.', type: 'warning' })
    return
  }

  savingAsignacionCruces.value = true
  try {
    const response = await planTorneoService.asignarCruces({ id_torneo: idTorneoSeleccionado.value })
    toast.showToast({ message: response?.message || 'Cruces asignados correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron asignar los cruces.'), type: 'danger' })
  } finally {
    savingAsignacionCruces.value = false
  }
}

const getCruceDraft = (idEvento) => {
  const id = Number(idEvento)
  if (!cruceDrafts.value[id]) {
    cruceDrafts.value[id] = {
      id_equipo_local: null,
      id_equipo_visitante: null,
    }
  }
  return cruceDrafts.value[id]
}

const setCruceDraftField = (idEvento, field, value) => {
  const draft = getCruceDraft(idEvento)
  const parsed = Number(value)
  draft[field] = Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

const isEditingCruceManual = (idEvento) => editingCruceManualIds.value.includes(Number(idEvento))

const resetCruceDraftFromEvento = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  cruceDrafts.value[id] = {
    id_equipo_local: evento?.id_equipo_local ? Number(evento.id_equipo_local) : null,
    id_equipo_visitante: evento?.id_equipo_visitante ? Number(evento.id_equipo_visitante) : null,
  }
}

const editarCruceManual = (evento) => {
  if (!crucesHabilitados.value) return
  const id = Number(evento?.id || 0)
  if (!id) return
  resetCruceDraftFromEvento(evento)
  if (!isEditingCruceManual(id)) {
    editingCruceManualIds.value = [...editingCruceManualIds.value, id]
  }
}

const cancelarEdicionCruceManual = (evento) => {
  const id = Number(evento?.id || 0)
  if (!id) return
  resetCruceDraftFromEvento(evento)
  editingCruceManualIds.value = editingCruceManualIds.value.filter(item => item !== id)
}

const isSavingCruceManual = (idEvento) => savingAsignacionCruceIds.value.includes(Number(idEvento))

const guardarAsignacionCruceManual = async (evento) => {
  const idEvento = Number(evento?.id || 0)
  if (!idEvento || !idTorneoSeleccionado.value) return
  if (!crucesHabilitados.value) {
    toast.showToast({ message: 'Los cruces aún están bloqueados para edición.', type: 'warning' })
    return
  }

  const draft = getCruceDraft(idEvento)
  if (
    draft.id_equipo_local !== null &&
    draft.id_equipo_visitante !== null &&
    Number(draft.id_equipo_local) === Number(draft.id_equipo_visitante)
  ) {
    toast.showToast({ message: 'El equipo local y visitante no pueden ser el mismo.', type: 'warning' })
    return
  }

  savingAsignacionCruceIds.value = Array.from(new Set([...savingAsignacionCruceIds.value, idEvento]))
  try {
    await planTorneoService.actualizarAsignacionCruce({
      id_torneo: Number(idTorneoSeleccionado.value),
      id_evento: idEvento,
      id_equipo_local: draft.id_equipo_local,
      id_equipo_visitante: draft.id_equipo_visitante,
    })
    editingCruceManualIds.value = editingCruceManualIds.value.filter(item => item !== idEvento)
    toast.showToast({ message: 'Asignación manual del cruce guardada.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo guardar la asignación manual del cruce.'), type: 'danger' })
  } finally {
    savingAsignacionCruceIds.value = savingAsignacionCruceIds.value.filter(id => id !== idEvento)
  }
}

const abrirInscripcionesModal = () => {
  if (!idTorneoSeleccionado.value) {
    toast.showToast({ message: 'Selecciona un torneo antes de cargar inscripciones.', type: 'warning' })
    return
  }
  if (cupoLleno.value) {
    toast.showToast({ message: 'No se pueden cargar más inscripciones: el cupo del torneo está completo.', type: 'warning' })
    return
  }
  resetInscripcionForm()
  showInscripcionModal.value = true
}

const abrirPagoEventoModal = (evento) => {
  const idEvento = Number(evento?.id || 0)
  if (!idEvento) return
  idPagoEventoSeleccionado.value = idEvento
  showPagoEventoModal.value = true
}

const cerrarPagoEventoModal = () => {
  showPagoEventoModal.value = false
  idPagoEventoSeleccionado.value = null
}

const eliminarAsignaciones = async () => {
  if (!idTorneoSeleccionado.value) return
  const ok = window.confirm(
    '\u00bfEliminar todas las asignaciones de grupos de este torneo?\n\nEsta acci\u00f3n es reversible siempre que no haya partidos finalizados.',
  )
  if (!ok) return

  savingEliminarAsignaciones.value = true
  try {
    await planTorneoService.eliminarAsignaciones({ id_torneo: idTorneoSeleccionado.value })
    toast.showToast({ message: 'Asignaciones eliminadas. Podés volver a asignar.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron eliminar las asignaciones.'), type: 'danger' })
  } finally {
    savingEliminarAsignaciones.value = false
  }
}

const abrirBulkInscripcionModal = () => {
  if (!idTorneoSeleccionado.value) {
    toast.showToast({ message: 'Selecciona un torneo antes de cargar inscripciones.', type: 'warning' })
    return
  }
  if (cupoLleno.value) {
    toast.showToast({ message: 'No se pueden cargar más inscripciones: el cupo del torneo está completo.', type: 'warning' })
    return
  }
  bulkSeleccionados.value = []
  showBulkInscripcionModal.value = true
}

const toggleBulkTodos = () => {
  if (bulkSeleccionados.value.length === equiposSinInscribir.value.length) {
    bulkSeleccionados.value = []
  } else {
    bulkSeleccionados.value = equiposSinInscribir.value.map(e => e.id)
  }
}

const guardarBulkInscripciones = async () => {
  if (!bulkSeleccionados.value.length) {
    toast.showToast({ message: 'Seleccioná al menos un equipo.', type: 'warning' })
    return
  }

  if (vacantesDisponibles.value <= 0) {
    toast.showToast({ message: 'No se pueden cargar más inscripciones: el cupo del torneo está completo.', type: 'warning' })
    return
  }

  if (bulkSeleccionados.value.length > vacantesDisponibles.value) {
    toast.showToast({
      message: `Solo hay ${vacantesDisponibles.value} vacante(s) disponible(s).`,
      type: 'warning',
    })
    return
  }

  const inscripciones = bulkSeleccionados.value.map(idEquipo => ({
    id_equipo: Number(idEquipo),
  }))

  savingBulkInscripciones.value = true
  try {
    await planTorneoService.inscribirEquipos({
      id_torneo: idTorneoSeleccionado.value,
      inscripciones,
    })
    toast.showToast({ message: `${inscripciones.length} equipo(s) inscripto(s) correctamente.`, type: 'success' })
    showBulkInscripcionModal.value = false
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron guardar las inscripciones.'), type: 'danger' })
  } finally {
    savingBulkInscripciones.value = false
  }
}

const abrirEliminarTorneoModal = () => {
  showAccionesMenu.value = false
  confirmNombreEliminar.value = ''
  motivoBajaTorneo.value = ''
  showEliminarTorneoModal.value = true
}

const cancelarEliminarTorneo = () => {
  showEliminarTorneoModal.value = false
  confirmNombreEliminar.value = ''
  motivoBajaTorneo.value = ''
}

const eliminarTorneoSeleccionado = async () => {
  if (!idTorneoSeleccionado.value || !nombreEliminarCoincide.value) {
    return
  }

  savingEliminarTorneo.value = true
  try {
    await datosMaestrosService.eliminarTorneo(
      Number(idTorneoSeleccionado.value),
      motivoBajaTorneo.value?.trim() || null,
    )

    toast.showToast({ message: 'Torneo eliminado correctamente.', type: 'success' })
    cancelarEliminarTorneo()
    idTorneoSeleccionado.value = null
    detalle.value = null
    tabActiva.value = 'resumen'
    await cargarTorneos()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo eliminar el torneo.'), type: 'danger' })
  } finally {
    savingEliminarTorneo.value = false
  }
}

const resetInscripcionForm = () => {
  inscripcionForm.value = {
    id_equipo: '',
  }
}

const onEquipoInscripcionChange = () => {
  const idEquipo = Number(inscripcionForm.value.id_equipo || 0)
  if (!idEquipo) {
    resetInscripcionForm()
    return
  }
  inscripcionForm.value = {
    id_equipo: String(idEquipo),
  }
}

const eliminarInscripcion = async (item) => {
  const nombre = item?.equipo_nombre || 'este equipo'
  const ok = window.confirm(`¿Eliminar la inscripción de ${nombre}?`)
  if (!ok) return

  try {
    await planTorneoService.eliminarInscripcion({
      id_torneo: idTorneoSeleccionado.value,
      id_equipo_torneo: Number(item.id),
    })
    toast.showToast({ message: 'Inscripción eliminada correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo eliminar la inscripción.'), type: 'danger' })
  }
}

const guardarInscripciones = async () => {
  const idEquipo = Number(inscripcionForm.value.id_equipo || 0)
  if (!idEquipo) {
    toast.showToast({ message: 'Selecciona un equipo para inscribir.', type: 'warning' })
    return
  }

  const yaInscripto = (detalle.value?.inscriptos || []).some(i => Number(i.id_equipo) === idEquipo)
  if (!yaInscripto && vacantesDisponibles.value <= 0) {
    toast.showToast({ message: 'No se pueden cargar más inscripciones: el cupo del torneo está completo.', type: 'warning' })
    return
  }

  savingInscripciones.value = true
  try {
    const payload = [{
      id_equipo: idEquipo,
    }]

    await planTorneoService.inscribirEquipos({
      id_torneo: idTorneoSeleccionado.value,
      inscripciones: payload,
    })

    toast.showToast({ message: 'Inscripción guardada correctamente.', type: 'success' })
    showInscripcionModal.value = false
    resetInscripcionForm()
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudieron guardar las inscripciones.'), type: 'danger' })
  } finally {
    savingInscripciones.value = false
  }
}

const guardarPagoEventoEquipo = async (evento, lado, options = {}) => {
  const {
    showSuccessToast = true,
    showErrorToast = true,
    reloadOnSuccess = true,
  } = options

  const idEvento = Number(evento?.id || 0)
  if (!idTorneoSeleccionado.value || !idEvento) return
  if (lado !== 'local' && lado !== 'visitante') return

  const draft = getPagoEventoDraft(idEvento, lado)
  const key = getPagoEventoKey(idEvento, lado)
  savingPagoEventoKeys.value = Array.from(new Set([...savingPagoEventoKeys.value, key]))

  try {
    let comprobantePago = draft.url_comprobante_pago || null
    if (draft.comprobante_file) {
      const formData = new FormData()
      formData.append('comprobante', draft.comprobante_file)
      formData.append('id_torneo', String(idTorneoSeleccionado.value || ''))
      const uploadResp = await planTorneoService.subirComprobante(formData)
      comprobantePago = uploadResp?.comprobante_pago || comprobantePago
    }

    await planTorneoService.actualizarPagoEventoEquipo({
      id_torneo: Number(idTorneoSeleccionado.value),
      id_evento: idEvento,
      lado,
      pago_realizado: Boolean(draft.pago_realizado),
      url_comprobante_pago: draft.pago_realizado
        ? (comprobantePago ? String(comprobantePago).trim() : null)
        : null,
    })

    if (showSuccessToast) {
      toast.showToast({ message: `Pago de ${lado} guardado correctamente.`, type: 'success' })
    }
    if (reloadOnSuccess) {
      await cargarDetalle()
    }
    return true
  } catch (error) {
    if (showErrorToast) {
      toast.showToast({ message: getApiMessage(error, 'No se pudo guardar el pago por partido.'), type: 'danger' })
    }
    return false
  } finally {
    savingPagoEventoKeys.value = savingPagoEventoKeys.value.filter(item => item !== key)
  }
}

const guardarPagoEventoPartido = async (evento) => {
  const idEvento = Number(evento?.id || 0)
  if (!idEvento) return

  const okLocal = await guardarPagoEventoEquipo(evento, 'local', {
    showSuccessToast: false,
    showErrorToast: false,
    reloadOnSuccess: false,
  })

  const okVisitante = await guardarPagoEventoEquipo(evento, 'visitante', {
    showSuccessToast: false,
    showErrorToast: false,
    reloadOnSuccess: false,
  })

  if (okLocal && okVisitante) {
    toast.showToast({ message: 'Pagos de local y visitante guardados correctamente.', type: 'success' })
    await cargarDetalle()
    return
  }

  if (okLocal || okVisitante) {
    toast.showToast({ message: 'Se guardo solo uno de los dos pagos. Revisar y reintentar.', type: 'warning' })
    await cargarDetalle()
    return
  }

  toast.showToast({ message: 'No se pudieron guardar los pagos del partido.', type: 'danger' })
}

const programarAutomatico = async () => {
  if (!idTorneoSeleccionado.value) return

  const franjas = (programacionForm.value.franjas || [])
    .filter(f => f.activa)
    .map(f => ({ dia_semana: Number(f.dia_semana), hora_inicio: f.hora_inicio, hora_fin: f.hora_fin }))

  if (!franjas.length) {
    toast.showToast({ message: 'Activa al menos una franja horaria para programar.', type: 'warning' })
    return
  }
  if (!(programacionForm.value.id_canchas || []).length) {
    toast.showToast({ message: 'Selecciona al menos una cancha.', type: 'warning' })
    return
  }
  if (!(programacionForm.value.id_arbitros || []).length) {
    toast.showToast({ message: 'Selecciona al menos un árbitro.', type: 'warning' })
    return
  }
  if (programacionForm.value.fase_programar === 'seleccionados' && !(programacionForm.value.id_eventos || []).length) {
    toast.showToast({ message: 'Selecciona al menos un partido pendiente para programar.', type: 'warning' })
    return
  }

  savingProgramacion.value = true
  try {
    const resp = await planTorneoService.autoProgramar({
      id_torneo: idTorneoSeleccionado.value,
      fase_programar: programacionForm.value.fase_programar,
      fecha_inicio: programacionForm.value.fecha_inicio,
      duracion_minutos: Number(programacionForm.value.duracion_minutos || 70),
      max_dias_busqueda: Number(programacionForm.value.max_dias_busqueda || 365),
      id_canchas: (programacionForm.value.id_canchas || []).map(Number),
      id_arbitros: (programacionForm.value.id_arbitros || []).map(Number),
      id_eventos: (programacionForm.value.id_eventos || []).map(Number),
      franjas,
      force_reprogramar: false,
    })

    toast.showToast({
      message: `${resp?.programados || 0} partidos programados automáticamente.`,
      type: 'success',
    })

    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo ejecutar la programación automática.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

const programarSeleccionados = async () => {
  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido para programar.', type: 'warning' })
    return
  }

  programacionForm.value.fase_programar = 'seleccionados'
  programacionForm.value.id_eventos = selectedProgramacionIds.value.map(Number)
  await programarAutomatico()
  showProgramacionModal.value = false
}

const deshacerProgramacion = async () => {
  if (!idTorneoSeleccionado.value) return

  if (!selectedProgramacionIds.value.length) {
    toast.showToast({ message: 'Selecciona al menos un partido para deshacer la programación.', type: 'warning' })
    return
  }

  const ok = window.confirm(
    `Se deshará la programación de ${selectedProgramacionIds.value.length} partido(s) seleccionados en estado Programado.\n` +
      'Esto limpiará fecha/hora, cancha y árbitro, y los devolverá a Programación pendiente.\n\n' +
      '¿Deseas continuar?'
  )
  if (!ok) return

  savingProgramacion.value = true
  try {
    const resp = await planTorneoService.deshacerProgramacion({
      id_torneo: idTorneoSeleccionado.value,
      fase_programar: 'seleccionados',
      id_eventos: selectedProgramacionIds.value.map(Number),
    })

    toast.showToast({
      message: `${resp?.revertidos || 0} partidos pasaron a Programación pendiente.`,
      type: 'success',
    })

    selectedProgramacionIds.value = []
    programacionForm.value.id_eventos = []
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo deshacer la programación.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

const guardarProgramacionEvento = async (idEvento) => {
  if (!idTorneoSeleccionado.value || !idEvento) return

  const draft = getProgramacionDraft(idEvento)
  if (!draft.fecha_hora_inicio || !draft.id_cancha || !draft.id_arbitro) {
    toast.showToast({ message: 'Debes completar fecha/hora, cancha y árbitro.', type: 'warning' })
    return
  }

  savingProgramacion.value = true
  try {
    await planTorneoService.actualizarProgramacionEvento({
      id_torneo: idTorneoSeleccionado.value,
      id_evento: Number(idEvento),
      fecha_hora_inicio: draft.fecha_hora_inicio,
      id_cancha: Number(draft.id_cancha),
      id_arbitro: Number(draft.id_arbitro),
      duracion_minutos: Number(programacionForm.value.duracion_minutos || 70),
    })

    editingProgramacionIds.value = editingProgramacionIds.value.filter(item => item !== Number(idEvento))
    toast.showToast({ message: 'Partido actualizado correctamente.', type: 'success' })
    await cargarDetalle()
  } catch (error) {
    toast.showToast({ message: getApiMessage(error, 'No se pudo actualizar el partido.'), type: 'danger' })
  } finally {
    savingProgramacion.value = false
  }
}

onMounted(async () => {
  await cargarTorneos()

  const idTorneoDesdeQuery = Number(route.query?.id_torneo || 0)
  if (idTorneoDesdeQuery > 0) {
    const existeEnListado = torneos.value.some(t => Number(t.id) === idTorneoDesdeQuery)
    if (existeEnListado) {
      await cargarDetalle(idTorneoDesdeQuery)
    }
  }
})
</script>

<style scoped>
.torneo-tabs {
  border-bottom: 1px solid #e5e7eb;
  gap: 6px;
}

.torneo-tab {
  border: 0;
  background: transparent;
  padding: 8px 12px;
  border-radius: 10px 10px 0 0;
  color: #64748b;
  font-weight: 600;
}

.torneo-tab.active {
  color: #0f172a;
  background: #f1f5f9;
}

.asignaciones-subtabs .nav-link {
  border-radius: 999px;
  color: #475569;
  font-weight: 600;
}

.asignaciones-subtabs .nav-link.active {
  background: #0ea5e9;
  color: #fff;
}

.resumen-box {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.programacion-box {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.franja-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.selector-box {
  max-height: 180px;
  overflow: auto;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  padding: 8px;
}

.selector-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 2px;
  font-size: 0.9rem;
}

.acciones-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  min-width: 180px;
  z-index: 20;
  padding: 4px;
}

.acciones-item {
  width: 100%;
  border: 0;
  background: transparent;
  text-align: left;
  padding: 8px 10px;
  border-radius: 6px;
}

.acciones-item:hover {
  background: #f8fafc;
}

.acciones-item-danger {
  color: #b91c1c;
}

.group-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.pool-box {
  border: 1px dashed #cbd5e1;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.pool-item {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 6px 8px;
  background: #fff;
  font-size: 0.9rem;
}

.inscripcion-row {
  display: block;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.pago-lado-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.escudo-thumb {
  width: 24px;
  height: 24px;
  object-fit: cover;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
}

.escudo-thumb-placeholder {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  border: 1px solid #cbd5e1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  color: #94a3b8;
  font-size: 0.78rem;
  flex-shrink: 0;
}

.bulk-equipo-list {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  max-height: 340px;
  overflow-y: auto;
  padding-right: 2px;
}

.bulk-equipo-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.6rem;
  background: #fff;
  cursor: pointer;
  user-select: none;
  transition: background 0.12s, border-color 0.12s;
}

.bulk-equipo-row:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.bulk-equipo-row--selected {
  background: #eff6ff;
  border-color: #3b82f6;
}

.torneo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.torneo-card {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #fff;
  transition: all 0.2s ease;
  width: 100%;
  min-height: 110px;
  text-align: left;
}

.torneo-card:hover {
  border-color: #94a3b8;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}

.torneo-card.active {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.12);
  background: #f0f9ff;
}

.torneo-estado-pill {
  font-size: 0.74rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.torneo-card-create {
  display: flex;
  align-items: center;
  justify-content: center;
  border-style: dashed;
  border-color: #94a3b8;
  background: #f8fafc;
}

.torneo-card-create:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.torneo-card-create-icon {
  font-size: 2rem;
  color: #64748b;
  transition: transform 0.2s ease, color 0.2s ease;
}

.torneo-card-create:hover .torneo-card-create-icon {
  color: #2563eb;
  transform: scale(1.08);
}

.programacion-row-selected {
  background: #eff6ff;
}

.cruce-bracket-box {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px;
  background: #f8fafc;
}

.cruce-bracket-scroll {
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 6px;
}

.cruce-bracket-grid {
  --round-count: 1;
  display: grid;
  grid-template-columns: repeat(var(--round-count), minmax(220px, 1fr));
  gap: 22px;
  min-width: calc(var(--round-count) * 220px + (var(--round-count) - 1) * 22px);
}

.cruce-round-column {
  position: relative;
}

.cruce-round-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.cruce-round-track {
  position: relative;
}

.cruce-match-wrapper {
  position: absolute;
  left: 0;
  right: 0;
}

.cruce-match-card {
  background: #fff;
  border: 1px solid #dbe1ea;
  border-radius: 12px;
  padding: 8px;
  display: grid;
  gap: 4px;
  position: relative;
  min-height: 84px;
}

.cruce-match-card.has-next::after,
.cruce-match-card.has-prev::before {
  content: '';
  position: absolute;
  top: 50%;
  width: 14px;
  border-top: 2px solid #cbd5e1;
}

.cruce-match-card.has-next::after {
  right: -14px;
}

.cruce-match-card.has-prev::before {
  left: -14px;
}

.cruce-round-merge {
  position: absolute;
  right: -11px;
  width: 11px;
  border-right: 2px solid #cbd5e1;
  border-bottom: 2px solid #cbd5e1;
}

.cruce-team-line {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.cruce-team-line .team-name {
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 0.88rem;
}

.cruce-score-pill {
  justify-self: end;
  font-size: 0.78rem;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  padding: 2px 8px;
  font-weight: 700;
}

@media (max-width: 768px) {
  .cruce-bracket-grid {
    gap: 14px;
    grid-template-columns: repeat(var(--round-count), minmax(190px, 1fr));
    min-width: calc(var(--round-count) * 190px + (var(--round-count) - 1) * 14px);
  }
}
</style>
