/**
 * i18n.js — Motor de internacionalización de RollerCoaster World
 * ─────────────────────────────────────────────────────────────────
 * Uso:
 *   window.t('nav.home')          → string traducido
 *   window.rcwI18n.setLang('en')  → cambia idioma y reaplica traducción
 * ─────────────────────────────────────────────────────────────────
 */
(function () {
  "use strict";

  const SUPPORTED_LANGS = ["es", "en", "fr", "de"];
  const DEFAULT_LANG = "es";
  const COOKIE_NAME = "rcw_lang";
  const LANG_FLAGS = { es: "🇪🇸", en: "🇬🇧", fr: "🇫🇷", de: "🇩🇪" };
  const LANG_NAMES = { es: "Español", en: "English", fr: "Français", de: "Deutsch" };

  // Cache de traducciones cargadas
  const _cache = {};
  let _currentLang = DEFAULT_LANG;
  let _translations = {};

  /* ── Utilidades ────────────────────────────────────────────────── */

  function _getCookie(name) {
    const match = document.cookie.match(
      new RegExp("(?:^|; )" + name + "=([^;]*)")
    );
    return match ? decodeURIComponent(match[1]) : null;
  }

  function _setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      ";expires=" +
      d.toUTCString() +
      ";path=/;SameSite=Lax";
  }

  /**
   * Obtiene un valor anidado de un objeto usando notación de puntos.
   * Ej: _get({nav:{home:'Home'}}, 'nav.home') → 'Home'
   */
  function _get(obj, key) {
    return key.split(".").reduce(function (o, k) {
      return o && o[k] !== undefined ? o[k] : null;
    }, obj);
  }

  /* ── Carga del fichero de traducción ───────────────────────────── */

  function _loadLang(lang) {
    return new Promise(function (resolve, reject) {
      if (_cache[lang]) {
        resolve(_cache[lang]);
        return;
      }
      // Usar traducciones pre-cargadas inline por PHP (sin fetch, sin red)
      if (window._RCW_LANG_CACHE && window._RCW_LANG_CACHE[lang]) {
        _cache[lang] = window._RCW_LANG_CACHE[lang];
        resolve(_cache[lang]);
        return;
      }
      // Fallback: cargar desde URL (puede fallar en producción si el asset no se sirve bien)
      const base = window.BASE_URL || "";
      const url = base + "/web/lang/" + lang + ".json?v=" + Date.now();
      fetch(url)
        .then(function (r) {
          // Leer el body UNA SOLA VEZ con text() para evitar "body stream already read"
          return r.text().then(function (body) {
            if (!r.ok) {
              console.error(
                "[i18n] El servidor devolvió HTTP " + r.status + " para " + url +
                "\n→ Respuesta del servidor:\n" + body.slice(0, 300)
              );
              throw new Error("HTTP " + r.status + " — " + url);
            }
            try {
              return JSON.parse(body);
            } catch (jsonErr) {
              // El servidor respondió 200 pero con HTML en vez de JSON
              console.error(
                "[i18n] Respuesta 200 pero no es JSON válido para " + url +
                "\n→ Primeros 300 chars:\n" + body.slice(0, 300)
              );
              throw jsonErr;
            }
          });
        })
        .then(function (data) {
          _cache[lang] = data;
          resolve(data);
        })
        .catch(reject);
    });
  }

  /* ── Aplicar traducciones al DOM ───────────────────────────────── */

  function _applyToDOM(translations) {
    // Traducir todos los elementos con [data-i18n]
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      const key = el.getAttribute("data-i18n");
      const val = _get(translations, key);
      if (val !== null) {
        // Si tiene data-i18n-attr, traduce el atributo en vez del textContent
        const attr = el.getAttribute("data-i18n-attr");
        if (attr) {
          el.setAttribute(attr, val);
        } else {
          // Preservar elementos hijo (iconos FA, badges, etc.)
          // Solo actualizamos el texto, no destruimos children
          const textNode = _findOrCreateTextNode(el);
          if (textNode) {
            textNode.textContent = val;
          } else {
            el.textContent = val;
          }
        }
      }
    });

    // Traducir placeholders con [data-i18n-placeholder]
    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
      const key = el.getAttribute("data-i18n-placeholder");
      const val = _get(translations, key);
      if (val !== null) el.setAttribute("placeholder", val);
    });

    // Actualizar el selector de idioma activo
    _updateLangSwitcher();
  }

  /**
   * Busca el nodo de texto "principal" de un elemento (ignorando iconos FA).
   * Si no existe, lo crea al final del elemento.
   */
  function _findOrCreateTextNode(el) {
    // Busca un nodo de texto que no esté vacío
    for (let i = 0; i < el.childNodes.length; i++) {
      const node = el.childNodes[i];
      if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== "") {
        return node;
      }
    }
    // Si no hay nodo de texto, lo creamos
    const tn = document.createTextNode("");
    el.appendChild(tn);
    return tn;
  }

  /* ── Actualizar el selector visual en el dropdown ──────────────── */

  function _updateLangSwitcher() {
    const flagEl = document.getElementById("rcw-lang-flag");
    const labelEl = document.getElementById("rcw-lang-label");
    if (flagEl) flagEl.textContent = LANG_FLAGS[_currentLang] || "🌐";
    if (labelEl) labelEl.textContent = LANG_NAMES[_currentLang] || _currentLang.toUpperCase();

    // Marcar activo en los items del dropdown
    document.querySelectorAll("[data-lang-option]").forEach(function (el) {
      const lang = el.getAttribute("data-lang-option");
      if (lang === _currentLang) {
        el.classList.add("active", "fw-bold");
      } else {
        el.classList.remove("active", "fw-bold");
      }
    });
  }

  /* ── API pública ───────────────────────────────────────────────── */

  /**
   * Traduce una clave. Devuelve la clave si no se encuentra.
   * @param {string} key - Clave en notación de puntos (ej: 'nav.home')
   * @param {object} [vars] - Variables para interpolación (ej: {name: 'Carlos'} → :name)
   */
  function t(key, vars) {
    let val = _get(_translations, key);
    if (val === null) return key; // fallback: devolver la clave
    if (vars) {
      Object.keys(vars).forEach(function (k) {
        val = val.replace(new RegExp(":" + k, "g"), vars[k]);
      });
    }
    return val;
  }

  /**
   * Cambia el idioma activo, guarda en cookie y reaplica traducciones.
   * @param {string} lang - Código de idioma ('es', 'en', 'fr', 'de')
   */
  function setLang(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) lang = DEFAULT_LANG;
    _currentLang = lang;
    _setCookie(COOKIE_NAME, lang, 365);
    localStorage.setItem(COOKIE_NAME, lang);

    // Actualizar lang del HTML
    document.documentElement.lang = lang;

    _loadLang(lang)
      .then(function (data) {
        _translations = data;
        _applyToDOM(data);
        // Notificar a otros módulos que el idioma ha cambiado
        window.dispatchEvent(new CustomEvent('rcw:langchanged', { detail: { lang: lang } }));
      })
      .catch(function (err) {
        console.warn("[i18n] Error cargando idioma " + lang + ":", err);
      });
  }

  /**
   * Inicializa el motor: detecta idioma y aplica traducciones.
   * Se llama automáticamente al cargar el script.
   */
  function init() {
    // Prioridad: window.APP_LANG (PHP) > cookie > localStorage > navegador > defecto
    let lang =
      window.APP_LANG ||
      _getCookie(COOKIE_NAME) ||
      localStorage.getItem(COOKIE_NAME) ||
      (navigator.language || "").slice(0, 2) ||
      DEFAULT_LANG;

    if (!SUPPORTED_LANGS.includes(lang)) lang = DEFAULT_LANG;
    _currentLang = lang;

    // Escuchar clicks en el selector de idioma (delegación en document)
    document.addEventListener("click", function (e) {
      const btn = e.target.closest("[data-lang-option]");
      if (btn) {
        e.preventDefault();
        setLang(btn.getAttribute("data-lang-option"));
      }
    });

    // Cargar y aplicar
    _loadLang(lang)
      .then(function (data) {
        _translations = data;
        _applyToDOM(data);
        // Notificar también en el init inicial (igual que setLang)
        window.dispatchEvent(new CustomEvent('rcw:langchanged', { detail: { lang: lang } }));
      })
      .catch(function (err) {
        console.warn("[i18n] Error en init:", err);
      });
  }

  /* ── Exponer API global ────────────────────────────────────────── */
  window.t = t;
  window.rcwI18n = {
    setLang: setLang,
    getLang: function () { return _currentLang; },
    t: t,
    SUPPORTED_LANGS: SUPPORTED_LANGS,
    LANG_FLAGS: LANG_FLAGS,
    LANG_NAMES: LANG_NAMES,
    /**
     * Aplica las traducciones actuales a todos los [data-i18n] dentro de un contenedor.
     * Útil para contenido generado dinámicamente por JS.
     * @param {HTMLElement} container
     */
    applyToContainer: function (container) {
      if (!container || !_translations) return;
      container.querySelectorAll("[data-i18n]").forEach(function (el) {
        var key = el.getAttribute("data-i18n");
        var val = _get(_translations, key);
        if (val !== null) {
          var attr = el.getAttribute("data-i18n-attr");
          if (attr) {
            el.setAttribute(attr, val);
          } else {
            var tn = _findOrCreateTextNode(el);
            if (tn) tn.textContent = val;
          }
        }
      });
      container.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
        var key = el.getAttribute("data-i18n-placeholder");
        var val = _get(_translations, key);
        if (val !== null) el.setAttribute("placeholder", val);
      });
    }
  };

  /* ── Auto-init cuando el DOM está listo ────────────────────────── */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
