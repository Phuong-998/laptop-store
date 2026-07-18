@extends('admin.layout.layouts')

@section('conten')
<div class="page-header">
  <div>
    <h1>Quản lý sản phẩm</h1>
    <div class="breadcrumb">Trang chủ / Sản phẩm</div>
  </div>
  <button class="btn btn-primary" id="addBtn" type="button">
    <i class="bi bi-plus-lg"></i> Thêm sản phẩm
  </button>
</div>

<div class="card-soft" style="margin-bottom:16px">
  <div class="table-toolbar">
    <div class="grow">
      <input type="search" id="qInput" placeholder="🔍  Tìm theo tên, SKU...">
    </div>
    <select id="categoryFilter" style="max-width:180px">
      <option value="">Tất cả danh mục</option>
    </select>
    <select id="brandFilter" style="max-width:180px">
      <option value="">Tất cả thương hiệu</option>
    </select>
    <select id="statusFilter" style="max-width:140px">
      <option value="">Tất cả trạng thái</option>
      <option value="active">Đang bán</option>
      <option value="inactive">Ngừng bán</option>
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
        <th>Thương hiệu</th>
        <th>Giá gốc</th>
        <th>Giá bán</th>
        <th>Tồn</th>
        <th>Trạng thái</th>
        <th style="width:130px;text-align:center">Thao tác</th>
      </tr>
    </thead>
    <tbody id="tbody"></tbody>
  </table>
</div>
<div class="pagination" id="pager"></div>

<div
  id="productConfig"
  data-products="{{ base64_encode(json_encode($products ?? [])) }}"
  data-categories="{{ base64_encode(json_encode($categories ?? [])) }}"
  data-brands="{{ base64_encode(json_encode($brands ?? [])) }}"
  data-store-url="{{ route('admin.product.store') }}"
  data-update-url-template="{{ route('admin.product.update', ['id' => '__ID__']) }}"
  data-delete-url-template="{{ route('admin.product.delete', ['id' => '__ID__']) }}"
  data-csrf="{{ csrf_token() }}"
  hidden
></div>
@endsection

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
let editor = null;
const productConfig = document.getElementById('productConfig');
let products = JSON.parse(atob(productConfig.dataset.products || 'W10='));
const categories = JSON.parse(atob(productConfig.dataset.categories || 'W10='));
const brands = JSON.parse(atob(productConfig.dataset.brands || 'W10='));
const productStoreUrl = productConfig.dataset.storeUrl;
const productUpdateUrlTemplate = productConfig.dataset.updateUrlTemplate;
const productDeleteUrlTemplate = productConfig.dataset.deleteUrlTemplate;
const csrfToken = productConfig.dataset.csrf;

const state = {q: '', cat: '', brand: '', status: '', page: 1, perPage: 8};

document.getElementById('categoryFilter').innerHTML = '<option value="">Tất cả danh mục</option>'
  + categories.map(c => `<option value="${c.id}">${UI.escapeHtml(c.name)}</option>`).join('');
document.getElementById('brandFilter').innerHTML = '<option value="">Tất cả thương hiệu</option>'
  + brands.map(b => `<option value="${b.id}">${UI.escapeHtml(b.name)}</option>`).join('');

function isActive(p){
  return p.status !== undefined ? Number(p.status) === 1 : Boolean(p.active);
}

function applyFilters(){
  return products.filter(p => {
    if (state.q) {
      const q = state.q.toLowerCase();
      const name = String(p.name || '').toLowerCase();
      const sku = String(p.sku || '').toLowerCase();
      if (!name.includes(q) && !sku.includes(q)) return false;
    }
    if (state.cat && String(p.category_id) !== String(state.cat)) return false;
    if (state.brand && String(p.branch_id) !== String(state.brand)) return false;
    if (state.status === 'active' && !isActive(p)) return false;
    if (state.status === 'inactive' && isActive(p)) return false;
    return true;
  }).sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
}

function render(){
  const all = applyFilters();
  const {rows, totalPages, page} = UI.paginate(all, state.page, state.perPage);
  const tbody = document.getElementById('tbody');

  if (rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="9" class="empty"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:6px"></i>Không tìm thấy sản phẩm nào</td></tr>`;
  } else {
    tbody.innerHTML = rows.map(p => {
      const cat = categories.find(c => String(c.id) === String(p.category_id));
      const br = brands.find(b => String(b.id) === String(p.branch_id));
      const active = isActive(p);
      const stock = Number(p.stock_quantity || 0);
      const image = p.image || 'https://via.placeholder.com/42?text=L';
      return `<tr>
        <td><img class="table-thumb" src="${UI.escapeHtml(image)}" onerror="this.src='https://via.placeholder.com/42?text=L'"></td>
        <td>
          <div style="font-weight:500">${UI.escapeHtml(p.name)}</div>
          <div style="font-size:.78rem;color:#6b7280">${UI.escapeHtml(p.sku || '')}</div>
        </td>
        <td>${cat ? UI.escapeHtml(cat.name) : '—'}</td>
        <td>${br ? UI.escapeHtml(br.name) : '—'}</td>
        <td style="color:#6b7280;text-decoration:line-through">${UI.fmtMoney(p.price)}</td>
        <td style="font-weight:600;color:#dc2626">${UI.fmtMoney(p.sale_price || p.price)}</td>
        <td>${stock <= 10 ? `<span style="color:${stock === 0 ? '#dc2626' : '#f59e0b'};font-weight:600">${stock}</span>` : stock}</td>
        <td><span class="badge-pill ${active ? 'success' : 'muted'}">${active ? 'Đang bán' : 'Ngừng bán'}</span></td>
        <td style="text-align:center">
          <button class="btn btn-icon btn-outline" title="Sửa" onclick="onEdit('${p.id}')"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-icon btn-outline" title="Xóa" onclick="onRemove('${p.id}')"><i class="bi bi-trash" style="color:#dc2626"></i></button>
        </td>
      </tr>`;
    }).join('');
  }

  UI.renderPagination(document.getElementById('pager'), page, totalPages, p => {state.page = p; render();});
}

render();

document.getElementById('qInput').addEventListener('input', e => {state.q = e.target.value; state.page = 1; render();});
document.getElementById('categoryFilter').addEventListener('change', e => {state.cat = e.target.value; state.page = 1; render();});
document.getElementById('brandFilter').addEventListener('change', e => {state.brand = e.target.value; state.page = 1; render();});
document.getElementById('statusFilter').addEventListener('change', e => {state.status = e.target.value; state.page = 1; render();});
document.getElementById('addBtn').addEventListener('click', () => openForm());

function openForm(id){
  const p = id ? products.find(item => String(item.id) === String(id)) : {status: 1};
  const isEdit = !!id;
  const active = isActive(p);
  const removedImages = new Set();
  const existingImages = (p.images || []).map(im =>
    `<div class="existing-image" data-id="${im.id}" style="position:relative;display:inline-block">
      <img src="${UI.escapeHtml(im.url)}" style="width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">
      <button type="button" class="remove-existing-image" data-id="${im.id}" title="Xóa ảnh"
        style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;padding:0;border:none;border-radius:50%;background:#dc2626;color:#fff;font-size:12px;line-height:16px;cursor:pointer">&times;</button>
    </div>`
  ).join('');
  const body = `
    <div class="form-grid">
      <div class="form-group">
        <label>Tên sản phẩm <span class="req">*</span></label>
        <input class="form-control" name="name" required value="${UI.escapeHtml(p.name || '')}">
      </div>
      <div class="form-group">
        <label>SKU</label>
        <input class="form-control" name="sku" value="${UI.escapeHtml(p.sku || '')}">
      </div>
      <div class="form-group">
        <label>Danh mục <span class="req">*</span></label>
        <select class="form-control" name="category_id" required>
          <option value="">— Chọn —</option>
          ${categories.map(c => `<option value="${c.id}" ${String(p.category_id || '') === String(c.id) ? 'selected' : ''}>${UI.escapeHtml(c.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Thương hiệu <span class="req">*</span></label>
        <select class="form-control" name="branch_id" required>
          <option value="">— Chọn —</option>
          ${brands.map(b => `<option value="${b.id}" ${String(p.branch_id || '') === String(b.id) ? 'selected' : ''}>${UI.escapeHtml(b.name)}</option>`).join('')}
        </select>
      </div>
      <div class="form-group">
        <label>Giá gốc (đ) <span class="req">*</span></label>
        <input class="form-control" type="number" name="price" required value="${p.price || ''}">
      </div>
      <div class="form-group">
        <label>Giá bán (đ)</label>
        <input class="form-control" type="number" name="sale_price" value="${p.sale_price || ''}">
      </div>
      <div class="form-group">
        <label>Tồn kho</label>
        <input class="form-control" type="number" name="stock_quantity" value="${p.stock_quantity || 0}">
      </div>
      <div class="form-group">
        <label>Bảo hành</label>
        <input class="form-control" name="warranty" value="${UI.escapeHtml(p.warranty || '')}" placeholder="VD: 12 tháng">
      </div>
      <div class="form-group full">
        <label>Hình ảnh sản phẩm</label>
        <input class="form-control" type="file" name="images" id="productImages" accept="image/*" multiple>
        <small style="color:#6b7280">Có thể chọn nhiều ảnh cùng lúc. Ảnh đầu tiên sẽ là ảnh đại diện.</small>
        ${existingImages ? `<div style="margin-top:8px">
          <div style="font-size:.78rem;color:#6b7280;margin-bottom:4px">Ảnh hiện có</div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">${existingImages}</div>
        </div>` : ''}
        <div id="imagePreview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px"></div>
      </div>
      <div class="form-group full">
        <label>Mô tả</label>
        <textarea class="form-control" name="description" id="productDescription">${UI.escapeHtml(p.description || '')}</textarea>
      </div>
      <div class="form-group" style="display:flex;align-items:flex-end">
        <label class="switch status-switch">
          <input type="checkbox" name="status" ${active ? 'checked' : ''}>
          <span class="track"></span>
          <span class="switch-label">Đang bán</span>
        </label>
      </div>
    </div>
  `;

  const modal = UI.openModal({
    title: isEdit ? 'Sửa sản phẩm' : 'Thêm sản phẩm',
    body,
    size: 'lg',
    confirmText: isEdit ? 'Cập nhật' : 'Tạo mới',
    onOpen: overlay => {
      // CKEditor cho phần mô tả
      if (editor) { editor.destroy().catch(() => {}); editor = null; }
      const textarea = overlay.querySelector('#productDescription');
      if (window.ClassicEditor && textarea) {
        ClassicEditor.create(textarea).then(ed => { editor = ed; }).catch(() => {});
      }
      // Xóa từng ảnh cũ
      overlay.querySelectorAll('.remove-existing-image').forEach(btn => {
        btn.addEventListener('click', () => {
          removedImages.add(btn.dataset.id);
          const wrap = btn.closest('.existing-image');
          if (wrap) wrap.remove();
        });
      });
      // Preview các ảnh vừa chọn
      const fileInput = overlay.querySelector('#productImages');
      const preview = overlay.querySelector('#imagePreview');
      if (fileInput && preview) {
        fileInput.addEventListener('change', () => {
          preview.innerHTML = '';
          Array.from(fileInput.files).forEach(file => {
            const img = document.createElement('img');
            img.style.cssText = 'width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #93c5fd';
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
          });
        });
      }
    },
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

      const fd = new FormData();
      fd.append('name', name);
      fd.append('sku', get('sku'));
      fd.append('category_id', categoryId);
      fd.append('branch_id', branchId);
      fd.append('price', price);
      if (get('sale_price')) fd.append('sale_price', get('sale_price'));
      fd.append('stock_quantity', get('stock_quantity') || '0');
      fd.append('warranty', get('warranty'));
      fd.append('description', editor ? editor.getData() : get('description'));
      fd.append('status', f.querySelector('[name="status"]').checked ? '1' : '0');

      const fileInput = f.querySelector('#productImages');
      if (fileInput && fileInput.files.length) {
        Array.from(fileInput.files).forEach(file => fd.append('images[]', file));
      }
      removedImages.forEach(rid => fd.append('removed_images[]', rid));

      const requestUrl = isEdit
        ? productUpdateUrlTemplate.replace('__ID__', encodeURIComponent(id))
        : productStoreUrl;

      fetch(requestUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: fd
      })
        .then(async response => {
          const result = await response.json().catch(() => ({}));
          if (!response.ok) {
            const message = result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể lưu sản phẩm';
            throw new Error(message);
          }
          if (isEdit) {
            products = products.map(item => String(item.id) === String(id) ? result.product : item);
          } else {
            products.unshift(result.product);
          }
          UI.toast(result.message || (isEdit ? 'Đã cập nhật sản phẩm' : 'Đã thêm sản phẩm'), 'success');
          state.page = 1;
          render();
          if (editor) { editor.destroy().catch(() => {}); editor = null; }
          modal.close();
        })
        .catch(error => UI.toast(error.message, 'danger'));

      return false;
    }
  });
}

window.onEdit = id => openForm(id);
window.onRemove = id => {
  const product = products.find(item => String(item.id) === String(id));
  if (!product) {
    UI.toast('Không tìm thấy sản phẩm', 'danger');
    return;
  }

  UI.confirmDialog(`Xóa sản phẩm <b>${UI.escapeHtml(product.name)}</b>? Hành động không thể hoàn tác.`, () => {
    fetch(productDeleteUrlTemplate.replace('__ID__', encodeURIComponent(id)), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      }
    })
      .then(async response => {
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
          throw new Error(result.message || 'Không thể xóa sản phẩm');
        }
        products = products.filter(item => String(item.id) !== String(id));
        UI.toast(result.message || 'Đã xóa sản phẩm', 'success');
        render();
      })
      .catch(error => UI.toast(error.message, 'danger'));
  });
};
</script>
@endpush
