@extends('shop.layout.app')
@section('title', 'Thanh toán — LaptopStore')

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / <a href="{{ route('shop.cart') }}">Giỏ hàng</a> / Thanh toán</div>
<h1 style="font-size:1.6rem;margin:8px 0 20px">Thanh toán</h1>

<form method="post" action="{{ route('shop.checkout.place') }}" id="checkoutForm">
  @csrf
  <div class="cart-grid">
    {{-- Thông tin giao hàng --}}
    <div class="card">
      <h3 style="margin-top:0"><i class="bi bi-truck"></i> Thông tin giao hàng</h3>
      <div class="form-group">
        <label>Họ và tên <span class="req">*</span></label>
        <input class="form-control" name="customer_name" required value="{{ old('customer_name', $user->name ?? '') }}">
      </div>
      <div class="form-group">
        <label>Số điện thoại <span class="req">*</span></label>
        <input class="form-control" name="customer_phone" required value="{{ old('customer_phone', $user->phone ?? '') }}">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" name="customer_email" value="{{ old('customer_email', $user->email ?? '') }}">
      </div>
      <div class="form-group">
        <label>Địa chỉ giao hàng <span class="req">*</span></label>
        <textarea class="form-control" name="shipping_address" rows="2" required>{{ old('shipping_address', $user->address ?? '') }}</textarea>
      </div>
      <div class="form-group">
        <label>Khu vực giao hàng <span class="req">*</span></label>
        <select class="form-control" name="zone_id" id="zoneSelect" required>
          <option value="">— Chọn khu vực để tính phí ship —</option>
          @foreach($zones as $z)
            <option value="{{ $z->id }}" {{ old('zone_id') == $z->id ? 'selected' : '' }}>{{ $z->region }} — {{ (float) $z->fee > 0 ? number_format($z->fee, 0, ',', '.') . 'đ' : 'Miễn phí' }} @if($z->estimate_days)({{ $z->estimate_days }})@endif</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label>Ghi chú</label>
        <textarea class="form-control" name="note" rows="2" placeholder="Ghi chú cho đơn hàng (tùy chọn)">{{ old('note') }}</textarea>
      </div>
    </div>

    {{-- Tóm tắt đơn --}}
    <div class="summary">
      <h3>Đơn hàng ({{ $cart['count'] }} sản phẩm)</h3>
      <div style="max-height:220px;overflow:auto;margin-bottom:12px">
        @foreach($cart['items'] as $item)
          <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)">
            <img src="{{ $item['image'] }}" style="width:48px;height:40px;object-fit:contain;border:1px solid var(--line);border-radius:6px" onerror="this.src='https://placehold.co/60x50?text=L'">
            <div style="flex:1;min-width:0">
              <div style="font-size:.85rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item['name'] }}</div>
              <div class="text-muted" style="font-size:.8rem">{{ $item['quantity'] }} × {{ number_format($item['price'], 0, ',', '.') }}đ</div>
            </div>
          </div>
        @endforeach
      </div>

      <div class="form-group">
        <label>Mã giảm giá</label>
        <div style="display:flex;gap:8px">
          <input class="form-control" id="couponInput" placeholder="Nhập mã">
          <input type="hidden" name="coupon_code" id="couponHidden">
          <button type="button" class="btn btn-outline" id="applyCoupon">Áp dụng</button>
        </div>
        <div id="couponMsg" style="font-size:.82rem;margin-top:6px"></div>
      </div>

      <div class="summary-row"><span>Tạm tính</span><span id="sumSubtotal">{{ number_format($cart['subtotal'], 0, ',', '.') }}đ</span></div>
      <div class="summary-row"><span>Giảm giá</span><span id="sumDiscount" style="color:var(--danger)">0đ</span></div>
      <div class="summary-row"><span>Phí vận chuyển</span><span id="sumShip" class="text-muted">Chọn khu vực</span></div>
      <div class="summary-row total"><span>Tổng cộng</span><span id="sumTotal">{{ number_format($cart['subtotal'], 0, ',', '.') }}đ</span></div>

      <div class="form-group" style="margin-top:16px">
        <label>Phương thức thanh toán</label>
        <select class="form-control" name="payment_method">
          <option value="cod">Thanh toán khi nhận hàng (COD)</option>
          <option value="bank">Chuyển khoản ngân hàng</option>
          <option value="card">Thẻ tín dụng / ghi nợ</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="bi bi-bag-check"></i> Đặt hàng</button>
    </div>
  </div>
</form>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const couponUrl = "{{ route('shop.checkout.coupon') }}";
const fmt = n => new Intl.NumberFormat('vi-VN').format(Math.round(n)) + 'đ';

const zoneSelect = document.getElementById('zoneSelect');
const sumShip = document.getElementById('sumShip');

function setShip(hasZone, fee){
  if(!hasZone){ sumShip.textContent = 'Chọn khu vực'; sumShip.className = 'text-muted'; return; }
  if(fee > 0){ sumShip.textContent = fmt(fee); sumShip.className = ''; }
  else { sumShip.textContent = 'Miễn phí'; sumShip.className = 'text-success'; }
}

function refreshSummary(){
  const code = document.getElementById('couponHidden').value || null;
  const zone_id = zoneSelect.value || null;
  fetch(couponUrl, {
    method: 'POST',
    headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
    body: JSON.stringify({code, zone_id})
  })
    .then(async r => {
      const d = await r.json().catch(() => ({}));
      const msg = document.getElementById('couponMsg');
      if(!r.ok){
        // Lỗi coupon: bỏ coupon nhưng vẫn tính lại ship theo khu vực
        msg.textContent = d.message || 'Mã không hợp lệ';
        msg.style.color = 'var(--danger)';
        document.getElementById('couponHidden').value = '';
        return recalc(null, zone_id);
      }
      document.getElementById('sumSubtotal').textContent = fmt(d.subtotal);
      document.getElementById('sumDiscount').textContent = d.discount > 0 ? '-' + fmt(d.discount) : '0đ';
      setShip(!!zone_id, d.shipping_fee);
      document.getElementById('sumTotal').textContent = fmt(d.total);
      if(d.code){ msg.textContent = '✓ Đã áp dụng mã ' + d.code; msg.style.color = 'var(--success)'; }
    })
    .catch(() => {});
}

function recalc(code, zone_id){
  fetch(couponUrl, {
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
    body: JSON.stringify({code, zone_id})
  }).then(async r => {
    const d = await r.json().catch(()=>({}));
    if(r.ok){
      setShip(!!zone_id, d.shipping_fee);
      document.getElementById('sumDiscount').textContent = d.discount > 0 ? '-' + fmt(d.discount) : '0đ';
      document.getElementById('sumTotal').textContent = fmt(d.total);
    }
  });
}

document.getElementById('applyCoupon').addEventListener('click', () => {
  document.getElementById('couponHidden').value = document.getElementById('couponInput').value.trim();
  refreshSummary();
});
zoneSelect.addEventListener('change', refreshSummary);

// Tính ngay khi tải trang nếu đã chọn sẵn khu vực (giữ lựa chọn cũ)
if(zoneSelect.value){ refreshSummary(); }

// Chặn đặt hàng khi chưa chọn khu vực
document.getElementById('checkoutForm').addEventListener('submit', function(e){
  if(!zoneSelect.value){
    e.preventDefault();
    if(window.shopToast) shopToast('Vui lòng chọn khu vực giao hàng', 'error');
    zoneSelect.focus();
    zoneSelect.style.borderColor = 'var(--red)';
  }
});
</script>
@endpush
