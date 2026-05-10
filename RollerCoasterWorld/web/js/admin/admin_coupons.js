const API_URL = window.ADMIN_COUPONS_API;

async function apiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  const r = await fetch(url, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, method: 'POST', body: fd, credentials: 'include' } );
  return r.json();
}
async function apiGet(url) {
  const r = await fetch(url, { credentials: 'include' });
  return r.json();
}

async function loadCoupons() {
  const res = await apiGet(API_URL + '?action=list');
  const tbody = document.getElementById('admin-coupons-tbody');
  const empty = document.getElementById('admin-coupons-empty');
  const data = res.data || [];

  if (data.length === 0) {
    tbody.innerHTML = '';
    empty.classList.remove('d-none');
    return;
  }
  
  empty.classList.add('d-none');
  tbody.innerHTML = data.map(c => `
    <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
      <td class="px-3 fw-bold font-monospace text-success">${c.code}</td>
      <td class="text-white">${c.description || '—'}</td>
      <td class="fw-semibold text-warning">${parseFloat(c.discount_value)}%</td>
      <td class="text-muted">${c.uses_count}</td>
      <td class="text-muted">${c.max_uses ? c.max_uses : '∞'}</td>
      <td class="text-muted">${c.expires_at ? new Date(c.expires_at).toLocaleDateString('es-ES') : '—'}</td>
      <td>
        <div class="form-check form-switch m-0">
          <input class="form-check-input btn-toggle-active" type="checkbox" data-id="${c.id}" ${c.active ? 'checked' : ''}>
        </div>
      </td>
      <td class="text-center">
        <button class="btn btn-outline-danger btn-sm rounded-0 btn-delete-coupon" data-id="${c.id}" title="Eliminar">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      </td>
    </tr>
  `).join('');

  // Listeners de toggle y delete
  tbody.querySelectorAll('.btn-toggle-active').forEach(btn => {
    btn.addEventListener('change', async (e) => {
      const active = e.target.checked;
      const res = await apiPost(API_URL + '?action=toggle', { id: btn.dataset.id, active: active });
      if (!res.success) {
        alert(res.error || 'Error al cambiar estado');
        e.target.checked = !active;
      }
    });
  });

  tbody.querySelectorAll('.btn-delete-coupon').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('¿Seguro que deseas eliminar este cupón?')) return;
      const res = await apiPost(API_URL + '?action=delete', { id: btn.dataset.id });
      if (res.success) {
        loadCoupons();
      } else {
        alert(res.error || 'Error al eliminar');
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('admin-coupons-tbody')) return;
  loadCoupons();

  document.getElementById('btn-save-coupon')?.addEventListener('click', async () => {
    const btn = document.getElementById('btn-save-coupon');
    btn.disabled = true;
    btn.innerHTML = 'Guardando...';

    const data = {
      code: document.getElementById('coupon-code').value,
      description: document.getElementById('coupon-desc').value,
      value: document.getElementById('coupon-value').value,
      max_uses: document.getElementById('coupon-max-uses').value,
      expires_at: document.getElementById('coupon-expires').value,
      active: document.getElementById('coupon-active').checked
    };

    const res = await apiPost(API_URL + '?action=create', data);
    btn.disabled = false;
    btn.innerHTML = 'Guardar';

    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('modal-coupon')).hide();
      document.getElementById('form-coupon').reset();
      loadCoupons();
    } else {
      alert(res.error || 'Error al crear cupón');
    }
  });
});
