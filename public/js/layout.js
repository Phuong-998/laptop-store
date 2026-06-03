/* ============================================================
   layout.js — sidebar, topbar, toast, modal helpers
   ============================================================ */
  function adminRoute(name, fallback = '#') {
  return window.ADMIN_ROUTES?.[name] || fallback;
}
(function(){
  const MENU = [
    {section:'Tổng quan'},
    {key:'dashboard', label:'Dashboard', icon:'bi-speedometer2', href:adminRoute('dashboard')},
    {section:'Bán hàng'},
    {key:'products', label:'Sản phẩm', icon:'bi-laptop', href:'products.html'},
    {key:'categories', label:'Danh mục', icon:'bi-tags', href:adminRoute('category')},
    {key:'brands', label:'Thương hiệu', icon:'bi-award', href:adminRoute('brands')},
    {key:'specs', label:'Thông số kỹ thuật', icon:'bi-sliders', href:'specifications.html'},
    {key:'inventory', label:'Tồn kho', icon:'bi-box-seam', href:'inventory.html'},
    {section:'Khuyến mãi & Vận chuyển'},
    {key:'coupons', label:'Mã giảm giá', icon:'bi-ticket-perforated', href:'coupons.html'},
    {key:'shipping', label:'Phí vận chuyển', icon:'bi-truck', href:'shipping.html'},
    {section:'Hệ thống'},
    {key:'users', label:'Người dùng', icon:'bi-people', href:'users.html'}
  ];

  function renderLayout(opts){
    const active = opts.active || '';
    const pageTitle = opts.title || '';

    const sidebar = document.createElement('aside');
    sidebar.className = 'sidebar';
    sidebar.id = 'sidebar';
    sidebar.innerHTML = `
      <div class="brand">
        <i class="bi bi-laptop"></i> Laptop Admin
      </div>
      <div class="menu">
        ${MENU.map(m=>{
          if(m.section){
            return `<div class="nav-section">${m.section}</div>`;
          }
          return `<a href="${m.href}" class="${active===m.key?'active':''}">
            <i class="bi ${m.icon}"></i>
            <span>${m.label}</span>
          </a>`;
        }).join('')}
      </div>
      
    `;

    const topbar = document.createElement('header');
    topbar.className = 'topbar';
    topbar.innerHTML = `
      <button class="toggle-sidebar" id="toggleSidebar"><i class="bi bi-list"></i></button>
      <h1 class="page-title">${pageTitle}</h1>
      <div class="topbar-actions">
        <button class="icon-btn" title="Thông báo"><i class="bi bi-bell"></i><span class="dot"></span></button>
        <button class="icon-btn" title="Xem website" onclick="window.open('../computer-shop/index.html','_blank')"><i class="bi bi-globe"></i></button>
        <div class="user-chip" id="userChip">
          <div class="avatar">AD</div>
          <div>
            <div class="user-name">Quản trị viên</div>
            <div class="user-role">Super Admin</div>
          </div>
          <i class="bi bi-chevron-down" style="font-size:.85rem;color:#9ca3af"></i>
        </div>
      </div>
    `;

    const toastWrap = document.createElement('div');
    toastWrap.className = 'toast-wrap';
    toastWrap.id = 'toastWrap';

    const main = document.createElement('div');
    main.className = 'main';

    const content = document.querySelector('#page-content');
    const app = document.createElement('div');
    app.className = 'app';

    document.body.insertBefore(app, document.body.firstChild);
    app.appendChild(sidebar);
    app.appendChild(main);
    main.appendChild(topbar);
    if(content){
      content.classList.add('content');
      main.appendChild(content);
    }
    document.body.appendChild(toastWrap);

    document.getElementById('toggleSidebar').addEventListener('click',()=>{
      sidebar.classList.toggle('open');
    });
    document.getElementById('resetSeedBtn').addEventListener('click',(e)=>{
      e.preventDefault();
      if(confirm('Reset toàn bộ dữ liệu về mẫu ban đầu?')){
        DB.resetAll();
        location.reload();
      }
    });
    document.getElementById('userChip').addEventListener('click',()=>{
      if(confirm('Đăng xuất khỏi admin?')){
        // user will wire up to real login. Default redirect:
        location.href = '../login.html';
      }
    });
  }

  /* ----- Toast ----- */
  function toast(message, type='success'){
    const wrap = document.getElementById('toastWrap');
    if(!wrap) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.textContent = message;
    wrap.appendChild(el);
    setTimeout(()=>{
      el.style.transition='opacity .25s ease, transform .25s ease';
      el.style.opacity='0';
      el.style.transform='translateX(10px)';
      setTimeout(()=>el.remove(),250);
    }, 2400);
  }

  /* ----- Modal ----- */
  function openModal(opts){
    let overlay = document.getElementById('appModal');
    if(!overlay){
      overlay = document.createElement('div');
      overlay.className = 'modal-overlay';
      overlay.id = 'appModal';
      document.body.appendChild(overlay);
    }
    const sizeClass = opts.size==='lg' ? 'lg' : '';
    overlay.innerHTML = `
      <div class="modal-box ${sizeClass}">
        <div class="modal-header">
          <h3>${opts.title||''}</h3>
          <button class="close" type="button" aria-label="Đóng">&times;</button>
        </div>
        <div class="modal-body">${opts.body||''}</div>
        <div class="modal-footer">
          <button class="btn btn-outline" data-action="cancel">${opts.cancelText||'Hủy'}</button>
          <button class="btn btn-primary" data-action="confirm">${opts.confirmText||'Lưu'}</button>
        </div>
      </div>
    `;
    overlay.classList.add('show');

    function close(){ overlay.classList.remove('show'); }

    overlay.querySelector('.close').onclick = close;
    overlay.querySelector('[data-action="cancel"]').onclick = close;
    overlay.querySelector('[data-action="confirm"]').onclick = ()=>{
      const ok = opts.onConfirm ? opts.onConfirm(overlay) : true;
      if(ok !== false) close();
    };
    overlay.addEventListener('click',(e)=>{ if(e.target===overlay) close(); }, {once:true});

    if(opts.onOpen) opts.onOpen(overlay);
    return {close, el:overlay};
  }

  function confirmDialog(message, onYes){
    openModal({
      title:'Xác nhận',
      body:`<p style="margin:0">${message}</p>`,
      confirmText:'Xóa', cancelText:'Hủy',
      onConfirm:()=>{ onYes && onYes(); return true; },
      onOpen:(o)=>{ o.querySelector('[data-action="confirm"]').classList.replace('btn-primary','btn-danger'); }
    });
  }

  /* ----- Formatters ----- */
  function fmtMoney(n){
    if(n==null||isNaN(n)) return '—';
    return new Intl.NumberFormat('vi-VN').format(n) + 'đ';
  }
  function fmtDate(ts){
    if(!ts) return '—';
    const d = new Date(ts);
    return d.toLocaleDateString('vi-VN');
  }
  function fmtDateTime(ts){
    if(!ts) return '—';
    return new Date(ts).toLocaleString('vi-VN');
  }
  function escapeHtml(s){
    if(s==null) return '';
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
  }

  /* ----- Pagination helper ----- */
  function paginate(items, page, perPage){
    const total = items.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    page = Math.min(Math.max(1,page), totalPages);
    return {
      total, totalPages, page,
      rows: items.slice((page-1)*perPage, page*perPage)
    };
  }
  function renderPagination(container, current, totalPages, onChange){
    container.innerHTML = '';
    if(totalPages<=1){ container.innerHTML = `<span style="color:#6b7280">Tổng ${current?current:0} trang</span>`; return; }
    const mk = (label, page, disabled, active)=>{
      const b = document.createElement('button');
      b.textContent = label;
      if(disabled) b.disabled = true;
      if(active) b.classList.add('active');
      b.onclick = ()=>onChange(page);
      return b;
    };
    container.appendChild(mk('«', current-1, current===1));
    const win = 2;
    for(let p=1;p<=totalPages;p++){
      if(p===1||p===totalPages||(p>=current-win&&p<=current+win)){
        container.appendChild(mk(p,p,false,p===current));
      } else if(p===current-win-1 || p===current+win+1){
        const dot = document.createElement('span'); dot.textContent='…'; dot.style.padding='0 4px'; container.appendChild(dot);
      }
    }
    container.appendChild(mk('»', current+1, current===totalPages));
  }

  window.UI = {renderLayout, toast, openModal, confirmDialog, fmtMoney, fmtDate, fmtDateTime, escapeHtml, paginate, renderPagination};
})();
