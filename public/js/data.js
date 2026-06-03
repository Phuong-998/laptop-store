/* ============================================================
   data.js — localStorage CRUD layer + seed data
   ============================================================ */
(function(global){
  const KEYS = {
    categories: 'ls_admin_categories',
    brands: 'ls_admin_brands',
    specs: 'ls_admin_specs',
    products: 'ls_admin_products',
    coupons: 'ls_admin_coupons',
    shipping: 'ls_admin_shipping',
    users: 'ls_admin_users',
    inventory: 'ls_admin_inventory',
    initialized: 'ls_admin_initialized_v1'
  };

  function read(key, fallback){
    try{
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : (fallback ?? []);
    }catch(e){ return fallback ?? []; }
  }
  function write(key, value){ localStorage.setItem(key, JSON.stringify(value)); }
  function uid(prefix){ return (prefix||'id') + '_' + Date.now().toString(36) + Math.floor(Math.random()*1000).toString(36); }

  /* ----- Seed ----- */
  function seed(){
    if(localStorage.getItem(KEYS.initialized) === '1') return;

    const categories = [
      {id:'cat_gaming', name:'Laptop Gaming', description:'Laptop chuyên game đồ họa cao', icon:'bi-controller', active:true, createdAt:Date.now()-86400000*30},
      {id:'cat_office', name:'Laptop Văn phòng', description:'Laptop mỏng nhẹ phục vụ công việc', icon:'bi-briefcase', active:true, createdAt:Date.now()-86400000*25},
      {id:'cat_design', name:'Laptop Đồ họa', description:'Cấu hình mạnh cho thiết kế, dựng phim', icon:'bi-palette', active:true, createdAt:Date.now()-86400000*20},
      {id:'cat_student', name:'Laptop Sinh viên', description:'Cấu hình vừa đủ, giá hợp lý', icon:'bi-book', active:true, createdAt:Date.now()-86400000*15},
      {id:'cat_business', name:'Laptop Doanh nhân', description:'Bảo mật cao, độ bền cao', icon:'bi-suitcase', active:true, createdAt:Date.now()-86400000*10}
    ];

    const brands = [
      {id:'brand_dell', name:'Dell', country:'USA', logo:'https://logo.clearbit.com/dell.com', website:'https://dell.com', active:true},
      {id:'brand_hp', name:'HP', country:'USA', logo:'https://logo.clearbit.com/hp.com', website:'https://hp.com', active:true},
      {id:'brand_lenovo', name:'Lenovo', country:'China', logo:'https://logo.clearbit.com/lenovo.com', website:'https://lenovo.com', active:true},
      {id:'brand_asus', name:'ASUS', country:'Taiwan', logo:'https://logo.clearbit.com/asus.com', website:'https://asus.com', active:true},
      {id:'brand_acer', name:'Acer', country:'Taiwan', logo:'https://logo.clearbit.com/acer.com', website:'https://acer.com', active:true},
      {id:'brand_msi', name:'MSI', country:'Taiwan', logo:'https://logo.clearbit.com/msi.com', website:'https://msi.com', active:true},
      {id:'brand_apple', name:'Apple', country:'USA', logo:'https://logo.clearbit.com/apple.com', website:'https://apple.com', active:true},
      {id:'brand_lg', name:'LG', country:'Korea', logo:'https://logo.clearbit.com/lg.com', website:'https://lg.com', active:true}
    ];

    const specs = [
      {id:'spec_cpu', name:'CPU', group:'Hiệu năng', unit:'', dataType:'text', required:true},
      {id:'spec_ram', name:'RAM', group:'Hiệu năng', unit:'GB', dataType:'number', required:true},
      {id:'spec_storage', name:'Ổ cứng', group:'Lưu trữ', unit:'GB', dataType:'text', required:true},
      {id:'spec_gpu', name:'Card đồ họa', group:'Hiệu năng', unit:'', dataType:'text', required:false},
      {id:'spec_screen', name:'Màn hình', group:'Hiển thị', unit:'inch', dataType:'text', required:true},
      {id:'spec_resolution', name:'Độ phân giải', group:'Hiển thị', unit:'', dataType:'text', required:false},
      {id:'spec_refresh', name:'Tần số quét', group:'Hiển thị', unit:'Hz', dataType:'number', required:false},
      {id:'spec_battery', name:'Pin', group:'Khác', unit:'Wh', dataType:'number', required:false},
      {id:'spec_weight', name:'Khối lượng', group:'Khác', unit:'kg', dataType:'number', required:false},
      {id:'spec_os', name:'Hệ điều hành', group:'Khác', unit:'', dataType:'text', required:false},
      {id:'spec_ports', name:'Cổng kết nối', group:'Khác', unit:'', dataType:'text', required:false}
    ];

    const products = [
      {
        id:'p_001', sku:'DEL-G15-001', name:'Dell Gaming G15 5530',
        categoryId:'cat_gaming', brandId:'brand_dell',
        price:32990000, salePrice:29990000, status:'active', featured:true,
        thumbnail:'https://i.dell.com/sites/csimages/Banner_Imagery/all/g15-non-touch-laptop-pdp.jpg',
        description:'Laptop gaming hiệu năng cao với CPU Intel Core i7 và GPU RTX 4060.',
        specs:{cpu:'Intel Core i7-13650HX', ram:'16', storage:'512GB SSD', gpu:'NVIDIA RTX 4060 8GB', screen:'15.6"', resolution:'1920x1080', refresh:'165', battery:'86', weight:'2.81', os:'Windows 11'},
        createdAt:Date.now()-86400000*20
      },
      {
        id:'p_002', sku:'HP-PAV-014', name:'HP Pavilion 14',
        categoryId:'cat_office', brandId:'brand_hp',
        price:19990000, salePrice:18490000, status:'active', featured:false,
        thumbnail:'https://www.hp.com/content/dam/sites/worldwide/laptops/pavilion-14-eh1000.png',
        description:'Laptop văn phòng mỏng nhẹ, pin lâu, thiết kế sang trọng.',
        specs:{cpu:'Intel Core i5-1335U', ram:'8', storage:'512GB SSD', gpu:'Intel Iris Xe', screen:'14"', resolution:'1920x1200', refresh:'60', battery:'51', weight:'1.41', os:'Windows 11'},
        createdAt:Date.now()-86400000*18
      },
      {
        id:'p_003', sku:'LEN-LOQ-001', name:'Lenovo LOQ 15IRH8',
        categoryId:'cat_gaming', brandId:'brand_lenovo',
        price:24990000, salePrice:22990000, status:'active', featured:true,
        thumbnail:'https://p3-ofp.static.pub//fes/cms/2023/04/19/u8nckcd6sjj5h0lkrxepj9ku1cd6yc551957.png',
        description:'Laptop gaming sinh viên cấu hình mạnh giá hợp lý.',
        specs:{cpu:'Intel Core i5-13420H', ram:'16', storage:'512GB SSD', gpu:'NVIDIA RTX 3050 6GB', screen:'15.6"', resolution:'1920x1080', refresh:'144', battery:'60', weight:'2.4', os:'Windows 11'},
        createdAt:Date.now()-86400000*15
      },
      {
        id:'p_004', sku:'ASU-VIV-016', name:'ASUS Vivobook 16',
        categoryId:'cat_student', brandId:'brand_asus',
        price:15990000, salePrice:14490000, status:'active', featured:false,
        thumbnail:'https://dlcdnwebimgs.asus.com/gain/27086d36-a6e2-4f99-b574-cd2f37b6c01b/',
        description:'Laptop sinh viên màn hình 16 inch, hiệu năng ổn định.',
        specs:{cpu:'Intel Core i3-1215U', ram:'8', storage:'512GB SSD', gpu:'Intel UHD Graphics', screen:'16"', resolution:'1920x1200', refresh:'60', battery:'42', weight:'1.88', os:'Windows 11'},
        createdAt:Date.now()-86400000*12
      },
      {
        id:'p_005', sku:'APP-MBA-M2', name:'MacBook Air M2 13"',
        categoryId:'cat_business', brandId:'brand_apple',
        price:28990000, salePrice:26990000, status:'active', featured:true,
        thumbnail:'https://store.storeimages.cdn-apple.com/4982/as-images.apple.com/is/macbook-air-midnight-select-20220606',
        description:'MacBook Air M2 thế hệ mới, mỏng nhẹ, pin trâu.',
        specs:{cpu:'Apple M2 8-core', ram:'8', storage:'256GB SSD', gpu:'Apple GPU 8-core', screen:'13.6"', resolution:'2560x1664', refresh:'60', battery:'52.6', weight:'1.24', os:'macOS'},
        createdAt:Date.now()-86400000*10
      },
      {
        id:'p_006', sku:'MSI-KTN-A12', name:'MSI Katana 15 B12',
        categoryId:'cat_gaming', brandId:'brand_msi',
        price:25990000, salePrice:23990000, status:'active', featured:false,
        thumbnail:'https://asset.msi.com/resize/image/global/product/product_16567569571c1c2d59b65fc46abe79c40ec25a1e8c.png62405b38c58fe0f07fcef2367d8a9ba1/600.png',
        description:'Gaming laptop đỉnh cao với màn 144Hz và GPU RTX.',
        specs:{cpu:'Intel Core i7-12650H', ram:'16', storage:'1TB SSD', gpu:'NVIDIA RTX 4050 6GB', screen:'15.6"', resolution:'1920x1080', refresh:'144', battery:'53.5', weight:'2.25', os:'Windows 11'},
        createdAt:Date.now()-86400000*8
      },
      {
        id:'p_007', sku:'ACE-NIT-V15', name:'Acer Nitro V15',
        categoryId:'cat_gaming', brandId:'brand_acer',
        price:22990000, salePrice:20990000, status:'inactive', featured:false,
        thumbnail:'https://images.acer.com/is/image/acer/Acer-Nitro-V15-ANV15-51-modelpreview',
        description:'Acer Nitro V15 — cấu hình gaming cân bằng giá tốt.',
        specs:{cpu:'Intel Core i5-13420H', ram:'8', storage:'512GB SSD', gpu:'NVIDIA RTX 3050 4GB', screen:'15.6"', resolution:'1920x1080', refresh:'144', battery:'57', weight:'2.1', os:'Windows 11'},
        createdAt:Date.now()-86400000*5
      },
      {
        id:'p_008', sku:'LEN-TPK-X1', name:'Lenovo ThinkPad X1 Carbon Gen 11',
        categoryId:'cat_business', brandId:'brand_lenovo',
        price:45990000, salePrice:42990000, status:'active', featured:true,
        thumbnail:'https://p3-ofp.static.pub//fes/cms/2023/03/16/ftkv2l1u5j5w1cgu3l3vc4cgmaqixa251952.png',
        description:'Laptop doanh nhân cao cấp với độ bền chuẩn quân đội.',
        specs:{cpu:'Intel Core i7-1365U', ram:'16', storage:'1TB SSD', gpu:'Intel Iris Xe', screen:'14"', resolution:'1920x1200', refresh:'60', battery:'57', weight:'1.12', os:'Windows 11 Pro'},
        createdAt:Date.now()-86400000*4
      }
    ];

    const coupons = [
      {id:'c_001', code:'WELCOME10', type:'percent', value:10, minOrder:5000000, maxDiscount:2000000, usageLimit:100, used:12, startDate:Date.now()-86400000*10, endDate:Date.now()+86400000*30, active:true, description:'Giảm 10% cho khách mới'},
      {id:'c_002', code:'FREESHIP', type:'shipping', value:100, minOrder:10000000, maxDiscount:0, usageLimit:500, used:67, startDate:Date.now()-86400000*5, endDate:Date.now()+86400000*60, active:true, description:'Miễn phí vận chuyển'},
      {id:'c_003', code:'SUMMER500K', type:'fixed', value:500000, minOrder:15000000, maxDiscount:500000, usageLimit:200, used:200, startDate:Date.now()-86400000*30, endDate:Date.now()-86400000*1, active:false, description:'Giảm 500K mùa hè'},
      {id:'c_004', code:'GAMING15', type:'percent', value:15, minOrder:20000000, maxDiscount:3000000, usageLimit:50, used:5, startDate:Date.now(), endDate:Date.now()+86400000*15, active:true, description:'Giảm 15% laptop gaming'}
    ];

    const shipping = [
      {id:'s_001', region:'Nội thành Hà Nội', provinces:['Hà Nội'], fee:30000, freeThreshold:10000000, estimateDays:'1-2', active:true},
      {id:'s_002', region:'Nội thành TP.HCM', provinces:['TP. Hồ Chí Minh'], fee:30000, freeThreshold:10000000, estimateDays:'1-2', active:true},
      {id:'s_003', region:'Miền Bắc', provinces:['Hải Phòng','Quảng Ninh','Bắc Ninh','Nam Định'], fee:50000, freeThreshold:15000000, estimateDays:'2-3', active:true},
      {id:'s_004', region:'Miền Trung', provinces:['Đà Nẵng','Huế','Nghệ An','Quảng Nam'], fee:70000, freeThreshold:15000000, estimateDays:'3-4', active:true},
      {id:'s_005', region:'Miền Nam', provinces:['Cần Thơ','Bình Dương','Đồng Nai','Vũng Tàu'], fee:60000, freeThreshold:15000000, estimateDays:'2-3', active:true},
      {id:'s_006', region:'Vùng sâu vùng xa', provinces:['Hà Giang','Lai Châu','Điện Biên','Cà Mau'], fee:120000, freeThreshold:20000000, estimateDays:'5-7', active:true}
    ];

    const users = [
      {id:'u_001', fullName:'Nguyễn Văn An', email:'an.nguyen@example.com', phone:'0901234567', role:'customer', status:'active', address:'12 Trần Hưng Đạo, Hà Nội', orders:5, totalSpent:120000000, createdAt:Date.now()-86400000*60},
      {id:'u_002', fullName:'Trần Thị Bình', email:'binh.tran@example.com', phone:'0912345678', role:'customer', status:'active', address:'45 Nguyễn Trãi, TP.HCM', orders:3, totalSpent:78000000, createdAt:Date.now()-86400000*45},
      {id:'u_003', fullName:'Lê Quang Cường', email:'cuong.le@example.com', phone:'0923456789', role:'customer', status:'inactive', address:'78 Hùng Vương, Đà Nẵng', orders:1, totalSpent:18490000, createdAt:Date.now()-86400000*30},
      {id:'u_004', fullName:'Phạm Minh Dũng', email:'dung.pham@example.com', phone:'0934567890', role:'staff', status:'active', address:'Showroom HN', orders:0, totalSpent:0, createdAt:Date.now()-86400000*120},
      {id:'u_005', fullName:'Hoàng Thị Em', email:'em.hoang@example.com', phone:'0945678901', role:'admin', status:'active', address:'Văn phòng trung tâm', orders:0, totalSpent:0, createdAt:Date.now()-86400000*200},
      {id:'u_006', fullName:'Vũ Văn Phong', email:'phong.vu@example.com', phone:'0956789012', role:'customer', status:'active', address:'99 Lê Lợi, Huế', orders:2, totalSpent:54980000, createdAt:Date.now()-86400000*20},
      {id:'u_007', fullName:'Đỗ Thị Giang', email:'giang.do@example.com', phone:'0967890123', role:'customer', status:'banned', address:'21 Bạch Đằng, Hải Phòng', orders:0, totalSpent:0, createdAt:Date.now()-86400000*10}
    ];

    const inventory = products.map((p,i)=>({
      productId:p.id,
      stock:[120,80,45,200,30,12,0,8][i] ?? 50,
      lowStockThreshold:10,
      warehouse:'Kho trung tâm Hà Nội',
      lastUpdated:Date.now()-86400000*(i+1),
      history:[
        {date:Date.now()-86400000*(i+2),type:'in',qty:[150,100,80,250,60,40,30,20][i] ?? 50,note:'Nhập kho ban đầu'},
        {date:Date.now()-86400000*(i+1),type:'out',qty:[30,20,35,50,30,28,30,12][i] ?? 0,note:'Bán ra'}
      ]
    }));

    write(KEYS.categories, categories);
    write(KEYS.brands, brands);
    write(KEYS.specs, specs);
    write(KEYS.products, products);
    write(KEYS.coupons, coupons);
    write(KEYS.shipping, shipping);
    write(KEYS.users, users);
    write(KEYS.inventory, inventory);
    localStorage.setItem(KEYS.initialized,'1');
  }

  /* ----- Generic resource factory ----- */
  function resource(key){
    return {
      all(){ return read(key,[]); },
      get(id){ return read(key,[]).find(x=>x.id===id) || null; },
      create(item){
        const list = read(key,[]);
        item.id = item.id || uid('id');
        item.createdAt = item.createdAt || Date.now();
        list.push(item);
        write(key,list);
        return item;
      },
      update(id, patch){
        const list = read(key,[]);
        const i = list.findIndex(x=>x.id===id);
        if(i<0) return null;
        list[i] = {...list[i], ...patch, id};
        write(key,list);
        return list[i];
      },
      remove(id){
        const list = read(key,[]).filter(x=>x.id!==id);
        write(key,list);
      },
      save(item){
        return item.id && this.get(item.id) ? this.update(item.id,item) : this.create(item);
      }
    };
  }

  /* Reset seed data */
  function resetAll(){
    Object.values(KEYS).forEach(k=>localStorage.removeItem(k));
    seed();
  }

  seed();

  global.DB = {
    keys: KEYS,
    categories: resource(KEYS.categories),
    brands: resource(KEYS.brands),
    specs: resource(KEYS.specs),
    products: resource(KEYS.products),
    coupons: resource(KEYS.coupons),
    shipping: resource(KEYS.shipping),
    users: resource(KEYS.users),
    inventory: resource(KEYS.inventory),
    uid, resetAll
  };
})(window);
