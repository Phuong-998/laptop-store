@extends('shop.layout.app')
@section('title', 'LaptopStore — Laptop chính hãng, giá tốt mỗi ngày')

@section('content')
{{-- HERO: banner full chiều rộng --}}
<section class="home-hero">
  <div class="hero-banner">
    <div class="kicker">Laptop chính hãng · Giá tốt mỗi ngày</div>
    <h1>Chọn laptop<br>theo đúng nhu cầu</h1>
    <p>Văn phòng, gaming hay đồ họa — cấu hình rõ ràng, giá minh bạch, trả góp 0% và bảo hành chính hãng toàn quốc.</p>
    <div><a href="{{ route('shop.products') }}" class="btn btn-lg"><i class="bi bi-bag"></i> Mua sắm ngay</a></div>
    <i class="bi bi-laptop halo"></i>
  </div>
  <div class="hero-mini">
    <a href="{{ route('shop.products', ['sort' => 'price_asc']) }}" class="m a"><b><i class="bi bi-lightning-charge-fill"></i> Deal giá sốc</b><span>Ưu đãi giới hạn trong ngày</span></a>
    <a href="{{ route('shop.products') }}" class="m b"><b><i class="bi bi-credit-card-2-front"></i> Trả góp 0%</b><span>Duyệt nhanh qua thẻ tín dụng</span></a>
  </div>
</section>

{{-- Cam kết --}}
<section class="features">
  <div class="feature"><i class="bi bi-patch-check"></i><div><b>Chính hãng 100%</b><span>Nguyên seal, đầy đủ VAT</span></div></div>
  <div class="feature"><i class="bi bi-shield-check"></i><div><b>Bảo hành tận nơi</b><span>Đổi mới trong 15 ngày</span></div></div>
  <div class="feature"><i class="bi bi-truck"></i><div><b>Giao hàng nhanh</b><span>Toàn quốc, freeship đơn lớn</span></div></div>
  <div class="feature"><i class="bi bi-credit-card"></i><div><b>Trả góp 0%</b><span>Thủ tục đơn giản</span></div></div>
</section>

{{-- BÁN CHẠY: tab theo danh mục + carousel --}}
@if($featuredProducts->count())
<section class="block" data-tabblock>
  <div class="block-head">
    <h2><i class="bi bi-fire"></i> Sản phẩm bán chạy</h2>
    <div class="block-tabs">
      <button class="tab active" data-tab="all">Tất cả</button>
      @foreach($categories as $cat)
        <button class="tab" data-tab="cat{{ $cat->id }}">{{ $cat->name }}</button>
      @endforeach
    </div>
    <a href="{{ route('shop.products') }}" class="seeall">Xem tất cả →</a>
  </div>
  <div class="block-body">
    {{-- pane: tất cả --}}
    <div class="rail-wrap rail-pane" data-pane="all">
      <button class="rail-btn prev" type="button" aria-label="Trước"><i class="bi bi-chevron-left"></i></button>
      <div class="rail">
        @foreach($featuredProducts as $product)
          @include('shop.partials.product-card', ['product' => $product])
        @endforeach
      </div>
      <button class="rail-btn next" type="button" aria-label="Sau"><i class="bi bi-chevron-right"></i></button>
    </div>
    {{-- pane theo từng danh mục --}}
    @foreach($categories as $cat)
      @php $inCat = $featuredProducts->where('category_id', $cat->id)->values(); @endphp
      <div class="rail-pane" data-pane="cat{{ $cat->id }}" hidden>
        @if($inCat->count())
          <div class="rail-wrap">
            <button class="rail-btn prev" type="button"><i class="bi bi-chevron-left"></i></button>
            <div class="rail">
              @foreach($inCat as $product)
                @include('shop.partials.product-card', ['product' => $product])
              @endforeach
            </div>
            <button class="rail-btn next" type="button"><i class="bi bi-chevron-right"></i></button>
          </div>
        @else
          <div class="empty"><i class="bi bi-inbox"></i><p>Danh mục "{{ $cat->name }}" chưa có sản phẩm</p><a href="{{ route('shop.products') }}" class="btn btn-outline btn-sm">Xem sản phẩm khác</a></div>
        @endif
      </div>
    @endforeach
  </div>
</section>
@endif

{{-- KHUYẾN MÃI --}}
@if($saleProducts->count())
<section class="block">
  <div class="block-head">
    <h2><i class="bi bi-lightning-charge-fill"></i> Đang khuyến mãi</h2>
    <a href="{{ route('shop.products') }}" class="seeall">Xem tất cả →</a>
  </div>
  <div class="block-body">
    <div class="rail-wrap">
      <button class="rail-btn prev" type="button"><i class="bi bi-chevron-left"></i></button>
      <div class="rail">
        @foreach($saleProducts as $product)
          @include('shop.partials.product-card', ['product' => $product])
        @endforeach
      </div>
      <button class="rail-btn next" type="button"><i class="bi bi-chevron-right"></i></button>
    </div>
  </div>
</section>
@endif

{{-- KHỐI THEO DANH MỤC (có sub-tab thương hiệu) --}}
@foreach($categorySections as $sec)
<section class="block">
  <div class="block-head">
    <h2><i class="bi bi-laptop"></i> {{ $sec['category']->name }}</h2>
    <div class="block-tabs">
      @foreach($sec['brands'] as $b)
        <a class="tab" href="{{ route('shop.products', ['category' => $sec['category']->id, 'brand' => $b->id]) }}">{{ $b->name }}</a>
      @endforeach
    </div>
    <a href="{{ route('shop.products', ['category' => $sec['category']->id]) }}" class="seeall">Xem tất cả →</a>
  </div>
  <div class="block-body">
    <div class="product-grid">
      @foreach($sec['products'] as $product)
        @include('shop.partials.product-card', ['product' => $product])
      @endforeach
    </div>
  </div>
</section>
@endforeach

{{-- Danh mục nổi bật --}}
@if($categories->count())
<section class="section">
  <div class="section-head"><h2>Danh mục sản phẩm</h2><a href="{{ route('shop.products') }}">Xem tất cả →</a></div>
  <div class="cat-grid">
    @foreach($categories as $i => $cat)
      <a href="{{ route('shop.products', ['category' => $cat->id]) }}" class="cat-tile">
        <i class="bi bi-laptop"></i><span>{{ $cat->name }}</span>
        <span class="idx">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
      </a>
    @endforeach
  </div>
</section>
@endif
@endsection
