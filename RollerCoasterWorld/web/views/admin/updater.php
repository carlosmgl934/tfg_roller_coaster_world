<?php
$page_css = ['web/css/admin.css']; // Assuming there is some base admin css
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Router::redirect('home');
}
?>

<div class="container-fluid px-3 px-xl-5 py-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 text-white fw-bold mb-0">
            <i class="fa-solid fa-cloud-arrow-down text-danger me-2"></i> RCDB Updater
        </h1>
    </div>

    <!-- Controles de Escaneo -->
    <div class="card rcw-card mb-4"
        style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="card-body p-4 text-white">
            <h5 class="card-title fw-bold mb-3">Ejecutar Escaneo</h5>
            <p class="text-muted mb-4">El escaneo rápido revisa los últimos 2000 IDs de RCDB y es ideal para uso diario.
                El escaneo completo revisa toda la base de datos y puede tardar varias horas.</p>

            <div class="d-flex gap-3 flex-wrap">
                <button id="btn-scan-quick" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-bolt me-2"></i> Escaneo Rápido
                </button>
                <button id="btn-scan-full" class="btn btn-outline-danger px-4 fw-semibold">
                    <i class="fa-solid fa-database me-2"></i> Escaneo Completo
                </button>
                <button id="btn-load-cache" class="btn btn-outline-secondary px-4 fw-semibold ms-auto">
                    <i class="fa-solid fa-rotate me-2"></i> Cargar Resultados Previos
                </button>
            </div>
        </div>
    </div>

    <!-- Consola de Logs (Oculta por defecto) -->
    <div id="console-container" class="card rcw-card mb-4 d-none"
        style="background: #0d1117; border: 1px solid #30363d;">
        <div class="card-header border-bottom-0 py-3 d-flex justify-content-between align-items-center"
            style="background: #161b22;">
            <span class="fw-semibold text-white"><i class="fa-solid fa-terminal me-2"></i> Progreso del Escaneo</span>
            <div class="spinner-border spinner-border-sm text-primary d-none" id="scan-spinner" role="status"></div>
        </div>
        <div class="card-body p-0">
            <div id="console-output" class="p-3 font-monospace text-success"
                style="height: 300px; overflow-y: auto; font-size: 0.85rem; line-height: 1.5; white-space: pre-wrap;">
                Esperando para iniciar...
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div id="results-container" class="d-none">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="h4 text-white fw-bold mb-0">Resultados</h3>
            <div class="d-flex gap-2">
                <button id="btn-select-page" class="btn btn-outline-info fw-semibold">
                    <i class="fa-solid fa-check-double me-2"></i> Seleccionar Página
                </button>
                <button id="btn-discard-selected" class="btn btn-outline-danger fw-semibold">
                    <i class="fa-solid fa-trash me-2"></i> Descartar Seleccionados
                </button>
                <button id="btn-apply-selected" class="btn btn-success fw-semibold">
                    <i class="fa-solid fa-check me-2"></i> Aplicar Seleccionados
                </button>
            </div>
        </div>

        <ul class="nav nav-pills mb-3 gap-2" id="resultsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-semibold" id="new-tab" data-bs-toggle="pill"
                    data-bs-target="#new" type="button" role="tab">
                    Nuevas (<span id="count-new">0</span>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-semibold" id="changed-tab" data-bs-toggle="pill"
                    data-bs-target="#changed" type="button" role="tab">
                    Con Cambios (<span id="count-changed">0</span>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="resultsTabContent">
            <!-- Nuevas -->
            <div class="tab-pane fade show active" id="new" role="tabpanel">
                <div class="card rcw-card bg-transparent border-0">
                    <div class="card-body p-0" id="list-new">
                        <!-- Items irán aquí -->
                    </div>
                    <div id="pagination-new" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
            <!-- Cambios -->
            <div class="tab-pane fade" id="changed" role="tabpanel">
                <div class="card rcw-card bg-transparent border-0">
                    <div class="card-body p-0" id="list-changed">
                        <!-- Items irán aquí -->
                    </div>
                    <div id="pagination-changed" class="d-flex justify-content-center mt-3"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .coaster-result-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }

    .coaster-result-card:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .form-check-input.custom-checkbox {
        width: 1.5em;
        height: 1.5em;
        margin-top: 0.25em;
        cursor: pointer;
    }

    .change-item {
        font-size: 0.9rem;
        padding: 6px 12px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 6px;
        margin-bottom: 6px;
    }

    .nav-pills .nav-link {
        color: var(--rcw-text-muted);
        background: rgba(255, 255, 255, 0.05);
    }

    .nav-pills .nav-link.active {
        background: var(--rcw-primary);
        color: white;
    }
</style>

<script>
    $(document).ready(function () {
        let evtSource = null;
        let allNewCoasters = [];
        let allChangedCoasters = [];
        let currentPageNew = 1;
        let currentPageChg = 1;
        const ITEMS_PER_PAGE = 10;
        const selectedIds = new Set();
        const manualOverrides = {}; // { rcdb_id: { field_name: { checked: boolean, value: string } } }

        $(document).on('change', '.item-checkbox', function () {
            if (this.checked) selectedIds.add($(this).val());
            else selectedIds.delete($(this).val());
        });

        $(document).on('change', '.field-checkbox', function () {
            const id = $(this).data('rcdb-id');
            const field = $(this).data('field');
            if (manualOverrides[id] && manualOverrides[id][field]) {
                manualOverrides[id][field].checked = this.checked;
            }
        });

        $(document).on('input', '.field-input', function () {
            const id = $(this).data('rcdb-id');
            const field = $(this).data('field');
            if (manualOverrides[id] && manualOverrides[id][field]) {
                manualOverrides[id][field].value = $(this).val();
            }
        });

        window.changePage = function (type, delta) {
            if (type === 'new') {
                currentPageNew += delta;
                renderPage('new', currentPageNew);
            } else {
                currentPageChg += delta;
                renderPage('changed', currentPageChg);
            }
        };

        window.goToPage = function (type, page) {
            if (type === 'new') {
                currentPageNew = page;
                renderPage('new', currentPageNew);
            } else {
                currentPageChg = page;
                renderPage('changed', currentPageChg);
            }
        };

        $('#btn-scan-quick').on('click', function () { startScan('quick'); });
        $('#btn-scan-full').on('click', function () {
            showConfirm('¿Seguro que quieres iniciar el escaneo completo? Puede tardar horas.', function () {
                startScan('full');
            });
        });
        $('#btn-load-cache').on('click', loadCache);

        function startScan(type) {
            if (evtSource) {
                evtSource.close();
            }

            $('#console-container').removeClass('d-none');
            $('#results-container').addClass('d-none');
            $('#scan-spinner').removeClass('d-none');

            const $out = $('#console-output');
            $out.text('Iniciando escaneo ' + type + '...\n\n');

            const url = '<?= Router::url('api_updater_scan') ?>' + '?type=' + type;
            evtSource = new EventSource(url);

            evtSource.onmessage = function (e) {
                if (e.data.startsWith('[DONE]')) {
                    $out.append('\n' + e.data + '\n');
                    evtSource.close();
                    $('#scan-spinner').addClass('d-none');
                    $out.scrollTop($out[0].scrollHeight);
                    // Load results automatically after done
                    loadCache();
                } else {
                    // If it's a progress update with brackets, replace the last line to simulate \r
                    if (e.data.trim().startsWith('[')) {
                        let text = $out.text();
                        let lines = text.split('\n');
                        if (lines.length > 0 && lines[lines.length - 2] && lines[lines.length - 2].trim().startsWith('[')) {
                            lines[lines.length - 2] = e.data;
                            $out.text(lines.join('\n'));
                        } else {
                            $out.append(e.data + '\n');
                        }
                    } else {
                        $out.append(e.data + '\n');
                    }
                    $out.scrollTop($out[0].scrollHeight);
                }
            };

            evtSource.onerror = function (e) {
                $out.append('\n[ERROR DE CONEXIÓN CON EL SERVIDOR SSE]\n');
                evtSource.close();
                $('#scan-spinner').addClass('d-none');
            };
        }

        function loadCache() {
            $.ajax({
                url: '<?= Router::url('api_updater_cache') ?>',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.error) {
                        showAlert('Error: ' + res.error);
                        return;
                    }
                    renderResults(res);
                },
                error: function () {
                    showAlert('No se pudo cargar la caché. Puede que no exista.');
                }
            });
        }

        function renderResults(data) {
            $('#results-container').removeClass('d-none');

            allNewCoasters = data.new_coasters || [];
            allChangedCoasters = data.changed_coasters || [];

            $('#count-new').text(allNewCoasters.length);
            $('#count-changed').text(allChangedCoasters.length);

            currentPageNew = 1;
            currentPageChg = 1;

            renderPage('new', currentPageNew);
            renderPage('changed', currentPageChg);
        }

        function renderPage(type, page) {
            const dataArr = type === 'new' ? allNewCoasters : allChangedCoasters;
            const $list = type === 'new' ? $('#list-new') : $('#list-changed');
            const $pagination = type === 'new' ? $('#pagination-new') : $('#pagination-changed');

            $list.empty();

            if (dataArr.length === 0) {
                $list.append('<div class="text-muted p-3 text-center">No hay resultados.</div>');
                $pagination.empty();
                return;
            }

            const totalPages = Math.ceil(dataArr.length / ITEMS_PER_PAGE);
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;

            if (type === 'new') currentPageNew = page;
            else currentPageChg = page;

            const start = (page - 1) * ITEMS_PER_PAGE;
            const end = Math.min(start + ITEMS_PER_PAGE, dataArr.length);
            const items = dataArr.slice(start, end);

            items.forEach(item => {
                let isChecked = selectedIds.has(String(item.rcdb_id)) ? 'checked' : '';

                if (type === 'new') {
                    let img = item.main_image_url ? `<img src="${item.main_image_url}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;" class="me-3">` : `<div class="bg-secondary me-3 d-flex align-items-center justify-content-center text-white" style="width: 80px; height: 80px; border-radius: 8px;"><i class="fa-solid fa-image"></i></div>`;
                    $list.append(`
                        <div class="coaster-result-card p-3 d-flex align-items-center">
                            <div class="form-check me-3">
                                <input class="form-check-input custom-checkbox item-checkbox" type="checkbox" value="${item.rcdb_id}" id="chk-new-${item.rcdb_id}" ${isChecked}>
                            </div>
                            ${img}
                            <div class="flex-grow-1">
                                <h5 class="mb-1 text-white fw-bold"><label for="chk-new-${item.rcdb_id}" style="cursor:pointer;">${item.name || 'Sin Nombre'}</label></h5>
                                <div class="text-muted small">
                                    <i class="fa-solid fa-location-dot me-1"></i> ${item.park || 'Parque Desconocido'} &nbsp;|&nbsp; 
                                    <i class="fa-solid fa-tag me-1"></i> ${item.status || '?'} &nbsp;|&nbsp;
                                    <a href="${item.rcdb_url}" target="_blank" class="text-primary text-decoration-none"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> RCDB</a>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">NUEVA</span>
                            </div>
                        </div>
                    `);
                } else {
                    let changesHtml = item.changes.map(ch => {
                        if (typeof ch === 'object') {
                            if (!manualOverrides[item.rcdb_id]) manualOverrides[item.rcdb_id] = {};
                            if (!manualOverrides[item.rcdb_id][ch.field]) {
                                manualOverrides[item.rcdb_id][ch.field] = { checked: true, value: ch.new };
                            }
                            let state = manualOverrides[item.rcdb_id][ch.field];
                            let isChecked = state.checked ? 'checked' : '';

                            return `
                                <div class="change-item mb-2 d-flex align-items-center flex-wrap">
                                    <div class="form-check me-2 mb-0">
                                        <input class="form-check-input field-checkbox custom-checkbox" type="checkbox" data-rcdb-id="${item.rcdb_id}" data-field="${ch.field}" ${isChecked} id="chk-${item.rcdb_id}-${ch.field}">
                                    </div>
                                    <label class="text-warning fw-semibold me-2 mb-0" for="chk-${item.rcdb_id}-${ch.field}"><i class="fa-solid fa-pen me-1"></i> ${ch.label}: <span class="text-secondary text-decoration-line-through">${ch.old}</span> &rarr;</label>
                                    <input type="text" class="form-control form-control-sm bg-dark text-white border-secondary field-input" style="width: auto; max-width: 200px;" data-rcdb-id="${item.rcdb_id}" data-field="${ch.field}" value="${state.value}">
                                </div>
                            `;
                        } else {
                            return `<div class="change-item text-warning mb-1"><i class="fa-solid fa-pen me-2"></i> ${ch}</div>`;
                        }
                    }).join('');

                    let img = item.scraped && item.scraped.main_image_url ? `<img src="${item.scraped.main_image_url}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;" class="me-3">` : `<div class="bg-secondary me-3 d-flex align-items-center justify-content-center text-white" style="width: 80px; height: 80px; border-radius: 8px;"><i class="fa-solid fa-image"></i></div>`;
                    let parkName = (item.scraped && item.scraped.park) ? item.scraped.park : 'Parque Desconocido';
                    let rcdbUrl = (item.scraped && item.scraped.rcdb_url) ? item.scraped.rcdb_url : `https://rcdb.com/${item.rcdb_id}.htm`;

                    $list.append(`
                        <div class="coaster-result-card p-3 d-flex flex-column mb-3" style="background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div class="d-flex align-items-center mb-3">
                                <div class="form-check me-3">
                                    <input class="form-check-input custom-checkbox item-checkbox" type="checkbox" value="${item.rcdb_id}" id="chk-chg-${item.rcdb_id}" ${isChecked}>
                                </div>
                                ${img}
                                <div class="flex-grow-1">
                                    <h5 class="mb-1 text-white fw-bold"><label for="chk-chg-${item.rcdb_id}" style="cursor:pointer;">${item.name || 'Sin Nombre'} (ID: ${item.rcdb_id})</label></h5>
                                    <div class="text-muted small">
                                        <i class="fa-solid fa-location-dot me-1"></i> ${parkName} &nbsp;|&nbsp; 
                                        <a href="${rcdbUrl}" target="_blank" class="text-primary text-decoration-none"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> RCDB</a>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge" style="background: rgba(255, 193, 7, 0.2); border: 1px solid #ffc107; color: #ffc107;">MODIFICADA</span>
                                </div>
                            </div>
                            <div class="ps-4 ms-2 border-start border-warning" style="border-left-width: 3px !important; opacity: 0.9;">
                                ${changesHtml}
                            </div>
                        </div>
                    `);
                }
            });

            // Render Pagination HTML
            let pagHtml = `
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" onclick="goToPage('${type}', 1)" ${page === 1 ? 'disabled' : ''} title="Primera Página"><i class="fa-solid fa-angles-left"></i></button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="changePage('${type}', -1)" ${page === 1 ? 'disabled' : ''} title="Anterior"><i class="fa-solid fa-angle-left"></i></button>
                    </div>
                    
                    <div class="d-flex align-items-center mx-2">
                        <span class="text-muted small me-2">Página</span>
                        <input type="number" class="form-control form-control-sm text-center" style="width: 70px; background: rgba(255,255,255,0.05); color: white; border-color: rgba(255,255,255,0.1);" value="${page}" min="1" max="${totalPages}" onchange="goToPage('${type}', parseInt(this.value))">
                        <span class="text-muted small ms-2">de ${totalPages}</span>
                    </div>

                    <div class="btn-group">
                        <button class="btn btn-outline-secondary btn-sm" onclick="changePage('${type}', 1)" ${page === totalPages ? 'disabled' : ''} title="Siguiente"><i class="fa-solid fa-angle-right"></i></button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="goToPage('${type}', ${totalPages})" ${page === totalPages ? 'disabled' : ''} title="Última Página"><i class="fa-solid fa-angles-right"></i></button>
                    </div>
                </div>
            `;
            $pagination.html(pagHtml);
        }

        $('#btn-apply-selected').on('click', function () {
            const selected = Array.from(selectedIds);

            if (selected.length === 0) {
                showAlert('No has seleccionado ninguna coaster.');
                return;
            }

            showConfirm('¿Aplicar los cambios seleccionados en la base de datos? (' + selected.length + ' elementos)', function () {
                const $btn = $('#btn-apply-selected');
                const originalHtml = $btn.html();
                $btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Aplicando...');
                $btn.prop('disabled', true);

                let overridesToSend = {};
                selected.forEach(id => {
                    if (manualOverrides[id]) {
                        overridesToSend[id] = {};
                        for (let field in manualOverrides[id]) {
                            if (manualOverrides[id][field].checked) {
                                overridesToSend[id][field] = manualOverrides[id][field].value;
                            }
                        }
                    }
                });

                $.ajax({
                    url: '<?= Router::url('api_updater_apply') ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                    data: JSON.stringify({ ids: selected, overrides: overridesToSend }),
                    success: function (res) {
                        if (res.error) {
                            showAlert('Error: ' + res.error);
                        } else {
                            showAlert('Cambios aplicados correctamente.');
                            selectedIds.clear();
                            // Desmarcar y recargar el cache
                            loadCache();
                            console.log(res.output);
                        }
                    },
                    error: function (err) {
                        showAlert('Error al aplicar cambios.');
                    },
                    complete: function () {
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        $('#btn-discard-selected').on('click', function () {
            const selected = Array.from(selectedIds);

            if (selected.length === 0) {
                showAlert('No has seleccionado ninguna coaster para descartar.');
                return;
            }

            showConfirm('¿Estás seguro de que quieres descartar estos ' + selected.length + ' elementos? Desaparecerán de la lista sin aplicarse a la base de datos.', function () {
                const $btn = $('#btn-discard-selected');
                const originalHtml = $btn.html();
                $btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Descartando...');
                $btn.prop('disabled', true);

                $.ajax({
                    url: '<?= Router::url('api_updater_discard') ?>',
                    type: 'POST',
                    contentType: 'application/json',
                    headers: { 'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content') },
                    data: JSON.stringify({ ids: selected }),
                    success: function (res) {
                        if (res.error) {
                            showAlert('Error: ' + res.error);
                        } else {
                            showAlert('Elementos descartados correctamente.');
                            selectedIds.clear();
                            loadCache();
                        }
                    },
                    error: function (err) {
                        showAlert('Error al descartar elementos.');
                    },
                    complete: function () {
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        $('#btn-select-page').on('click', function () {
            // Select all visible checkboxes
            const checkboxes = $('.item-checkbox:visible');
            if (checkboxes.length === 0) return;

            // Check if all are already checked
            const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

            checkboxes.each(function () {
                // Toggle state
                $(this).prop('checked', !allChecked);
                // Trigger change to update the selectedIds Set
                $(this).trigger('change');
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>