@php $p = $product; @endphp
<div class="product-card">
  @if($p['has_discount'])<span class="discount-tag">-{{ $p['discount_percent'] }}%</span>@endif
  @if($p['stock_quantity'] <= 0)<span class="stock-out">Hết hàng</span>@endif
  <a href="{{ route('shop.product', ['slug' => $p['slug']]) }}" class="thumb">
    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" loading="lazy" onerror="this.src='https://placehold.co/400x400?text=Laptop'">
  </a>
  <div class="body">
    <a href="{{ route('shop.product', ['slug' => $p['slug']]) }}" class="name">{{ $p['name'] }}</a>

    <div>
      <span class="price">{{ number_format($p['price'], 0, ',', '.') }}đ</span>
      @if($p['has_discount'])<span class="price-old">{{ number_format($p['original_price'], 0, ',', '.') }}đ</span>@endif
    </div>

    <div class="card-actions">
      @if($p['stock_quantity'] > 0)
        <button class="btn btn-primary btn-sm btn-block" data-add-to-cart data-id="{{ $p['id'] }}">
          <i class="bi bi-cart-plus"></i> Thêm vào giỏ
        </button>
      @else
        <button class="btn btn-outline btn-sm btn-block" disabled>Tạm hết hàng</button>
      @endif
    </div>
  </div>
</div>
