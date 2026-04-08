<?php
$page_css = ['web/css/privacy.css'];
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */
?>
<main>

    <!-- Hero -->
    <div class="privacy-hero">
        <div class="container">
            <span class="privacy-hero-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <h1 class="privacy-hero-title">Política de Privacidad</h1>
            <p class="privacy-hero-sub">Última actualización: Diciembre 2025</p>
        </div>
    </div>

    <div class="container privacy-body">
        <div class="privacy-card">

            <div class="privacy-section">
                <h2><i class="fa-solid fa-circle-info me-2 text-success"></i>1. Introducción</h2>
                <p>Bienvenido a <strong>Roller Coaster World</strong>. Tu privacidad es importante para nosotros. Esta Política de Privacidad explica cómo recopilamos, usamos y protegemos tu información cuando visitas este sitio web.</p>
                <p>Este es un proyecto personal desarrollado con fines educativos y de entretenimiento.</p>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-database me-2 text-success"></i>2. Información que Recopilamos</h2>
                <p>Roller Coaster World recopila una cantidad mínima de datos necesarios para el funcionamiento de la página:</p>
                <ul class="privacy-list">
                    <li><strong>Información proporcionada por el usuario:</strong> Datos que introduces voluntariamente en los formularios de registro o contacto (como tu nombre o correo electrónico).</li>
                    <li><strong>Cookies y Almacenamiento Local:</strong> Utilizamos el almacenamiento local de tu navegador para recordar tu sesión y preferencias. No utilizamos cookies de rastreo de terceros para publicidad.</li>
                </ul>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-gears me-2 text-success"></i>3. Uso de la Información</h2>
                <p>La información recopilada se utiliza exclusivamente para:</p>
                <ul class="privacy-list">
                    <li>Permitir el funcionamiento de la página y el registro de usuarios.</li>
                    <li>Mostrar tablas de clasificación y rankings globales.</li>
                    <li>Responder a tus consultas o sugerencias enviadas a través del formulario de contacto.</li>
                </ul>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-share-nodes me-2 text-success"></i>4. Compartir Información</h2>
                <p><strong>No vendo, comercio ni alquilo tu información personal a terceros.</strong> Dado que este es un proyecto personal sin fines de lucro comercial, tus datos se mantienen estrictamente dentro del ámbito de la aplicación.</p>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-lock me-2 text-success"></i>5. Seguridad de los Datos</h2>
                <p>Implementamos medidas de seguridad básicas y razonables para proteger tu información contra acceso no autorizado, alteración o destrucción. Sin embargo, ten en cuenta que ninguna transmisión por Internet es 100% segura.</p>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-user-check me-2 text-success"></i>6. Tus Derechos</h2>
                <p>Tienes derecho a acceder, corregir o eliminar tu información personal en cualquier momento. Si deseas que eliminemos tu cuenta o tus datos de los rankings, por favor contáctanos.</p>
            </div>

            <div class="privacy-section privacy-section--last">
                <h2><i class="fa-solid fa-envelope me-2 text-success"></i>7. Contacto</h2>
                <p>Si tienes alguna pregunta sobre esta Política de Privacidad, puedes contactarnos en:</p>
                <a href="mailto:carxmgl934@gmail.com" class="privacy-email">
                    <i class="fa-solid fa-at me-2"></i>carxmgl934@gmail.com
                </a>
            </div>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
