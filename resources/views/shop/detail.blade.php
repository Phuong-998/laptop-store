@extends('shop.layout.app')
@section('title', $product['name'] . ' — LaptopStore')

@section('content')
<div class="breadcrumb">
  <a href="{{ route('shop.home') }}">Trang chủ</a> /
  <a href="{{ route('shop.products') }}">Sản phẩm</a> / {{ $product['name'] }}
</div>

<div class="detail-grid">
  {{-- Gallery --}}
  <div class="gallery">
    <div class="main-img"><img id="mainImg" src="{{ $images[0] }}" alt="{{ $product['name'] }}" onerror="this.src='https://placehold.co/600x450?text=Laptop'"></div>
    @if(count($images) > 1)
      <div class="thumbs">
        @foreach($images as $i => $img)
          <img src="{{ $img }}" class="{{ $i === 0 ? 'active' : '' }}" onclick="selectImg(this,'{{ $img }}')">
        @endforeach
      </div>
    @endif
  </div>

  {{-- Info --}}
  <div class="detail-info">
    <h1>{{ $product['name'] }}</h1>
    <div class="meta-code">
      @if($product['brand_name']){{ strtoupper($product['brand_name']) }} @endif
      @if($product['sku'])· {{ $product['sku'] }}@endif
    </div>
    <div class="stars" style="margin-top:10px">
      @for($i=1;$i<=5;$i++)<i class="bi {{ $i <= round($reviewStats['avg']) ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor
      <span class="text-muted" style="font-family:var(--mono);font-size:.74rem;margin-left:4px">{{ $reviewStats['count'] }} đánh giá</span>
    </div>

    <div class="detail-price">
      <span class="now">{{ number_format($product['price'], 0, ',', '.') }}</span>
      @if($product['has_discount'])
        <span class="old">{{ number_format($product['original_price'], 0, ',', '.') }}₫</span>
        <span class="save">Tiết kiệm {{ number_format($product['original_price'] - $product['price'], 0, ',', '.') }}₫ · giảm {{ $product['discount_percent'] }}%</span>
      @endif
    </div>

    <ul class="detail-meta">
      <li><b>Tình trạng</b> @if($product['stock_quantity'] > 0)<span class="text-success">Còn hàng ({{ $product['stock_quantity'] }})</span>@else<span class="text-danger">Hết hàng</span>@endif</li>
      @if($product['category_name'])<li><b>Danh mục</b> {{ $product['category_name'] }}</li>@endif
      @if($product['warranty'])<li><b>Bảo hành</b> {{ $product['warranty'] }}</li>@endif
    </ul>

    @if($product['stock_quantity'] > 0)
      <div class="detail-actions">
        <div class="qty-box">
          <button type="button" data-qty-dec>−</button>
          <input type="number" id="detailQty" value="1" min="1" max="{{ $product['stock_quantity'] }}">
          <button type="button" data-qty-inc>+</button>
        </div>
        <button class="btn btn-primary btn-lg" data-add-to-cart data-id="{{ $product['id'] }}" data-qty-input="#detailQty">
          <i class="bi bi-cart-plus"></i> Thêm vào giỏ
        </button>
        <a href="{{ route('shop.cart') }}" class="btn btn-outline btn-lg"><i class="bi bi-bag-check"></i> Xem giỏ hàng</a>
      </div>
    @else
      <button class="btn btn-outline btn-lg" disabled>Sản phẩm tạm hết hàng</button>
    @endif
  </div>
</div>

{{-- Mô tả + thông số --}}
<div class="panel">
  <h2>Thông số kỹ thuật</h2>
  @if($specs)
    <table class="spec-table">
      @if($specs->cpu)<tr><td>CPU</td><td>{{ $specs->cpu }}</td></tr>@endif
      @if($specs->ram)<tr><td>RAM</td><td>{{ $specs->ram }}</td></tr>@endif
      @if($specs->storage)<tr><td>Ổ cứng</td><td>{{ $specs->storage }}</td></tr>@endif
      @if($specs->gpu)<tr><td>Card đồ họa</td><td>{{ $specs->gpu }}</td></tr>@endif
      @if($specs->screen)<tr><td>Màn hình</td><td>{{ $specs->screen }}</td></tr>@endif
      @if($specs->battery)<tr><td>Pin</td><td>{{ $specs->battery }}</td></tr>@endif
      @if($specs->weight)<tr><td>Trọng lượng</td><td>{{ $specs->weight }} kg</td></tr>@endif
      @if($specs->os)<tr><td>Hệ điều hành</td><td>{{ $specs->os }}</td></tr>@endif
    </table>
  @else
    <p class="text-muted">Chưa cập nhật thông số kỹ thuật.</p>
  @endif

  @if($product['description'])
    <h2 style="margin-top:26px">Mô tả sản phẩm</h2>
    <div class="prose">{!! $product['description'] !!}</div>
  @endif
</div>

{{-- Đánh giá --}}
<div class="panel" id="reviews">
  <h2>Đánh giá sản phẩm</h2>
  <div class="review-summary">
    <div class="review-score">
      <div class="num">{{ number_format($reviewStats['avg'], 1) }}</div>
      <div class="stars">@for($i=1;$i<=5;$i++)<i class="bi {{ $i <= round($reviewStats['avg']) ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor</div>
      <div style="color:var(--muted);font-size:.82rem;margin-top:4px">{{ $reviewStats['count'] }} đánh giá</div>
    </div>
    <div style="flex:1">
      @auth
        <p style="margin:0 0 8px;font-weight:600">Chia sẻ cảm nhận của bạn về sản phẩm</p>
        <form method="post" action="{{ route('shop.review.store', ['id' => $product['id']]) }}">
          @csrf
          <div class="rate-input">
            @for($i=5;$i>=1;$i--)
              <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}" @checked($i===5)><label for="star{{ $i }}"><i class="bi bi-star-fill"></i></label>
            @endfor
          </div>
          <div class="form-group" style="margin-top:10px">
            <textarea name="comment" class="form-control" rows="3" placeholder="Nội dung đánh giá..."></textarea>
          </div>
          <button class="btn btn-primary"><i class="bi bi-send"></i> Gửi đánh giá</button>
        </form>
      @else
        <p class="text-muted">Vui lòng <a href="{{ route('login') }}" style="color:var(--brand);font-weight:600">đăng nhập</a> để viết đánh giá.</p>
      @endauth
    </div>
  </div>

  @forelse($reviews as $rv)
    <div class="review-item">
      <div style="display:flex;justify-content:space-between">
        <span class="who">{{ $rv->user_name ?? 'Khách hàng' }}</span>
        <span class="when">{{ \Carbon\Carbon::parse($rv->created_at)->format('d/m/Y') }}</span>
      </div>
      <div class="stars" style="margin:4px 0">@for($i=1;$i<=5;$i++)<i class="bi {{ $i <= $rv->rating ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor</div>
      @if($rv->comment)<div style="font-size:.92rem;color:#374151">{{ $rv->comment }}</div>@endif
    </div>
  @empty
    <p class="text-muted">Chưa có đánh giá nào. Hãy là người đầu tiên đánh giá!</p>
  @endforelse
</div>

{{-- Sản phẩm liên quan --}}
@if($related->count())
<section class="section">
  <div class="section-head"><h2>Sản phẩm liên quan</h2></div>
  <div class="product-grid">
    @foreach($related as $product)
      @include('shop.partials.product-card', ['product' => $product])
    @endforeach
  </div>
</section>
@endif
@endsection

@push('scripts')
<script>
function selectImg(el, src){
  document.getElementById('mainImg').src = src;
  document.querySelectorAll('.gallery .thumbs img').forEach(i => i.classList.remove('active'));
  el.classList.add('active');
}
</script>
@endpush
