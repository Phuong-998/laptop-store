@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Mã giảm giá</h1>
    <div class="breadcrumb">Trang chủ / Mã giảm giá</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm mã
  </button>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm mã..."></div>
    <select id="typeFilter" style="max-width:160px">
      <option value="">Tất cả loại</option>
      <option value="percent">% giảm giá</option>
      <option value="fixed">Số tiền cố định</option>
      <option value="shipping">Miễn phí ship</option>
    </select>
    <select id="statusFilter" style="max-width:160px">
      <option value="">Tất cả trạng thái</option>
      <option value="active">Đang hoạt động</option>
      <option value="scheduled">Đang chờ</option>
      <option value="expired">Hết hạn</option>
      <option value="inactive">Vô hiệu</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Mã</th>
        <th>Loại</th>
        <th>Giá trị</th>
        <th>Đơn tối thiểu</th>
        <th>Đã dùng</th>
        <th>Hiệu lực</th>
        <th>Trạng thái</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="couponConfig"
  data-coupons="{{ base64_encode(json_encode($coupons ?? [])) }}"
  data-store-url="{{ route('admin.coupon.store') }}"
  data-update-url-template="{{ route('admin.coupon.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.coupon.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const couponConfig = document.getElementById('couponConfig');
let coupons = JSON.parse(atob(couponConfig.dataset.coupons || 'W10='));
const couponStoreUrl = couponConfig.dataset.storeUrl;
const couponUpdateUrlTemplate = couponConfig.dataset.updateUrlTemplate;
const couponDeleteUrlTemplate = couponConfig.dataset.deleteUrlTemplate;
const csrfToken = couponConfig.dataset.csrf;

const state = {q: '', type: '', status: ''};

const typeLabel = {percent: '% Giảm', fixed: 'Tiền cố định', shipping: 'Miễn ship'};
const statusBadge = {
  active: '<span class="badge-pill success">Đang chạy</span>',
  expired: '<span class="badge-pill danger">Hết hạn</span>',
  inactive: '<span class="badge-pill muted">Vô hiệu</span>',
  scheduled: '<span class="badge-pill info">Đang chờ</span>'
};

function toTime(v){
  if (!v) return null;
  const d = new Date(String(v).replace(' ', 'T'));
  return isNaN(d) ? null : d.getTime();
}

function toDateInput(v){
  if (!v) return '';
  const s = String(v);
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10);
  const d = new Date(s);
  return isNaN(d) ? '' : d.toISOString().slice(0, 10);
}

function isActive(c){
  return c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
}

function getCouponStatus(c){
  const now = Date.now();
  if (!isActive(c)) return 'inactive';
  const start = toTime(c.start_date);
  const end = toTime(c.end_date);
  if (end && end < now) return 'expired';
  if (Number(c.use_limit) && Number(c.use_count) >= Number(c.use_limit)) return 'expired';
  if (start && start > now) return 'scheduled';
  return 'active';
}

function render(){
  const list = coupons.filter(c => {
    if (state.q && !String(c.code || '').toLowerCase().includes(state.q.toLowerCase())) return false;
    if (state.type && c.type !== state.type) return false;
    if (state.status && getCouponStatus(c) !== state.status) return false;
    return true;
  }).sort((a, b) => new Date(b.create_at || 0) - new Date(a.create_at || 0));

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Không có mã giảm giá nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(c => {
    let valueText = '';
    if (c.type === 'percent') valueText = Number(c.value) + '%';
    else if (c.type === 'fixed') valueText = UI.fmtMoney(c.value);
    else valueText = 'Miễn phí ship';
    const limit = Number(c.use_limit) || 0;
    const used = Number(c.use_count) || 0;
    const usedPct = limit ? Math.round((used / limit) * 100) : 0;
    return `<tr>
      <td>
        <code style="background:#dbeafe;color:#1e40af;padding:4px 10px;border-radius:6px;font-weight:600;font-size:.85rem">${UI.escapeHtml(c.code)}</code>
      </td>
      <td><span class="badge-pill info">${typeLabel[c.type] || c.type}</span></td>
      <td style="font-weight:600">${valueText}</td>
      <td>${UI.fmtMoney(c.min_order_amount)}</td>
      <td>
        <div style="font-size:.85rem">${used} / ${limit || '∞'}</div>
        ${limit ? `<div style="width:80px;height:5px;background:#e5e7eb;border-radius:3px;margin-top:3px"><div style="width:${Math.min(usedPct, 100)}%;height:100%;background:${usedPct >= 100 ? '#dc2626' : '#16a34a'};border-radius:3px"></div></div>` : ''}
      </td>
      <td style="font-size:.82rem;color:#6b7280">
        ${UI.fmtDate(c.start_date)}<br>→ ${UI.fmtDate(c.end_date)}
      </td>
      <td>${statusBadge[getCouponStatus(c)]}</td>
      <td style="text-align:center">
        <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${c.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${c.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
      </td>
    </tr>`;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('typeFilter').addEventListener('change', e => {state.type = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(id){
  const c = id ? coupons.find(item => String(item.id) === String(id)) : {type: 'percent', status: 1};
  const isEdit = !!id;
  const active = isActive(c);
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Mã code <span class="req">*</span></label>
        <input class="form-control" name="code" required value="${UI.escapeHtml(c.code || '')}" placeholder="WELCOME10" style="text-transform:uppercase">
      </div>
      <div class="form-group">
        <label>Loại giảm giá <span class="req">*</span></label>
        <select class="form-control" name="type">
          <option value="percent" ${c.type === 'percent' ? 'selected' : ''}>% Giảm giá</option>
          <option value="fixed" ${c.type === 'fixed' ? 'selected' : ''}>Số tiền cố định (đ)</option>
          <option value="shipping" ${c.type === 'shipping' ? 'selected' : ''}>Miễn phí vận chuyển</option>
        </select>
      </div>
      <div class="form-group">
        <label>Giá trị</label>
        <input class="form-control" type="number" name="value" value="${c.value || ''}">
        <div class="form-hint">% hoặc số tiền tùy theo loại (bỏ trống nếu miễn phí ship)</div>
      </div>
      <div class="form-group">
        <label>Giảm tối đa (đ)</label>
        <input class="form-control" type="number" name="max_discount_amount" value="${c.max_discount_amount || ''}">
      </div>
      <div class="form-group">
        <label>Đơn hàng tối thiểu (đ)</label>
        <input class="form-control" type="number" name="min_order_amount" value="${c.min_order_amount || ''}">
      </div>
      <div class="form-group">
        <label>Giới hạn lượt dùng</label>
        <input class="form-control" type="number" name="use_limit" value="${c.use_limit || ''}" placeholder="Để trống = không giới hạn">
      </div>
      <div class="form-group">
        <label>Ngày bắt đầu</label>
        <input class="form-control" type="date" name="start_date" value="${toDateInput(c.start_date)}">
      </div>
      <div class="form-group">
        <label>Ngày kết thúc</label>
        <input class="form-control" type="date" name="end_date" value="${toDateInput(c.end_date)}">
      </div>
      <div class="form-group full" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="active" ${active ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Kích hoạt mã</span>
        </label>
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa mã giảm giá' : 'Thêm mã giảm giá',
    body,
    size: 'lg',
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => f.querySelector(`[name="${n}"]`).value.trim();

      const code = get('code').toUpperCase();
      const type = get('type');
      if (!code) {
        UI.toast('Mã code không được trống', 'danger');
        return false;
      }
      if (type !== 'shipping' && !get('value')) {
        UI.toast('Vui lòng nhập giá trị giảm', 'danger');
        return false;
      }

      const data = {
        code,
        type,
        value: get('value') ? Number(get('value')) : null,
        max_discount_amount: get('max_discount_amount') ? Number(get('max_discount_amount')) : null,
        min_order_amount: get('min_order_amount') ? Number(get('min_order_amount')) : null,
        use_limit: get('use_limit') ? Number(get('use_limit')) : null,
        start_date: get('start_date') || null,
        end_date: get('end_date') || null,
        active: f.querySelector('[name="active"]').checked
      };

      const requestUrl = isEdit
        ? couponUpdateUrlTemplate.replace('__ID__', encodeURIComponent(id))
        : couponStoreUrl;

      fetch(requestUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(data)
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu mã giảm giá';
            throw new Error(message);
          }
          if (isEdit) {
            coupons = coupons.map(item => String(item.id) === String(id) ? result.coupon : item);
          } else {
            coupons.unshift(result.coupon);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật mã' : 'Đã thêm mã giảm giá'), 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

window.onEdit = id => openForm(id);
window.onRemove = id => {
  const coupon = coupons.find(item => String(item.id) === String(id));
  if (!coupon) {
    UI.toast('Không tìm thấy mã giảm giá', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa mã <b>${UI.escapeHtml(coupon.code)}</b>?`, () => {
    fetch(couponDeleteUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa mã giảm giá');
        }
        coupons = coupons.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa mã giảm giá', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
