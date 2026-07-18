@php
    $routeName = request()->route()?->getName();

    $activeMenu = match (true) {
        request()->routeIs('admin.orders') => 'orders',
        request()->routeIs('admin.reviews') => 'reviews',
        request()->routeIs('admin.category') => 'categories',
        request()->routeIs('admin.branch') => 'branches',
        request()->routeIs('admin.products') => 'products',
        request()->routeIs('admin.brand') => 'brands',
        request()->routeIs('admin.coupons') => 'coupons',
        request()->routeIs('admin.shipping') => 'shipping',
        request()->routeIs('admin.specs') => 'specs',
        request()->routeIs('admin.users') => 'users',
        request()->routeIs('admin.inventory') => 'inventory',
        request()->routeIs('admin.imports') => 'imports',
        request()->routeIs('admin.exports') => 'exports',
        default => 'dashboard',
    };

    $pageTitle = match (true) {
        request()->routeIs('admin.orders') => 'Đơn hàng',
        request()->routeIs('admin.reviews') => 'Đánh giá khách hàng',
        request()->routeIs('admin.category') => 'Danh mục',
        request()->routeIs('admin.branch') => 'Chi nhánh',
        request()->routeIs('admin.products') => 'Sản phẩm',
        request()->routeIs('admin.brand') => 'Thương hiệu',
        request()->routeIs('admin.coupons') => 'Mã giảm giá',
        request()->routeIs('admin.shipping') => 'Phí vận chuyển',
        request()->routeIs('admin.specs') => 'Thông số kỹ thuật',
        request()->routeIs('admin.users') => 'Người dùng',
        request()->routeIs('admin.inventory') => 'Tồn kho',
        request()->routeIs('admin.imports') => 'Nhập hàng',
        request()->routeIs('admin.exports') => 'Xuất hàng',
        default => 'Dashboard',
    };
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laptop Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="{{asset('css/admin.css')}}" rel="stylesheet">
</head>
<body>
<main id="page-content">
  @yield('conten')
</main>
<script>
window.ADMIN_ROUTES = {
  dashboard: "{{ route('dashboard') }}",
  orders: "{{ route('admin.orders') }}",
  reviews: "{{ route('admin.reviews') }}",
  category: "{{ route('admin.category') }}",
  brands: "{{ route('admin.brand') }}",
  products: "{{ route('admin.products') }}",
  coupons: "{{ route('admin.coupons') }}",
  shipping: "{{ route('admin.shipping') }}",
  specs: "{{ route('admin.specs') }}",
  users: "{{ route('admin.users') }}",
  inventory: "{{ route('admin.inventory') }}",
  imports: "{{ route('admin.imports') }}",
  exports: "{{ route('admin.exports') }}"
};
</script>
<script src="{{asset('js/data.js')}}"></script>
<script src="{{asset('js/layout.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
UI.renderLayout({
    active: @json($activeMenu),
    title: @json($pageTitle)
});
</script>
@stack('scripts')
</body>
</html>
