<?php
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */
require_once __DIR__ . '/../routes/routes.php';
?>

<main>
    <section class="contact">
        <h2>Formulario de Contacto</h2>

        <section class="contact-info">
            <h3>
                Si tienes alguna pregunta o sugerencia, no dudes en contactar con nosotros.
            </h3>
        </section>

        <section class="contact-subinfo">
            <p>
                Usa este formulario para ponerte en contacto con nosotros. Puedes
                reportar errores, enviar sugerencias, aportar información sobre
                coasters o parques, denunciar contenido o colaborar con la comunidad.
            </p>
        </section>

        <form action="<?= $base_url ?>/api/php/contact.php" method="post" class="contact-form" id="contact-form">
            <fieldset style="text-align:center; border-radius:5px; border:1px solid black; padding:10px; margin:10px;">
                <legend>Formulario de contacto</legend>

                <div class="form-group">
                    <label for="name">Nombre de Usuario</label><br />
                    <input type="text" name="name" id="name" required /><br />
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label><br />
                    <input type="email" name="email" id="email" required /><br />
                    <span id="email-error"
                        style="color:red; font-size:0.85rem; margin-top:1rem; text-align:center;"></span>
                </div>

                <div class="form-group">
                    <label for="reason">Motivo de Contacto</label><br />
                    <select name="reason" id="reason" required>
                        <option value="" selected disabled hidden>Selecciona una opción</option>
                        <option value="error">Reportar un error</option>
                        <option value="suggestion">Enviar una sugerencia</option>
                        <option value="report">Denunciar a un usuario</option>
                        <option value="info">Aportar información sobre una coaster o parque</option>
                        <option value="other">Otro</option>
                    </select><br />
                </div>

                <div class="form-group">
                    <label for="subject">Asunto</label><br />
                    <input type="text" name="subject" id="subject" required /><br />
                </div>

                <div class="form-group">
                    <label for="message">Mensaje</label><br />
                    <textarea name="message" id="message" cols="30" rows="10" required></textarea><br />
                    <span id="message-error"
                        style="color:red; font-size:0.85rem; margin-top:1rem; text-align:center;"></span>
                </div>

                <p>Caracteres totales: <span id="char-count" style="color:red;">0</span></p>

                <div class="form-group">
                    <p>¿Con qué tipo de frecuencia usas la web?</p>
                    <input type="radio" name="frecuency" id="daily" value="daily" required />
                    <label for="daily">A Diario</label>

                    <input type="radio" name="frecuency" id="ocasional" value="ocasional" required />
                    <label for="ocasional">Ocasionalmente</label>

                    <input type="radio" name="frecuency" id="rarely" value="rarely" required />
                    <label for="rarely">Raramente</label><br /><br />
                </div>

                <div class="form-group">
                    <p>Marca esta casilla si esperas que te contactemos de vuelta al correo electrónico proporcionado
                    </p>
                    <input type="checkbox" name="contact" id="contact" value="contact" />
                    <label for="contact">Contactarme</label><br /><br />
                </div>

                <button type="submit" id="submit">Enviar mensaje</button>

                <p id="error" style="color:red; font-size:0.85rem; margin-top:1rem; text-align:center;"></p>
                <p id="success" style="color:green; font-size:0.85rem; margin-top:1rem; text-align:center;"></p>

                <?php if (isset($_GET['error'])): ?>
                    <p style="color:red; font-size:0.85rem; margin-top:1rem; text-align:center;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </p>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <p style="color:green; font-size:0.85rem; margin-top:1rem; text-align:center;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </p>
                <?php endif; ?>

            </fieldset>
        </form>
    </section>
</main>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/contact.css" />
<script src="<?= $base_url ?>/web/js/contact.js"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>