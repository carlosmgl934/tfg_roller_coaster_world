<?php
$page_css = ['web/css/contact.css'];
require_once __DIR__ . '/../partials/header.php';
?>
<main>

    <!-- Hero -->
    <div class="contact-hero">
        <div class="container">
            <span class="contact-hero-icon"><i class="fa-solid fa-envelope"></i></span>
            <h1 class="contact-hero-title">Contacta con nosotros</h1>
            <p class="contact-hero-sub">¿Tienes una sugerencia, error o simplemente quieres decir hola? Escríbenos.</p>
        </div>
    </div>

    <div class="container contact-body">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-7">
                <div class="contact-card">
                    <form action="<?= Router::getBaseUrl() ?>/api/php/contact.php" method="post" id="contact-form">

                        <div class="mb-4">
                            <label for="name" class="contact-label">
                                <i class="fa-solid fa-user me-2 text-success"></i>Nombre de usuario
                            </label>
                            <input type="text" name="name" id="name"
                                   class="form-control contact-input"
                                   placeholder="Tu nombre" required>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="contact-label">
                                <i class="fa-solid fa-at me-2 text-success"></i>Correo electrónico
                            </label>
                            <input type="email" name="email" id="email"
                                   class="form-control contact-input"
                                   placeholder="tu@correo.com" required>
                            <span id="email-error" class="contact-field-error"></span>
                        </div>

                        <div class="mb-4">
                            <label for="reason" class="contact-label">
                                <i class="fa-solid fa-tag me-2 text-success"></i>Motivo de contacto
                            </label>
                            <select name="reason" id="reason" class="form-select contact-input" required>
                                <option value="" selected disabled hidden>Selecciona una opción</option>
                                <option value="error">Reportar un error</option>
                                <option value="suggestion">Enviar una sugerencia</option>
                                <option value="report">Denunciar a un usuario</option>
                                <option value="info">Aportar información sobre una coaster o parque</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="subject" class="contact-label">
                                <i class="fa-solid fa-heading me-2 text-success"></i>Asunto
                            </label>
                            <input type="text" name="subject" id="subject"
                                   class="form-control contact-input"
                                   placeholder="Breve descripción del asunto" required>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="contact-label">
                                <i class="fa-solid fa-message me-2 text-success"></i>Mensaje
                            </label>
                            <textarea name="message" id="message" rows="5"
                                      class="form-control contact-input"
                                      placeholder="Desarrolla aquí tu mensaje..." required></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <span id="message-error" class="contact-field-error"></span>
                                <span class="contact-char-count">Caracteres: <strong id="char-count">0</strong></span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="contact-label mb-2">
                                <i class="fa-solid fa-clock me-2 text-success"></i>¿Con qué frecuencia usas la web?
                            </p>
                            <div class="contact-radio-group">
                                <label class="contact-radio">
                                    <input type="radio" name="frecuency" value="daily" required>
                                    <span>A diario</span>
                                </label>
                                <label class="contact-radio">
                                    <input type="radio" name="frecuency" value="ocasional">
                                    <span>Ocasionalmente</span>
                                </label>
                                <label class="contact-radio">
                                    <input type="radio" name="frecuency" value="rarely">
                                    <span>Raramente</span>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="contact-checkbox">
                                <input type="checkbox" name="contact" value="contact">
                                <span>Quiero recibir respuesta al correo proporcionado</span>
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="submit" class="btn contact-btn-submit">
                                <i class="fa-solid fa-paper-plane me-2"></i>Enviar mensaje
                            </button>
                        </div>

                        <p id="error"   class="contact-feedback contact-feedback--error mt-3"></p>
                        <p id="success" class="contact-feedback contact-feedback--success mt-3"></p>

                        <?php if (isset($_GET['error'])): ?>
                            <p class="contact-feedback contact-feedback--error mt-3">
                                <?= htmlspecialchars($_GET['error']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (isset($_GET['success'])): ?>
                            <p class="contact-feedback contact-feedback--success mt-3">
                                <?= htmlspecialchars($_GET['success']) ?>
                            </p>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="<?= Router::asset('web/js/shared/contact.js') ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
