@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Thông số kỹ thuật</h1>
    <div class="breadcrumb">Trang chủ / Thông số kỹ thuật</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm thông số
  </button>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm theo tên sản phẩm..."></div>
    <select id="statusFilter" style="max-width:180px">
      <option value="">Tất cả sản phẩm</option>
      <option value="has">Đã có thông số</option>
      <option value="none">Chưa có thông số</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Sản phẩm</th>
        <th>CPU</th>
        <th>RAM</th>
        <th>Ổ cứng</th>
        <th>GPU</th>
        <th>Màn hình</th>
        <th style="width:150px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="specConfig"
  data-specs="{{ base64_encode(json_encode($specs ?? [])) }}"
  data-store-url="{{ route('admin.spec.store') }}"
  data-update-url-template="{{ route('admin.spec.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.spec.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const specConfig = document.getElementById('specConfig');
let specs = JSON.parse(atob(specConfig.dataset.specs || 'W10='));
const specStoreUrl = specConfig.dataset.storeUrl;
const specUpdateUrlTemplate = specConfig.dataset.updateUrlTemplate;
const specDeleteUrlTemplate = specConfig.dataset.deleteUrlTemplate;
const csrfToken = specConfig.dataset.csrf;

const state = {q: '', status: ''};

const FIELDS = [
  {name: 'cpu', label: 'CPU', placeholder: 'Intel Core i7-13700H'},
  {name: 'ram', label: 'RAM', placeholder: '16GB DDR5'},
  {name: 'storage', label: 'Ổ cứng', placeholder: '512GB SSD NVMe'},
  {name: 'gpu', label: 'GPU', placeholder: 'RTX 4060 8GB'},
  {name: 'screen', label: 'Màn hình', placeholder: '15.6" FHD 144Hz'},
  {name: 'battery', label: 'Pin', placeholder: '90Wh'},
  {name: 'weight', label: 'Cân nặng (kg)', placeholder: '2.3', type: 'number'},
  {name: 'os', label: 'Hệ điều hành', placeholder: 'Windows 11'}
];

function render(){
  const list = specs.filter(s => {
    if (state.q && !String(s.product_name || '').toLowerCase().includes(state.q.toLowerCase())) return false;
    if (state.status === 'has' && !s.spec_id) return false;
    if (state.status === 'none' && s.spec_id) return false;
    return true;
  });

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có sản phẩm nào</td></tr>`;
    return;
  }

  const cell = v => v ? UI.escapeHtml(v) : '<span style="color:#9ca3af">—</span>';
  tbody.innerHTML = list.map(s => `<tr>
    <td style="font-weight:500"><i class="bi bi-laptop" style="color:#6b7280;margin-right:6px"></i>${UI.escapeHtml(s.product_name)}</td>
    <td>${cell(s.cpu)}</td>
    <td>${cell(s.ram)}</td>
    <td>${cell(s.storage)}</td>
    <td>${cell(s.gpu)}</td>
    <td>${cell(s.screen)}</td>
    <td style="text-align:center">
      ${s.spec_id
        ? `<button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${s.product_id}')"><i class="bi bi-pencil"></i></button>
           <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${s.spec_id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>`
        : `<button class="btn btn-outline" style="font-size:.8rem;padding:4px 10px" onclick="onEdit('${s.product_id}')"><i class="bi bi-plus-lg"></i> Thêm</button>`}
    </td>
  </tr>`).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(productId){
  // Nếu có productId => làm việc trên sản phẩm đó; ngược lại chọn sản phẩm chưa có thông số
  const item = productId ? specs.find(s => String(s.product_id) === String(productId)) : null;
  const isEdit = !!(item && item.spec_id);

  let productField;
  if (item) {
    productField = `
      <div class="form-group full">
        <label>Sản phẩm</label>
        <input class="form-control" value="${UI.escapeHtml(item.product_name)}" disabled>
      </div>`;
  } else {
    const available = specs.filter(s => !s.spec_id);
    if (available.length === 0) {
      UI.toast('Tất cả sản phẩm đều đã có thông số', 'info');
      return;
    }
    productField = `
      <div class="form-group full">
        <label>Sản phẩm <span class="req">*</span></label>
        <select class="form-control" name="product_id" required>
          <option value="">— Chọn sản phẩm —</option>
          ${available.map(s => `<option value="${s.product_id}">${UI.escapeHtml(s.product_name)}</option>`).join('')}
        </select>
      </div>`;
  }

  const src = item || {};
  const body = `
    <div class="form-grid">
      ${productField}
      ${FIELDS.map(fld => `
        <div class="form-group">
          <label>${fld.label}</label>
          <input class="form-control" type="${fld.type || 'text'}" name="${fld.name}" value="${UI.escapeHtml(src[fld.name] ?? '')}" placeholder="${fld.placeholder}">
        </div>
      `).join('')}
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa thông số' : 'Thêm thông số',
    body,
    size: 'lg',
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => {const el = f.querySelector(`[name="${n}"]`); return el ? el.value.trim() : '';};

      const data = {};
      FIELDS.forEach(fld => {data[fld.name] = get(fld.name) || null;});

      let requestUrl;
      if (isEdit) {
        requestUrl = specUpdateUrlTemplate.replace('__ID__', encodeURIComponent(item.spec_id));
      } else {
        const pid = item ? item.product_id : get('product_id');
        if (!pid) {
          UI.toast('Vui lòng chọn sản phẩm', 'danger');
          return false;
        }
        data.product_id = pid;
        requestUrl = specStoreUrl;
      }

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
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu thông số';
            throw new Error(message);
          }
          specs = specs.map(s => String(s.product_id) === String(result.spec.product_id) ? result.spec : s);
          UI.toast(result.message || (isEdit ? 'Đã cập nhật thông số' : 'Đã thêm thông số'), 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

window.onEdit = productId => openForm(productId);
window.onRemove = specId => {
  const item = specs.find(s => String(s.spec_id) === String(specId));
  if (!item) {
    UI.toast('Không tìm thấy thông số', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa thông số của <b>${UI.escapeHtml(item.product_name)}</b>?`, () => {
    fetch(specDeleteUrlTemplate.replace('__ID__', encodeURIComponent(specId)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa thông số');
        }
        // Giữ lại dòng sản phẩm nhưng xóa dữ liệu thông số
        specs = specs.map(s => String(s.product_id) === String(result.product_id)
          ? {product_id: s.product_id, product_name: s.product_name, spec_id: null,
             cpu: null, ram: null, storage: null, gpu: null, screen: null, battery: null, weight: null, os: null}
          : s);
        UI.toast(result.message || 'Đã xóa thông số', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
