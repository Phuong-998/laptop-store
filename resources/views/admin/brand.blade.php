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
  data-brand="{{ json_encode($brand ?? []) }}"
  hidden
>
</div>
@endsection
@push('scripts')
<script>
const brandConfig = document.getElementById('brandConfig');
console.log(brandConfig);
let brand = JSON.parse(brandConfig?.dataset.brand || 'W10=');
const state = {q: '', status: ''};
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

function openForm(id)
{
  
}
</script>
@endpush
