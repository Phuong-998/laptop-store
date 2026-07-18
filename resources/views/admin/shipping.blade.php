@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Phí vận chuyển</h1>
    <div class="breadcrumb">Trang chủ / Phí vận chuyển</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm khu vực
  </button>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm khu vực hoặc tỉnh..."></div>
    <select id="statusFilter" style="max-width:160px">
      <option value="">Tất cả</option>
      <option value="active">Đang áp dụng</option>
      <option value="inactive">Tạm dừng</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Khu vực</th>
        <th>Tỉnh / Thành áp dụng</th>
        <th>Phí ship</th>
        <th>Miễn phí từ</th>
        <th>Thời gian giao</th>
        <th>Trạng thái</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="shippingConfig"
  data-zones="{{ base64_encode(json_encode($zones ?? [])) }}"
  data-store-url="{{ route('admin.shipping.store') }}"
  data-update-url-template="{{ route('admin.shipping.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.shipping.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const shippingConfig = document.getElementById('shippingConfig');
let zones = JSON.parse(atob(shippingConfig.dataset.zones || 'W10='));
const shippingStoreUrl = shippingConfig.dataset.storeUrl;
const shippingUpdateUrlTemplate = shippingConfig.dataset.updateUrlTemplate;
const shippingDeleteUrlTemplate = shippingConfig.dataset.deleteUrlTemplate;
const csrfToken = shippingConfig.dataset.csrf;

const state = {q: '', status: ''};

// Danh sách tỉnh/thành lấy từ https://provinces.open-api.vn (có cache)
let provinceCache = null;
function loadProvinces(){
  if (provinceCache) return Promise.resolve(provinceCache);
  return fetch('https://provinces.open-api.vn/api/v2/p/', {headers: {'Accept': 'application/json'}})
    .then(r => r.ok ? r.json() : Promise.reject(new Error('load failed')))
    .then(list => {provinceCache = list.map(p => p.name); return provinceCache;})
    .catch(() => null);
}
loadProvinces(); // làm ấm cache ngay khi mở trang

function renderProvinceBox(box, selected, filter){
  if (!provinceCache) {
    box.innerHTML = `<div style="color:#dc2626;font-size:.85rem;margin-bottom:6px">Không tải được danh sách tỉnh. Nhập thủ công (phân cách bằng dấu phẩy):</div>
      <input class="form-control" id="provinceManual" value="${UI.escapeHtml([...selected].join(', '))}">`;
    return;
  }
  const extras = [...selected].filter(n => !provinceCache.includes(n)); // giữ dữ liệu cũ không có trong API
  const all = [...extras, ...provinceCache];
  const f = (filter || '').toLowerCase();
  const rows = all.filter(n => n.toLowerCase().includes(f));
  box.innerHTML = rows.map(n => `
    <label style="display:flex;align-items:center;gap:8px;padding:3px 0;font-size:.88rem;cursor:pointer">
      <input type="checkbox" class="province-check" value="${UI.escapeHtml(n)}" ${selected.has(n) ? 'checked' : ''}>
      <span>${UI.escapeHtml(n)}</span>
    </label>`).join('') || '<div style="color:#9ca3af;font-size:.85rem">Không tìm thấy</div>';
}

function isActive(z){
  return z.status !== undefined ? Number(z.status) === 1 : Boolean(z.active);
}

function render(){
  const list = zones.filter(z => {
    if (state.q) {
      const q = state.q.toLowerCase();
      const inProvince = (z.provinces || []).some(p => p.toLowerCase().includes(q));
      if (!String(z.region || '').toLowerCase().includes(q) && !inProvince) return false;
    }
    if (state.status === 'active' && !isActive(z)) return false;
    if (state.status === 'inactive' && isActive(z)) return false;
    return true;
  }).sort((a, b) => Number(a.fee) - Number(b.fee));

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có khu vực nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(z => {
    const active = isActive(z);
    const provinces = (z.provinces || []).map(p => `<span class="tag">${UI.escapeHtml(p)}</span>`).join('')
      || '<span style="color:#9ca3af">Toàn quốc</span>';
    return `<tr>
      <td style="font-weight:500"><i class="bi bi-geo-alt-fill" style="color:#dc2626;margin-right:6px"></i>${UI.escapeHtml(z.region)}</td>
      <td>${provinces}</td>
      <td style="font-weight:600">${UI.fmtMoney(z.fee)}</td>
      <td style="color:#16a34a">${z.free_threshold ? UI.fmtMoney(z.free_threshold) : '—'}</td>
      <td>${z.estimate_days ? UI.escapeHtml(z.estimate_days) + ' ngày' : '—'}</td>
      <td><span class="badge-pill ${active ? 'success' : 'muted'}">${active ? 'Đang áp dụng' : 'Tạm dừng'}</span></td>
      <td style="text-align:center">
        <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${z.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${z.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
      </td>
    </tr>`;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(id){
  const z = id ? zones.find(item => String(item.id) === String(id)) : {status: 1, provinces: []};
  const isEdit = !!id;
  const active = isActive(z);
  const selected = new Set((z.provinces || []).map(String));
  const body = `
    <div class="form-grid">
      <div class="form-group full">
        <label>Tên khu vực <span class="req">*</span></label>
        <input class="form-control" name="region" required value="${UI.escapeHtml(z.region || '')}" placeholder="Miền Bắc, Nội thành Hà Nội...">
      </div>
      <div class="form-group full">
        <label>Tỉnh / Thành áp dụng</label>
        <input type="search" class="form-control" id="provinceSearch" placeholder="🔍 Tìm tỉnh/thành..." style="margin-bottom:8px" autocomplete="off">
        <div id="provinceBox" style="max-height:190px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px">
          <div style="color:#6b7280;font-size:.85rem">Đang tải danh sách tỉnh/thành...</div>
        </div>
        <div class="form-hint">Chọn các tỉnh/thành áp dụng. Không chọn = áp dụng toàn quốc.</div>
      </div>
      <div class="form-group">
        <label>Phí vận chuyển (đ) <span class="req">*</span></label>
        <input class="form-control" type="number" name="fee" required value="${z.fee || ''}">
      </div>
      <div class="form-group">
        <label>Miễn phí từ (đ)</label>
        <input class="form-control" type="number" name="free_threshold" value="${z.free_threshold || ''}">
        <div class="form-hint">Đơn hàng ≥ giá này sẽ miễn ship</div>
      </div>
      <div class="form-group">
        <label>Thời gian giao (ngày)</label>
        <input class="form-control" name="estimate_days" value="${UI.escapeHtml(z.estimate_days || '')}" placeholder="2-3">
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="active" ${active ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Đang áp dụng</span>
        </label>
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa khu vực giao hàng' : 'Thêm khu vực giao hàng',
    body,
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onOpen: overlay => {
      const box = overlay.querySelector('#provinceBox');
      const search = overlay.querySelector('#provinceSearch');
      loadProvinces().then(() => {
        renderProvinceBox(box, selected, '');
        // Cập nhật tập đã chọn khi tick/bỏ tick
        box.addEventListener('change', e => {
          if (!e.target.classList.contains('province-check')) return;
          if (e.target.checked) selected.add(e.target.value);
          else selected.delete(e.target.value);
        });
        if (search) search.addEventListener('input', e => renderProvinceBox(box, selected, e.target.value));
      });
    },
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => f.querySelector(`[name="${n}"]`).value.trim();

      const region = get('region');
      if (!region) {
        UI.toast('Tên khu vực không được trống', 'danger');
        return false;
      }
      if (get('fee') === '' || isNaN(Number(get('fee')))) {
        UI.toast('Phí vận chuyển không hợp lệ', 'danger');
        return false;
      }

      // Lấy tỉnh đã chọn: từ ô chọn (checkbox) hoặc ô nhập thủ công khi API lỗi
      const manual = f.querySelector('#provinceManual');
      const provinces = manual
        ? manual.value.split(',').map(p => p.trim()).filter(Boolean)
        : [...selected];

      const data = {
        region,
        provinces,
        fee: Number(get('fee')),
        free_threshold: get('free_threshold') ? Number(get('free_threshold')) : null,
        estimate_days: get('estimate_days'),
        active: f.querySelector('[name="active"]').checked
      };

      const requestUrl = isEdit
        ? shippingUpdateUrlTemplate.replace('__ID__', encodeURIComponent(id))
        : shippingStoreUrl;

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
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu khu vực';
            throw new Error(message);
          }
          if (isEdit) {
            zones = zones.map(item => String(item.id) === String(id) ? result.zone : item);
          } else {
            zones.unshift(result.zone);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật khu vực' : 'Đã thêm khu vực'), 'success');
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
  const zone = zones.find(item => String(item.id) === String(id));
  if (!zone) {
    UI.toast('Không tìm thấy khu vực', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa khu vực <b>${UI.escapeHtml(zone.region)}</b>?`, () => {
    fetch(shippingDeleteUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa khu vực');
        }
        zones = zones.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa khu vực', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
