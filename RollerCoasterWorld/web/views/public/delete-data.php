<?php
$page_css = ['web/css/privacy.css'];
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */
?>
<main>

    <!-- Hero -->
    <div class="privacy-hero">
        <div class="container">
            <span class="privacy-hero-icon"><i class="fa-solid fa-user-xmark"></i></span>
            <h1 class="privacy-hero-title" data-i18n="legal.delete_title">Eliminación de Datos</h1>
            <p class="privacy-hero-sub">Cómo solicitar el borrado de tu cuenta e información personal</p>
        </div>
    </div>

    <div class="container privacy-body">
        <div class="privacy-card">

            <div class="privacy-section">
                <h2><i class="fa-solid fa-trash-can me-2 text-success"></i>Solicitud de Borrado</h2>
                <p>En <strong>Roller Coaster World</strong> respetamos tu derecho a la privacidad y el control sobre tus
                    datos personales.</p>
                <p>Si deseas que eliminemos tu cuenta y toda la información asociada a ella de nuestros servidores,
                    puedes ejercer este derecho en cualquier momento.</p>
            </div>

            <div class="privacy-section">
                <h2><i class="fa-solid fa-list-check me-2 text-success"></i>¿Qué se eliminará?</h2>
                <p>Al procesar tu solicitud, eliminaremos de forma permanente:</p>
                <ul class="privacy-list">
                    <li>Tu cuenta de usuario y credenciales de acceso.</li>
                    <li>Tu perfil público y la personalización del mismo.</li>
                    <li>Tus tops de montañas rusas y parques (desaparecerán de los rankings globales).</li>
                    <li>Cualquier otra información vinculada a tu identificador de usuario.</li>
                </ul>
            </div>

            <div class="privacy-section privacy-section--last">
                <h2><i class="fa-solid fa-envelope me-2 text-success"></i>Cómo hacer la solicitud</h2>
                <p>Para solicitar la eliminación completa de tus datos, por favor envíanos un correo electrónico
                    indicando el nombre de usuario o correo asociado a tu cuenta. Procesaremos tu petición en la mayor
                    brevedad posible.</p>
                <p>Escríbenos a:</p>
                <a href="mailto:tfgrollercoaster@gmail.com" class="privacy-email">
                    <i class="fa-solid fa-at me-2"></i>tfgrollercoaster@gmail.com
                </a>
            </div>

        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>