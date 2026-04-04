<?php
require_once __DIR__ . '/../../partials/header.php';
// if (!$is_logged) { Router::redirect('login'); } // Descomentar si es privado

// SVG logo for inline usage
$coaster_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="15" height="15" class="me-1" style="vertical-align: text-bottom;">
  <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
  <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" />
  <line x1="16" y1="42" x2="16" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
  <line x1="32" y1="20" x2="32" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
  <line x1="48" y1="24" x2="48" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
  <line x1="24" y1="28" x2="16" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.6" />
  <line x1="40" y1="16" x2="32" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity="0.6" />
  <rect x="23" y="10" width="10" height="6" rx="0" fill="currentColor" />
  <circle cx="25" cy="18" r="2" fill="currentColor" />
  <circle cx="31" cy="18" r="2" fill="currentColor" />
  <rect x="11" y="24" width="10" height="6" rx="0" fill="currentColor" transform="rotate(-40 16 27)" />
</svg>';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">
<main class="container-fluid px-lg-5 my-5 min-vh-100">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom border-light border-opacity-50 pb-2 text-success">
                <i class="fa-solid fa-ranking-star me-2"></i> Tops de la Comunidad
            </h1>
            <p class="text-muted text-uppercase fw-bold mt-3" style="letter-spacing: 0.1em; font-size: 0.85rem;">
                Descubre los ránkings de otros enthusiasts
            </p>
        </div>
    </div>

    <!-- Controles de búsqueda y filtrado -->
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center bg-dark p-3 border border-secondary border-opacity-25" style="border-radius: 0;">
                
                <!-- Buscador -->
                <div class="flex-grow-1 position-relative" style="min-width: 200px;">
                    <input type="text" id="top-search" class="form-control bg-transparent text-white border-success rounded-0 ps-3 pe-5 py-2 shadow-sm" placeholder="Buscar usuario..." style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 14px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <!-- Ordenar por -->
                <select class="form-select bg-transparent text-white border-success rounded-0 py-2 shadow-sm w-auto" style="border-width: 2px;">
                    <option value="date_desc" style="background: #212529;" selected>Última modificación</option>
                    <option value="credits_desc" style="background: #212529;">Mayor nº credits</option>
                    <option value="alpha_asc" style="background: #212529;">Orden alfabético</option>
                </select>

                <!-- Filtro Amigos -->
                <?php if (isset($is_logged) && $is_logged): ?>
                <div class="form-check form-switch fs-5 d-flex align-items-center ms-md-3">
                    <input class="form-check-input rounded-0 bg-transparent border-success focus-ring focus-ring-success mt-0" type="checkbox" role="switch" id="filterFriends" style="width: 2.5em; height: 1.2em; border-width: 2px; cursor: pointer;">
                    <label class="form-check-label ms-2 text-white" for="filterFriends" style="font-size: 0.95rem; cursor: pointer;"><i class="fa-solid fa-user-group text-success me-1"></i> Solo amigos</label>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Grid de Tops -->
    <div class="row g-4 justify-content-center" id="tops-grid">

        <?php for ($i = 1; $i <= 9; $i++): 
            // Mock data variation
            $credits = rand(50, 450);
            $names = ['Carlos', 'Ale', 'Dani', 'María', 'Jorge', 'Sara'];
            $name = $names[array_rand($names)] . ' ' . $i;
            $days = rand(0, 15);
            $timeAgo = $days === 0 ? 'Hoy' : "Hace $days días";
        ?>
        <!-- TARJETA MOCKUP -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 bg-transparent border-0 rcw-top-card" style="transition: transform 0.3s ease, box-shadow 0.3s ease; border-radius: 0;">
                <div class="card-body p-0 d-flex flex-column" style="background:#161b22; border:1px solid #30363d; border-radius: 0; position: relative;">
                    
                    <!-- CABECERA -->
                    <div class="d-flex align-items-center p-3" style="background: rgba(25,135,84,0.05); border-bottom: 1px solid #30363d;">
                        <div class="position-relative me-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($name) ?>&background=198754&color=fff&size=128" alt="Avatar" class="object-fit-cover shadow-sm rounded-circle" style="width: 48px; height: 48px; border: 2px solid var(--bs-success);">
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="fw-bold text-white mb-0 text-truncate" style="font-size: 1.1rem;">Top de <?= htmlspecialchars($name) ?></h5>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge fw-semibold rounded-0" style="background: rgba(43,222,142,0.15); color: #2bde8e; font-size: 0.75rem; border: 1px solid rgba(43,222,142,0.3);">
                                    <?= $coaster_svg ?> <?= $credits ?> Credits
                                </span>
                                <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-clock-rotate-left me-1"></i> <?= $timeAgo ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- COASTERS -->
                    <div class="flex-grow-1 p-3 d-flex flex-column gap-2" style="background: linear-gradient(180deg, #161b22 0%, #1a202a 100%);">
                        
                        <!-- Puesto 1 -->
                        <div class="d-flex align-items-center position-relative w-100" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 0;">
                            <div class="position-absolute d-flex align-items-center justify-content-center shadow" style="width: 24px; height: 24px; background: linear-gradient(135deg, #ffd700, #daa520); color: #000; font-weight: 900; font-size: 0.8rem; left: -8px; top: 50%; transform: translateY(-50%); z-index: 2; border: 2px solid #161b22; border-radius: 0;">1</div>
                            <img src="https://rcdb.com/123.jpg" onerror="this.src='<?= $base_url ?>/web/img/defaults/default_coaster.jpg'" class="object-fit-cover" style="width: 60px; height: 50px; opacity: 0.9; border-radius: 0;">
                            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left: 1.25rem !important;">
                                <div class="text-white fw-bold mb-0 text-truncate" style="font-size: 0.9rem;">Iron Gwazi</div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem;"><i class="fa-solid fa-location-dot me-1 text-success opacity-75"></i>Busch Gardens</div>
                            </div>
                        </div>

                        <!-- Puesto 2 -->
                        <div class="d-flex align-items-center position-relative w-100" style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.03); border-radius: 0;">
                            <div class="position-absolute d-flex align-items-center justify-content-center shadow" style="width: 24px; height: 24px; background: linear-gradient(135deg, #e0e0e0, #9e9e9e); color: #000; font-weight: 900; font-size: 0.8rem; left: -8px; top: 50%; transform: translateY(-50%); z-index: 2; border: 2px solid #161b22; border-radius: 0;">2</div>
                            <img src="https://rcdb.com/456.jpg" onerror="this.src='<?= $base_url ?>/web/img/defaults/default_coaster.jpg'" class="object-fit-cover" style="width: 60px; height: 46px; opacity: 0.85; border-radius: 0;">
                            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left: 1.25rem !important;">
                                <div class="text-white fw-bold mb-0 text-truncate" style="font-size: 0.85rem;">Steel Vengeance</div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem;"><i class="fa-solid fa-location-dot me-1 text-success opacity-75"></i>Cedar Point</div>
                            </div>
                        </div>

                        <!-- Puesto 3 -->
                        <div class="d-flex align-items-center position-relative w-100" style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.03); border-radius: 0;">
                            <div class="position-absolute d-flex align-items-center justify-content-center shadow" style="width: 24px; height: 24px; background: linear-gradient(135deg, #cd7f32, #8b4513); color: #fff; font-weight: 900; font-size: 0.8rem; left: -8px; top: 50%; transform: translateY(-50%); z-index: 2; border: 2px solid #161b22; border-radius: 0;">3</div>
                            <img src="https://rcdb.com/700.jpg" onerror="this.src='<?= $base_url ?>/web/img/defaults/default_coaster.jpg'" class="object-fit-cover" style="width: 60px; height: 46px; opacity: 0.85; border-radius: 0;">
                            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left: 1.25rem !important;">
                                <div class="text-light fw-semibold mb-0 text-truncate" style="font-size: 0.85rem;">VelociCoaster</div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem;"><i class="fa-solid fa-location-dot me-1 text-success opacity-75"></i>Islands of Adventure</div>
                            </div>
                        </div>

                        <!-- Puesto 4 -->
                        <div class="d-flex align-items-center position-relative w-100" style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.03); border-radius: 0;">
                            <div class="position-absolute d-flex align-items-center justify-content-center shadow" style="width: 22px; height: 22px; background: #30363d; color: #94a3b8; font-weight: 800; font-size: 0.75rem; left: -7px; top: 50%; transform: translateY(-50%); z-index: 2; border: 2px solid #161b22; border-radius: 0;">4</div>
                            <img src="https://rcdb.com/101.jpg" onerror="this.src='<?= $base_url ?>/web/img/defaults/default_coaster.jpg'" class="object-fit-cover" style="width: 60px; height: 42px; opacity: 0.75; border-radius: 0;">
                            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left: 1rem !important;">
                                <div class="text-light mb-0 text-truncate" style="font-size: 0.8rem;">Ride to Happiness</div>
                            </div>
                        </div>

                        <!-- Puesto 5 -->
                        <div class="d-flex align-items-center position-relative w-100" style="background: rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.03); border-radius: 0;">
                            <div class="position-absolute d-flex align-items-center justify-content-center shadow" style="width: 22px; height: 22px; background: #30363d; color: #94a3b8; font-weight: 800; font-size: 0.75rem; left: -7px; top: 50%; transform: translateY(-50%); z-index: 2; border: 2px solid #161b22; border-radius: 0;">5</div>
                            <img src="https://rcdb.com/102.jpg" onerror="this.src='<?= $base_url ?>/web/img/defaults/default_coaster.jpg'" class="object-fit-cover" style="width: 60px; height: 42px; opacity: 0.75; border-radius: 0;">
                            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left: 1rem !important;">
                                <div class="text-light mb-0 text-truncate" style="font-size: 0.8rem;">Untamed</div>
                            </div>
                        </div>

                    </div>

                    <!-- FOOTER (BOTON VER MAS) -->
                    <a href="<?= $base_url ?>/web/views/public/users/user_profile.php?id=<?= $i ?>#tops" 
                       class="d-block text-center py-2 text-decoration-none fw-bold text-uppercase text-white border-top border-success mt-auto" 
                       style="background-color: #198754; font-size: 0.8rem; letter-spacing: 0.5px; transition: background-color 0.2s;" 
                       onmouseover="this.style.backgroundColor='#157347'" 
                       onmouseout="this.style.backgroundColor='#198754'">
                        <i class="fa-solid fa-eye me-1"></i> Ver top completo
                    </a>

                </div>
            </div>
        </div>
        <?php endfor; ?>

    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
