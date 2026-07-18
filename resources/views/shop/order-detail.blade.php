@extends('shop.layout.app')
@section('title', 'Đơn hàng ' . $order->order_code . ' — LaptopStore')

@php
  $statusMap = [
    'pending' => ['Chờ xác nhận', 'pending'],
    'processing' => ['Đang xử lý', 'processing'],
    'shipping' => ['Đang giao', 'shipping'],
    'completed' => ['Hoàn thành', 'completed'],
    'cancelled' => ['Đã hủy', 'cancelled'],
  ];
  $payMap = [
    'unpaid' => ['Chưa thanh toán', 'unpaid'],
    'paid' => ['Đã thanh toán', 'paid'],
    'refunded' => ['Đã hoàn tiền', 'refunded'],
  ];
  $methodMap = ['cod' => 'Thanh toán khi nhận (COD)', 'bank' => 'Chuyển khoản', 'card' => 'Thẻ tín dụng/ghi nợ'];
  $st = $statusMap[$order->order_status] ?? [$order->order_status,'pending'];
  $pt = $payMap[$order->payment_status] ?? [$order->payment_status,'unpaid'];
@endphp

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / <a href="{{ route('shop.account.orders') }}">Đơn hàng</a> / {{ $order->order_code }}</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin:8px 0 20px;flex-wrap:wrap;gap:10px">
  <h1 style="font-size:1.5rem;margin:0">Đơn hàng {{ $order->order_code }}</h1>
  <div><span class="badge {{ $st[1] }}">{{ $st[0] }}</span> <span class="badge {{ $pt[1] }}">{{ $pt[0] }}</span></div>
</div>

<div class="cart-grid">
  <div class="card">
    <h3 style="margin-top:0">Sản phẩm</h3>
    @foreach($items as $item)
    <div class="cart-row" style="padding:12px 0">
      <img src="{{ $item->product_image }}" onerror="this.src='https://placehold.co/120x90?text=Laptop'">
      <div class="info">
        <div class="nm">{{ $item->product_name }}</div>
        <div class="text-muted" style="font-size:.85rem">{{ (int) $item->quanity }} × {{ number_format($item->product_price, 0, ',', '.') }}đ</div>
      </div>
      <div style="font-weight:700;color:var(--danger)">{{ number_format($item->total_price, 0, ',', '.') }}đ</div>
    </div>
    @endforeach
  </div>

  <div>
    <div class="summary" style="position:static;margin-bottom:16px">
      <h3>Thông tin đơn</h3>
      <div class="summary-row"><span>Người nhận</span><span>{{ $order->customer_name }}</span></div>
      <div class="summary-row"><span>Điện thoại</span><span>{{ $order->customer_phone }}</span></div>
      <div class="summary-row"><span>Thanh toán</span><span>{{ $methodMap[$order->payment_method] ?? $order->payment_method }}</span></div>
      <div class="summary-row"><span>Ngày đặt</span><span>{{ \Carbon\Carbon::parse($order->create_at)->format('d/m/Y H:i') }}</span></div>
      <div style="padding:8px 0;font-size:.9rem"><div class="text-muted">Địa chỉ giao:</div>{{ $order->shiping_address }}</div>
      @if($order->note)<div style="padding:8px 0;font-size:.9rem"><div class="text-muted">Ghi chú:</div>{{ $order->note }}</div>@endif
    </div>
    <div class="summary" style="position:static">
      <div class="summary-row"><span>Tạm tính</span><span>{{ number_format($order->subtotal, 0, ',', '.') }}đ</span></div>
      @if($order->discount_amount > 0)<div class="summary-row"><span>Giảm giá</span><span style="color:var(--danger)">-{{ number_format($order->discount_amount, 0, ',', '.') }}đ</span></div>@endif
      <div class="summary-row"><span>Phí vận chuyển</span><span>{{ number_format($order->shipping_fee, 0, ',', '.') }}đ</span></div>
      <div class="summary-row total"><span>Tổng cộng</span><span>{{ number_format($order->total_amount, 0, ',', '.') }}đ</span></div>
    </div>
  </div>
</div>
@endsection
