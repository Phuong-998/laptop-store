@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Nhập hàng</h1>
    <div class="breadcrumb">Trang chủ / Nhập hàng</div>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-outline" id="addProductBtn" type="button">
      <i class="bi bi-box-seam"></i> Thêm sản phẩm
    </button>
    <button class="btn btn-primary" id="addBtn" type="button">
      <i class="bi bi-plus-lg"></i> Lập phiếu nhập
    </button>
  </div>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm mã phiếu / nhà cung cấp..."></div>
    <select id="statusFilter" style="max-width:180px">
      <option value="">Tất cả trạng thái</option>
      <option value="pending">Chờ duyệt</option>
      <option value="completed">Đã duyệt</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Mã phiếu</th>
        <th>Nhà cung cấp</th>
        <th>Lý do</th>
        <th style="text-align:center">Số SP</th>
        <th>Tổng tiền</th>
        <th>Ngày nhập</th>
        <th>Trạng thái</th>
        <th style="width:150px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="importConfig"
  data-receipts="{{ base64_encode(json_encode($receipts ?? [])) }}"
  data-products="{{ base64_encode(json_encode($products ?? [])) }}"
  data-categories="{{ base64_encode(json_encode($categories ?? [])) }}"
  data-brands="{{ base64_encode(json_encode($brands ?? [])) }}"
  data-store-url="{{ route('admin.import.store') }}"
  data-product-store-url="{{ route('admin.import.product.store') }}"
  data-show-url-template="{{ route('admin.import.show', ['id' => '__ID__']) }}"
  data-confirm-url-template="{{ route('admin.import.confirm', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.import.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const cfg = document.getElementById('importConfig');
let receipts = JSON.parse(atob(cfg.dataset.receipts || 'W10='));
const products = JSON.parse(atob(cfg.dataset.products || 'W10='));
const categories = JSON.parse(atob(cfg.dataset.categories || 'W10='));
const brands = JSON.parse(atob(cfg.dataset.brands || 'W10='));
const storeUrl = cfg.dataset.storeUrl;
const productStoreUrl = cfg.dataset.productStoreUrl;
const showTpl = cfg.dataset.showUrlTemplate;
const confirmTpl = cfg.dataset.confirmUrlTemplate;
const deleteTpl = cfg.dataset.deleteUrlTemplate;
const csrfToken = cfg.dataset.csrf;

const state = {q: '', status: ''};

function buildProductOptions(){
  return products.map(p =>
    `<option value="${p.id}" data-price="${p.price ?? 0}">${UI.escapeHtml(p.name)}${p.sku ? ' — ' + UI.escapeHtml(p.sku) : ''} (tồn ${p.stock_quantity ?? 0})</option>`
  ).join('');
}

const REASONS = {purchase: 'Nhập mua hàng', return: 'Khách/NCC trả lại', other: 'Khác'};
const reasonOptions = Object.entries(REASONS).map(([k, v]) => `<option value="${k}">${v}</option>`).join('');

function statusBadge(status){
  return status === 'completed'
    ? '<span class="badge-pill success">Đã duyệt</span>'
    : '<span class="badge-pill info">Chờ duyệt</span>';
}

function render(){
  const q = state.q.toLowerCase();
  const list = receipts.filter(r => {
    if (state.status && r.status !== state.status) return false;
    if (q) {
      const hay = ((r.code || '') + ' ' + (r.supplier_name || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }
    return true;
  });

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có phiếu nhập nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(r => {
    const pending = r.status !== 'completed';
    return `<tr>
      <td><code style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-weight:600;font-size:.85rem">${UI.escapeHtml(r.code)}</code></td>
      <td>${UI.escapeHtml(r.supplier_name || '—')}</td>
      <td style="font-size:.85rem">${r.reason ? UI.escapeHtml(REASONS[r.reason] || r.reason) : '—'}</td>
      <td style="text-align:center">${r.item_count ?? 0}</td>
      <td style="font-weight:600">${UI.fmtMoney(r.total_amount)}</td>
      <td style="font-size:.85rem;color:#6b7280">${UI.fmtDate(r.import_date)}</td>
      <td>${statusBadge(r.status)}</td>
      <td style="text-align:center;white-space:nowrap">
        <button class="btn btn-icon btn-outline" title="Xem" onclick="onView('${r.id}')"><i class="bi bi-eye"></i></button>
        ${pending ? `<button class="btn btn-icon btn-outline" title="Duyệt" onclick="onConfirm('${r.id}')"><i class="bi bi-check2-circle" style="color:#16a34a"></i></button>` : ''}
        ${pending ? `<button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${r.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>` : ''}
      </td>
    </tr>`;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());
document.getElementById('addProductBtn').addEventListener('click', () => openProductForm());

function openProductForm(){
  if (categories.length === 0 || brands.length === 0) {
    UI.toast('Cần có ít nhất 1 danh mục và 1 thương hiệu trước khi thêm sản phẩm', 'danger');
    return;
  }
  const body = `
    <div class="form-grid">
      <div class="form-group full">
        <label>Tên sản phẩm <span class="req">*</span></label>
        <input class="form-control" name="name" required>
      </div>
      <div class="form-group">
        <label>Danh mục <span class="req">*</span></label>
        <select class="form-control" name="category_id" required>
          <option value="">— Chọn —</option>
          ${categories.map(c => `<option value="${c.id}">${UI.escapeHtml(c.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Thương hiệu <span class="req">*</span></label>
        <select class="form-control" name="branch_id" required>
          <option value="">— Chọn —</option>
          ${brands.map(b => `<option value="${b.id}">${UI.escapeHtml(b.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>SKU</label>
        <input class="form-control" name="sku">
      </div>
      <div class="form-group">
        <label>Giá gốc (đ) <span class="req">*</span></label>
        <input class="form-control" type="number" name="price" required>
      </div>
      <div class="form-group">
        <label>Giá bán (đ)</label>
        <input class="form-control" type="number" name="sale_price">
      </div>
      <div class="form-group">
        <label>Ngưỡng cảnh báo</label>
        <input class="form-control" type="number" name="low_stock_threshold" value="10" min="0">
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="status" checked>
          <span class="track"></span>
          <span class="switch-label">Đang bán</span>
        </label>
      </div>
    </div>
    <div style="margin-top:10px;color:#6b7280;font-size:.85rem"><i class="bi bi-info-circle"></i> Sản phẩm mới bắt đầu với <b>tồn kho 0</b>. Số lượng sẽ được cộng khi bạn duyệt phiếu nhập.</div>
  `;

  const modal = UI.openModal({
    title: 'Thêm sản phẩm mới',
    body,
    size: 'lg',
    confirmText: 'Tạo mới',
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => f.querySelector(`[name="${n}"]`).value.trim();

      const name = get('name');
      const categoryId = get('category_id');
      const branchId = get('branch_id');
      const price = get('price');
      if (!name || !categoryId || !branchId || !price) {
        UI.toast('Vui lòng điền đầy đủ các trường bắt buộc', 'danger');
        return false;
      }

      const data = {
        name,
        category_id: Number(categoryId),
        branch_id: Number(branchId),
        sku: get('sku') || null,
        price: Number(price),
        sale_price: get('sale_price') ? Number(get('sale_price')) : null,
        low_stock_threshold: Number(get('low_stock_threshold') || 10),
        status: f.querySelector('[name="status"]').checked
      };

      fetch(productStoreUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify(data)
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể thêm sản phẩm');
          }
          products.push(result.product);
          UI.toast(result.message || 'Đã thêm sản phẩm mới', 'success');
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

function itemRowHtml(){
  return `<tr class="item-row">
    <td><select class="form-control item-product"><option value="">-- Chọn sản phẩm --</option>${buildProductOptions()}</select></td>
    <td><input class="form-control item-qty" type="number" min="1" value="1"></td>
    <td><input class="form-control item-price" type="number" min="0" placeholder="0"></td>
    <td class="item-subtotal" style="font-weight:600;white-space:nowrap">0đ</td>
    <td style="text-align:center"><button type="button" class="btn btn-icon btn-outline item-remove" title="Bỏ dòng"><i class="bi bi-x-lg" style="color:#dc2626"></i></button></td>
  </tr>`;
}

function openForm(){
  const today = new Date().toISOString().slice(0, 10);
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Nhà cung cấp</label>
        <input class="form-control" type="text" name="supplier_name" placeholder="Nhập tên nhà cung cấp">
      </div>
      <div class="form-group">
        <label>Ngày nhập</label>
        <input class="form-control" type="date" name="import_date" value="${today}">
      </div>
      <div class="form-group">
        <label>Loại nhập</label>
        <select class="form-control" name="reason">
          <option value="">-- Không chọn --</option>
          ${reasonOptions}
        </select>
      </div>
      <div class="form-group">
        <label>Ghi chú</label>
        <input class="form-control" type="text" name="note" placeholder="Mô tả thêm (nếu có)">
      </div>
    </div>
    <div style="margin-top:14px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <strong>Chi tiết sản phẩm nhập</strong>
        <button type="button" class="btn btn-outline" id="addItemRow"><i class="bi bi-plus-lg"></i> Thêm dòng</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr>
            <th style="min-width:220px">Sản phẩm</th>
            <th style="width:100px">Số lượng</th>
            <th style="width:150px">Đơn giá nhập</th>
            <th style="width:140px">Thành tiền</th>
            <th style="width:44px"></th>
          </tr></thead>
          <tbody id="itemRows"></tbody>
        </table>
      </div>
      <div style="text-align:right;margin-top:12px;font-size:1.05rem">Tổng tiền: <strong id="grandTotal">0đ</strong></div>
    </div>
  `;

  const modal = UI.openModal({
    title: 'Lập phiếu nhập',
    body,
    size: 'lg',
    confirmText: 'Tạo phiếu',
    onOpen: overlay => {
      const rows = overlay.querySelector('#itemRows');
      const recalc = () => {
        let total = 0;
        overlay.querySelectorAll('.item-row').forEach(tr => {
          const qty = Number(tr.querySelector('.item-qty').value) || 0;
          const price = Number(tr.querySelector('.item-price').value) || 0;
          const sub = qty * price;
          tr.querySelector('.item-subtotal').textContent = UI.fmtMoney(sub);
          total += sub;
        });
        overlay.querySelector('#grandTotal').textContent = UI.fmtMoney(total);
      };
      rows.insertAdjacentHTML('beforeend', itemRowHtml());
      overlay.querySelector('#addItemRow').addEventListener('click', () => {
        rows.insertAdjacentHTML('beforeend', itemRowHtml());
      });
      rows.addEventListener('input', recalc);
      rows.addEventListener('change', e => {
        if (e.target.classList.contains('item-product')) {
          const opt = e.target.selectedOptions[0];
          const priceInput = e.target.closest('.item-row').querySelector('.item-price');
          if (opt && opt.dataset.price && !priceInput.value) priceInput.value = opt.dataset.price;
          recalc();
        }
      });
      rows.addEventListener('click', e => {
        const btn = e.target.closest('.item-remove');
        if (btn) { btn.closest('.item-row').remove(); recalc(); }
      });
      recalc();
    },
    onConfirm: overlay => {
      const supplier_name = overlay.querySelector('[name="supplier_name"]').value.trim() || null;
      const import_date = overlay.querySelector('[name="import_date"]').value || null;
      const reason = overlay.querySelector('[name="reason"]').value || null;
      const note = overlay.querySelector('[name="note"]').value.trim() || null;
      const items = [];
      let invalid = false;
      overlay.querySelectorAll('.item-row').forEach(tr => {
        const product_id = tr.querySelector('.item-product').value;
        if (!product_id) return;
        const quantity = Number(tr.querySelector('.item-qty').value);
        const unit_price = Number(tr.querySelector('.item-price').value);
        if (!quantity || quantity < 1) invalid = true;
        if (unit_price == null || isNaN(unit_price) || unit_price < 0) invalid = true;
        items.push({product_id: Number(product_id), quantity, unit_price});
      });

      if (!items.length) { UI.toast('Vui lòng chọn ít nhất 1 sản phẩm', 'danger'); return false; }
      if (invalid) { UI.toast('Số lượng / đơn giá không hợp lệ', 'danger'); return false; }

      fetch(storeUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify({supplier_name, import_date, reason, note, items})
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể tạo phiếu');
          }
          receipts.unshift(result.receipt);
          UI.toast(result.message || 'Đã tạo phiếu nhập', 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

function itemsTable(items){
  if (!items || !items.length) return '<p style="color:#6b7280">Không có sản phẩm</p>';
  return `<div class="table-wrap"><table>
    <thead><tr><th>Sản phẩm</th><th style="text-align:center">SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
    <tbody>${items.map(i => `<tr>
      <td>${UI.escapeHtml(i.product_name || ('#' + i.product_id))}${i.sku ? `<div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(i.sku)}</div>` : ''}</td>
      <td style="text-align:center">${i.quantity}</td>
      <td>${UI.fmtMoney(i.unit_price)}</td>
      <td style="font-weight:600">${UI.fmtMoney(i.subtotal)}</td>
    </tr>`).join('')}</tbody>
  </table></div>`;
}

window.onView = id => {
  fetch(showTpl.replace('__ID__', encodeURIComponent(id)), {headers: {'Accept': 'application/json'}})
    .then(async response => {
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.message || 'Không tải được phiếu');
      const r = result.receipt;
      UI.openModal({
        title: 'Phiếu nhập ' + r.code,
        size: 'lg',
        confirmText: 'Đóng',
        body: `
          <div class="form-grid" style="margin-bottom:12px">
            <div><div style="color:#6b7280;font-size:.82rem">Nhà cung cấp</div><div style="font-weight:600">${UI.escapeHtml(r.supplier_name || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Loại nhập</div><div style="font-weight:600">${UI.escapeHtml(r.reason_label || '—')}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Ngày nhập</div><div style="font-weight:600">${UI.fmtDate(r.import_date)}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Trạng thái</div><div>${statusBadge(r.status)}</div></div>
            <div><div style="color:#6b7280;font-size:.82rem">Tổng tiền</div><div style="font-weight:700;color:#1e40af">${UI.fmtMoney(r.total_amount)}</div></div>
          </div>
          ${r.note ? `<div style="margin-bottom:12px;color:#4b5563;font-size:.9rem"><b>Ghi chú:</b> ${UI.escapeHtml(r.note)}</div>` : ''}
          ${itemsTable(r.items)}
        `,
        onConfirm: () => true
      });
    })
    .catch(error => UI.toast(error.message, 'danger'));
};

window.onConfirm = id => {
  const r = receipts.find(item => String(item.id) === String(id));
  UI.openModal({
    title: 'Duyệt phiếu nhập',
    body: `<p style="margin:0">Duyệt phiếu <b>${UI.escapeHtml(r?.code || '')}</b>? Tồn kho sẽ được <b>cộng thêm</b> và không thể hoàn tác.</p>`,
    confirmText: 'Duyệt',
    onConfirm: () => {
      fetch(confirmTpl.replace('__ID__', encodeURIComponent(id)), {
        method: 'POST',
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) throw new Error(result.message || 'Không thể duyệt phiếu');
          receipts = receipts.map(item => String(item.id) === String(id) ? result.receipt : item);
          UI.toast(result.message || 'Đã duyệt phiếu', 'success');
          render();
        })
        .catch(error => UI.toast(error.message, 'danger'));
      return true;
    }
  });
};

window.onRemove = id => {
  const r = receipts.find(item => String(item.id) === String(id));
  UI.confirmDialog(`Xóa phiếu nhập <b>${UI.escapeHtml(r?.code || '')}</b>?`, () => {
    fetch(deleteTpl.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Không thể xóa phiếu');
        receipts = receipts.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa phiếu', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
