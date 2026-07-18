@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Đơn hàng</h1>
    <div class="breadcrumb">Trang chủ / Đơn hàng</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Tạo đơn hàng
  </button>
</div>

<div class="row-grid cols-4" id="kpiGrid" style="margin-bottom:16px"></div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm mã đơn / tên / SĐT khách..."></div>
    <select id="statusFilter" style="max-width:170px">
      <option value="">Tất cả trạng thái</option>
      <option value="pending">Chờ xác nhận</option>
      <option value="processing">Đang xử lý</option>
      <option value="shipping">Đang giao</option>
      <option value="completed">Hoàn thành</option>
      <option value="cancelled">Đã hủy</option>
    </select>
    <select id="paymentFilter" style="max-width:170px">
      <option value="">Tất cả thanh toán</option>
      <option value="unpaid">Chưa thanh toán</option>
      <option value="paid">Đã thanh toán</option>
      <option value="refunded">Đã hoàn tiền</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Mã đơn</th>
        <th>Khách hàng</th>
        <th style="text-align:center">Số SP</th>
        <th>Tổng tiền</th>
        <th>Thanh toán</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
        <th style="width:150px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="orderConfig"
  data-orders="{{ base64_encode(json_encode($orders ?? [])) }}"
  data-products="{{ base64_encode(json_encode($products ?? [])) }}"
  data-users="{{ base64_encode(json_encode($users ?? [])) }}"
  data-coupons="{{ base64_encode(json_encode($coupons ?? [])) }}"
  data-store-url="{{ route('admin.order.store') }}"
  data-show-url-template="{{ route('admin.order.show', ['id' => '__ID__']) }}"
  data-status-url-template="{{ route('admin.order.status', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.order.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const cfg = document.getElementById('orderConfig');
let orders = JSON.parse(atob(cfg.dataset.orders || 'W10='));
const products = JSON.parse(atob(cfg.dataset.products || 'W10='));
const users = JSON.parse(atob(cfg.dataset.users || 'W10='));
const coupons = JSON.parse(atob(cfg.dataset.coupons || 'W10='));
const storeUrl = cfg.dataset.storeUrl;
const showTpl = cfg.dataset.showUrlTemplate;
const statusTpl = cfg.dataset.statusUrlTemplate;
const deleteTpl = cfg.dataset.deleteUrlTemplate;
const csrfToken = cfg.dataset.csrf;

const state = {q: '', status: '', payment: ''};

const ORDER_STATUS = {
  pending: {label: 'Chờ xác nhận', cls: 'warning'},
  processing: {label: 'Đang xử lý', cls: 'info'},
  shipping: {label: 'Đang giao', cls: 'info'},
  completed: {label: 'Hoàn thành', cls: 'success'},
  cancelled: {label: 'Đã hủy', cls: 'danger'}
};
const PAYMENT_STATUS = {
  unpaid: {label: 'Chưa thanh toán', cls: 'muted'},
  paid: {label: 'Đã thanh toán', cls: 'success'},
  refunded: {label: 'Đã hoàn tiền', cls: 'danger'}
};
const PAYMENT_METHOD = {cod: 'COD', bank: 'Chuyển khoản', card: 'Thẻ'};

function orderBadge(key){
  const s = ORDER_STATUS[key] || {label: key, cls: 'muted'};
  return `<span class="badge-pill ${s.cls}">${s.label}</span>`;
}
function paymentBadge(key){
  const s = PAYMENT_STATUS[key] || {label: key, cls: 'muted'};
  return `<span class="badge-pill ${s.cls}">${s.label}</span>`;
}

function renderKpi(){
  const totalOrders = orders.length;
  const revenue = orders
    .filter(o => o.order_status === 'completed')
    .reduce((s, o) => s + Number(o.total_amount || 0), 0);
  const pending = orders.filter(o => o.order_status === 'pending').length;
  const cancelled = orders.filter(o => o.order_status === 'cancelled').length;
  const kpis = [
    {label: 'Tổng đơn hàng', value: totalOrders.toLocaleString('vi-VN'), icon: 'bi-receipt', color: 'bg-brand'},
    {label: 'Doanh thu (hoàn thành)', value: UI.fmtMoney(revenue), icon: 'bi-cash-coin', color: 'bg-success'},
    {label: 'Chờ xác nhận', value: pending, icon: 'bi-hourglass-split', color: 'bg-warning'},
    {label: 'Đã hủy', value: cancelled, icon: 'bi-x-circle', color: 'bg-danger'}
  ];
  document.getElementById('kpiGrid').innerHTML = kpis.map(k => `
    <div class="card-soft kpi">
      <div class="kpi-icon ${k.color}"><i class="bi ${k.icon}"></i></div>
      <div><div class="kpi-label">${k.label}</div><div class="kpi-value">${k.value}</div></div>
    </div>
  `).join('');
}

function render(){
  renderKpi();
  const q = state.q.toLowerCase();
  const list = orders.filter(o => {
    if (state.status && o.order_status !== state.status) return false;
    if (state.payment && o.payment_status !== state.payment) return false;
    if (q) {
      const hay = ((o.order_code || '') + ' ' + (o.customer_name || '') + ' ' + (o.customer_phone || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }
    return true;
  });

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có đơn hàng nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(o => {
    const canDelete = o.order_status === 'cancelled';
    return `<tr>
      <td><code style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-weight:600;font-size:.85rem">${UI.escapeHtml(o.order_code)}</code></td>
      <td>
        <div style="font-weight:500">${UI.escapeHtml(o.customer_name || '—')}</div>
        <div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(o.customer_phone || o.customer_email || '')}</div>
      </td>
      <td style="text-align:center">${o.item_count ?? 0}</td>
      <td style="font-weight:600">${UI.fmtMoney(o.total_amount)}</td>
      <td>${paymentBadge(o.payment_status)}<div style="font-size:.75rem;color:#6b7280;margin-top:2px">${PAYMENT_METHOD[o.payment_method] || o.payment_method || ''}</div></td>
      <td>${orderBadge(o.order_status)}</td>
      <td style="font-size:.85rem;color:#6b7280">${UI.fmtDate(o.create_at)}</td>
      <td style="text-align:center;white-space:nowrap">
        <button class="btn btn-icon btn-outline" title="Xem" onclick="onView('${o.id}')"><i class="bi bi-eye"></i></button>
        <button class="btn btn-icon btn-outline" title="Cập nhật trạng thái" onclick="onStatus('${o.id}')"><i class="bi bi-arrow-repeat" style="color:#2563c9"></i></button>
        ${canDelete ? `<button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${o.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>` : ''}
      </td>
    </tr>`;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('paymentFilter').addEventListener('change', e => {state.payment = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

/* ---------- Tạo đơn ---------- */
function productPrice(p){
  return Number(p.sale_price != null && p.sale_price !== '' ? p.sale_price : p.price) || 0;
}
function buildProductOptions(){
  return products.map(p =>
    `<option value="${p.id}" data-price="${productPrice(p)}">${UI.escapeHtml(p.name)}${p.sku ? ' — ' + UI.escapeHtml(p.sku) : ''} (${UI.fmtMoney(productPrice(p))})</option>`
  ).join('');
}
function couponOptions(){
  return coupons.map(c => `<option value="${c.id}" data-type="${c.type}" data-value="${c.value}" data-min="${c.min_order_amount || 0}" data-max="${c.max_discount_amount || 0}">${UI.escapeHtml(c.code)}</option>`).join('');
}

function itemRowHtml(){
  return `<tr class="item-row">
    <td><select class="form-control item-product"><option value="">-- Chọn sản phẩm --</option>${buildProductOptions()}</select></td>
    <td><input class="form-control item-qty" type="number" min="1" value="1"></td>
    <td class="item-price-cell" style="white-space:nowrap">0đ</td>
    <td class="item-subtotal" style="font-weight:600;white-space:nowrap">0đ</td>
    <td style="text-align:center"><button type="button" class="btn btn-icon btn-outline item-remove" title="Bỏ dòng"><i class="bi bi-x-lg" style="color:#dc2626"></i></button></td>
  </tr>`;
}

function calcDiscount(couponEl, subtotal, shippingFee){
  if (!couponEl || !couponEl.value) return 0;
  const opt = couponEl.selectedOptions[0];
  const type = opt.dataset.type;
  const value = Number(opt.dataset.value) || 0;
  const min = Number(opt.dataset.min) || 0;
  const max = Number(opt.dataset.max) || 0;
  if (min && subtotal < min) return 0;
  let d = 0;
  if (type === 'percent') d = subtotal * value / 100;
  else if (type === 'fixed') d = value;
  else if (type === 'shipping') d = shippingFee;
  if (max && type !== 'shipping') d = Math.min(d, max);
  d = Math.min(d, subtotal + (type === 'shipping' ? shippingFee : 0));
  return Math.round(d);
}

function openForm(){
  if (products.length === 0) {
    UI.toast('Cần có ít nhất 1 sản phẩm đang bán để tạo đơn', 'danger');
    return;
  }
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Khách hàng (tài khoản)</label>
        <select class="form-control" name="user_id">
          <option value="">— Khách vãng lai —</option>
          ${users.map(u => `<option value="${u.id}" data-name="${UI.escapeHtml(u.name || '')}" data-email="${UI.escapeHtml(u.email || '')}" data-phone="${UI.escapeHtml(u.phone || '')}" data-address="${UI.escapeHtml(u.address || '')}">${UI.escapeHtml(u.name)} (${UI.escapeHtml(u.email)})</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Tên khách hàng <span class="req">*</span></label>
        <input class="form-control" name="customer_name" required>
      </div>
      <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" name="customer_phone">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" name="customer_email">
      </div>
      <div class="form-group full">
        <label>Địa chỉ giao hàng</label>
        <input class="form-control" name="shipping_address">
      </div>
      <div class="form-group">
        <label>Phương thức thanh toán</label>
        <select class="form-control" name="payment_method">
          <option value="cod">Thanh toán khi nhận (COD)</option>
          <option value="bank">Chuyển khoản ngân hàng</option>
          <option value="card">Thẻ tín dụng / ghi nợ</option>
        </select>
      </div>
      <div class="form-group">
        <label>Trạng thái thanh toán</label>
        <select class="form-control" name="payment_status">
          <option value="unpaid">Chưa thanh toán</option>
          <option value="paid">Đã thanh toán</option>
        </select>
      </div>
      <div class="form-group">
        <label>Trạng thái đơn</label>
        <select class="form-control" name="order_status">
          <option value="pending">Chờ xác nhận</option>
          <option value="processing">Đang xử lý</option>
          <option value="shipping">Đang giao</option>
          <option value="completed">Hoàn thành</option>
        </select>
      </div>
      <div class="form-group">
        <label>Mã giảm giá</label>
        <select class="form-control" name="coupon_id">
          <option value="">— Không dùng —</option>
          ${couponOptions()}
        </select>
      </div>
    </div>
    <div style="margin-top:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <strong>Sản phẩm trong đơn</strong>
        <button type="button" class="btn btn-outline" id="addItemRow"><i class="bi bi-plus-lg"></i> Thêm dòng</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th style="min-width:220px">Sản phẩm</th>
            <th style="width:100px">Số lượng</th>
            <th style="width:130px">Đơn giá</th>
            <th style="width:140px">Thành tiền</th>
            <th style="width:44px"></th>
          </tr></thead>
          <tbody id="itemRows"></tbody>
        </table>
      </div>
    </div>
    <div class="form-grid" style="margin-top:14px">
      <div class="form-group">
        <label>Phí vận chuyển (đ)</label>
        <input class="form-control" type="number" name="shipping_fee" min="0" value="0">
      </div>
      <div class="form-group full">
        <label>Ghi chú</label>
        <input class="form-control" name="note" placeholder="Ghi chú thêm (nếu có)">
      </div>
    </div>
    <div style="margin-top:12px;text-align:right;line-height:1.9">
      <div>Tạm tính: <strong id="sumSubtotal">0đ</strong></div>
      <div>Giảm giá: <strong id="sumDiscount" style="color:#dc2626">0đ</strong></div>
      <div>Phí vận chuyển: <strong id="sumShip">0đ</strong></div>
      <div style="font-size:1.15rem">Tổng cộng: <strong id="sumTotal" style="color:#1e40af">0đ</strong></div>
    </div>
  `;

  const modal = UI.openModal({
    title: 'Tạo đơn hàng',
    body,
    size: 'lg',
    confirmText: 'Tạo đơn',
    onOpen: overlay => {
      const rows = overlay.querySelector('#itemRows');
      const couponEl = overlay.querySelector('[name="coupon_id"]');
      const shipEl = overlay.querySelector('[name="shipping_fee"]');

      const recalc = () => {
        let subtotal = 0;
        overlay.querySelectorAll('.item-row').forEach(tr => {
          const opt = tr.querySelector('.item-product').selectedOptions[0];
          const price = opt && opt.dataset.price ? Number(opt.dataset.price) : 0;
          const qty = Number(tr.querySelector('.item-qty').value) || 0;
          const sub = price * qty;
          tr.querySelector('.item-price-cell').textContent = UI.fmtMoney(price);
          tr.querySelector('.item-subtotal').textContent = UI.fmtMoney(sub);
          subtotal += sub;
        });
        const shippingFee = Number(shipEl.value) || 0;
        const discount = calcDiscount(couponEl, subtotal, shippingFee);
        const total = Math.max(0, subtotal - discount) + shippingFee;
        overlay.querySelector('#sumSubtotal').textContent = UI.fmtMoney(subtotal);
        overlay.querySelector('#sumDiscount').textContent = '-' + UI.fmtMoney(discount);
        overlay.querySelector('#sumShip').textContent = UI.fmtMoney(shippingFee);
        overlay.querySelector('#sumTotal').textContent = UI.fmtMoney(total);
      };

      // Đổ thông tin khách khi chọn tài khoản.
      overlay.querySelector('[name="user_id"]').addEventListener('change', e => {
        const opt = e.target.selectedOptions[0];
        if (!opt || !e.target.value) return;
        const set = (n, v) => { const el = overlay.querySelector(`[name="${n}"]`); if (!el.value) el.value = v || ''; };
        overlay.querySelector('[name="customer_name"]').value = opt.dataset.name || '';
        overlay.querySelector('[name="customer_phone"]').value = opt.dataset.phone || '';
        overlay.querySelector('[name="customer_email"]').value = opt.dataset.email || '';
        overlay.querySelector('[name="shipping_address"]').value = opt.dataset.address || '';
      });

      rows.insertAdjacentHTML('beforeend', itemRowHtml());
      overlay.querySelector('#addItemRow').addEventListener('click', () => {
        rows.insertAdjacentHTML('beforeend', itemRowHtml());
      });
      rows.addEventListener('input', recalc);
      rows.addEventListener('change', recalc);
      rows.addEventListener('click', e => {
        const btn = e.target.closest('.item-remove');
        if (btn) { btn.closest('.item-row').remove(); recalc(); }
      });
      couponEl.addEventListener('change', recalc);
      shipEl.addEventListener('input', recalc);
      recalc();
    },
    onConfirm: overlay => {
      const get = n => overlay.querySelector(`[name="${n}"]`).value.trim();
      const customer_name = get('customer_name');
      if (!customer_name) { UI.toast('Vui lòng nhập tên khách hàng', 'danger'); return false; }

      const items = [];
      let invalid = false;
      overlay.querySelectorAll('.item-row').forEach(tr => {
        const product_id = tr.querySelector('.item-product').value;
        if (!product_id) return;
        const quantity = Number(tr.querySelector('.item-qty').value);
        if (!quantity || quantity < 1) invalid = true;
        items.push({product_id: Number(product_id), quantity});
      });
      if (!items.length) { UI.toast('Vui lòng chọn ít nhất 1 sản phẩm', 'danger'); return false; }
      if (invalid) { UI.toast('Số lượng sản phẩm không hợp lệ', 'danger'); return false; }

      const data = {
        user_id: get('user_id') ? Number(get('user_id')) : null,
        customer_name,
        customer_phone: get('customer_phone') || null,
        customer_email: get('customer_email') || null,
        shipping_address: get('shipping_address') || null,
        payment_method: get('payment_method'),
        payment_status: get('payment_status'),
        order_status: get('order_status'),
        coupon_id: get('coupon_id') ? Number(get('coupon_id')) : null,
        shipping_fee: Number(get('shipping_fee')) || 0,
        note: get('note') || null,
        items
      };

      fetch(storeUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify(data)
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể tạo đơn hàng');
          }
          orders.unshift(result.order);
          UI.toast(result.message || 'Đã tạo đơn hàng', 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

/* ---------- Chi tiết ---------- */
function itemsTable(items){
  if (!items || !items.length) return '<p style="color:#6b7280">Không có sản phẩm</p>';
  return `<div class="table-wrap"><table>
    <thead><tr><th>Sản phẩm</th><th style="text-align:center">SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
    <tbody>${items.map(i => `<tr>
      <td>${UI.escapeHtml(i.product_name || ('#' + i.product_id))}</td>
      <td style="text-align:center">${i.quantity}</td>
      <td>${UI.fmtMoney(i.product_price)}</td>
      <td style="font-weight:600">${UI.fmtMoney(i.total_price)}</td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

window.onView = id => {
  fetch(showTpl.replace('__ID__', encodeURIComponent(id)), {headers: {'Accept': 'application/json'}})
    .then(async response => {
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.message || 'Không tải được đơn hàng');
      const o = result.order;
      UI.openModal({
        title: 'Đơn hàng ' + o.order_code,
        size: 'lg',
        confirmText: 'Đóng',
        body: `
          <div class="form-grid" style="margin-bottom:12px">
            <div><div style="color:#6b7280;font-size:.82rem">Khách hàng</div><div style="font-weight:600">${UI.escapeHtml(o.customer_name || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Điện thoại</div><div style="font-weight:600">${UI.escapeHtml(o.customer_phone || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Email</div><div style="font-weight:600">${UI.escapeHtml(o.customer_email || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Ngày tạo</div><div style="font-weight:600">${UI.fmtDateTime(o.create_at)}</div></div>
            <div class="full"><div style="color:#6b7280;font-size:.82rem">Địa chỉ giao</div><div style="font-weight:600">${UI.escapeHtml(o.shipping_address || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Thanh toán</div><div>${paymentBadge(o.payment_status)} <span style="font-size:.82rem;color:#6b7280">${UI.escapeHtml(o.payment_method_label || '')}</span></div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Trạng thái</div><div>${orderBadge(o.order_status)}</div></div>
          </div>
          ${o.note ? `<div style="margin-bottom:12px;color:#4b5563;font-size:.9rem"><b>Ghi chú:</b> ${UI.escapeHtml(o.note)}</div>` : ''}
          ${itemsTable(o.items)}
          <div style="margin-top:12px;text-align:right;line-height:1.9">
            <div>Tạm tính: <strong>${UI.fmtMoney(o.subtotal)}</strong></div>
            <div>Giảm giá: <strong style="color:#dc2626">-${UI.fmtMoney(o.discount_amount)}</strong>${o.coupon_code ? ` <span style="font-size:.8rem;color:#6b7280">(${UI.escapeHtml(o.coupon_code)})</span>` : ''}</div>
            <div>Phí vận chuyển: <strong>${UI.fmtMoney(o.shipping_fee)}</strong></div>
            <div style="font-size:1.15rem">Tổng cộng: <strong style="color:#1e40af">${UI.fmtMoney(o.total_amount)}</strong></div>
          </div>
        `,
        onConfirm: () => true
      });
    })
    .catch(error => UI.toast(error.message, 'danger'));
};

/* ---------- Cập nhật trạng thái ---------- */
window.onStatus = id => {
  const o = orders.find(item => String(item.id) === String(id));
  if (!o) { UI.toast('Không tìm thấy đơn hàng', 'danger'); return; }

  const statusOpts = Object.entries(ORDER_STATUS).map(([k, v]) =>
    `<option value="${k}" ${o.order_status === k ? 'selected' : ''}>${v.label}</option>`).join('');
  const payOpts = Object.entries(PAYMENT_STATUS).map(([k, v]) =>
    `<option value="${k}" ${o.payment_status === k ? 'selected' : ''}>${v.label}</option>`).join('');

  UI.openModal({
    title: 'Cập nhật đơn ' + o.order_code,
    confirmText: 'Cập nhật',
    body: `
      <div class="form-grid">
        <div class="form-group full">
          <label>Trạng thái đơn hàng</label>
          <select class="form-control" name="order_status">${statusOpts}</select>
        </div>
        <div class="form-group full">
          <label>Trạng thái thanh toán</label>
          <select class="form-control" name="payment_status">${payOpts}</select>
        </div>
      </div>
    `,
    onConfirm: overlay => {
      const data = {
        order_status: overlay.querySelector('[name="order_status"]').value,
        payment_status: overlay.querySelector('[name="payment_status"]').value
      };
      fetch(statusTpl.replace('__ID__', encodeURIComponent(id)), {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify(data)
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) throw new Error(result.message || 'Không thể cập nhật');
          orders = orders.map(item => String(item.id) === String(id)
            ? {...item, order_status: result.order.order_status, payment_status: result.order.payment_status}
            : item);
          UI.toast(result.message || 'Đã cập nhật', 'success');
          render();
        })
        .catch(error => UI.toast(error.message, 'danger'));
      return true;
    }
  });
};

window.onRemove = id => {
  const o = orders.find(item => String(item.id) === String(id));
  UI.confirmDialog(`Xóa đơn hàng <b>${UI.escapeHtml(o?.order_code || '')}</b>?`, () => {
    fetch(deleteTpl.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Không thể xóa đơn hàng');
        orders = orders.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa đơn hàng', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
