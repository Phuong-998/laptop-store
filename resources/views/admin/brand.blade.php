@extends('admin.layout.layouts')
@section('conten')
<div class="page-header">
  <div>
    <h1>Quản lý thương hiệu</h1>
    <div class="breadcrumb">Trang chủ / Thương hiệu</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm thương hiệu
  </button>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow">
      <input type="search" id="qInput" placeholder="Tìm danh mục...">
    </div>
    <select id="statusFilter" style="max-width:160px">
      <option value="">Tất cả trạng thái</option>
      <option value="active">Hoạt động</option>
      <option value="inactive">Tạm dừng</option>
    </select>
  </div>
</div>  

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Tên thương hiệu</th>
        <th style="text-align:center">Số sản phẩm</th>
        <th>Trạng thái</th>
        <th>Ngày tạo</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>
<div
  id="brandConfig"
  data-brand="{{ base64_encode(json_encode($brand ?? [])) }}"
  data-store-url="{{ route('admin.brand.store') }}"
  data-update-brand-url = "{{ route('admin.brand.update',['id' => 'ID'])}}"
  data-delete-brand-url = "{{ route('admin.brand.delete',['id' => 'ID'])}}"
  data-csrf="{{ csrf_token() }}"
  hidden
>
</div>
@endsection
@push('scripts')
<script>
const brandConfig = document.getElementById('brandConfig');
let brand = JSON.parse(atob(brandConfig.dataset.brand || 'W10='));
const brandStoreUrl = brandConfig.dataset.storeUrl;
const brandUpdateUrl = brandConfig.dataset.updateBrandUrl;
const brandDeleteUrl = brandConfig.dataset.deleteBrandUrl;
const state = {q: '', status: ''};
const csrfToken = brandConfig.dataset.csrf;
function render(){
  const list = brand
    .filter(c => {
      const name = String(c.name || '').toLowerCase();
      const q = state.q.toLowerCase();
      const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
      if (q && !name.includes(q)) return false;
      if (state.status === 'active' && !active) return false;
      if (state.status === 'inactive' && active) return false;
      return true;
    })
    .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có danh mục nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(c => {
    const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
    return `
    <tr>
      <td style="font-weight:500">${UI.escapeHtml(c.name)}</td>
      <td style="text-align:center;font-weight:600">0</td>
      <td><span class="badge-pill ${active ? 'success' : 'muted'}">${active ? 'Hoạt động' : 'Tạm dừng'}</span></td>
      <td style="color:#6b7280;font-size:.85rem">${UI.fmtDate(c.created_at)}</td>
      <td style="text-align:center">
        <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${c.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${c.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
      </td>
    </tr>
  `;
  }).join('');
}

render();

document.getElementById('qInput').addEventListener('input', e => {
  state.q = e.target.value;
  render();
});

document.getElementById('statusFilter').addEventListener('change', e => {
  state.status = e.target.value;
  render();
});
document.getElementById('addBtn').addEventListener('click', () => openForm());
window.onEdit = id => openForm(id);
window.onRemove = id => {
  const brands = brand.find(item => String(item.id) === String(id));
  if (!brands) {
    UI.toast('Không tìm thấy thương hiệu', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa thương <b>${UI.escapeHtml(brands.name)}</b>?`, () => {
    fetch(brandDeleteUrl.replace('ID', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa thương hiệu');
        }
        brand = brand.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa thương hiệu', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
}
function openForm(id)
{
  const c = id ? brand.find(item => String(item.id) === String(id)) : {active: true};
  const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
  const isEdit = !!id;
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Tên thương hiệu<span class="req">*</span></label>
        <input class="form-control" name="name" required value="${UI.escapeHtml(c.name || '')}">
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="active" ${active ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Kích hoạt</span>
        </label>
      </div>
    </div>
  `;
  const modal = UI.openModal({
    title: isEdit ? 'Sửa thương hiệu' : 'Thêm thương hiệu',
    body,
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
     onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => f.querySelector(`[name="${n}"]`).value.trim();
      const name = get('name');
      if (!name) {
        UI.toast('Tên danh mục không được trống', 'danger');
        return false;
      };
      const data = {
        'name' : name,
         active: f.querySelector('[name="active"]').checked
      };
      const requestUrl = isEdit
        ? brandUpdateUrl.replace('ID', encodeURIComponent(id))
        : brandStoreUrl;
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
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu thương hiệu';
            throw new Error(message);
          }
          if (isEdit) {
            brand = brand.map(item => String(item.id) === String(id) ? result.brand : item);
          } else {
            brand.unshift(result.brand);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật thương hiêu' : 'Đã thêm thương hiệu'), 'success');
          render();
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
   
        
  })
}
</script>
@endpush
