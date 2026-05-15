# 🎢 RollerCoasterWorld — Tecnologías, Frameworks y Librerías Utilizadas

Documento completo con todas las tecnologías empleadas en el TFG, organizadas por categoría con su versión, propósito y dónde se usan dentro del proyecto.

---

## 🖥️ Frontend — Librerías CDN

### 1. Bootstrap `v5.3.3`
- **Fuente:** `cdn.jsdelivr.net/npm/bootstrap@5.3.3`
- **Tipo:** Framework CSS + JS
- **Qué hace:** Sistema de grid responsivo, componentes UI (modales, botones, badges, formularios, alerts, spinners, tabs, dropdowns), utilidades CSS y JavaScript para interactividad básica. Es la columna vertebral del layout de toda la aplicación.
- **Dónde se usa:** En todas las vistas. Se carga globalmente desde `header.php`.

---

### 2. jQuery `v3.7.1`
- **Fuente:** `code.jquery.com/jquery-3.7.1.min.js`
- **Tipo:** Librería JavaScript
- **Qué hace:** Selección y manipulación del DOM, manejo de eventos, peticiones AJAX simplificadas. Muy usada en los módulos de búsqueda, formularios y acciones sociales.
- **Dónde se usa:** Foros, búsqueda de usuarios (`user_search.js`), formularios de administración y otras interacciones dinámicas de la UI.

---

### 3. Font Awesome `v6.5.0`
- **Fuente:** `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0`
- **Tipo:** Librería de iconos vectoriales (SVG/CSS)
- **Qué hace:** Proporciona todos los iconos del sitio: montaña rusa, ubicación, corazón, estrellas, candados, vuelos, usuarios, etc.
- **Dónde se usa:** En todas las vistas como iconos decorativos e informativos.

---

### 4. Google Fonts — Inter + Outfit
- **Fuente:** `fonts.googleapis.com`
- **Tipo:** Tipografías web
- **Qué hace:**
  - **Inter** (pesos 400–800): Fuente principal del cuerpo de texto, menús y tarjetas.
  - **Outfit** (pesos 400–900): Fuente de títulos y encabezados (`--rcw-font-title`).
- **Dónde se usa:** Se carga globalmente en `header.php` y se aplica mediante variables CSS globales.

---

### 5. Firebase SDK `v10.14.1` (modo compat)
- **Fuente:** `gstatic.com/firebasejs/10.14.1`
- **Tipo:** SDK de autenticación y almacenamiento en la nube
- **Módulos cargados:**
  - `firebase-app-compat.js` — Inicialización de la app Firebase
  - `firebase-auth-compat.js` — Autenticación de usuarios
  - `firebase-storage-compat.js` — Subida de archivos (avatares de perfil)
- **Qué hace:**
  - Gestiona el **login, registro y logout** con email/contraseña, Google y Facebook (OAuth).
  - Genera y valida **tokens JWT** que el backend PHP verifica para cada petición protegida.
  - Permite la subida de **imágenes de perfil** al bucket de Firebase Storage.
- **Dónde se usa:** `auth.js`, `profile.js`, todos los flujos de autenticación.

---

### 6. Leaflet.js `v1.9.4`
- **Fuente:** `unpkg.com/leaflet@1.9.4`
- **Tipo:** Librería de mapas interactivos
- **Qué hace:** Renderiza un **mapa interactivo de OpenStreetMap** en el que se muestran los parques de atracciones con marcadores personalizados. Permite zoom, arrastrar, ver popups con información del parque y navegar a su ficha.
- **Dónde se usa:** `map.js`, sección Mapa de la web pública.

---

### 7. Leaflet.markercluster `v1.5.3`
- **Fuente:** `unpkg.com/leaflet.markercluster@1.5.3`
- **Tipo:** Plugin para Leaflet
- **Qué hace:** Agrupa automáticamente los marcadores del mapa cuando hay muchos cercanos, evitando el solapamiento visual. Los grupos muestran el número de parques agrupados y se expanden al hacer zoom.
- **Dónde se usa:** `map.js`, junto a Leaflet.js.

---

### 8. Choices.js (última estable)
- **Fuente:** `cdn.jsdelivr.net/npm/choices.js`
- **Tipo:** Librería para inputs select mejorados
- **Qué hace:** Transforma los `<select>` estándar en selectores con búsqueda, múltiple selección, etiquetas (tags) y diseño personalizable. Se integra con el tema oscuro del proyecto.
- **Dónde se usa:**
  - Selector de **países** en filtros de parques y coasters.
  - Selector de **fabricantes** de montañas rusas.
  - Selector de **tipos de coaster** (wooden, steel, etc.).
  - Input de **países visitados** al crear un viaje.
  - Perfil de usuario: edición de ciudad y país.

---

### 9. Flatpickr (última estable) + locale ES
- **Fuente:** `cdn.jsdelivr.net/npm/flatpickr`
- **Tipo:** Librería de selector de fechas (datepicker)
- **Qué hace:** Reemplaza los inputs `<input type="date">` nativos con un calendario visualmente bonito, adaptado al tema oscuro del proyecto. Soporta rangos de fechas, restricciones de mínimo/máximo, y está localizado en español.
- **Dónde se usa:**
  - Creación y edición de **viajes** (fechas de inicio y fin).
  - **Estadísticas** personales: filtro por rango de fechas.
  - **Ranking de montañas rusas**: filtros temporales.
  - Perfil de usuario: campo de **fecha de nacimiento**.

---

### 10. SortableJS `v1.15.2`
- **Fuente:** `cdn.jsdelivr.net/npm/sortablejs@1.15.2`
- **Tipo:** Librería de drag & drop
- **Qué hace:** Permite reordenar elementos de una lista arrastrándolos. Tras soltar, actualiza el orden en la base de datos mediante una petición AJAX al backend.
- **Dónde se usa:**
  - **Top de Coasters** personales: reordenar posiciones del ranking.
  - **Top de Parques** personales: reordenar posiciones del ranking.
  - Ambas listas en la vista de perfil (sección "Mis Tops").

---

### 11. Chart.js (última estable)
- **Fuente:** `cdn.jsdelivr.net/npm/chart.js`
- **Tipo:** Librería de gráficos y visualización de datos
- **Qué hace:** Genera gráficos interactivos y animados para representar estadísticas.
- **Dónde se usa:**
  - **Dashboard de administración**: gráficos de actividad, registros, ingresos.
  - **Estadísticas personales de viajes**: coasters montadas por parque, distribución de visitas, etc.

---

### 12. FullCalendar `v6.1.11`
- **Fuente:** `cdn.jsdelivr.net/npm/fullcalendar@6.1.11`
- **Tipo:** Librería de calendario de eventos
- **Qué hace:** Muestra un **calendario mensual interactivo** con los días de viajes y visitas del usuario marcados visualmente. Al hacer clic en un día carga el detalle de ese día mediante un modal dinámico.
- **Dónde se usa:** Sección de **Viajes / Agenda** (`trips.php`), vista de calendario de actividad personal.

---

### 13. Cropper.js `v1.6.2`
- **Fuente:** `cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2`
- **Tipo:** Librería de recorte de imágenes
- **Qué hace:** Permite al usuario **recortar y centrar su foto de perfil** antes de subirla. Muestra una interfaz con una máscara circular ajustable sobre la imagen seleccionada.
- **Dónde se usa:** Modal de **edición de avatar** en el perfil de usuario. Se carga dinámicamente (lazy-load) solo cuando el usuario abre ese modal.

---

### 14. Stripe.js `v3`
- **Fuente:** `js.stripe.com/v3/`
- **Tipo:** SDK de pagos frontend de Stripe
- **Qué hace:** Integración del checkout de Stripe en el lado del cliente. Redirige al usuario a la **página de pago segura de Stripe** (Stripe Checkout) para completar la compra de entradas.
- **Dónde se usa:** `stripe_checkout.php`, carrito de compra, proceso de pago de tickets de parques.

---

## ⚙️ Backend — Librerías PHP (Composer)

Gestionadas con **Composer** y definidas en `composer.json`.

### 1. `stripe/stripe-php` `^16`
- **Qué hace:** SDK oficial de Stripe para PHP. Permite al servidor crear **Checkout Sessions** (sesiones de pago), recuperar el estado de una sesión completada, gestionar webhooks y verificar pagos.
- **Dónde se usa:** `api/php/stripe_checkout.php`, generación y validación de pedidos de tickets.

---

### 2. `phpmailer/phpmailer` `6.9`
- **Qué hace:** Librería de envío de emails para PHP. Permite enviar correos transaccionales a través de **SMTP** con HTML, adjuntos, codificación Base64 y cifrado STARTTLS/SMTPS.
- **Dónde se usa:**
  - Envío de **tickets de entrada en PDF** tras completar una compra con Stripe.
  - Envío de **emails de contacto** desde el formulario de contacto.

---

### 3. `dompdf/dompdf` `^3.1`
- **Qué hace:** Convierte HTML+CSS en documentos **PDF** directamente desde PHP. Permite diseñar los tickets de entrada en HTML y renderizarlos como archivos PDF listos para imprimir o adjuntar a emails.
- **Dónde se usa:** `api/php/generate_ticket_pdf.php` — Generación de los PDFs de tickets de parques temáticos.

---

### 4. `picqer/php-barcode-generator` `^3.2`
- **Qué hace:** Genera **códigos de barras** en varios formatos (Code 128, EAN, etc.) como imágenes PNG o SVG directamente desde PHP, sin dependencias externas.
- **Dónde se usa:** Los tickets PDF incluyen un **código de barras único** por cada entrada comprada para simular un ticket de parque real.

---

### 5. `endroid/qr-code` `6.0`
- **Qué hace:** Genera **códigos QR** personalizables (color, tamaño, corrección de errores) en formato PNG. Los QRs generados llevan la URL de verificación del ticket o información del pedido.
- **Dónde se usa:** Junto a `dompdf`, cada ticket PDF incluye un **código QR** con los colores corporativos del proyecto (verde `#1a6e2e` sobre blanco).

---

## 🌐 APIs Externas

### 1. Nominatim (OpenStreetMap Geocoding API)
- **Endpoint:** `https://nominatim.openstreetmap.org`
- **Coste:** Gratuita (uso razonable)
- **Qué hace:** API de geocodificación: convierte nombres de ciudades/lugares en coordenadas y viceversa.
- **Usos concretos en el proyecto:**
  - **Auto-relleno de país**: cuando el usuario escribe su ciudad en el perfil, Nominatim devuelve el país y se rellena automáticamente.
  - **Geocodificación de parques**: el mapa usa Nominatim para obtener las coordenadas de cada parque y colocar su marcador. Los resultados se cachean en `localStorage`.
- **Dónde se usa:** `profile.js`, `map.js`, `trip_modals.js`.

---

### 2. Supabase Storage
- **Endpoint:** `https://ubtoaaawqdneblyvbelr.supabase.co`
- **Qué hace:** Servicio de **almacenamiento de archivos en la nube** (similar a AWS S3). Bucket para guardar las imágenes de perfil y las fotos de coasters.
- **Usos concretos:**
  - Los avatares se suben al bucket `avatars/` de Supabase desde el frontend.
  - Las fotos del módulo de galería de coasters se almacenan en Supabase.
- **Dónde se usa:** `profile.js`, módulo de fotos de coasters.

---

### 3. Firebase Authentication + Google OAuth + Facebook OAuth
- **Proveedor:** Google Firebase
- **Qué hace:** Gestiona todo el sistema de identidad y autenticación:
  - **Email/contraseña**: registro e inicio de sesión clásico.
  - **Google OAuth**: login con cuenta de Google en un paso.
  - **Facebook OAuth**: login con cuenta de Facebook.
  - Los tokens JWT generados por Firebase se envían al backend PHP, que los verifica en `SessionManager.php`.
- **Dónde se usa:** `auth.js`, `SessionManager.php`, todos los endpoints protegidos de la API.

---

## 🗄️ Base de Datos

### PostgreSQL (vía Supabase)
- **Versión:** PostgreSQL 15+ (gestionado por Supabase)
- **Qué hace:** Base de datos relacional principal del proyecto. Almacena todos los datos: usuarios, parques, coasters, viajes, tickets, reseñas, foros, rankings, fotos, notas, etc.
- **Conexión:** A través de la clase PHP propia `DBConexion` (wrapper sobre PDO).
- **Particularidades de sintaxis PostgreSQL usadas:**
  - `INSERT ... ON CONFLICT DO NOTHING` (en lugar de `INSERT IGNORE` de MySQL)
  - `RETURNING id` para obtener el ID del registro recién insertado en el mismo query
  - `unaccent()` para búsquedas sin tildes ni caracteres especiales
  - `COALESCE()` para valores por defecto en agregaciones

---

## 🧰 Utilidades PHP Propias (sin Composer)

Clases internas del proyecto ubicadas en `api/php/utils/`:

### `SessionManager.php`
Gestión centralizada de sesiones PHP. Verifica el token Firebase en cada petición, regenera IDs de sesión para prevenir session hijacking, y mapea el `firebase_uid` al `user_id` de la base de datos.

### `Response.php`
Helper para estandarizar las respuestas JSON de todos los endpoints de la API. Garantiza que todos los endpoints devuelvan siempre `{"success": true, "data": {...}}` o `{"success": false, "error": "..."}`.

### `RateLimiter.php`
Limitador de tasa de peticiones por IP. Previene abusos en endpoints críticos (login, registro, búsquedas, API de viajes). Configurable: número máximo de peticiones y ventana de tiempo.

### `Router.php`
Enrutador de URLs del proyecto. Genera rutas relativas y absolutas correctas independientemente del subdirectorio de despliegue. Expone `Router::redirect()`, `Router::asset()`, `Router::getBaseUrl()`.

### `DBConexion.php`
Wrapper PDO para PostgreSQL/Supabase. Gestiona la conexión a la base de datos, carga las credenciales desde el archivo `.env` del proyecto y expone un objeto PDO listo para usar en todos los endpoints.

---

## 🔧 Herramientas de Desarrollo

| Herramienta | Propósito |
|---|---|
| **XAMPP** | Servidor local Apache + PHP para desarrollo |
| **Composer** | Gestor de dependencias PHP |
| **Git** | Control de versiones del proyecto |
| **Supabase Dashboard** | Gestión de la BD PostgreSQL y el almacenamiento cloud |
| **Firebase Console** | Gestión de usuarios, configuración de Auth y proveedores OAuth |
| **Stripe Dashboard** | Gestión de pagos, productos y webhooks (modo test) |
| **Visual Studio Code** | Editor de código principal |

---

## 📦 Tabla Resumen

| Categoría | Librería / Herramienta | Versión |
|---|---|---|
| CSS Framework | Bootstrap | 5.3.3 |
| JS Utility | jQuery | 3.7.1 |
| Iconos | Font Awesome | 6.5.0 |
| Tipografía | Inter + Outfit (Google Fonts) | — |
| Autenticación | Firebase SDK (compat) | 10.14.1 |
| Auth OAuth | Google Sign-In / Facebook Login | — |
| Mapas | Leaflet.js | 1.9.4 |
| Clusters de mapa | Leaflet.markercluster | 1.5.3 |
| Selects mejorados | Choices.js | última |
| Datepicker | Flatpickr + locale ES | última |
| Drag & Drop | SortableJS | 1.15.2 |
| Gráficos | Chart.js | última |
| Calendario | FullCalendar | 6.1.11 |
| Recorte de imagen | Cropper.js | 1.6.2 |
| Pagos (frontend) | Stripe.js | v3 |
| Pagos (backend) | stripe/stripe-php | ^16 |
| Emails SMTP | phpmailer/phpmailer | 6.9 |
| Generación PDFs | dompdf/dompdf | ^3.1 |
| Códigos de barras | picqer/php-barcode-generator | ^3.2 |
| Códigos QR | endroid/qr-code | 6.0 |
| Geocodificación | Nominatim (OpenStreetMap) | API gratuita |
| Almacenamiento cloud | Supabase Storage | — |
| Base de datos | PostgreSQL (Supabase) | 15+ |
