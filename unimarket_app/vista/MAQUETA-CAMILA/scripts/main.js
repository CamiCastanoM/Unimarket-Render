/* ================================================
   main.js — UniMarket
   Universidad del Magdalena
   ================================================ */

const CATEGORY_ICON_MAP = {
  all: '▦',
  comida: '🍟',
  tecnologia: '💻',
  ropa: '👕',
  papeleria: '📝',
  otros: '📦'
};

function normalizeCategoryName(value = '') {
  return String(value)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
}

function getCategoryIcon(category = '') {
  const key = normalizeCategoryName(category);
  if (key.includes('comida')) return CATEGORY_ICON_MAP.comida;
  if (key.includes('papeleria')) return CATEGORY_ICON_MAP.papeleria;
  if (key.includes('tecnologia')) return CATEGORY_ICON_MAP.tecnologia;
  if (key.includes('ropa')) return CATEGORY_ICON_MAP.ropa;
  if (key.includes('otro')) return CATEGORY_ICON_MAP.otros;
  if (key === 'all' || key === 'todo') return CATEGORY_ICON_MAP.all;
  return CATEGORY_ICON_MAP.otros;
}

// ==================== VIEW SWITCHING ====================
function showView(name) {
  document.querySelectorAll('.view').forEach(view => {
    view.classList.remove('active');
    view.style.display = '';
  });

  let target = document.getElementById('view-' + name);
  if (!target && name === 'product') {
    target = document.getElementById('detail-view');
  }

  if (!target) {
    console.error('Vista no encontrada:', name);
    return;
  }

  target.classList.add('active');
  target.style.display = '';
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ==================== DATOS EN VISTA DETALLE ====================
function verProducto(eventOrElement, maybeCard = null) {
  if (eventOrElement && typeof eventOrElement.stopPropagation === 'function') {
    eventOrElement.stopPropagation();
  }

  let cardElement = maybeCard || eventOrElement;

  if (cardElement && cardElement.target) {
    cardElement = cardElement.target.closest('.product-card, .mini-card');
  }

  if (!cardElement || typeof cardElement.querySelector !== 'function') {
    console.error('No se encontró la card del producto');
    return;
  }

  const idProducto = cardElement.dataset.id || '';
  const idVendedor = cardElement.dataset.idVendedor || '';
  const ubicacion = cardElement.dataset.ubicacion || 'Campus Unimagdalena';
  const descripcion = cardElement.dataset.descripcion || 'Producto publicado en UniMarket.';

  const title = cardElement.querySelector('.card-title, .mini-title')?.textContent?.trim() || 'Producto';
  const img = cardElement.querySelector('.card-img, img');
  const imgSrc = img ? img.src : 'uploads/productos/default.png';
  const price = cardElement.querySelector('.card-price, .mini-price')?.textContent?.trim() || '$0';
  let seller = cardElement.querySelector('.card-seller, .mini-seller')?.textContent || 'Vendedor';
  seller = seller.replace(/🌟|📚|💻|📓/g, '').trim();

  const detailTitle = document.getElementById('detail-title');
  const detailPrice = document.getElementById('detail-price');
  const detailSeller = document.getElementById('detail-seller');
  const detailUbicacion = document.getElementById('detail-ubicacion');
  const detailDesc = document.getElementById('detail-descripcion');
  const breadcrumbName = document.getElementById('breadcrumb-product-name');
  const mainImg = document.getElementById('main-gallery-img');
  const detailView = document.getElementById('detail-view');

  if (detailTitle) detailTitle.textContent = title;
  if (detailPrice) detailPrice.textContent = price;
  if (detailSeller) detailSeller.textContent = seller;
  if (detailUbicacion) detailUbicacion.textContent = ubicacion;
  if (detailDesc) detailDesc.textContent = descripcion;
  if (breadcrumbName) breadcrumbName.textContent = title;
  if (mainImg) mainImg.src = imgSrc;
  if (detailView) detailView.dataset.id = idProducto;

  const detailFavBtn = document.getElementById('detail-fav-btn');
  const cardFavBtn = cardElement.querySelector('.fav-btn');
  if (detailFavBtn) {
    detailFavBtn.dataset.id = idProducto;
    if (cardFavBtn && cardFavBtn.classList.contains('active')) {
      detailFavBtn.classList.add('active');
      detailFavBtn.textContent = '❤️';
    } else {
      detailFavBtn.classList.remove('active');
      detailFavBtn.textContent = '🤍';
    }
  }

  const btnTienda = document.getElementById('detail-ver-tienda');
  if (btnTienda) btnTienda.dataset.id = idVendedor;

  const btnContactar = document.getElementById('detail-btn-contactar');
  if (btnContactar) btnContactar.dataset.id = idVendedor;

  const thumbs = document.querySelectorAll('.gallery-thumbs .thumb');
  if (thumbs[0]) {
    const thumbImg = thumbs[0].querySelector('img');
    if (thumbImg) thumbImg.src = imgSrc;
    thumbs[0].style.display = 'block';
  }
  if (thumbs[1]) thumbs[1].style.display = 'none';

  // Más opciones: usar TODOS los productos del home, no solo los visibles por el filtro actual.
  const miniGrid = document.getElementById('mini-productos-grid');
  if (miniGrid) {
    const todas = document.querySelectorAll('#home-grid .product-card');
    miniGrid.innerHTML = '';
    let agregados = 0;

    todas.forEach(card => {
      if (agregados >= 4 || card.dataset.id === idProducto) return;

      const clone = card.cloneNode(true);
      clone.style.display = '';
      clone.classList.remove('product-card');
      clone.classList.add('mini-card');
      clone.onclick = function (event) {
        verProducto(event, clone);
      };

      clone.querySelectorAll('.btn-cancel, .seller-action-btn, .delete-btn, .edit-btn, .btn-aprovechar').forEach(btn => {
        const texto = (btn.textContent || '').toLowerCase();
        if (texto.includes('editar') || texto.includes('eliminar')) {
          btn.remove();
        }
      });

      miniGrid.appendChild(clone);
      agregados++;
    });
  }

  showView('product');
}

// ==================== CATEGORY PILLS (FILTROS) ====================
function initCatPills() {
  document.querySelectorAll('.cat-pills').forEach(container => {
    container.querySelectorAll('.cat-pill').forEach(pill => {
      pill.addEventListener('click', function () {
        container.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');

        const targetCat = this.getAttribute('data-target');
        const view = this.closest('.view');

        if (view) {
          let count = 0;
          const cards = view.querySelectorAll('.product-card');

          cards.forEach(card => {
            const cardCat = card.getAttribute('data-cat');
            if (targetCat === 'all' || cardCat === targetCat) {
              card.style.display = 'block';
              count++;
              card.style.animation = 'none';
              setTimeout(() => card.style.animation = 'fadeIn 0.35s ease both', 10);
            } else {
              card.style.display = 'none';
            }
          });

          const countText = view.querySelector('#resultados-count');
          if (countText) countText.textContent = `Mostrando ${count} resultados`;
        }
      });
    });
  });
}

// ==================== MORE TABS (EXPANDIR OPCIONES) ====================
function initMoreTabs() {
  document.querySelectorAll('.more-tabs').forEach(tabBar => {
    tabBar.querySelectorAll('.more-tab').forEach(tab => {
      tab.addEventListener('click', function () {
        tabBar.querySelectorAll('.more-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');

        const targetCat = this.getAttribute('data-target');
        const grid = document.getElementById('mini-productos-grid');

        if (grid) {
          grid.querySelectorAll('.mini-card').forEach(card => {
            const cardCat = card.getAttribute('data-cat');
            if (targetCat === 'all' || cardCat === targetCat) {
              card.style.display = 'block';
            } else {
              card.style.display = 'none';
            }
          });
        }
      });
    });
  });
}

function switchProfileTab(btn, sectionId) {
  // 1. Quitar 'active' de todas los botones de pestaña del perfil
  const tabs = btn.parentElement.querySelectorAll('.more-tab');
  tabs.forEach(t => t.classList.remove('active'));

  // 2. Ocultar todas las secciones de contenido del perfil
  const sections = document.querySelectorAll('.profile-content-section');
  sections.forEach(s => s.style.display = 'none');

  // 3. Activar el botón clicado y mostrar su sección
  btn.classList.add('active');
  const target = document.getElementById(sectionId);
  if (target) {
    target.style.display = 'block';
    // Si activamos la pestaña de favoritos, refrescar el contenido vía AJAX
    if (sectionId === 'profile-fav') {
      refreshFavoritosView();
    }
  }
}

function refreshFavoritosView() {
  const grid = document.getElementById('profile-fav-grid');
  if (!grid) return;

  fetch('../../controlador/FavoritoController.php?accion=listar_html')
    .then(response => response.text())
    .then(html => {
      grid.innerHTML = html;
    });
}

function toggleFavorito(id_producto, btn) {
  const formData = new FormData();
  formData.append('id_producto', id_producto);

  fetch('../../controlador/FavoritoController.php?accion=toggle', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status !== 'success') {
        alert(data.message || 'Error al actualizar favoritos');
        if (data.message === 'Debes iniciar sesión') showView('auth');
        return;
      }

      const isNowActive = !btn.classList.contains('active');
      document.querySelectorAll(`[onclick*="toggleFavorito(${id_producto}"]`).forEach(heart => {
        heart.classList.toggle('active', isNowActive);
        heart.textContent = isNowActive ? '❤️' : '🤍';
      });

      const favGrid = document.getElementById('profile-fav-grid');
      if (favGrid && favGrid.offsetParent !== null && !isNowActive) {
        const card = btn.closest('.product-card');
        if (card) card.remove();
        if (!favGrid.querySelector('.product-card')) {
          favGrid.innerHTML = '<div style="padding:40px; text-align:center; color:var(--gray-500); width:100%;">No tienes favoritos guardados.</div>';
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Error de conexión al actualizar favoritos');
    });
}

// ==================== GALLERY THUMBS ====================
function changeImg(el, src) {

    document.getElementById('main-gallery-img').src = src;

    document.querySelectorAll('.thumb').forEach(t => {
        t.classList.remove('active');
    });

    el.classList.add('active');
}

function updateTimer() {
  // 1. Reloj general del sidebar (Solo estético para la maqueta)
  const sidebarHours = document.getElementById('hours');
  const sidebarMinutes = document.getElementById('minutes');
  const sidebarSeconds = document.getElementById('seconds');

  if (sidebarHours) {
    // Usamos la hora sincronizada del servidor
    const ahora = new Date(Date.now() + (window.serverOffset || 0));
    let endOfDay = new Date(ahora);
    endOfDay.setHours(23, 59, 59);
    let diff = endOfDay - ahora;
    let h = Math.floor(diff / 3600000);
    let m = Math.floor((diff % 3600000) / 60000);
    let s = Math.floor((diff % 60000) / 1000);
    sidebarHours.textContent = String(h).padStart(2, '0');
    sidebarMinutes.textContent = String(m).padStart(2, '0');
    sidebarSeconds.textContent = String(s).padStart(2, '0');
  }

  // 2. Contadores individuales de cada Venta Flash real
  document.querySelectorAll('.um-flash-countdown').forEach(el => {
    let finStr = el.getAttribute('data-fin');
    if (!finStr) return;
    finStr = finStr.replace(' ', 'T');
    const fin = new Date(finStr);
    const ahora = new Date(Date.now() + (window.serverOffset || 0));
    const diff = fin - ahora;
    if (diff <= 0) {
      el.textContent = '⏳ Tiempo finalizado';
      return;
    }
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const sec = Math.floor((diff % 60000) / 1000);
    let text = '⏳ ';
    if (d > 0) text += d + 'd ';
    text += h + 'h ' + m + 'm ' + sec + 's restantes';
    el.textContent = text;
  });

  document.querySelectorAll('.flash-card-horizontal').forEach(card => {
    let finStr = card.getAttribute('data-fin');
    if (!finStr) return;

    // Reemplazamos el espacio por 'T' para que sea compatible con todos los navegadores
    finStr = finStr.replace(" ", "T");
    const fin = new Date(finStr);
    const ahora = new Date(Date.now() + (window.serverOffset || 0));
    const diff = fin - ahora;

    const timerDisplay = card.querySelector('.timer-display');
    if (!timerDisplay) return;

    if (diff <= 0) {
      card.style.opacity = '0.5';
      timerDisplay.textContent = "Expirado";
      return;
    }

    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);

    let text = "";
    if (h > 0) text += h + "h ";
    text += m + "m " + s + "s";

    timerDisplay.textContent = text;
  });
}


function initCountdown() {
  updateTimer();
  setInterval(updateTimer, 1000);
}


// ==================== STORE FILTER ITEMS ====================
function initFilterItems() {
  document.querySelectorAll('.filter-item').forEach(item => {
    item.addEventListener('click', function () {
      const sidebar = this.closest('.store-sidebar');
      if (sidebar) {
        sidebar.querySelectorAll('.filter-item').forEach(fi => {
          fi.style.background = '';
          fi.style.color = '';
        });
      }
      this.style.background = 'var(--gray-100)';
      this.style.color = 'var(--primary)';
    });
  });
}

// ==================== MOBILE NAV ====================
function setMobileActive(el) {
  document.querySelectorAll('.mobile-nav li').forEach(li => li.classList.remove('active'));
  el.classList.add('active');
}

function toggleMobileMenu() {
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ==================== FAV BUTTON ====================
function initFavButtons() {
  // Los favoritos se manejan por toggleFavorito() y BD.
  // No alternamos aquí para evitar doble cambio visual.
}

// ==================== PUBLISH FORM PREVIEW (CON SUBIDA REAL) ====================
// ---SUBIR ARCHIVO REAL (MANEJADOR) ---
function handleImagePreview(input) {
  const file = input.files[0];
  const fileNameLabel = document.getElementById('file-name');
  const previewCont = document.getElementById('preview-img-cont');
  const sidePreviewCont = document.getElementById('prev-img-display');

  if (file) {
    const objectUrl = URL.createObjectURL(file);
    const imgHtml = `<img src="${objectUrl}" style="width:100%; height:100%; object-fit:cover; border-radius:var(--radius-sm);">`;
    
    // Actualizar nombre
    if (fileNameLabel) fileNameLabel.innerText = file.name;
    
    // Actualizar previsualización en el dropzone
    if (previewCont) previewCont.innerHTML = imgHtml;
    
    // Actualizar previsualización en la tarjeta lateral (Vista Previa)
    if (sidePreviewCont) {
      sidePreviewCont.innerHTML = imgHtml;
      sidePreviewCont.style.background = 'transparent';
    }
  }
}

function handleFlashImagePreview(input) {
  const file = input.files[0];
  const previewCont = document.getElementById('flash-preview-cont');

  if (file) {
    const objectUrl = URL.createObjectURL(file);
    if (previewCont) {
      previewCont.innerHTML = `<img src="${objectUrl}" style="width:100%; height:80px; object-fit:cover; border-radius:var(--radius-sm); margin-top:5px;">`;
    }
  }
}

function initPublishPreview() {
  const titleInput = document.getElementById('pub-title');
  const priceInput = document.getElementById('pub-price');
  const previewTitle = document.getElementById('prev-title');
  const previewPrice = document.getElementById('prev-price');

  // Actualizar Título
  if (titleInput && previewTitle) {
    titleInput.addEventListener('input', function () {
      previewTitle.textContent = this.value || 'Título del producto';
    });
  }

  // Actualizar Precio
  if (priceInput && previewPrice) {
    priceInput.addEventListener('input', function () {
      const val = parseInt(this.value, 10);
      previewPrice.textContent = val ? '$' + val.toLocaleString('es-CO') : '$0';
    });
  }
}

// ==================== PUBLISH STEPS ====================
function initPublishSteps() {
  const steps = document.querySelectorAll('.step-item');
  steps.forEach((step, i) => {
    step.addEventListener('click', function () {
      steps.forEach(s => s.classList.remove('active'));
      this.classList.add('active');
    });
  });
}

// ==================== BÚSQUEDA Y ORDENAMIENTO ====================
function initSearchAndSort() {
  const desktopSearch = document.querySelector('.navbar-search input'); // El de arriba
  const mobileSearch = document.getElementById('main-search-input');   // El nuevo de la vista search
  const sortSelect = document.querySelector('.sort-select');
  if (sortSelect) {

      sortSelect.addEventListener('change', function () {

          const grid =
              document.getElementById('home-grid');

          if (!grid) return;

          const cards =
              Array.from(grid.querySelectorAll('.product-card'));

          cards.sort((a, b) => {

              const precioA =
                  parseInt(
                      a.querySelector('.card-price')
                      .textContent
                      .replace(/\D/g, '')
                  );

              const precioB =
                  parseInt(
                      b.querySelector('.card-price')
                      .textContent
                      .replace(/\D/g, '')
                  );

              if (this.value === 'Menor precio') {
                  return precioA - precioB;
              }

              if (this.value === 'Mayor precio') {
                  return precioB - precioA;
              }

              return 0;

          });

          cards.forEach(card => grid.appendChild(card));

    });

  }

  // Función genérica de filtrado
  const performSearch = (term, gridId) => {
    const grid = document.getElementById(gridId) || document.querySelector('.products-grid');
    const cards = grid.querySelectorAll('.product-card');
    let count = 0;
    const cleanTerm = term.toLowerCase().trim();

    cards.forEach(card => {
      const title = card.querySelector('.card-title').textContent.toLowerCase();
      if (title.includes(cleanTerm)) {
        card.style.display = 'block';
        count++;
      } else {
        card.style.display = 'none';
      }
    });

    // Actualizar contadores
    const countDisplay = document.getElementById('search-count') || document.getElementById('resultados-count');
    if (countDisplay) {
      countDisplay.textContent = cleanTerm === "" ? "Escribe algo para buscar..." : `Mostrando ${count} resultados`;
    }
  };

  // Listener para el buscador de escritorio
  if (desktopSearch) {
    desktopSearch.addEventListener('input', (e) => performSearch(e.target.value, 'view-home'));
  }

  // Listener para el buscador de la nueva vista (Móvil)
  if (mobileSearch) {
    mobileSearch.addEventListener('input', (e) => performSearch(e.target.value, 'search-grid'));
  }
}


// ==================== AUTH SWITCH ====================
function initAuthSwitch() {
  const signUpButton = document.getElementById('signUpBtn');
  const signInButton = document.getElementById('signInBtn');
  const authContainer = document.getElementById('auth-container');

  if (!authContainer) return;

  if (signUpButton) {
    signUpButton.addEventListener('click', (e) => {
      e.preventDefault();
      authContainer.classList.add('right-panel-active');
    });
  }

  if (signInButton) {
    signInButton.addEventListener('click', (e) => {
      e.preventDefault();
      authContainer.classList.remove('right-panel-active');
    });
  }
}

function initInitialProfileTab() {
  if (!window.initialProfileTab) return;
  const map = {
    flash: 'profile-flash',
    pedidos: 'profile-pedidos',
    favoritos: 'profile-fav',
    productos: 'profile-pub'
  };
  const sectionId = map[window.initialProfileTab] || window.initialProfileTab;
  const tab = Array.from(document.querySelectorAll('.more-tab')).find(btn => (btn.getAttribute('onclick') || '').includes(sectionId));
  if (tab && document.getElementById(sectionId)) {
    switchProfileTab(tab, sectionId);
  }
}

function initPedidoEstadoForms() {
  document.querySelectorAll('.pedido-estado-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      const estado = form.querySelector('[name="estado"]')?.value || '';
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Guardando...';
      }

      fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      })
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            alert(data.message || 'No se pudo actualizar el estado');
            return;
          }
          const card = form.closest('.seller-order-card') || form.parentElement;
          const badge = card ? card.querySelector('.pedido-estado-badge') : null;
          if (badge) badge.textContent = 'Estado: ' + (data.estado || estado);
          if (btn) btn.textContent = 'Guardado ✓';
          setTimeout(() => {
            if (btn) btn.textContent = 'Guardar';
          }, 1200);
        })
        .catch(err => {
          console.error(err);
          alert('Error de conexión al actualizar estado');
        })
        .finally(() => {
          if (btn) btn.disabled = false;
        });
    });
  });
}

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
  initCatPills();
  initCountdown();
  initMoreTabs();
  initFilterItems();
  initFavButtons();
  initPublishPreview();
  initPublishSteps();
  initAuthSwitch();
  initPedidoEstadoForms();
  initInitialProfileTab();

  // ¡Aquí está la llamada correcta para que funcione el buscador!
  initSearchAndSort();
});

// ==================== FUNCIONES VENTA FLASH ====================

// Abre el modal de publicación rápida para estudiantes
function openFlashModal() {
  const modal = document.getElementById('flash-modal');
  if (modal) {
    modal.style.display = 'flex';
  }
}

// Cierra el modal de venta flash
function closeFlashModal() {
  const modal = document.getElementById('flash-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

function showFlashPublish() {
  openFlashModal();
}

// Cambia el texto visual cuando el estudiante selecciona una foto (Cámara o Galería)
function handleFlashPhoto(input) {
  const text = document.getElementById('flash-upload-text');
  if (input.files && input.files[0] && text) {
    text.innerHTML = "✅ ¡Foto cargada con éxito!";
    text.style.color = "var(--green)";
  }
}

// Simula la publicación de la oferta efímera en el campus
function publishFlashNow() {
  const priceInput = document.getElementById('flash-price-input');
  const locationInput = document.getElementById('flash-location-input');

  if (!priceInput || !priceInput.value) {
    alert("Por favor, ponle un precio a tu oferta.");
    return;
  }

  const price = priceInput.value;
  const location = locationInput ? locationInput.value : "el campus";

  // Simulación de éxito para la maqueta
  alert(`⚡ ¡Publicado! Tu oferta de $${price} en ${location} estará activa por 2 horas.`);

  //Limpieza y redirección
  closeFlashModal();
  if (typeof showView === 'function') {
    showView('flash');
  }
}

// ==================== NOTIFICACIONES Y MENSAJERÍA ====================


function toggleNotificacionesDropdown(force) {
  const dropdown = document.getElementById('notificaciones-dropdown');
  if (!dropdown) return;
  if (typeof force === 'boolean') {
    dropdown.style.display = force ? 'block' : 'none';
  } else {
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  }
  if (dropdown.style.display === 'block') fetchNotificaciones('Todas');
}

function renderNotificaciones(lista, targetId, compact = true) {
  const container = document.getElementById(targetId);
  if (!container) return;

  if (!lista || lista.length === 0) {
    container.innerHTML = '<p class="noti-empty">No hay notificaciones para mostrar</p>';
    return;
  }

  container.innerHTML = lista.map(n => {
    const id = n.id_notificacion || '';
    const titulo = n.titulo || n.tipo || 'Notificación';
    const tipo = n.tipo || 'Sistema';
    const leida = String(n.leida || '0') === '1';
    const fecha = n.fecha ? new Date(n.fecha.replace(' ', 'T')).toLocaleString('es-CO') : '';
    const url = (n.url || '').replace(/'/g, "\\'");

    if (!compact) {
      return `
        <article class="noti-page-item ${leida ? '' : 'unread'}" data-tipo="${tipo}" data-id="${id}">
          <div class="noti-page-icon">${leida ? '⚪' : '🔵'}</div>
          <div>
            <strong>${titulo}</strong>
            <p>${n.mensaje || ''}</p>
            <span>${tipo} · ${fecha}</span>
          </div>
          <div class="noti-page-actions">
            ${url ? `<button type="button" onclick="handleNotificacionClick(${id}, '${url}')">Abrir</button>` : ''}
            <button type="button" onclick="marcarNotificacionLeida(${id})">Leída</button>
            <button type="button" onclick="eliminarNotificacion(${id})">Eliminar</button>
          </div>
        </article>`;
    }

    return `
      <div class="noti-item ${leida ? '' : 'unread'}">
        <button type="button" onclick="handleNotificacionClick(${id}, '${url}')">
          <strong>${titulo}</strong>
          <span>${n.mensaje || ''}</span>
          <small>${tipo} · ${fecha}</small>
        </button>
        <button type="button" class="noti-delete" onclick="event.stopPropagation(); eliminarNotificacion(${id})">×</button>
      </div>`;
  }).join('');
}

function actualizarBadgeNotificaciones(unread) {
  const badge = document.getElementById('noti-badge');
  if (!badge) return;
  const total = parseInt(unread || 0, 10);
  badge.textContent = total;
  badge.style.display = total > 0 ? 'inline-flex' : 'none';
}

function fetchNotificaciones(tipo = 'Todas') {
  fetch('../../controlador/NotificacionController.php?accion=listar_todas&tipo=' + encodeURIComponent(tipo))
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        renderNotificaciones(data.data || [], 'notificaciones-lista', true);
        renderNotificaciones(data.data || [], 'notificaciones-pagina-lista', false);
        actualizarBadgeNotificaciones(data.unread || 0);
      }
    })
    .catch(() => {});
}

function filtrarNotificaciones(tipo, btn, pageOnly = false) {
  const scope = pageOnly ? '.page-filters' : '.noti-filters';
  const container = btn ? btn.closest(scope) || btn.parentElement : null;
  if (container) {
    container.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
  }
  fetchNotificaciones(tipo);
}

function handleNotificacionClick(id, url) {
  fetch('../../controlador/NotificacionController.php?accion=marcar_leida', {
    method: 'POST',
    body: JSON.stringify({ id_notificacion: id })
  }).then(() => {
    fetchNotificaciones();
    if (url && url.startsWith('chat/')) {
      iniciarChatVendedor(url.split('/')[1]);
      return;
    }
    if (url && url.startsWith('pedido/')) {
       const idPedido = encodeURIComponent(url.split('/')[1]);
       window.location.href =
           'index.php?view=order-detail&id=' + idPedido;
   }
  });
}

function marcarNotificacionLeida(id) {
  fetch('../../controlador/NotificacionController.php?accion=marcar_leida', {
    method: 'POST',
    body: JSON.stringify({ id_notificacion: id })
  }).then(() => fetchNotificaciones());
}

function marcarTodasNotificaciones() {
  fetch('../../controlador/NotificacionController.php?accion=marcar_todas', { method: 'POST' })
    .then(() => fetchNotificaciones());
}

function eliminarNotificacion(id) {
  fetch('../../controlador/NotificacionController.php?accion=eliminar', {
    method: 'POST',
    body: JSON.stringify({ id_notificacion: id })
  }).then(() => fetchNotificaciones());
}

document.addEventListener('DOMContentLoaded', () => {
  fetchNotificaciones();
  setInterval(() => fetchNotificaciones(), 30000);
});

let chatInterval = null;

function fetchConversacion(id_destinatario) {
    if (!id_destinatario) return;
    
    fetch(`../../controlador/MensajeController.php?accion=listar&id_destinatario=${id_destinatario}`)
      .then(async r => {
          const text = await r.text();
          try {
              return JSON.parse(text);
          } catch(e) {
              console.error("Mensaje fetch json error:", text);
              return {status: 'error', message: 'Error de respuesta del servidor'};
          }
      })
      .then(data => {
         const container = document.getElementById('mensajes-lista');
         if (data.status === 'success') {
             container.innerHTML = '';
             if(data.data.length === 0) {
                 container.innerHTML = '<p style="text-align: center; color: #9ca3af; margin-top: 50px;">No hay mensajes previos. ¡Comienza saludando!</p>';
             } else {
                 data.data.forEach(m => {
                     const isMiMensaje = m.id_destinatario == id_destinatario;
                     container.innerHTML += `
                     <div style="margin-bottom: 10px; text-align: ${isMiMensaje ? 'right' : 'left'};">
                         <div style="display:inline-block; max-width: 70%; padding: 10px; border-radius: 8px; background: ${isMiMensaje ? 'var(--primary)' : '#e5e7eb'}; color: ${isMiMensaje ? 'white' : 'black'}; text-align: left;">
                             ${m.contenido}
                         </div>
                         <div style="font-size: 0.7rem; color: var(--gray-400); margin-top: 2px;">${new Date(m.fecha_envio).toLocaleString()}</div>
                     </div>`;
                 });
                 // auto scroll to bottom
                 container.scrollTop = container.scrollHeight;
             }
         } else {
             container.innerHTML = `<p style="text-align: center; color: var(--red); margin-top: 50px;">Error: ${data.message}</p>`;
         }
      })
      .catch(e => console.error("Network error on fetchConversacion:", e));
      
    if(chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(() => {
        fetchConversacion(document.getElementById('chat-user-select').value);
    }, 5000);
}

function enviarMensaje() {
    const id_destinatario = document.getElementById('chat-user-select').value;
    const input = document.getElementById('mensaje-input');
    const contenido = input.value;
    
    if(!id_destinatario || !contenido.trim()) return;
    
    const formData = new FormData();
    formData.append('id_destinatario', id_destinatario);
    formData.append('contenido', contenido);
    
    fetch('../../controlador/MensajeController.php?accion=enviar', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            input.value = '';
            fetchConversacion(id_destinatario);
        } else {
            alert(data.message);
        }
    });
}

function iniciarChatVendedor(idVendedor){

    if(!idVendedor){
        alert('Vendedor inválido');
        return;
    }

    fetch(
        '../../controlador/UsuarioController.php?accion=obtenerTelefono&id=' + idVendedor
    )
    .then(response => response.json())
    .then(data => {

        if(data.telefono){

            const mensaje =
                encodeURIComponent(
                    'Hola, vi tu producto en UniMarket.'
                );

            window.open(
                `https://wa.me/57${data.telefono}?text=${mensaje}`,
                '_blank'
            );

        }else{

            alert('El vendedor no tiene teléfono registrado');
        }
    })
    .catch(error => {

        console.error(error);

        alert('Error al contactar vendedor');
    });
}


// ==================== REPORTES ====================
function openReportModal(idProducto) {
  const modal = document.getElementById('report-modal');
  const input = document.getElementById('report-product-id');
  if (input) input.value = idProducto || '';
  if (modal) modal.style.display = 'flex';
}

function closeReportModal() {
  const modal = document.getElementById('report-modal');
  if (modal) modal.style.display = 'none';
}

// ==================== CRUD FLASH ====================

function editarFlash(idFlash){

    window.location.href =
        "editar_venta_flash.php?id=" + idFlash + "&returnTab=flash";

}


function reactivarFlash(idFlash){

    if(confirm("¿Volver a publicar esta venta flash hasta finalizar el día?")){

        window.location.href =
            "../../controlador/FlashController.php?accion=reactivar&id=" + idFlash;

    }

}

function eliminarFlash(idFlash){

    if(confirm("¿Eliminar esta venta flash?")){

        window.location.href =
            "../../controlador/FlashController.php?accion=eliminar&id=" + idFlash;

    }

}

// ==================== DETALLE COMPRA ====================

function verDetalleCompra(idVenta){

    window.location.href =
        "detalle_compra.php?id=" + idVenta;

}


// ==================== CRUD PRODUCTOS ====================

function editarProducto(idProducto, categoria = ''){

    if(!idProducto){
        alert("Producto no válido");
        return;
    }

    window.location.href =
        "index.php?view=publish&editar=" + idProducto;

}

function eliminarProducto(idProducto){

    if(!idProducto){
        alert("Producto no válido");
        return;
    }

    if(confirm("¿Eliminar producto?")){

        window.location.href =
            "/unimarket_app/controlador/ProductoController.php?accion=eliminar&id=" + idProducto;

    }

}


// ==================== CARGA DE VISTAS ====================

window.addEventListener('DOMContentLoaded', () => {

    const params = new URLSearchParams(window.location.search);

    const view = params.get('view');

    // verificar login real
    const isLogged =
        document.body.dataset.logged === "1";

    if (view && document.getElementById('view-' + view)) {

        // bloquear perfil sin sesión
        if(view === 'profile' && !isLogged){

            showView('home');
            return;

        }

        setTimeout(() => {

            showView(view);

        }, 100);

    }

});

function verTienda(idVendedor){

    if(!idVendedor) return;

    fetch(`../../controlador/ProductoController.php?accion=listar_por_usuario&id_usuario=${idVendedor}`)

    .then(r => r.json())

    .then(res => {

        if(
            res.status !== 'success' ||
            !res.data ||
            !res.data.length
        ){

            alert('Esta tienda no tiene productos');

            return;
        }

        const productos = res.data;

        const vendedor =
            productos[0].vendedor || 'Tienda';

        /*
        =====================================
        DATOS TIENDA
        =====================================
        */

        document.getElementById('breadcrumb-store-name')
            .textContent = vendedor;

        document.getElementById('store-section-title')
            .textContent =
            `Productos de ${vendedor}`;

        document.getElementById('store-name')
            .textContent = vendedor;

        document.getElementById('store-desc')
            .textContent =
            productos[0].descripcion_tienda ||
            `Productos publicados por ${vendedor}`;

        /*
        =====================================
        LOGO
        =====================================
        */

        const logoImg =
            document.getElementById('store-logo-img');

        if(logoImg){

            logoImg.src =
                productos[0].logo
                ? `uploads/tiendas/${productos[0].logo}`
                : 'uploads/tiendas/default-logo.png';

        }

        /*
        =====================================
        BANNER
        =====================================
        */

        const bannerImg =
            document.getElementById('store-banner-img');

        if(bannerImg){

            bannerImg.src =
                productos[0].banner
                ? `uploads/tiendas/${productos[0].banner}`
                : 'uploads/tiendas/default-banner.jpg';

        }

        /*
        =====================================
        GRID PRODUCTOS
        =====================================
        */

        const grid =
            document.getElementById('store-grid');

        grid.innerHTML = '';

        /*
        =====================================
        CATEGORÍAS DINÁMICAS
        =====================================
        */

        const categorias = [

            ...new Set(

                productos.map(p =>

                    (p.categoria_nombre || 'Otros')
                    .trim()

                )

            )

        ];

        /*
        =====================================
        PILLS
        =====================================
        */

        const pills =
            document.getElementById('store-pills');

        pills.innerHTML = `

            <button class="cat-pill active"
                    data-target="all">

                🛒 Todo

            </button>

        `;

        categorias.forEach(cat => {

            pills.innerHTML += `

                <button class="cat-pill"
                        data-target="${cat.toLowerCase()}">

                    ${cat}

                </button>

            `;

        });

        /*
        =====================================
        SIDEBAR FILTROS
        =====================================
        */

        const sidebar =
            document.getElementById('store-filters');

        sidebar.innerHTML = '';

        categorias.forEach(cat => {

            sidebar.innerHTML += `

                <div class="filter-item"
                     data-target="${cat.toLowerCase()}">

                    <div class="fi-icon">${getCategoryIcon(cat)}</div>

                    <span>${cat}</span>

                </div>

            `;

        });

        /*
        =====================================
        RENDER PRODUCTOS
        =====================================
        */

        productos.forEach(p => {

            const categoria =

                (p.categoria_nombre || 'Otros')
                .toLowerCase()
                .trim();

            const img =

                p.imagen
                ? `uploads/productos/${p.imagen}`
                : 'uploads/productos/default.png';

            grid.innerHTML += `

            <div class="product-card"

                 data-id="${p.id_producto}"
                 data-id-vendedor="${idVendedor}"
                 data-vendedor="${p.vendedor || vendedor}"
                 data-precio="${p.precio}"
                 data-imagen="${img}"
                 data-descripcion="${p.descripcion || ''}"
                 data-ubicacion="${p.ubicacion || 'Sin ubicación'}"
                 data-cat="${categoria}"

                 onclick="verProducto(event,this)">

                <img class="card-img"
                     src="${img}"
                     alt="${p.nombre}">

                <div class="card-body">

                    <div class="card-title">

                        ${p.nombre}

                    </div>

                    <div class="card-price">

                        $${new Intl.NumberFormat('es-CO')
                            .format(p.precio)}

                    </div>

                    <div class="card-seller">

                        🏪 ${vendedor}

                    </div>

                    <button class="btn-aprovechar"

                            onclick="
                                event.stopPropagation();

                                verProducto(
                                    event,
                                    this.closest('.product-card')
                                );
                            ">

                        Ver producto

                    </button>

                </div>

            </div>

            `;

        });

        /*
        =====================================
        FILTROS
        =====================================
        */

        function aplicarFiltro(target){

            document.querySelectorAll(
                '#store-grid .product-card'
            )

            .forEach(card => {

                const cat =
                    card.dataset.cat;

                if(
                    target === 'all' ||
                    cat === target
                ){

                    card.style.display = 'block';

                }else{

                    card.style.display = 'none';

                }

            });

        }

        // pills
        document.querySelectorAll('.cat-pill')

        .forEach(btn => {

            btn.addEventListener('click', function(){

                document.querySelectorAll('.cat-pill')
                    .forEach(b =>
                        b.classList.remove('active')
                    );

                this.classList.add('active');

                aplicarFiltro(
                    this.dataset.target
                );

            });

        });

        // sidebar
        document.querySelectorAll('.filter-item')

        .forEach(btn => {

            btn.addEventListener('click', function(){

                document.querySelectorAll('.filter-item')
                    .forEach(b =>
                        b.classList.remove('active-filter')
                    );

                this.classList.add('active-filter');

                aplicarFiltro(
                    this.dataset.target
                );

            });

        });

        /*
        =====================================
        CONTACTAR TIENDA
        =====================================
        */

        const btnContact =
            document.getElementById('btn-contact-store');

        if(btnContact){

            btnContact.onclick = () => {

                if(!productos[0].telefono){

                    alert(
                        'El vendedor no tiene teléfono registrado'
                    );

                    return;
                }

                window.open(

                    `https://wa.me/57${productos[0].telefono}`,

                    '_blank'

                );

            };

        }

        /*
        =====================================
        MOSTRAR VISTA
        =====================================
        */

        showView('store');

    })

    .catch(err => {

        console.error(err);

        alert('Error cargando tienda');

    });

}

function agregarAlCarrito(idProducto){

    fetch(
        '../../controlador/CarritoController.php?accion=agregar',
        {
            method:'POST',
            headers:{
                'Content-Type':'application/x-www-form-urlencoded'
            },
            body:'id_producto=' + idProducto
        }
    )
    .then(response => response.json().catch(() => ({success:true})))
    .then((data) => {

        if (data && data.success === false) {
            alert(data.message || 'No se pudo agregar al carrito');
            if (data.message === 'Debes iniciar sesión') showView('auth');
            return;
        }

        alert('Producto agregado al carrito');

        /*
        RECARGAR SOLO EL CARRITO
        */

        fetch(window.location.href)
        .then(res => res.text())
        .then(html => {

            const parser =
                new DOMParser();

            const doc =
                parser.parseFromString(
                    html,
                    'text/html'
                );

            const nuevoCarrito =
                doc.getElementById('view-cart');

            if(nuevoCarrito){

                document.getElementById('view-cart')
                    .innerHTML = nuevoCarrito.innerHTML;
            }

            showView('cart');

        });

    });

}

function finalizarCompra(){

    showView('checkout');
}

function cambiarCantidad(idCarrito, cambio) {
  const row = document.querySelector(`[data-cart-id="${idCarrito}"]`);
  const cantidadEl = row?.querySelector('.cart-cantidad');
  const subtotalEl = row?.querySelector('.cart-subtotal');

  const cantidadActual = parseInt(cantidadEl?.textContent || '1', 10);
  const nuevaCantidad = cantidadActual + cambio;

  if (nuevaCantidad < 1) return;

  fetch('../../controlador/CarritoController.php?accion=actualizarCantidad', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id_carrito=' + encodeURIComponent(idCarrito) + '&cantidad=' + encodeURIComponent(nuevaCantidad)
  })
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert(data.message || 'Error al actualizar cantidad');
        return;
      }

      if (cantidadEl) cantidadEl.textContent = nuevaCantidad;

      if (subtotalEl) {
        const precio = parseFloat(subtotalEl.dataset.precio || '0');
        subtotalEl.textContent = 'Subtotal: $' + new Intl.NumberFormat('es-CO').format(precio * nuevaCantidad);
      }

      let total = 0;
      document.querySelectorAll('.cart-subtotal').forEach(el => {
        const itemRow = el.closest('[data-cart-id]');
        const cant = parseInt(itemRow?.querySelector('.cart-cantidad')?.textContent || '0', 10);
        const precio = parseFloat(el.dataset.precio || '0');
        total += cant * precio;
      });

      const totalEl = document.getElementById('cart-total-value');
      if (totalEl) totalEl.textContent = '$' + new Intl.NumberFormat('es-CO').format(total);

      // Actualizar checkout en segundo plano sin sacar al usuario del carrito
      fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
          const doc = new DOMParser().parseFromString(html, 'text/html');
          const nuevoCheckout = doc.getElementById('view-checkout');
          if (nuevoCheckout && document.getElementById('view-checkout')) {
            document.getElementById('view-checkout').innerHTML = nuevoCheckout.innerHTML;
          }
        })
        .catch(() => {});

      showView('cart');
    })
    .catch(() => alert('Error al actualizar cantidad'));
}



/* =========================================
   FUNCIONES GLOBALES PARA ONCLICK HTML
   ========================================= */
window.showView = showView;
window.verProducto = verProducto;
window.verTienda = typeof verTienda !== 'undefined' ? verTienda : window.verTienda;
window.cambiarCantidad = typeof cambiarCantidad !== 'undefined' ? cambiarCantidad : window.cambiarCantidad;
window.agregarAlCarrito = typeof agregarAlCarrito !== 'undefined' ? agregarAlCarrito : window.agregarAlCarrito;
window.finalizarCompra = typeof finalizarCompra !== 'undefined' ? finalizarCompra : window.finalizarCompra;
window.editarProducto = typeof editarProducto !== 'undefined' ? editarProducto : window.editarProducto;
window.eliminarProducto = typeof eliminarProducto !== 'undefined' ? eliminarProducto : window.eliminarProducto;
window.openFlashModal = typeof openFlashModal !== 'undefined' ? openFlashModal : window.openFlashModal;
window.closeFlashModal = typeof closeFlashModal !== 'undefined' ? closeFlashModal : window.closeFlashModal;
window.showFlashPublish = typeof showFlashPublish !== 'undefined' ? showFlashPublish : window.showFlashPublish;
window.toggleMobileMenu = typeof toggleMobileMenu !== 'undefined' ? toggleMobileMenu : window.toggleMobileMenu;
window.toggleFavorito = typeof toggleFavorito !== 'undefined' ? toggleFavorito : window.toggleFavorito;
window.switchProfileTab = typeof switchProfileTab !== 'undefined' ? switchProfileTab : window.switchProfileTab;
window.verDetalleCompra = typeof verDetalleCompra !== 'undefined' ? verDetalleCompra : window.verDetalleCompra;
window.reactivarFlash = typeof reactivarFlash !== 'undefined' ? reactivarFlash : window.reactivarFlash;
window.openReportModal = typeof openReportModal !== 'undefined' ? openReportModal : window.openReportModal;
window.closeReportModal = typeof closeReportModal !== 'undefined' ? closeReportModal : window.closeReportModal;
window.iniciarChatVendedor = typeof iniciarChatVendedor !== 'undefined' ? iniciarChatVendedor : window.iniciarChatVendedor;
window.handleImagePreview = typeof handleImagePreview !== 'undefined' ? handleImagePreview : window.handleImagePreview;
window.handleFlashImagePreview = typeof handleFlashImagePreview !== 'undefined' ? handleFlashImagePreview : window.handleFlashImagePreview;
window.toggleNotificacionesDropdown = typeof toggleNotificacionesDropdown !== 'undefined' ? toggleNotificacionesDropdown : window.toggleNotificacionesDropdown;
window.filtrarNotificaciones = typeof filtrarNotificaciones !== 'undefined' ? filtrarNotificaciones : window.filtrarNotificaciones;
window.marcarTodasNotificaciones = typeof marcarTodasNotificaciones !== 'undefined' ? marcarTodasNotificaciones : window.marcarTodasNotificaciones;
window.eliminarNotificacion = typeof eliminarNotificacion !== 'undefined' ? eliminarNotificacion : window.eliminarNotificacion;
window.marcarNotificacionLeida = typeof marcarNotificacionLeida !== 'undefined' ? marcarNotificacionLeida : window.marcarNotificacionLeida;

console.log('UniMarket main.js listo');
