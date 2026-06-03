@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Quản lý danh mục</h1>
    <div class="breadcrumb">Trang chủ / Danh mục</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm danh mục
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
        <th>Tên danh mục</th>
        <th>Danh mục cha</th>
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
  id="categoryConfig"
  data-categories="{{ base64_encode(json_encode($categories ?? [])) }}"
  data-store-url="{{ route('admin.category.store') }}"
  data-update-url-template="{{ route('admin.category.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.category.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const categoryConfig = document.getElementById('categoryConfig');
let categories = JSON.parse(atob(categoryConfig.dataset.categories || 'W10='));
const categoryStoreUrl = categoryConfig.dataset.storeUrl;
const categoryUpdateUrlTemplate = categoryConfig.dataset.updateUrlTemplate;
const categoryDeleteUrlTemplate = categoryConfig.dataset.deleteUrlTemplate;

const csrfToken = categoryConfig.dataset.csrf;
const state = {q: '', status: ''};

function render(){
  const list = categories
    .filter(c => {
      const name = String(c.name || '').toLowerCase();
      const q = state.q.toLowerCase();
      const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
      if (q && !name.includes(q)) return false;
      if (state.status === 'active' && !active) return false;
      if (state.status === 'inactive' && active) return false;
      return true;
    })
    .sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Chưa có danh mục nào</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(c => {
    const parent = categories.find(item => String(item.id) === String(c.parent_id));
    const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
    return `
    <tr>
      <td style="font-weight:500">${UI.escapeHtml(c.name)}</td>
      <td style="color:#6b7280">${parent ? UI.escapeHtml(parent.name) : '-'}</td>
      <td style="text-align:center;font-weight:600">0</td>
      <td><span class="badge-pill ${active ? 'success' : 'muted'}">${active ? 'Hoạt động' : 'Tạm dừng'}</span></td>
      <td style="color:#6b7280;font-size:.85rem">${UI.fmtDate(c.createdAt)}</td>
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

function openForm(id){
  const c = id ? categories.find(item => String(item.id) === String(id)) : {active: true};
  const isEdit = !!id;
  const active = c.status !== undefined ? Number(c.status) === 1 : Boolean(c.active);
  const parentOptions = categories
    .filter(item => !id || String(item.id) !== String(id))
    .map(item => `<option value="${item.id}" ${String(c.parent_id || '') === String(item.id) ? 'selected' : ''}>${UI.escapeHtml(item.name)}</option>`)
    .join('');
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Tên danh mục <span class="req">*</span></label>
        <input class="form-control" name="name" required value="${UI.escapeHtml(c.name || '')}">
      </div>
      <div class="form-group">
        <label>Danh mục cha</label>
        <select class="form-control" name="parent_id">
          <option value="">Không có</option>
          ${parentOptions}
        </select>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch category-status-switch">
          <input type="checkbox" name="active" ${active ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Kích hoạt</span>
        </label>
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa danh mục' : 'Thêm danh mục',
    body,
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => f.querySelector(`[name="${n}"]`).value.trim();
      const name = get('name');
      if (!name) {
        UI.toast('Tên danh mục không được trống', 'danger');
        return false;
      }

      const data = {
        name,
        parent_id: get('parent_id') || null,
        active: f.querySelector('[name="active"]').checked
      };
      const requestUrl = isEdit
        ? categoryUpdateUrlTemplate.replace('__ID__', encodeURIComponent(id))
        : categoryStoreUrl;

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
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu danh mục';
            throw new Error(message);
          }
          if (isEdit) {
            categories = categories.map(item => String(item.id) === String(id) ? result.category : item);
          } else {
            categories.unshift(result.category);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật danh mục' : 'Đã thêm danh mục'), 'success');
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
  const category = categories.find(item => String(item.id) === String(id));
  if (!category) {
    UI.toast('Không tìm thấy danh mục', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa danh mục <b>${UI.escapeHtml(category.name)}</b>?`, () => {
    fetch(categoryDeleteUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa danh mục');
        }
        categories = categories.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa danh mục', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
