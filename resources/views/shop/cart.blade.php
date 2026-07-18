@extends('shop.layout.app')
@section('title', 'Giỏ hàng — LaptopStore')

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / Giỏ hàng</div>
<h1 style="font-size:1.6rem;margin:8px 0 20px">Giỏ hàng của bạn</h1>

@if(empty($cart['items']))
  <div class="empty">
    <i class="bi bi-cart-x"></i>
    <p>Giỏ hàng của bạn đang trống</p>
    <a href="{{ route('shop.products') }}" class="btn btn-primary"><i class="bi bi-bag"></i> Tiếp tục mua sắm</a>
  </div>
@else
<div class="cart-grid">
  <div class="cart-table">
    @foreach($cart['items'] as $item)
    <div class="cart-row">
      <img src="{{ $item['image'] }}" onerror="this.src='https://placehold.co/120x90?text=Laptop'">
      <div class="info">
        <a href="{{ route('shop.product', ['slug' => $item['slug']]) }}" class="nm">{{ $item['name'] }}</a>
        <div class="text-muted" style="font-size:.88rem;margin-top:2px">{{ number_format($item['price'], 0, ',', '.') }}đ</div>
      </div>
      <form method="post" action="{{ route('shop.cart.update') }}" style="margin:0">
        @csrf
        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
        <div class="qty-box">
          <button type="button" data-qty-dec>−</button>
          <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ $item['stock_quantity'] }}" onchange="this.form.submit()">
          <button type="button" data-qty-inc>+</button>
        </div>
      </form>
      <div style="min-width:120px;text-align:right;font-weight:700;color:var(--danger)">{{ number_format($item['line_total'], 0, ',', '.') }}đ</div>
      <form method="post" action="{{ route('shop.cart.remove') }}" style="margin:0">
        @csrf
        <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
        <button class="btn btn-outline btn-sm" title="Xóa"><i class="bi bi-trash" style="color:var(--danger)"></i></button>
      </form>
    </div>
    @endforeach
  </div>

  <div class="summary">
    <h3>Tổng đơn hàng</h3>
    <div class="summary-row"><span>Tạm tính ({{ $cart['count'] }} sản phẩm)</span><span>{{ number_format($cart['subtotal'], 0, ',', '.') }}đ</span></div>
    <div class="summary-row text-muted"><span>Phí vận chuyển</span><span>Tính ở bước thanh toán</span></div>
    <div class="summary-row total"><span>Tạm tính</span><span>{{ number_format($cart['subtotal'], 0, ',', '.') }}đ</span></div>
    <a href="{{ route('shop.checkout') }}" class="btn btn-primary btn-block btn-lg" style="margin-top:14px"><i class="bi bi-credit-card"></i> Tiến hành thanh toán</a>
    <a href="{{ route('shop.products') }}" class="btn btn-outline btn-block" style="margin-top:10px">Tiếp tục mua sắm</a>
  </div>
</div>
@endif
@endsection
