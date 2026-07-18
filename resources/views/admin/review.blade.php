@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Đánh giá khách hàng</h1>
    <div class="breadcrumb">Trang chủ / Đánh giá khách hàng</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm đánh giá
  </button>
</div>

<div class="row-grid cols-4" id="kpiGrid" style="margin-bottom:16px"></div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm theo sản phẩm / khách / nội dung..."></div>
    <select id="ratingFilter" style="max-width:150px">
      <option value="">Tất cả số sao</option>
      <option value="5">5 sao</option>
      <option value="4">4 sao</option>
      <option value="3">3 sao</option>
      <option value="2">2 sao</option>
      <option value="1">1 sao</option>
    </select>
    <select id="statusFilter" style="max-width:160px">
      <option value="">Tất cả trạng thái</option>
      <option value="1">Đang hiển thị</option>
      <option value="0">Đang ẩn</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Sản phẩm</th>
        <th>Khách hàng</th>
        <th style="width:130px">Đánh giá</th>
        <th>Nội dung</th>
        <th>Ngày</th>
        <th>Trạng thái</th>
        <th style="width:160px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="reviewConfig"
  data-reviews="{{ base64_encode(json_encode($reviews ?? [])) }}"
  data-products="{{ base64_encode(json_encode($products ?? [])) }}"
  data-users="{{ base64_encode(json_encode($users ?? [])) }}"
  data-store-url="{{ route('admin.review.store') }}"
  data-update-url-template="{{ route('admin.review.update', ['id' => '__ID__']) }}"
  data-toggle-url-template="{{ route('admin.review.toggle', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.review.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const cfg = document.getElementById('reviewConfig');
let reviews = JSON.parse(atob(cfg.dataset.reviews || 'W10='));
const products = JSON.parse(atob(cfg.dataset.products || 'W10='));
const users = JSON.parse(atob(cfg.dataset.users || 'W10='));
const storeUrl = cfg.dataset.storeUrl;
const updateTpl = cfg.dataset.updateUrlTemplate;
const toggleTpl = cfg.dataset.toggleUrlTemplate;
const deleteTpl = cfg.dataset.deleteUrlTemplate;
const csrfToken = cfg.dataset.csrf;

const state = {q: '', rating: '', status: ''};

function stars(rating){
  const r = Number(rating) || 0;
  let out = '';
  for (let i = 1; i <= 5; i++) {
    out += `<i class="bi ${i <= r ? 'bi-star-fill' : 'bi-star'}" style="color:${i <= r ? '#f59e0b' : '#d1d5db'};font-size:.9rem"></i>`;
  }
  return `<span title="${r}/5">${out}</span>`;
}

function statusBadge(status){
  return Number(status) === 1
    ? '<span class="badge-pill success">Hiển thị</span>'
    : '<span class="badge-pill muted">Đang ẩn</span>';
}

function renderKpi(){
  const total = reviews.length;
  const shown = reviews.filter(r => Number(r.status) === 1).length;
  const hidden = total - shown;
  const avg = total ? (reviews.reduce((s, r) => s + Number(r.rating || 0), 0) / total) : 0;
  const kpis = [
    {label: 'Tổng đánh giá', value: total.toLocaleString('vi-VN'), icon: 'bi-chat-square-text', color: 'bg-brand'},
    {label: 'Điểm trung bình', value: avg.toFixed(1) + ' ★', icon: 'bi-star-fill', color: 'bg-warning'},
    {label: 'Đang hiển thị', value: shown, icon: 'bi-eye', color: 'bg-success'},
    {label: 'Đang ẩn', value: hidden, icon: 'bi-eye-slash', color: 'bg-danger'}
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
  const list = reviews.filter(r => {
    if (state.rating && Number(r.rating) !== Number(state.rating)) return false;
    if (state.status !== '' && Number(r.status) !== Number(state.status)) return false;
    if (q) {
      const hay = ((r.product_name || '') + ' ' + (r.user_name || '') + ' ' + (r.comment || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }
    return true;
  });

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có đánh giá nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(r => {
    const shown = Number(r.status) === 1;
    return `<tr>
      <td style="font-weight:500">${UI.escapeHtml(r.product_name || ('#' + r.product_id))}</td>
      <td>
        <div style="font-weight:500">${UI.escapeHtml(r.user_name || '—')}</div>
        <div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(r.user_email || '')}</div>
      </td>
      <td>${stars(r.rating)}</td>
      <td style="max-width:320px;font-size:.88rem;color:#4b5563">${r.comment ? UI.escapeHtml(r.comment) : '<span style="color:#9ca3af">—</span>'}</td>
      <td style="font-size:.85rem;color:#6b7280">${UI.fmtDate(r.created_at)}</td>
      <td>${statusBadge(r.status)}</td>
      <td style="text-align:center;white-space:nowrap">
        <button class="btn btn-icon btn-outline" title="${shown ? 'Ẩn' : 'Duyệt / hiển thị'}" onclick="onToggle('${r.id}')"><i class="bi ${shown ? 'bi-eye-slash' : 'bi-check2-circle'}" style="color:${shown ? '#f59e0b' : '#16a34a'}"></i></button>
        <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${r.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${r.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
      </td>
    </tr>`;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('ratingFilter').addEventListener('change', e => {state.rating = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(id){
  const r = id ? reviews.find(item => String(item.id) === String(id)) : {rating: 5, status: 1};
  const isEdit = !!id;
  if (products.length === 0 || users.length === 0) {
    UI.toast('Cần có sản phẩm và người dùng trước khi thêm đánh giá', 'danger');
    return;
  }
  const shown = Number(r.status) === 1;
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Sản phẩm <span class="req">*</span></label>
        <select class="form-control" name="product_id" required>
          <option value="">— Chọn sản phẩm —</option>
          ${products.map(p => `<option value="${p.id}" ${String(r.product_id) === String(p.id) ? 'selected' : ''}>${UI.escapeHtml(p.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Khách hàng <span class="req">*</span></label>
        <select class="form-control" name="user_id" required>
          <option value="">— Chọn khách hàng —</option>
          ${users.map(u => `<option value="${u.id}" ${String(r.user_id) === String(u.id) ? 'selected' : ''}>${UI.escapeHtml(u.name)} (${UI.escapeHtml(u.email)})</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Số sao <span class="req">*</span></label>
        <select class="form-control" name="rating">
          ${[5, 4, 3, 2, 1].map(n => `<option value="${n}" ${Number(r.rating) === n ? 'selected' : ''}>${n} sao</option>`).join('')}
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="status" ${shown ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Hiển thị công khai</span>
        </label>
      </div>
      <div class="form-group full">
        <label>Nội dung nhận xét</label>
        <textarea class="form-control" name="comment" rows="4" placeholder="Nội dung đánh giá của khách...">${UI.escapeHtml(r.comment || '')}</textarea>
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa đánh giá' : 'Thêm đánh giá',
    body,
    size: 'lg',
    confirmText: isEdit ? 'Cập nhật' : 'Thêm',
    onConfirm: overlay => {
      const get = n => overlay.querySelector(`[name="${n}"]`).value.trim();
      const product_id = get('product_id');
      const user_id = get('user_id');
      if (!product_id || !user_id) { UI.toast('Vui lòng chọn sản phẩm và khách hàng', 'danger'); return false; }

      const data = {
        product_id: Number(product_id),
        user_id: Number(user_id),
        rating: Number(get('rating')),
        comment: get('comment') || null,
        status: overlay.querySelector('[name="status"]').checked
      };

      const requestUrl = isEdit ? updateTpl.replace('__ID__', encodeURIComponent(id)) : storeUrl;
      fetch(requestUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken},
        body: JSON.stringify(data)
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu đánh giá');
          }
          if (isEdit) {
            reviews = reviews.map(item => String(item.id) === String(id) ? result.review : item);
          } else {
            reviews.unshift(result.review);
          }
          UI.toast(result.message || 'Đã lưu đánh giá', 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

window.onEdit = id => openForm(id);

window.onToggle = id => {
  fetch(toggleTpl.replace('__ID__', encodeURIComponent(id)), {
    method: 'POST',
    headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
  })
    .then(async response => {
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.message || 'Không thể cập nhật');
      reviews = reviews.map(item => String(item.id) === String(id) ? result.review : item);
      UI.toast(result.message || 'Đã cập nhật', 'success');
      render();
    })
    .catch(error => UI.toast(error.message, 'danger'));
};

window.onRemove = id => {
  const r = reviews.find(item => String(item.id) === String(id));
  UI.confirmDialog(`Xóa đánh giá của <b>${UI.escapeHtml(r?.user_name || 'khách hàng')}</b>?`, () => {
    fetch(deleteTpl.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken}
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || 'Không thể xóa đánh giá');
        reviews = reviews.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa đánh giá', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
