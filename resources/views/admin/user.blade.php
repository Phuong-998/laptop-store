@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Quản lý người dùng</h1>
    <div class="breadcrumb">Trang chủ / Người dùng</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm người dùng
  </button>
</div>

<div class="row-grid cols-4" id="kpiGrid" style="margin-bottom:16px"></div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm theo tên, email, số điện thoại..."></div>
    <select id="roleFilter" style="max-width:160px">
      <option value="">Tất cả vai trò</option>
      <option value="user">Khách hàng</option>
      <option value="staff">Nhân viên</option>
      <option value="admin">Admin</option>
    </select>
    <select id="statusFilter" style="max-width:160px">
      <option value="">Tất cả trạng thái</option>
      <option value="active">Hoạt động</option>
      <option value="inactive">Không hoạt động</option>
      <option value="banned">Bị chặn</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Người dùng</th>
        <th>Liên hệ</th>
        <th>Vai trò</th>
        <th>Ngày đăng ký</th>
        <th>Trạng thái</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>
<div class="pagination" id="pager"></div>

<div
  id="userConfig"
  data-users="{{ base64_encode(json_encode($users ?? [])) }}"
  data-store-url="{{ route('admin.user.store') }}"
  data-update-url-template="{{ route('admin.user.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.user.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const userConfig = document.getElementById('userConfig');
let users = JSON.parse(atob(userConfig.dataset.users || 'W10='));
const userStoreUrl = userConfig.dataset.storeUrl;
const userUpdateUrlTemplate = userConfig.dataset.updateUrlTemplate;
const userDeleteUrlTemplate = userConfig.dataset.deleteUrlTemplate;
const csrfToken = userConfig.dataset.csrf;

const state = {q: '', role: '', status: '', page: 1, perPage: 10};

const roleBadge = {
  user: '<span class="badge-pill info">Khách hàng</span>',
  staff: '<span class="badge-pill warning">Nhân viên</span>',
  admin: '<span class="badge-pill danger">Admin</span>'
};
const statusBadge = {
  active: '<span class="badge-pill success">Hoạt động</span>',
  inactive: '<span class="badge-pill muted">Không HĐ</span>',
  banned: '<span class="badge-pill danger">Bị chặn</span>'
};

function initials(name){
  return String(name || '?').split(/\s+/).map(s => s[0] || '').slice(-2).join('').toUpperCase();
}

function renderKpi(){
  const kpis = [
    {label: 'Tổng người dùng', value: users.length, icon: 'bi-people', color: 'bg-brand'},
    {label: 'Khách hàng', value: users.filter(u => u.role === 'user').length, icon: 'bi-person', color: 'bg-success'},
    {label: 'Nhân viên', value: users.filter(u => u.role === 'staff').length, icon: 'bi-person-badge', color: 'bg-warning'},
    {label: 'Admin', value: users.filter(u => u.role === 'admin').length, icon: 'bi-shield-lock', color: 'bg-purple'}
  ];
  document.getElementById('kpiGrid').innerHTML = kpis.map(k => `
    <div class="card-soft kpi">
      <div class="kpi-icon ${k.color}"><i class="bi ${k.icon}"></i></div>
      <div><div class="kpi-label">${k.label}</div><div class="kpi-value">${k.value}</div></div>
    </div>
  `).join('');
}
renderKpi();

function render(){
  const all = users.filter(u => {
    if (state.q) {
      const q = state.q.toLowerCase();
      const name = String(u.name || '').toLowerCase();
      const email = String(u.email || '').toLowerCase();
      const phone = String(u.phone || '');
      if (!name.includes(q) && !email.includes(q) && !phone.includes(state.q)) return false;
    }
    if (state.role && u.role !== state.role) return false;
    if (state.status && u.status !== state.status) return false;
    return true;
  }).sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));

  const {rows, totalPages, page} = UI.paginate(all, state.page, state.perPage);
  const tbody = document.getElementById('tbody');
  if (rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Không tìm thấy người dùng</td></tr>`;
  } else {
    tbody.innerHTML = rows.map(u => `<tr>
      <td>
        <div style="display:flex;gap:10px;align-items:center">
          <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:.85rem">${initials(u.name)}</div>
          <div>
            <div style="font-weight:500">${UI.escapeHtml(u.name)}</div>
            <div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(u.address || '—')}</div>
          </div>
        </div>
      </td>
      <td>
        <div style="font-size:.88rem"><i class="bi bi-envelope" style="color:#6b7280"></i> ${UI.escapeHtml(u.email)}</div>
        <div style="font-size:.82rem;color:#6b7280"><i class="bi bi-telephone"></i> ${UI.escapeHtml(u.phone || '—')}</div>
      </td>
      <td>${roleBadge[u.role] || UI.escapeHtml(u.role)}</td>
      <td style="color:#6b7280;font-size:.85rem">${UI.fmtDate(u.created_at)}</td>
      <td>${statusBadge[u.status] || UI.escapeHtml(u.status)}</td>
      <td style="text-align:center">
        <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${u.id}')"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${u.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
      </td>
    </tr>`).join('');
  }
  UI.renderPagination(document.getElementById('pager'), page, totalPages, p => {state.page = p; render();});
}
render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; state.page = 1; render();});
document.getElementById('roleFilter').addEventListener('change', e => {state.role = e.target.value; state.page = 1; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; state.page = 1; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(id){
  const u = id ? users.find(item => String(item.id) === String(id)) : {role: 'user', status: 'active'};
  const isEdit = !!id;
  const body = `
    <div class="form-grid">
      <div class="form-group full">
        <label>Họ và tên <span class="req">*</span></label>
        <input class="form-control" name="name" required value="${UI.escapeHtml(u.name || '')}">
      </div>
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input class="form-control" type="email" name="email" required value="${UI.escapeHtml(u.email || '')}">
      </div>
      <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" name="phone" value="${UI.escapeHtml(u.phone || '')}">
      </div>
      <div class="form-group full">
        <label>Địa chỉ</label>
        <input class="form-control" name="address" value="${UI.escapeHtml(u.address || '')}">
      </div>
      <div class="form-group">
        <label>Vai trò</label>
        <select class="form-control" name="role">
          <option value="user" ${u.role === 'user' ? 'selected' : ''}>Khách hàng</option>
          <option value="staff" ${u.role === 'staff' ? 'selected' : ''}>Nhân viên</option>
          <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Trạng thái</label>
        <select class="form-control" name="status">
          <option value="active" ${u.status === 'active' ? 'selected' : ''}>Hoạt động</option>
          <option value="inactive" ${u.status === 'inactive' ? 'selected' : ''}>Không hoạt động</option>
          <option value="banned" ${u.status === 'banned' ? 'selected' : ''}>Chặn</option>
        </select>
      </div>
      <div class="form-group full">
        <label>Mật khẩu ${isEdit ? '' : '<span class="req">*</span>'}</label>
        <input class="form-control" type="password" name="password" placeholder="${isEdit ? 'Để trống nếu không đổi' : 'Tối thiểu 6 ký tự'}">
        ${isEdit ? '<div class="form-hint">Chỉ nhập khi muốn đặt lại mật khẩu</div>' : ''}
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa người dùng' : 'Thêm người dùng',
    body,
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onConfirm: overlay => {
      const f = overlay.querySelector('.modal-body');
      const get = n => {const el = f.querySelector(`[name="${n}"]`); return el ? el.value.trim() : '';};

      const name = get('name');
      const email = get('email');
      const password = get('password');
      if (!name || !email) {
        UI.toast('Họ tên và email là bắt buộc', 'danger');
        return false;
      }
      if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
        UI.toast('Email không hợp lệ', 'danger');
        return false;
      }
      if (!isEdit && password.length < 6) {
        UI.toast('Mật khẩu tối thiểu 6 ký tự', 'danger');
        return false;
      }

      const data = {
        name,
        email,
        phone: get('phone'),
        address: get('address'),
        role: get('role'),
        status: get('status')
      };
      if (password) data.password = password;

      const requestUrl = isEdit
        ? userUpdateUrlTemplate.replace('__ID__', encodeURIComponent(id))
        : userStoreUrl;

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
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu người dùng';
            throw new Error(message);
          }
          if (isEdit) {
            users = users.map(item => String(item.id) === String(id) ? result.user : item);
          } else {
            users.unshift(result.user);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật người dùng' : 'Đã thêm người dùng'), 'success');
          state.page = 1;
          renderKpi();
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
  const user = users.find(item => String(item.id) === String(id));
  if (!user) {
    UI.toast('Không tìm thấy người dùng', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa người dùng <b>${UI.escapeHtml(user.name)}</b>?`, () => {
    fetch(userDeleteUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa người dùng');
        }
        users = users.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa người dùng', 'success');
        renderKpi();
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
