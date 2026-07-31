<?php
$page_css = ['web/css/coasters.css', 'web/css/tickets.css'];
require_once __DIR__ . '/../../partials/header.php';
// HIDDEN-TFG-START: Bloquear acceso directo a la vista de tienda
Router::redirect('home');
// HIDDEN-TFG-END
if (!$is_logged)
  Router::redirect('login');

// Leer claves Stripe del .env
$envFile = __DIR__ . '/../../../../.env';
$stripePublicKey = '';
if (file_exists($envFile)) {
  foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '='))
      continue;
    [$k, $v] = explode('=', $line, 2);
    if (trim($k) === 'STRIPE_PUBLIC_KEY') {
      $stripePublicKey = trim($v);
      break;
    }
  }
}

// Detectar retorno de Stripe
$paymentStatus = $_GET['payment'] ?? '';
$stripeSessionId = $_GET['session_id'] ?? '';
?>

<main class="container-fluid px-lg-5 my-5">

  <!-- ───── PANTALLA DE CARGA STRIPE (verificando pago) ───── -->
  <div id="checkout-verifying" class="d-none">
    <div class="row justify-content-center mt-5">
      <div class="col-12 col-md-6 text-center">
        <div class="spinner-border text-success mb-3" style="width:3rem;height:3rem;"></div>
        <h4 class="text-white">Verificando tu pago...</h4>
        <p class="text-muted">Por favor, espera un momento.</p>
      </div>
    </div>
  </div>

  <!-- ───── PANTALLA CANCELACIÓN STRIPE ───── -->
  <div id="checkout-cancelled" class="d-none">
    <div class="row justify-content-center mt-5">
      <div class="col-12 col-md-6">
        <div class="card bg-dark text-white rounded-0 border-warning shadow text-center p-4">
          <i class="fa-solid fa-circle-xmark fa-2x text-warning d-block mb-3"></i>
          <h5 class="fw-bold text-warning">Pago cancelado</h5>
          <p class="mb-4 text-white-50">No se ha completado el pago. Tu carrito sigue guardado y puedes intentarlo de
            nuevo cuando quieras.</p>
          <div>
            <a href="<?= Router::url('checkout') ?>" class="btn btn-warning rounded-0 fw-bold me-2">
              <i class="fa-solid fa-rotate-left me-1"></i>Reintentar pago
            </a>
            <a href="<?= Router::url('carrito') ?>" class="btn btn-outline-secondary rounded-0 text-white">
              <i class="fa-solid fa-cart-shopping me-1"></i>Ver carrito
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ───── PANTALLA ÉXITO (oculta al inicio, se muestra tras verificar) ───── -->
  <div id="checkout-success" class="d-none">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
          <i class="fa-solid fa-circle-check me-2"></i>¡Pago confirmado!
        </h1>
      </div>
    </div>
    <div class="row justify-content-center">
      <div class="col-12 col-md-7">
        <div class="card shadow-sm border-0 rounded-0">
          <div class="card-header bg-success text-white rounded-0 text-center">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-ticket me-2"></i>Resumen del pedido</h5>
          </div>
          <div class="card-body text-center py-4">
            <div class="mb-3">
              <i class="fa-brands fa-stripe fa-2x text-primary opacity-75"></i>
              <small class="d-block text-muted mt-1" style="font-size:.75rem;">Pago procesado de forma segura por
                Stripe</small>
            </div>
            <h4 class="text-success fw-bold mb-3"><i class="fa-solid fa-circle-check me-2"></i>¡Pedido Confirmado!</h4>
            <div class="bg-dark border border-success rounded-0 py-3 px-4 mb-4 d-inline-block">
              <small class="text-muted d-block mb-1">Referencia del pedido</small>
              <span class="fw-bold fs-5 text-success font-monospace" id="success-order-ref">—</span>
            </div>
            <p class="text-muted small mb-3">Tus entradas digitales ya están disponibles en tu cuenta para descargar.
            </p>
            <div class="alert alert-success rounded-0 border-success d-flex align-items-center gap-2 mb-4 text-start"
              style="font-size:.88rem;">
              <i class="fa-solid fa-envelope fa-lg flex-shrink-0"></i>
              <span>Te hemos enviado las entradas en PDF al correo electrónico indicado en el pedido.</span>
            </div>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="<?= Router::url('orders') ?>" class="btn btn-success fw-bold rounded-0 shadow-sm px-4">
                <i class="fa-solid fa-ticket me-2"></i>Ver mis pedidos
              </a>
              <a href="<?= Router::url('tickets') ?>" class="btn btn-outline-secondary rounded-0 px-4">
                <i class="fa-solid fa-arrow-left me-2"></i>Seguir comprando
              </a>
            </div>
          </div>
          <div class="card-footer rounded-0 border-success text-center" style="background:rgba(25,135,84,.08);">
            <small class="text-success">
              <i class="fa-solid fa-shield-halved me-1"></i>
              Pago seguro procesado por <strong>Stripe</strong> (modo test)
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Formulario de checkout -->
  <div id="checkout-form-wrap">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
          <i class="fa-solid fa-lock me-2"></i>Confirmar Pedido
        </h1>
      </div>
    </div>

    <div class="row g-4">

      <!-- Columna izquierda -->
      <div class="col-12 col-lg-7">

        <!-- Resumen del carrito -->
        <div class="card shadow-sm border-0 rounded-0 mb-4">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-list me-2"></i>Resumen de tu compra</h6>
          </div>
          <div class="card-body" id="checkout-items-list">
            <div class="text-center py-3 text-muted">
              <div class="spinner-border spinner-border-sm text-success me-2"></div> Cargando...
            </div>
          </div>
        </div>

        <!-- Datos personales -->
        <div class="card shadow-sm border-0 rounded-0">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-user me-2"></i>Datos del comprador</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-sm-6">
                <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-user me-1"></i>Nombre del
                  titular</label>
                <input type="text" id="checkout-name" class="form-control shadow-sm rounded-0"
                  value="<?= htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['display_name'] ?? '') ?>"
                  placeholder="Tu nombre completo" required>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-envelope me-1"></i>Email
                  para recibir las entradas</label>
                <input type="email" id="checkout-email" class="form-control shadow-sm rounded-0"
                  value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
              </div>
            </div>
          </div>
          <div class="card-footer rounded-0 p-0">
            <div style="border-left: 4px solid #f59e0b; background: rgba(245,158,11,0.07); padding: 14px 18px;">
              <div class="d-flex align-items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-warning mt-1" style="font-size:1.2rem;"></i>
                <div>
                  <div class="fw-bold text-warning mb-1" style="font-size:.85rem; letter-spacing:.03em;">
                    MODO TEST — Pago simulado
                  </div>
                  <div class="text-white-50" style="font-size:.8rem; line-height:1.5;">
                    No se cargará dinero real y las entradas son ficticias.<br>
                    Tarjeta de prueba:
                    <span class="fw-bold text-white ms-1"
                      style="font-family:'Courier New',monospace; letter-spacing:.12em; font-size:.9rem;">4242 4242 4242
                      4242</span>
                    <span class="text-muted ms-2" style="font-size:.75rem;">· Fecha: 12/30 · CVC: 123</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columna derecha: total -->
      <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 rounded-0" style="position:sticky;top:90px;">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-receipt me-2"></i>Total del pedido</h6>
          </div>
          <div class="card-body">
            <div id="checkout-breakdown" class="mb-3"></div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
              <span>Total a pagar</span>
              <span class="text-success" id="checkout-grand-total">0.00 €</span>
            </div>
            <!-- Botón Stripe -->
            <button class="btn btn-success w-100 fw-bold rounded-0 shadow-sm py-2 fs-5" id="btn-pay-stripe">
              <i class="fa-solid fa-credit-card me-2"></i>Pagar con Stripe
            </button>
            <!-- Logo Stripe -->
            <div class="text-center mt-2 mb-1">
              <small class="text-muted" style="font-size:.7rem;">
                <i class="fa-solid fa-shield-halved me-1 text-success"></i>Pago seguro con
                <i class="fa-brands fa-stripe ms-1 text-primary" style="font-size:1rem;"></i>
              </small>
            </div>
            <a href="<?= Router::url('carrito') ?>" class="btn btn-outline-secondary w-100 rounded-0 mt-2">
              <i class="fa-solid fa-arrow-left me-1"></i>Volver al carrito
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

</main>


<!-- Toast para mensajes (Diseño mejorado y centrado) -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3"
  style="z-index: 2000; margin-top: 85px;">
  <div id="cart-toast" class="toast align-items-center text-white border-0 rounded-0 shadow-lg" role="alert"
    aria-live="assertive" aria-atomic="true" style="min-width: 350px;">
    <div class="d-flex align-items-center p-3">
      <div class="toast-body flex-grow-1 text-center fw-medium" style="font-size: 0.95rem;">
        <i class="fa-solid fa-circle-info me-2" id="cart-toast-icon-tag"></i>
        <span id="cart-toast-msg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="https://js.stripe.com/v3/"></script>
<script>
  window.TICKETS_API = '<?= Router::getBaseUrl() ?>/api/php/tickets.php';
  window.STRIPE_API = '<?= Router::getBaseUrl() ?>/api/php/stripe_checkout.php';
  window.STRIPE_PK = '<?= htmlspecialchars($stripePublicKey) ?>';
  window.ORDERS_URL = '<?= Router::url('orders') ?>';
  window.CARRITO_URL = '<?= Router::url('carrito') ?>';
  window.CHECKOUT_URL = '<?= Router::url('checkout') ?>';
  // Retorno de Stripe
  window.STRIPE_RETURN_STATUS = '<?= htmlspecialchars($paymentStatus) ?>';
  window.STRIPE_RETURN_SESSION = '<?= htmlspecialchars($stripeSessionId) ?>';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>