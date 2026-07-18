@php
    use Illuminate\Support\Facades\DB;
    $navCategories = DB::table('categorise')->where('status', 1)->orderBy('name')->limit(14)->get(['id', 'name']);
    $navBrands = DB::table('brand')->where('status', 1)->orderBy('name')->limit(12)->get(['id', 'name']);
    // Thương hiệu theo từng danh mục (cho panel mega menu động).
    foreach ($navCategories as $navCat) {
        $navCat->brands = DB::table('products as p')
            ->join('brand as b', 'b.id', '=', 'p.branch_id')
            ->where('p.status', 1)->where('p.category_id', $navCat->id)
            ->distinct()->orderBy('b.name')->pluck('b.name', 'b.id');
    }
    $cartCount = (int) array_sum(session('cart', []));
    $authUser = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'LaptopStore — Laptop chính hãng, giá tốt')</title>
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="{{ asset('css/shop.css') }}" rel="stylesheet">
</head>
<body>

<div class="topbar">
  <div class="container">
    <div><i class="bi bi-truck"></i> Miễn phí giao hàng đơn từ 10 triệu <span class="sep">|</span> Trả góp 0% qua thẻ</div>
    <div>
      @auth
        <a href="{{ route('shop.account') }}"><i class="bi bi-person"></i> {{ $authUser->name }}</a>
      @else
        <a href="{{ route('login') }}">Đăng nhập</a><span class="sep">|</span><a href="{{ route('register') }}">Đăng ký</a>
      @endauth
    </div>
  </div>
</div>

<header class="header">
  <div class="container">
    <a href="{{ route('shop.home') }}" class="logo">
      <span class="mark"><i class="bi bi-laptop"></i></span>
      <span><b>LaptopStore</b><span class="tag">Thế giới là của bạn</span></span>
    </a>
    <form class="search" action="{{ route('shop.products') }}" method="get">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Nhập từ khóa tìm kiếm...">
      <button type="submit"><i class="bi bi-search"></i></button>
    </form>
    <div class="header-hotline">
      <i class="bi bi-telephone-outbound"></i>
      <div><div class="lbl">Mua hàng online</div><div class="num">0961 560 888</div></div>
    </div>
    <div class="header-actions">
      @auth
        <div class="header-icon account-menu">
          <i class="bi bi-person-circle"></i><span>Tài khoản</span>
          <div class="account-dropdown">
            <a href="{{ route('shop.account') }}"><i class="bi bi-person"></i> Thông tin cá nhân</a>
            <a href="{{ route('shop.account.orders') }}"><i class="bi bi-bag-check"></i> Đơn hàng của tôi</a>
            @if($authUser->role === 'admin')
              <hr><a href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Trang quản trị</a>
            @endif
            <hr><a href="{{ route('logout') }}"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="header-icon"><i class="bi bi-person"></i><span>Đăng nhập</span></a>
      @endauth
      <a href="{{ route('shop.cart') }}" class="header-icon">
        <i class="bi bi-cart3"></i><span>Giỏ hàng</span>
        <span class="cart-badge" id="cartBadge" style="{{ $cartCount ? '' : 'display:none' }}">{{ $cartCount }}</span>
      </a>
    </div>
  </div>
</header>

<nav class="mainnav">
  <div class="container">
    <div class="mega-wrap">
      <button type="button" class="mega-btn">
        <i class="bi bi-list"></i><span class="txt">DANH MỤC SẢN PHẨM</span><i class="bi bi-chevron-down caret"></i>
      </button>
      <div class="mega">
        <div class="mega-cats">
          @forelse($navCategories as $i => $cat)
            <a href="{{ route('shop.products', ['category' => $cat->id]) }}" data-cat="{{ $cat->id }}" class="{{ $i === 0 ? 'active' : '' }}">
              <i class="bi bi-laptop"></i><span>{{ $cat->name }}</span><i class="bi bi-chevron-right chev"></i>
            </a>
          @empty
            <a href="{{ route('shop.products') }}"><i class="bi bi-grid"></i><span>Tất cả sản phẩm</span></a>
          @endforelse
        </div>
        <div class="mega-panels">
          @foreach($navCategories as $i => $cat)
            <div class="mega-sub {{ $i === 0 ? 'active' : '' }}" data-cat="{{ $cat->id }}">
              <div class="mega-panel">
                <div class="mega-col">
                  <h4>Thương hiệu</h4>
                  @forelse($cat->brands as $bid => $bname)
                    <a href="{{ route('shop.products', ['category' => $cat->id, 'brand' => $bid]) }}">Laptop {{ $bname }}</a>
                  @empty
                    @foreach($navBrands as $b)
                      <a href="{{ route('shop.products', ['brand' => $b->id]) }}">Laptop {{ $b->name }}</a>
                    @endforeach
                  @endforelse
                </div>
                <div class="mega-col">
                  <h4>Mức giá</h4>
                  <a href="{{ route('shop.products', ['category' => $cat->id, 'max_price' => 15000000]) }}">Dưới 15 triệu</a>
                  <a href="{{ route('shop.products', ['category' => $cat->id, 'min_price' => 15000000, 'max_price' => 25000000]) }}">15 – 25 triệu</a>
                  <a href="{{ route('shop.products', ['category' => $cat->id, 'min_price' => 25000000, 'max_price' => 40000000]) }}">25 – 40 triệu</a>
                  <a href="{{ route('shop.products', ['category' => $cat->id, 'min_price' => 40000000]) }}">Trên 40 triệu</a>
                </div>
                <div class="mega-col">
                  <h4>{{ $cat->name }}</h4>
                  <a href="{{ route('shop.products', ['category' => $cat->id, 'sort' => 'price_asc']) }}">Giá thấp → cao</a>
                  <a href="{{ route('shop.products', ['category' => $cat->id]) }}">Mới nhất</a>
                  <a href="{{ route('shop.products', ['category' => $cat->id]) }}" style="color:var(--red);font-weight:700">Xem tất cả →</a>
                </div>
                <a href="{{ route('shop.products', ['sort' => 'price_asc']) }}" class="mega-promo">
                  <b><i class="bi bi-lightning-charge-fill"></i> Deal giá sốc mỗi ngày</b>
                  <span>Xem ngay →</span>
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="navlinks">
      <a href="{{ route('shop.products') }}"><i class="bi bi-headset"></i> <span>Tư vấn mua hàng</span></a>
      <a href="{{ route('shop.products') }}"><i class="bi bi-credit-card"></i> <span>Trả góp 0%</span></a>
      <a href="{{ route('shop.products', ['sort' => 'price_asc']) }}"><i class="bi bi-tags"></i> <span>Khuyến mãi</span></a>
    </div>
    <a href="{{ route('shop.products', ['sort' => 'price_asc']) }}" class="deal-hot"><i class="bi bi-fire"></i> DEAL GIÁ SỐC</a>
  </div>
</nav>

<main class="container">
  @if(session('success'))
    <div class="flash success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="flash error"><i class="bi bi-exclamation-circle"></i> {{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div class="flash error"><i class="bi bi-exclamation-circle"></i> {{ $errors->first() }}</div>
  @endif

  @yield('content')
</main>

<footer class="footer">
  <div class="container footer-grid">
    <div>
      <div class="brand"><span class="mark"><i class="bi bi-laptop"></i></span><b>LaptopStore</b></div>
      <p>Hệ thống bán lẻ laptop chính hãng: chọn máy theo cấu hình, giá minh bạch, bảo hành đầy đủ và giao hàng toàn quốc.</p>
    </div>
    <div>
      <h4>Về chúng tôi</h4>
      <a href="#">Giới thiệu</a><a href="#">Tuyển dụng</a><a href="#">Tin tức</a>
    </div>
    <div>
      <h4>Hỗ trợ</h4>
      <a href="#">Chính sách bảo hành</a><a href="#">Đổi trả</a><a href="#">Vận chuyển</a>
    </div>
    <div>
      <h4>Liên hệ</h4>
      <a href="#"><i class="bi bi-geo-alt"></i> 123 Đường ABC, Hà Nội</a>
      <a href="#"><i class="bi bi-telephone"></i> 0961 560 888</a>
      <a href="#"><i class="bi bi-envelope"></i> support@laptopstore.vn</a>
    </div>
  </div>
  <div class="footer-bottom">© {{ date('Y') }} LaptopStore. Đồ án website bán laptop.</div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>
<script>
window.SHOP_ROUTES = {
  cartAdd: "{{ route('shop.cart.add') }}",
  cartUpdate: "{{ route('shop.cart.update') }}",
  cartRemove: "{{ route('shop.cart.remove') }}"
};
</script>
<script src="{{ asset('js/shop.js') }}"></script>
@stack('scripts')
</body>
</html>
