@extends('layouts.app')

@section('title', 'Tentang Kami | Petshop')

@section('content')
@php
    $aboutImage = is_string($aboutImage ?? null) ? (string)$aboutImage : '';
    $hasAboutImage = $aboutImage !== '';
    $productCount = (int)($productCount ?? 0);
    $serviceCount = (int)($serviceCount ?? 0);
@endphp

<div class="container mt-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">About</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="row g-0 align-items-stretch">
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center bg-dark text-white">
                    <div class="text-uppercase small" style="opacity:.85; letter-spacing:.08em;">Tentang Kami</div>
                    <h2 class="fw-bold mt-2 mb-2">Petshop Sehat & Ceria</h2>
                    <div style="opacity:.9; max-width: 48ch;">
                        Solusi terpercaya untuk kebutuhan produk dan jasa hewan kesayangan Anda.
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('catalog', ['type' => 'product']) }}" class="btn btn-light">Lihat Produk</a>
                        <a href="{{ route('catalog', ['type' => 'service']) }}" class="btn btn-outline-light">Lihat Layanan</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                @if ($hasAboutImage)
                    <img src="{{ asset($aboutImage) }}" alt="" class="w-100 h-100" style="object-fit: cover; min-height: 280px;">
                @else
                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center" style="min-height: 280px;">
                        <div class="text-center">
                            <div class="fs-1">🐾</div>
                            <div class="text-muted">Tambahkan gambar About</div>
                            <div class="text-muted small">uploads/about/</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mt-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Produk Aktif</div>
                    <div class="fs-4 fw-bold">{{ number_format($productCount) }}</div>
                    <div class="text-muted small">Siap checkout kapan saja</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Layanan Aktif</div>
                    <div class="fs-4 fw-bold">{{ number_format($serviceCount) }}</div>
                    <div class="text-muted small">Booking dengan slot jadwal</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Fokus</div>
                    <div class="fs-4 fw-bold">Kualitas</div>
                    <div class="text-muted small">Produk aman & terpercaya</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Standar</div>
                    <div class="fs-4 fw-bold">Bersih</div>
                    <div class="text-muted small">Nyaman untuk hewan</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row align-items-start g-4 mt-4">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">📌</div>
                        <h4 class="mb-0">Profil Singkat</h4>
                    </div>
                    <div class="text-muted" style="white-space: normal;">
                        <p class="mb-3">
                            Petshop kami merupakan usaha yang bergerak di bidang penyediaan produk dan jasa untuk hewan peliharaan seperti anjing dan kucing.
                            Kami menyediakan berbagai produk berkualitas serta layanan profesional guna menjaga kesehatan dan kenyamanan hewan kesayangan.
                        </p>
                        <p class="mb-0">
                            Sistem informasi petshop ini dikembangkan untuk memudahkan pengelolaan data produk, jasa, dan pengguna, serta memberikan informasi yang jelas kepada pelanggan.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">🐾</div>
                        <h4 class="mb-0">Komitmen Kami</h4>
                    </div>
                    <div class="text-muted">
                        Memberikan pelayanan terbaik dengan mengutamakan kualitas, kebersihan, dan kepercayaan pelanggan.
                    </div>

                    <hr>
                    <div class="d-grid gap-2">
                        <a class="btn btn-primary" href="{{ route('catalog', ['type' => 'product']) }}">Mulai Belanja</a>
                        <a class="btn btn-outline-primary" href="{{ route('catalog', ['type' => 'service']) }}">Mulai Booking</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">🎯</div>
                        <h4 class="mb-0">Visi</h4>
                    </div>
                    <div class="text-muted">
                        Menjadi petshop terpercaya yang menyediakan produk dan jasa terbaik untuk meningkatkan kualitas hidup hewan peliharaan.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">✅</div>
                        <h4 class="mb-0">Misi</h4>
                    </div>
                    <ul class="mb-0 text-muted">
                        <li>Menyediakan produk hewan berkualitas dan aman</li>
                        <li>Memberikan layanan jasa yang profesional</li>
                        <li>Meningkatkan kepuasan dan kepercayaan pelanggan</li>
                        <li>Mengembangkan sistem informasi yang efektif dan efisien</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 mb-3">
        <h3 class="mb-1">Mengapa Memilih Kami?</h3>
        <div class="text-muted">Singkat, jelas, dan fokus ke pengalaman pelanggan.</div>
    </div>

    <div class="row text-center g-3">
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="fs-2 mb-2">🐶</div>
                    <div class="fw-semibold">Produk Berkualitas</div>
                    <div class="text-muted mt-2">Produk makanan dan aksesoris pilihan untuk hewan kesayangan.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="fs-2 mb-2">✂️</div>
                    <div class="fw-semibold">Layanan Profesional</div>
                    <div class="text-muted mt-2">Grooming dan jasa lainnya ditangani oleh tenaga berpengalaman.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="fs-2 mb-2">💙</div>
                    <div class="fw-semibold">Terpercaya</div>
                    <div class="text-muted mt-2">Mengutamakan kepuasan pelanggan dan kenyamanan hewan.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
