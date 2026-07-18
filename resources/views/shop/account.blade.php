@extends('shop.layout.app')
@section('title', 'Tài khoản của tôi — LaptopStore')

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / Tài khoản</div>
<h1 style="font-size:1.6rem;margin:8px 0 20px">Tài khoản của tôi</h1>

<div class="account-grid">
  <nav class="account-nav">
    <a href="{{ route('shop.account') }}" class="active"><i class="bi bi-person"></i> Thông tin cá nhân</a>
    <a href="{{ route('shop.account.orders') }}"><i class="bi bi-bag-check"></i> Đơn hàng ({{ $orderCount }})</a>
    <a href="{{ route('logout') }}"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
  </nav>

  <div class="card">
    <h3 style="margin-top:0">Thông tin cá nhân</h3>
    <form method="post" action="{{ route('shop.account.update') }}">
      @csrf
      <div class="form-group">
        <label>Họ và tên <span class="req">*</span></label>
        <input class="form-control" name="name" required value="{{ old('name', $user->name) }}">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" value="{{ $user->email }}" disabled>
      </div>
      <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" name="phone" value="{{ old('phone', $user->phone) }}">
      </div>
      <div class="form-group">
        <label>Địa chỉ</label>
        <textarea class="form-control" name="address" rows="2">{{ old('address', $user->address) }}</textarea>
      </div>
      <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Lưu thay đổi</button>
    </form>
  </div>
</div>
@endsection
