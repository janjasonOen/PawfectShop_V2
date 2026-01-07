@extends('layouts.app')

@section('title', 'Petshop Sehat & Ceria')

@section('content')
@php
    function rupiah($n): string {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }

    $bannerImages = is_array($bannerImages ?? null) ? $bannerImages : [];
    $bannerImages = array_values(array_filter($bannerImages, fn($p) => is_string($p) && trim($p) !== ''));
    $hasBanners = count($bannerImages) > 0;
@endphp

<div class="container mt-4">
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="row g-0 align-items-stretch">
            <div class="col-12 col-lg-7">
                <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center bg-dark text-white">
                    <div class="text-uppercase small" style="opacity:.85; letter-spacing:.08em;">Petshop Sehat & Ceria</div>
                    <h1 class="fw-bold mt-2 mb-2">Semua kebutuhan hewan kesayangan, satu tempat</h1>
                    <div class="mb-4" style="opacity:.9; max-width: 44ch;">
                        Belanja produk berkualitas dan booking layanan profesional dengan proses cepat.
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('catalog', ['type' => 'product']) }}" class="btn btn-light">Lihat Produk</a>
                        <a href="{{ route('catalog', ['type' => 'service']) }}" class="btn btn-outline-light">Lihat Layanan</a>
                        <a href="{{ route('cart') }}" class="btn btn-outline-light">🛒 Keranjang</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5">
                @if ($hasBanners)
                    <div id="homeHeroCarousel" class="carousel slide h-100" data-bs-ride="carousel">
                        @if (count($bannerImages) > 1)
                            <div class="carousel-indicators">
                                @foreach ($bannerImages as $i => $path)
                                    <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="{{ (int)$i }}" class="{{ $i === 0 ? 'active' : '' }}" aria-current="{{ $i === 0 ? 'true' : 'false' }}" aria-label="Slide {{ (int)$i + 1 }}"></button>
                                @endforeach
                            </div>
                        @endif

                        <div class="carousel-inner h-100">
                            @foreach ($bannerImages as $i => $path)
                                <div class="carousel-item {{ $i === 0 ? 'active' : '' }} h-100">
                                    <img
                                        src="{{ asset($path) }}"
                                        alt=""
                                        class="d-block w-100 h-100"
                                        style="object-fit: cover; min-height: 280px;"
                                    >
                                </div>
                            @endforeach
                        </div>

                        @if (count($bannerImages) > 1)
                            <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        @endif
                    </div>
                @else
                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center" style="min-height: 280px;">
                        <div class="text-center">
                            <div class="fs-1">🐾</div>
                            <div class="text-muted">Welcome to PawfectShop</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row mt-4 g-3">
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">🐶</div>
                        <div class="fw-semibold">Produk Berkualitas</div>
                    </div>
                    <div class="text-muted">Makanan, vitamin, aksesoris, dan kebutuhan harian hewan peliharaan.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">✂️</div>
                        <div class="fw-semibold">Layanan Profesional</div>
                    </div>
                    <div class="text-muted">Grooming, penitipan, dan layanan lain dengan jadwal yang jelas.</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">💙</div>
                        <div class="fw-semibold">Terpercaya</div>
                    </div>
                    <div class="text-muted">Proses checkout rapi, bukti pembayaran bisa di-upload, dan riwayat tersimpan.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-end justify-content-between mt-5 mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Produk</h3>
            <div class="text-muted">Pilihan produk terbaru yang siap kamu checkout.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}">Lihat semua</a>
    </div>

    <div class="row g-3">
        @forelse (($featuredProducts ?? []) as $p)
            <div class="col-6 col-md-3">
                <a href="{{ route('item.show', ['id' => (int)$p->id]) }}" class="text-decoration-none text-dark">
                    <div class="card h-100 shadow-sm">
                        @if (!empty($p->image))
                            <img src="{{ asset('uploads/items/' . $p->image) }}" alt="" class="card-img-top" style="height: 160px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                <div class="text-muted small">No image</div>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="small text-muted">{{ e((string)($p->category ?? '')) }}</div>
                            <div class="fw-semibold" style="line-height:1.2;">
                                {{ e((string)$p->name) }}
                            </div>
                            <div class="mt-2 fw-bold">{{ rupiah($p->price) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary mb-0">Belum ada produk.</div>
            </div>
        @endforelse
    </div>

    <div class="d-flex align-items-end justify-content-between mt-5 mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Layanan</h3>
            <div class="text-muted">Booking layanan dengan jadwal slot yang tersedia.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'service']) }}">Lihat semua</a>
    </div>

    <div class="row g-3">
        @forelse (($featuredServices ?? []) as $s)
            <div class="col-6 col-md-3">
                <a href="{{ route('item.show', ['id' => (int)$s->id]) }}" class="text-decoration-none text-dark">
                    <div class="card h-100 shadow-sm">
                        @if (!empty($s->image))
                            <img src="{{ asset('uploads/items/' . $s->image) }}" alt="" class="card-img-top" style="height: 160px; object-fit: cover;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                <div class="text-muted small">No image</div>
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="small text-muted">{{ e((string)($s->category ?? '')) }}</div>
                            <div class="fw-semibold" style="line-height:1.2;">
                                {{ e((string)$s->name) }}
                            </div>
                            <div class="mt-2 fw-bold">{{ rupiah($s->price) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-secondary mb-0">Belum ada layanan.</div>
            </div>
        @endforelse
    </div>

    <div class="card shadow-sm mt-5">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="fw-semibold">Siap belanja atau booking hari ini?</div>
                <div class="text-muted">Mulai dari katalog, lalu checkout dalam beberapa langkah.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="{{ route('catalog', ['type' => 'product']) }}">Mulai Belanja</a>
                <a class="btn btn-outline-primary" href="{{ route('catalog', ['type' => 'service']) }}">Mulai Booking</a>
            </div>
        </div>
    </div>
</div>
@endsection
