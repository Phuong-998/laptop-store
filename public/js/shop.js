/* ============================================================
   shop.js — tương tác storefront (giỏ hàng, toast, số lượng)
   ============================================================ */
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function toast(message, type = 'success') {
    const wrap = document.getElementById('toastWrap');
    if (!wrap) return;
    const el = document.createElement('div');
    el.className = 'toast ' + (type === 'error' ? 'error' : '');
    el.innerHTML = `<i class="bi ${type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'}"></i> ${message}`;
    wrap.appendChild(el);
    setTimeout(() => {
      el.style.transition = 'opacity .25s, transform .25s';
      el.style.opacity = '0';
      el.style.transform = 'translateX(20px)';
      setTimeout(() => el.remove(), 260);
    }, 2600);
  }
  window.shopToast = toast;

  function updateBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? '' : 'none';
  }

  /* Thêm vào giỏ (AJAX) — mọi nút có [data-add-to-cart] với data-id, data-qty tùy chọn */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-add-to-cart]');
    if (!btn) return;
    e.preventDefault();

    const productId = btn.getAttribute('data-id');
    let qty = 1;
    const qtyInput = btn.getAttribute('data-qty-input');
    if (qtyInput) {
      const el = document.querySelector(qtyInput);
      if (el) qty = Math.max(1, parseInt(el.value) || 1);
    }

    btn.disabled = true;
    fetch(window.SHOP_ROUTES.cartAdd, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ product_id: Number(productId), quantity: qty })
    })
      .then(async r => {
        const data = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(data.message || 'Không thể thêm vào giỏ');
        updateBadge(data.cart_count);
        toast(data.message || 'Đã thêm vào giỏ hàng');
      })
      .catch(err => toast(err.message, 'error'))
      .finally(() => { btn.disabled = false; });
  });

  /* Bộ tăng giảm số lượng */
  document.addEventListener('click', function (e) {
    const dec = e.target.closest('[data-qty-dec]');
    const inc = e.target.closest('[data-qty-inc]');
    if (!dec && !inc) return;
    const box = (dec || inc).closest('.qty-box');
    const input = box.querySelector('input');
    let v = parseInt(input.value) || 1;
    const max = parseInt(input.getAttribute('max')) || 9999;
    v = dec ? Math.max(1, v - 1) : Math.min(max, v + 1);
    input.value = v;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* Ẩn nút cuộn khi rail không tràn (ít sản phẩm) */
  function syncRailButtons(scope) {
    (scope || document).querySelectorAll('.rail-wrap').forEach(w => {
      const rail = w.querySelector('.rail');
      if (!rail) return;
      const overflow = rail.scrollWidth > rail.clientWidth + 4;
      w.querySelectorAll('.rail-btn').forEach(b => { b.style.display = overflow ? '' : 'none'; });
    });
  }
  window.addEventListener('load', () => syncRailButtons());
  window.addEventListener('resize', () => syncRailButtons());

  /* Chuyển tab trong block bán chạy */
  document.addEventListener('click', function (e) {
    const tab = e.target.closest('[data-tabblock] .block-tabs .tab');
    if (!tab || !tab.dataset.tab) return;
    const block = tab.closest('[data-tabblock]');
    block.querySelectorAll('.block-tabs .tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    block.querySelectorAll('.rail-pane').forEach(p => {
      p.hidden = p.dataset.pane !== tab.dataset.tab;
    });
    syncRailButtons(block);
  });

  /* Mega menu: đổi panel theo danh mục đang rê chuột */
  document.addEventListener('mouseover', function (e) {
    const link = e.target.closest('.mega-cats a[data-cat]');
    if (!link) return;
    const mega = link.closest('.mega');
    if (!mega) return;
    const cat = link.getAttribute('data-cat');
    mega.querySelectorAll('.mega-cats a[data-cat]').forEach(a => a.classList.toggle('active', a === link));
    mega.querySelectorAll('.mega-sub').forEach(p => p.classList.toggle('active', p.getAttribute('data-cat') === cat));
  });

  /* Nút cuộn carousel */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.rail-btn');
    if (!btn) return;
    const rail = btn.closest('.rail-wrap').querySelector('.rail');
    if (!rail) return;
    const amount = rail.clientWidth * 0.8;
    rail.scrollBy({ left: btn.classList.contains('prev') ? -amount : amount, behavior: 'smooth' });
  });
})();
