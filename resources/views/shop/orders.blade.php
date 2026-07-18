@extends('shop.layout.app')
@section('title', 'Đơn hàng của tôi — LaptopStore')

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
@endphp

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / <a href="{{ route('shop.account') }}">Tài khoản</a> / Đơn hàng</div>
<h1 style="font-size:1.6rem;margin:8px 0 20px">Đơn hàng của tôi</h1>

<div class="account-grid">
  <nav class="account-nav">
    <a href="{{ route('shop.account') }}"><i class="bi bi-person"></i> Thông tin cá nhân</a>
    <a href="{{ route('shop.account.orders') }}" class="active"><i class="bi bi-bag-check"></i> Đơn hàng</a>
    <a href="{{ route('logout') }}"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
  </nav>

  <div>
    @if(count($orders))
    <table class="data-table">
      <thead>
        <tr><th>Mã đơn</th><th>Ngày</th><th>SL</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr>
      </thead>
      <tbody>
        @foreach($orders as $o)
        @php $st = $statusMap[$o['order_status']] ?? [$o['order_status'],'pending']; $pt = $payMap[$o['payment_status']] ?? [$o['payment_status'],'unpaid']; @endphp
        <tr>
          <td style="font-weight:600;color:var(--brand)">{{ $o['order_code'] }}</td>
          <td>{{ \Carbon\Carbon::parse($o['create_at'])->format('d/m/Y') }}</td>
          <td>{{ $o['item_count'] }}</td>
          <td style="font-weight:600">{{ number_format($o['total_amount'], 0, ',', '.') }}đ</td>
          <td><span class="badge {{ $pt[1] }}">{{ $pt[0] }}</span></td>
          <td><span class="badge {{ $st[1] }}">{{ $st[0] }}</span></td>
          <td><a href="{{ route('shop.account.order', ['code' => $o['order_code']]) }}" class="btn btn-outline btn-sm">Chi tiết</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <div class="empty">
        <i class="bi bi-bag-x"></i>
        <p>Bạn chưa có đơn hàng nào</p>
        <a href="{{ route('shop.products') }}" class="btn btn-primary">Mua sắm ngay</a>
      </div>
    @endif
  </div>
</div>
@endsection
