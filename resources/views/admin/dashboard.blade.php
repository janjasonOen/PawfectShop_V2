@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Dashboard Admin</h3>
    <div class="text-muted mb-3">Ringkasan data sistem.</div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Kategori</h6>
                    <h3>{{ (int)$totalKategori }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Produk</h6>
                    <h3>{{ (int)$totalProduk }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Jasa</h6>
                    <h3>{{ (int)$totalJasa }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>User</h6>
                    <h3>{{ (int)$totalUser }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Order</h6>
                    <h3>{{ (int)$totalOrder }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <p>
            Selamat datang,
            <strong>{{ is_array($user) ? ($user['name'] ?? '') : '' }}</strong>.
            Gunakan menu di atas untuk mengelola data sistem petshop.
        </p>
    </div>
</div>
@endsection
