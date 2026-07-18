@extends('shop.layout.app')
@section('title', 'Sản phẩm — LaptopStore')

@section('content')
<div class="breadcrumb"><a href="{{ route('shop.home') }}">Trang chủ</a> / Sản phẩm</div>

<div class="shop-layout">
  {{-- Sidebar bộ lọc --}}
  <aside>
    <form class="filter-card" method="get" action="{{ route('shop.products') }}" id="filterForm">
      <h3><i class="bi bi-funnel"></i> Bộ lọc</h3>
      @if(!empty($filters['q']))<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
      <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}" id="sortHidden">

      <div class="filter-group">
        <label>Danh mục</label>
        <a href="{{ route('shop.products', array_merge(request()->except(['category','page']), [])) }}" class="{{ empty($filters['category']) ? 'active' : '' }}">Tất cả danh mục</a>
        @foreach($categories as $c)
          <a href="{{ route('shop.products', array_merge(request()->except('page'), ['category' => $c->id])) }}" class="{{ ($filters['category'] ?? '') == $c->id ? 'active' : '' }}">{{ $c->name }}</a>
        @endforeach
      </div>

      @if($brands->count())
      <div class="filter-group">
        <label>Thương hiệu</label>
        <a href="{{ route('shop.products', array_merge(request()->except(['brand','page']), [])) }}" class="{{ empty($filters['brand']) ? 'active' : '' }}">Tất cả thương hiệu</a>
        @foreach($brands as $b)
          <a href="{{ route('shop.products', array_merge(request()->except('page'), ['brand' => $b->id])) }}" class="{{ ($filters['brand'] ?? '') == $b->id ? 'active' : '' }}">{{ $b->name }}</a>
        @endforeach
      </div>
      @endif

      <div class="filter-group">
        <label>Khoảng giá (đ)</label>
        <div class="price-row">
          <input type="number" name="min_price" placeholder="Từ" value="{{ $filters['min_price'] ?? '' }}" min="0">
          <span>—</span>
          <input type="number" name="max_price" placeholder="Đến" value="{{ $filters['max_price'] ?? '' }}" min="0">
        </div>
        @if(!empty($filters['category']))<input type="hidden" name="category" value="{{ $filters['category'] }}">@endif
        @if(!empty($filters['brand']))<input type="hidden" name="brand" value="{{ $filters['brand'] }}">@endif
        <button type="submit" class="btn btn-primary btn-sm btn-block" style="margin-top:10px">Áp dụng</button>
      </div>
    </form>
  </aside>

  {{-- Danh sách --}}
  <div>
    <div class="toolbar">
      <div>{{ $paginator->total() }} sản phẩm @if(!empty($filters['q']))cho "<b>{{ $filters['q'] }}</b>"@endif</div>
      <div>
        <label style="font-size:.88rem;color:var(--muted)">Sắp xếp:</label>
        <select id="sortSelect" onchange="applySort(this.value)">
          <option value="">Mới nhất</option>
          <option value="price_asc" @selected(($filters['sort'] ?? '')==='price_asc')>Giá thấp → cao</option>
          <option value="price_desc" @selected(($filters['sort'] ?? '')==='price_desc')>Giá cao → thấp</option>
          <option value="name" @selected(($filters['sort'] ?? '')==='name')>Tên A → Z</option>
        </select>
      </div>
    </div>

    @if($products->count())
      <div class="product-grid">
        @foreach($products as $product)
          @include('shop.partials.product-card', ['product' => $product])
        @endforeach
      </div>

      @if($paginator->hasPages())
        <div class="pagination">
          @if($paginator->onFirstPage())
            <span class="disabled"><span>«</span></span>
          @else
            <a href="{{ $paginator->previousPageUrl() }}">«</a>
          @endif
          @foreach($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            @if($page == $paginator->currentPage())
              <span class="active"><span>{{ $page }}</span></span>
            @else
              <a href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
          @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">»</a>
          @else
            <span class="disabled"><span>»</span></span>
          @endif
        </div>
      @endif
    @else
      <div class="empty"><i class="bi bi-search"></i>Không tìm thấy sản phẩm phù hợp</div>
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
function applySort(value){
  const url = new URL(window.location.href);
  if(value) url.searchParams.set('sort', value); else url.searchParams.delete('sort');
  url.searchParams.delete('page');
  window.location.href = url.toString();
}
</script>
@endpush
