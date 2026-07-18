@extends('shop.layout.app')
@section('title', 'Đăng ký — LaptopStore')

@section('content')
<div class="auth-wrap">
  <div class="auth-card">
    <h1><i class="bi bi-laptop" style="color:var(--brand)"></i> Đăng ký</h1>
    <p class="sub">Tạo tài khoản để mua sắm và theo dõi đơn hàng</p>

    <form method="post" action="{{ route('handle-register') }}">
      @csrf
      <div class="form-group">
        <label>Họ và tên <span class="req">*</span></label>
        <input class="form-control" name="name" required value="{{ old('name') }}" autofocus>
      </div>
      <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input class="form-control" type="email" name="email" required value="{{ old('email') }}">
      </div>
      <div class="form-group">
        <label>Số điện thoại</label>
        <input class="form-control" name="phone" value="{{ old('phone') }}">
      </div>
      <div class="form-group">
        <label>Mật khẩu <span class="req">*</span></label>
        <input class="form-control" type="password" name="password" required minlength="6">
      </div>
      <div class="form-group">
        <label>Xác nhận mật khẩu <span class="req">*</span></label>
        <input class="form-control" type="password" name="password_confirmation" required minlength="6">
      </div>
      <button class="btn btn-primary btn-block btn-lg">Đăng ký</button>
    </form>

    <div class="foot">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></div>
  </div>
</div>
@endsection
