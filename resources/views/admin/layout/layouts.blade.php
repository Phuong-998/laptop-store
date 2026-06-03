@php
    $routeName = request()->route()?->getName();

    $activeMenu = match (true) {
        request()->routeIs('admin.category') => 'categories',
        request()->routeIs('admin.branch') => 'branches',
        request()->routeIs('admin.product') => 'products',
        request()->routeIs('admin.brand') => 'brands',
        default => 'dashboard',
    };

    $pageTitle = match (true) {
        request()->routeIs('admin.category') => 'Danh mục',
        request()->routeIs('admin.branch') => 'Chi nhánh',
        request()->routeIs('admin.product') => 'Sản phẩm',
        request()->routeIs('admin.brand') => 'Thương hiệu',
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
  category: "{{ route('admin.category') }}",
  brands: "{{ route('admin.brand') }}"
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
