@extends('shop.layout.app')
@section('title', 'Đăng nhập — LaptopStore')

@section('content')
<div class="auth-wrap">
  <div class="auth-card">
    <h1><i class="bi bi-laptop" style="color:var(--brand)"></i> Đăng nhập</h1>
    <p class="sub">Chào mừng bạn quay lại LaptopStore</p>

    <form method="post" action="{{ route('handle-login') }}">
      @csrf
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" name="email" required value="{{ old('email') }}" autofocus>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;font-size:.88rem">
        <label style="display:flex;gap:6px;align-items:center;font-weight:400;margin:0">
          <input type="checkbox" name="remember" checked> Ghi nhớ đăng nhập
        </label>
        <a href="{{ route('forgot-password') }}" style="color:var(--brand)">Quên mật khẩu?</a>
      </div>
      <button class="btn btn-primary btn-block btn-lg">Đăng nhập</button>
    </form>

    <div class="foot">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></div>
  </div>
</div>
@endsection
