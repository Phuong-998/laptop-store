@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Quản lý tồn kho</h1>
    <div class="breadcrumb">Trang chủ / Tồn kho</div>
  </div>
</div>

<div class="card-soft" style="margin-bottom:16px;display:flex;align-items:center;gap:10px;color:#4b5563;font-size:.9rem">
  <i class="bi bi-info-circle" style="color:#2563c9"></i>
  <span>Màn hình này <b>chỉ theo dõi</b> tồn kho. Mọi biến động số lượng được thực hiện qua <b>Phiếu nhập</b> / <b>Phiếu xuất</b>.</span>
</div>

<div class="row-grid cols-4" id="kpiGrid" style="margin-bottom:16px"></div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow"><input type="search" id="qInput" placeholder="🔍  Tìm sản phẩm..."></div>
    <select id="stockFilter" style="max-width:180px">
      <option value="">Tất cả</option>
      <option value="out">Hết hàng</option>
      <option value="low">Sắp hết</option>
      <option value="in">Còn hàng</option>
    </select>
    <select id="categoryFilter" style="max-width:180px">
      <option value="">Tất cả danh mục</option>
    </select>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th style="width:60px">Ảnh</th>
        <th>Sản phẩm</th>
        <th>Danh mục</th>
        <th style="text-align:right">Tồn hiện tại</th>
        <th style="text-align:right">Ngưỡng cảnh báo</th>
        <th>Trạng thái</th>
        <th>Cập nhật</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>

<div
  id="inventoryConfig"
  data-products="{{ base64_encode(json_encode($products ?? [])) }}"
  data-categories="{{ base64_encode(json_encode($categories ?? [])) }}"
  data-history-url-template="{{ route('admin.inventory.history', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script>
const inventoryConfig = document.getElementById('inventoryConfig');
let products = JSON.parse(atob(inventoryConfig.dataset.products || 'W10='));
const categories = JSON.parse(atob(inventoryConfig.dataset.categories || 'W10='));
const historyUrlTemplate = inventoryConfig.dataset.historyUrlTemplate;
const csrfToken = inventoryConfig.dataset.csrf;

const state = {q: '', stock: '', cat: ''};

document.getElementById('categoryFilter').innerHTML = '<option value="">Tất cả danh mục</option>'
  + categories.map(c => `<option value="${c.id}">${UI.escapeHtml(c.name)}</option>`).join('');

function statusOf(p){
  const stock = Number(p.stock_quantity);
  const threshold = Number(p.low_stock_threshold);
  if (stock === 0) return {key: 'out', label: 'Hết hàng', cls: 'danger', color: '#dc2626'};
  if (stock <= threshold) return {key: 'low', label: 'Sắp hết', cls: 'warning', color: '#f59e0b'};
  return {key: 'in', label: 'Còn hàng', cls: 'success', color: '#16a34a'};
}

function renderKpi(){
  const totalStock = products.reduce((s, p) => s + Number(p.stock_quantity), 0);
  const out = products.filter(p => statusOf(p).key === 'out').length;
  const low = products.filter(p => statusOf(p).key === 'low').length;
  const inStock = products.filter(p => statusOf(p).key === 'in').length;
  const kpis = [
    {label: 'Tổng SP trong kho', value: totalStock.toLocaleString('vi-VN'), icon: 'bi-box-seam', color: 'bg-brand'},
    {label: 'Còn hàng', value: inStock, icon: 'bi-check-circle', color: 'bg-success'},
    {label: 'Sắp hết', value: low, icon: 'bi-exclamation-triangle', color: 'bg-warning'},
    {label: 'Hết hàng', value: out, icon: 'bi-x-circle', color: 'bg-danger'}
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
  const list = products.filter(p => {
    if (state.q) {
      const q = state.q.toLowerCase();
      const name = String(p.name || '').toLowerCase();
      const sku = String(p.sku || '').toLowerCase();
      if (!name.includes(q) && !sku.includes(q)) return false;
    }
    if (state.cat && String(p.category_id) !== String(state.cat)) return false;
    if (state.stock && statusOf(p).key !== state.stock) return false;
    return true;
  }).sort((a, b) => Number(a.stock_quantity) - Number(b.stock_quantity));

  const tbody = document.getElementById('tbody');
  if (list.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Không tìm thấy bản ghi</td></tr>`;
    return;
  }

  tbody.innerHTML = list.map(p => {
    const s = statusOf(p);
    const image = p.image || 'https://via.placeholder.com/42?text=L';
    return `<tr>
      <td><img class="table-thumb" src="${UI.escapeHtml(image)}" onerror="this.src='https://via.placeholder.com/42?text=L'"></td>
      <td>
        <div style="font-weight:500">${UI.escapeHtml(p.name)}</div>
        <div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(p.sku || '')}</div>
      </td>
      <td style="font-size:.85rem">${p.category_name ? UI.escapeHtml(p.category_name) : '—'}</td>
      <td style="text-align:right;font-weight:700;font-size:1.05rem;color:${s.color}">${p.stock_quantity}</td>
      <td style="text-align:right;color:#6b7280">${p.low_stock_threshold}</td>
      <td><span class="badge-pill ${s.cls}">${s.label}</span></td>
      <td style="color:#6b7280;font-size:.82rem">${UI.fmtDateTime(p.update_at)}</td>
      <td style="text-align:center;white-space:nowrap">
        <button class="btn btn-sm btn-outline" title="Lịch sử biến động" onclick="showHistory('${p.id}')"><i class="bi bi-clock-history"></i> Lịch sử</button>
      </td>
    </tr>`;
  }).join('');
}
render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; render();});
document.getElementById('stockFilter').addEventListener('change', e => {state.stock = e.target.value; render();});
document.getElementById('categoryFilter').addEventListener('change', e => {state.cat = e.target.value; render();});

window.showHistory = productId => {
  const product = products.find(p => String(p.id) === String(productId));
  if (!product) return;

  fetch(historyUrlTemplate.replace('__ID__', encodeURIComponent(productId)), {
    headers: {'Accept': 'application/json'}
  })
    .then(async response => {
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.message || 'Không thể tải lịch sử');
      const history = result.history || [];
      const image = product.image || 'https://via.placeholder.com/50';
      const body = `
        <div style="display:flex;gap:14px;align-items:center;padding:14px;background:#f9fafb;border-radius:8px;margin-bottom:16px">
          <img src="${UI.escapeHtml(image)}" class="table-thumb" style="width:50px;height:50px" onerror="this.src='https://via.placeholder.com/50'">
          <div>
            <div style="font-weight:500">${UI.escapeHtml(product.name)}</div>
            <div style="font-size:.82rem;color:#6b7280">Tồn hiện tại: <b>${product.stock_quantity}</b></div>
          </div>
        </div>
        <div class="table-wrap" style="box-shadow:none">
          <table>
            <thead><tr><th>Thời gian</th><th>Loại</th><th style="text-align:right">Thay đổi</th><th style="text-align:right">Tồn sau</th><th>Ghi chú</th></tr></thead>
            <tbody>
              ${history.length === 0
                ? '<tr><td colspan="5" class="empty">Chưa có biến động</td></tr>'
                : history.map(h => `<tr>
                    <td style="font-size:.85rem;color:#6b7280">${UI.fmtDateTime(h.date)}</td>
                    <td><span class="badge-pill ${h.type === 'in' ? 'success' : 'warning'}">${h.type === 'in' ? 'Nhập' : 'Xuất'}</span></td>
                    <td style="text-align:right;font-weight:600;color:${h.type === 'in' ? '#16a34a' : '#f59e0b'}">${h.type === 'in' ? '+' : '-'}${h.quantity}</td>
                    <td style="text-align:right;font-weight:600">${h.after}</td>
                    <td style="font-size:.88rem">${UI.escapeHtml(h.note || '')}</td>
                  </tr>`).join('')}
            </tbody>
          </table>
        </div>
      `;
      const modal = UI.openModal({
        title: 'Lịch sử biến động kho',
        body,
        size: 'lg',
        confirmText: 'Đóng',
        onConfirm: () => {modal.close(); return false;},
        onOpen: o => {o.querySelector('[data-action="cancel"]').style.display = 'none';}
      });
    })
    .catch(error => UI.toast(error.message, 'danger'));
};
</script>
@endpush
