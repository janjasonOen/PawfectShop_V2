@extends('layouts.app')

@section('title', 'Kontak | Petshop')

@section('content')
@php
    $contactImage = is_string($contactImage ?? null) ? (string)$contactImage : '';
    $hasContactImage = $contactImage !== '';

    // Keep these aligned with footer for now
    $addressText = 'Jakarta, Indonesia';
    $phoneText = '0812-xxxx-xxxx';
    $emailText = 'petshop@email.com';

    $phoneDigits = preg_replace('/\D+/', '', $phoneText);
    $telHref = $phoneDigits ? ('tel:+' . $phoneDigits) : 'tel:';
    $mailtoHref = 'mailto:' . $emailText;
@endphp

<div class="container mt-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Contact</li>
        </ol>
    </nav>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="row g-0 align-items-stretch">
            <div class="col-12 col-lg-6">
                <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center bg-dark text-white">
                    <div class="text-uppercase small" style="opacity:.85; letter-spacing:.08em;">Kontak</div>
                    <h2 class="fw-bold mt-2 mb-2">Kami siap membantu</h2>
                    <div style="opacity:.9; max-width: 52ch;">
                        Hubungi kami untuk pertanyaan produk, layanan, atau bantuan checkout.
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a class="btn btn-light" href="{{ $telHref }}">📞 Telepon</a>
                        <a class="btn btn-outline-light" href="{{ $mailtoHref }}">✉️ Email</a>
                        <a class="btn btn-outline-light" href="{{ route('catalog', ['type' => 'service']) }}">Lihat Layanan</a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                @if ($hasContactImage)
                    <img src="{{ asset($contactImage) }}" alt="" class="w-100 h-100" style="object-fit: cover; min-height: 280px;">
                @else
                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center" style="min-height: 280px;">
                        <div class="text-center">
                            <div class="fs-1">📍</div>
                            <div class="text-muted">Tambahkan gambar Contact</div>
                            <div class="text-muted small">uploads/contact/</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mt-4">
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">📍</div>
                        <div class="fw-semibold">Alamat</div>
                    </div>
                    <div class="text-muted">{{ $addressText }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">📞</div>
                        <div class="fw-semibold">Telepon</div>
                    </div>
                    <div class="text-muted">{{ $phoneText }}</div>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ $telHref }}">Call</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">✉️</div>
                        <div class="fw-semibold">Email</div>
                    </div>
                    <div class="text-muted">{{ $emailText }}</div>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ $mailtoHref }}">Email</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-3">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="fs-4">🕒</div>
                        <h4 class="mb-0">Jam Operasional</h4>
                    </div>
                    <div class="text-muted">Senin – Minggu: 09.00 – 21.00</div>
                    <div class="text-muted small mt-2">*Sesuaikan jam operasional sesuai toko kamu.</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="fs-4">🚀</div>
                            <h4 class="mb-0">Butuh cepat?</h4>
                        </div>
                        <div class="text-muted">Kamu bisa mulai dari katalog dan checkout langsung.</div>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <a class="btn btn-primary" href="{{ route('catalog', ['type' => 'product']) }}">Mulai Belanja</a>
                        <a class="btn btn-outline-primary" href="{{ route('catalog', ['type' => 'service']) }}">Mulai Booking</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
