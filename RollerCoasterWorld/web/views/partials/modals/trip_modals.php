<!-- ══ MODAL: Detalle del Día ════════════════════════════════════ -->
<div class="modal fade" id="day-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-0 border-0" style="background:var(--rcw-bg-card); overflow:hidden;">
            <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal"
                style="top: 15px; right: 15px; z-index: 1050; text-shadow: 0 1px 2px rgba(0,0,0,0.5);"></button>
            <div class="modal-body p-0" id="day-modal-body"></div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Registrar Ride ═════════════════════════════════════ -->
<div class="modal fade" id="log-ride-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-warning text-dark rounded-0">
                <h5 class="modal-title fw-bold mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="20" height="20" class="me-2"
                        style="vertical-align:middle">
                        <path d="M4 48 C 20 48,24 16,40 16 C 52 16,56 32,60 48" fill="none" stroke="currentColor"
                            stroke-width="4" stroke-linecap="round" />
                        <path d="M4 56 C 24 56,28 24,40 24 C 50 24,54 38,60 56" fill="none" stroke="currentColor"
                            stroke-width="4" stroke-linecap="round" />
                    </svg>Registrar Ride
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3"><span class="text-danger fw-bold">*</span> Campos obligatorios</p>
                <input type="hidden" id="lr-park-id"><input type="hidden" id="lr-trip-id"><input type="hidden"
                    id="lr-date">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Parque</label>
                    <input type="text" class="form-control rounded-0" id="lr-park-name" readonly>
                </div>
                <div class="mb-3 position-relative">
                    <label class="form-label fw-semibold">Montaña rusa <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-0" id="lr-coaster-search"
                        placeholder="Buscar coaster..." autocomplete="off">
                    <input type="hidden" id="lr-coaster-id">
                    <div class="ac-dropdown" id="lr-coaster-dropdown"></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fila</label>
                        <input type="number" class="form-control rounded-0" id="lr-seat" min="1" max="30"
                            placeholder="Ej: 1">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Hora</label>
                        <input type="time" class="form-control rounded-0" id="lr-time">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notas</label>
                    <input type="text" class="form-control rounded-0" id="lr-notes" placeholder="Opcional"
                        maxlength="200">
                </div>
                <div id="lr-error" class="alert alert-danger rounded-0 d-none small"></div>
            </div>
            <div class="modal-footer rounded-0">
                <button type="button" class="btn btn-outline-secondary rounded-0"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning rounded-0 fw-bold" id="lr-submit-btn"><i
                        class="fa-solid fa-check me-1"></i>Registrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Detalle Viaje ══════════════════════════════════════ -->
<div class="modal fade" id="trip-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-0 border-0" style="background:var(--rcw-bg-card); overflow:hidden;">
            <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal"
                style="top: 15px; right: 15px; z-index: 1050; text-shadow: 0 1px 2px rgba(0,0,0,0.5);"></button>
            <div class="modal-body p-0" id="td-body"></div>
            <div class="modal-footer rounded-0"
                style="background:var(--rcw-bg-card-alt); border-top:1px solid var(--rcw-border);">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger rounded-0 px-4" id="td-delete-btn"><i
                        class="fa-solid fa-trash me-1"></i>Eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Confirmar Eliminación ══════════════════════════════ -->
<div class="modal fade" id="delete-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-danger text-white rounded-0">
                <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar viaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">¿Estás seguro? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer rounded-0">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 px-4" id="confirm-delete-btn"><i
                        class="fa-solid fa-trash me-1"></i>Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Añadir visita suelta ═══════════════════════════════ -->
<div class="modal fade" id="add-visit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-primary text-white rounded-0">
                <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-location-dot me-2"></i>Registrar Visita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3"><span class="text-danger fw-bold">*</span> Campos obligatorios</p>
                <input type="hidden" id="av-date">
                <div class="mb-3 position-relative">
                    <label class="form-label fw-semibold">Parque <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-0" id="av-park-search" placeholder="Buscar parque..."
                        autocomplete="off">
                    <input type="hidden" id="av-park-id">
                    <div class="ac-dropdown" id="av-park-dropdown"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notas</label>
                    <input type="text" class="form-control rounded-0" id="av-notes" placeholder="Opcional">
                </div>
            </div>
            <div class="modal-footer rounded-0">
                <button type="button" class="btn btn-outline-secondary rounded-0"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-0 fw-bold" id="av-submit-btn"><i
                        class="fa-solid fa-plus me-1"></i>Añadir</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Colaboradores ══════════════════════════════════════ -->
<div class="modal fade" id="collabs-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-info text-dark rounded-0">
                <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-users me-2"></i>Colaboradores</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="collabs-body"></div>
            <div class="modal-footer rounded-0 d-flex flex-column align-items-center position-relative">
                <p class="small text-muted mb-2 text-center w-100">Escribe el nombre de usuario de tu amigo para
                    añadirlo</p>
                <div class="input-group rounded-0 position-relative" style="max-width:320px; width:100%;">
                    <input type="text" class="form-control rounded-0" id="collab-username"
                        placeholder="Nombre de usuario..." autocomplete="off">
                    <button class="btn btn-info rounded-0 fw-bold" id="collab-invite-btn" style="z-index:2"><i
                            class="fa-solid fa-paper-plane me-1"></i>Invitar</button>
                    <div class="ac-dropdown w-100 position-absolute" id="collab-dropdown"
                        style="top:100%; left:0; z-index:1000; display:none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Confirmación genérica ══════════════════════════════ -->
<div class="modal fade" id="generic-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-0">
            <div class="modal-header rounded-0" id="gcm-header" style="background:#dc3545">
                <h6 class="modal-title fw-bold mb-0 text-white" id="gcm-title"><i
                        class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0" id="gcm-message">¿Estás seguro?</p>
            </div>
            <div class="modal-footer rounded-0 py-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-0 px-3"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm rounded-0 px-3 fw-bold" id="gcm-confirm-btn"
                    style="background:#dc3545;color:#fff">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Nuevo Credit 🎉 ════════════════════════════════════ -->
<div class="modal fade" id="new-credit-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-0 text-center" style="background:var(--rcw-bg-card)">
            <div class="modal-body py-4">
                <div style="font-size:3rem;line-height:1">🎢</div>
                <h5 class="fw-bold mt-2 mb-1" style="color:var(--rcw-green-neon)">¡Nuevo Credit!</h5>
                <p class="text-muted small mb-0">Primera vez en esta montaña rusa. ¡Añadida a tu colección!</p>
            </div>
            <div class="modal-footer justify-content-center py-2 border-0">
                <button type="button" class="btn btn-success rounded-0 px-4 fw-bold"
                    data-bs-dismiss="modal">¡Genial!</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: Crear/Editar Viaje ══════════════════════════════════════ -->
<div class="modal fade" id="create-trip-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-0">
            <div class="modal-header bg-success text-white rounded-0">
                <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-plus-circle me-2"></i>Nuevo Viaje</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3"><span class="text-danger fw-bold">*</span> Campos obligatorios</p>
                <input type="hidden" id="ct-trip-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del viaje <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-0" id="ct-title" placeholder="Ej: Verano Europa 2026"
                        maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea class="form-control rounded-0" id="ct-desc" rows="2"
                        placeholder="Describe tu viaje..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Países visitados</label>
                    <input type="hidden" id="ct-countries">
                    <div id="ct-countries-container"
                        class="form-control rounded-0 d-flex flex-wrap gap-1 p-1 align-items-center"
                        style="min-height: 38px; cursor: text;">
                        <input type="text" id="ct-countries-input" class="border-0 bg-transparent flex-grow-1 px-1"
                            style="outline: none; min-width: 120px;" placeholder="Escribe y pulsa Enter..."
                            autocomplete="off">
                    </div>
                    <div class="position-relative">
                        <ul id="ct-countries-dropdown" class="dropdown-menu w-100 rounded-0 shadow-sm"
                            style="max-height: 180px; overflow-y: auto; display: none;"></ul>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha inicio <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rounded-0" id="ct-start" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Fecha fin <span class="text-danger">*</span></label>
                        <input type="date" class="form-control rounded-0" id="ct-end" placeholder="dd/mm/aaaa">
                    </div>
                </div>
                <div id="ct-error" class="alert alert-danger border-0 rounded-0 d-none fw-bold small mt-3 mb-3"
                    style="background-color: #dc3545 !important; color: #ffffff !important;"></div>
                <div id="ct-days-container" class="d-none">
                    <hr>
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-map-location-dot text-success me-2"></i>Parques por
                        día</h6>
                    <div id="ct-days-list"></div>
                </div>
            </div>
            <div class="modal-footer rounded-0">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success rounded-0 px-4 fw-bold" id="ct-submit-btn"><i
                        class="fa-solid fa-plus me-1"></i>Crear Viaje</button>
            </div>
        </div>
    </div>
</div>