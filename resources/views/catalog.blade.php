@extends('layouts.app')

@section('title', 'Katalog Produk & Jasa')

@php
    function excerpt_text($text, $limit = 90) {
        $text = trim(strip_tags((string)$text));
        if ($text === '') return '';
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $limit) return $text;
            return mb_substr($text, 0, $limit) . '…';
        }
        if (strlen($text) <= $limit) return $text;
        return substr($text, 0, $limit) . '…';
    }
@endphp

@section('content')
<div class="container-fluid px-3 px-lg-5 mt-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('catalog', ['type' => $type]) }}">Catalog</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $type === 'product' ? 'Products' : 'Services' }}</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h3 class="section-title mb-1">
                {{ $type === 'product' ? 'Products' : 'Services' }}
            </h3>
            <div class="text-muted">
                {!! $type === 'product'
                    ? 'Pilih produk terbaik untuk hewan kesayanganmu.'
                    : 'Pilih layanan profesional untuk hewan kesayanganmu.' !!}
            </div>
        </div>

        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link {{ $type === 'product' ? 'active' : '' }}" href="{{ route('catalog', ['type' => 'product']) }}">Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $type === 'service' ? 'active' : '' }}" href="{{ route('catalog', ['type' => 'service']) }}">Services</a>
            </li>
        </ul>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-3 col-xl-2">
            <div class="card shadow-sm sticky-top" style="top: 84px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="fw-semibold">Filters</div>
                        <a class="small" href="{{ route('catalog', ['type' => $type]) }}">Reset</a>
                    </div>

                    <form method="GET" class="vstack gap-3" action="{{ route('catalog') }}">
                        <input type="hidden" name="type" value="{{ e($type) }}">

                        <div>
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" name="q" value="{{ e($q) }}" class="form-control form-control-sm" placeholder="Cari nama / deskripsi...">
                        </div>

                        <div>
                            <label class="form-label small text-muted">Category</label>
                            <select name="category_id" class="form-select form-select-sm">
                                <option value="0">All categories</option>
                                @foreach ($categories as $c)
                                    <option value="{{ (int)$c->id }}" {{ ((int)$c->id === (int)$categoryId) ? 'selected' : '' }}>
                                        {{ e($c->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label small text-muted">Sort</label>
                            <select name="sort" class="form-select form-select-sm">
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>Newest</option>
                                <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low → High</option>
                                <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High → Low</option>
                                <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Name: A → Z</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Apply</button>

                        <div class="text-muted small">
                            <span class="fw-semibold">{{ count($items) }}</span> results
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9 col-xl-10">
            @if (count($items) === 0)
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <div class="fw-semibold">Belum ada item</div>
                                <div class="text-muted small">Silakan tambah data melalui admin.</div>
                            </div>
                            <a class="btn btn-outline-secondary" href="{{ route('home') }}">Kembali</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="row g-4">
                    @foreach ($items as $item)
                        @php
                            $isProduct = (($item->type ?? '') === 'product');
                            $stock = $isProduct ? (int)($item->stock ?? 0) : null;
                            $outOfStock = $isProduct && $stock <= 0;
                            $desc = excerpt_text($item->description ?? '', 100);
                        @endphp

                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm">
                                <div class="position-relative">
                                    @if (!empty($item->image))
                                        <a href="{{ route('item.show', ['id' => (int)$item->id]) }}" class="d-block">
                                            <img src="{{ asset('uploads/items/' . $item->image) }}" class="card-img-top" alt="">
                                        </a>
                                    @else
                                        <a href="{{ route('item.show', ['id' => (int)$item->id]) }}" class="d-block">
                                            <div class="bg-light" style="height:180px;"></div>
                                        </a>
                                    @endif

                                    <span class="badge text-bg-dark position-absolute top-0 start-0 m-2">
                                        {{ $isProduct ? 'Product' : 'Service' }}
                                    </span>

                                    @if ($outOfStock)
                                        <span class="badge text-bg-secondary position-absolute top-0 end-0 m-2">
                                            Out of stock
                                        </span>
                                    @endif
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <h6 class="mb-1">
                                            <a class="text-decoration-none" href="{{ route('item.show', ['id' => (int)$item->id]) }}">
                                                {{ e($item->name) }}
                                            </a>
                                        </h6>
                                    </div>

                                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                        <span class="badge text-bg-light">{{ e($item->category) }}</span>
                                        @if ($isProduct)
                                            <span class="text-muted small">Stok: {{ $stock }}</span>
                                        @endif
                                    </div>

                                    @if ($desc !== '')
                                        <div class="text-muted small mb-3">{{ e($desc) }}</div>
                                    @else
                                        <div class="text-muted small mb-3">&nbsp;</div>
                                    @endif

                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="fw-bold">Rp {{ number_format((float)($item->price ?? 0)) }}</div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            @if ($isProduct)
                                                <form method="POST" action="{{ route('cart.action') }}" class="m-0">
                                                    @csrf
                                                    <input type="hidden" name="action" value="add">
                                                    <input type="hidden" name="item_id" value="{{ (int)$item->id }}">
                                                    <input type="hidden" name="qty" value="1">
                                                    <button type="submit" class="btn btn-primary btn-sm" {{ $outOfStock ? 'disabled' : '' }}>
                                                        Add to Cart
                                                    </button>
                                                </form>
                                            @else
                                                <a class="btn btn-primary btn-sm" href="{{ route('item.show', ['id' => (int)$item->id]) }}">Choose Schedule</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
