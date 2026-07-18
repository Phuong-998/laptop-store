@extends('shop.layout.app')
@section('title', 'Đặt hàng thành công — LaptopStore')

@section('content')
<div class="card" style="max-width:680px;margin:32px auto;text-align:center">
  <i class="bi bi-check-circle-fill" style="font-size:4rem;color:var(--success)"></i>
  <h1 style="font-size:1.6rem;margin:14px 0 6px">Đặt hàng thành công!</h1>
  <p class="text-muted">Cảm ơn bạn đã mua sắm tại LaptopStore. Mã đơn hàng của bạn là:</p>
  <div style="font-size:1.4rem;font-weight:800;color:var(--brand);letter-spacing:1px;margin:6px 0 20px">{{ $order->order_code }}</div>

  <div style="text-align:left;background:var(--bg);border-radius:var(--radius);padding:18px 20px;margin-bottom:20px">
    <div class="summary-row"><span>Người nhận</span><span>{{ $order->customer_name }} — {{ $order->customer_phone }}</span></div>
    <div class="summary-row"><span>Địa chỉ</span><span style="text-align:right;max-width:60%">{{ $order->shiping_address }}</span></div>
    <div class="summary-row"><span>Sản phẩm</span><span>{{ $items->count() }} mặt hàng</span></div>
    <div class="summary-row"><span>Tạm tính</span><span>{{ number_format($order->subtotal, 0, ',', '.') }}đ</span></div>
    @if($order->discount_amount > 0)<div class="summary-row"><span>Giảm giá</span><span style="color:var(--danger)">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span></div>@endif
    <div class="summary-row"><span>Phí vận chuyển</span><span>{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span></div>
    <div class="summary-row total"><span>Tổng cộng</span><span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span></div>
  </div>

  <p class="text-muted" style="font-size:.9rem">Chúng tôi sẽ liên hệ xác nhận đơn hàng trong thời gian sớm nhất.</p>
  <div style="display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap">
    <a href="{{ route('shop.products') }}" class="btn btn-outline">Tiếp tục mua sắm</a>
    @auth<a href="{{ route('shop.account.orders') }}" class="btn btn-primary">Xem đơn hàng của tôi</a>@endauth
  </div>
</div>
@endsection
