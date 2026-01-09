@extends('layouts.app')

@section('title', 'Shopping Cart')

@php
    function rupiah($n): string {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }

    $total = 0;
@endphp

@section('content')
<div class="container mt-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Cart</li>
        </ol>
    </nav>

    <h3 class="section-title">Shopping Cart</h3>

    @if (count($normalizedCart) === 0)
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column align-items-center text-center gap-2">
                    <div class="fs-1">🛒</div>
                    <div class="fs-5 fw-semibold">Keranjang masih kosong</div>
                    <div class="text-muted">Yuk pilih produk favoritmu dulu.</div>
                    <a class="btn btn-primary mt-2" href="{{ route('catalog', ['type' => 'product']) }}">Belanja Sekarang</a>
                </div>
            </div>
        </div>
    @else
        <form method="POST" action="{{ route('cart.action') }}">
            @csrf
            <input type="hidden" name="action" value="update">

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Items</div>
                                <div class="text-muted small">{{ count($normalizedCart) }} item di keranjang</div>
                            </div>
                            <a class="btn btn-outline-danger btn-sm" href="{{ route('cart.action', ['action' => 'clear']) }}">Clear</a>
                        </div>

                        <div class="card-body pt-0">
                            @foreach ($normalizedCart as $id => $entry)
                                @php
                                    $id = (int)$id;
                                    $qty = (int)($entry['qty'] ?? 1);
                                    $slotId = (int)($entry['slot_id'] ?? 0);
                                    $item = $itemsById[$id] ?? null;
                                    if (!$item) continue;
                                    $price = (float)($item->price ?? 0);
                                    $subtotal = $price * $qty;
                                    $total += $subtotal;
                                    $isService = (($item->type ?? '') === 'service');
                                    $slot = $slotId > 0 ? ($slotsById[$slotId] ?? null) : null;
                                @endphp

                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 py-3 border-top">
                                    <div class="d-flex align-items-center gap-3">
                                        @if (!empty($item->image))
                                            <img src="{{ asset('uploads/items/' . $item->image) }}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:14px;">
                                        @else
                                            <div style="width:72px;height:72px;border-radius:14px;" class="bg-light d-flex align-items-center justify-content-center">
                                                🐾
                                            </div>
                                        @endif

                                        <div>
                                            <div class="fw-semibold">
                                                <a class="text-decoration-none" href="{{ route('item.show', ['id' => (int)$id]) }}">
                                                    {{ e($item->name) }}
                                                </a>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                                <span class="text-muted small">{{ rupiah($price) }}</span>
                                                @if (!empty($item->type))
                                                    <span class="badge text-bg-light">{{ e(ucfirst((string)$item->type)) }}</span>
                                                @endif
                                                @if ($isService)
                                                    <span class="badge text-bg-light">
                                                        Schedule: {{ e((string)($slot['starts_at'] ?? 'Belum dipilih')) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($isService)
                                                <div class="small text-muted mt-1">
                                                    Ubah jadwal di <a href="{{ route('item.show', ['id' => (int)$id]) }}">halaman detail</a>.
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div style="width:110px;">
                                            <label class="form-label small text-muted mb-1">Qty</label>
                                            @if ($isService)
                                                <input type="number" class="form-control" min="1" max="1" name="qty[{{ $id }}]" value="1" readonly>
                                            @else
                                                <input type="number" class="form-control" min="0" name="qty[{{ $id }}]" value="{{ $qty }}">
                                            @endif
                                        </div>

                                        <div class="text-end" style="min-width:140px;">
                                            <div class="small text-muted">Subtotal</div>
                                            <div class="fw-semibold">{{ rupiah($subtotal) }}</div>
                                        </div>

                                        <div>
                                            <a class="btn btn-outline-danger btn-sm" href="{{ route('cart.action', ['action' => 'remove', 'item_id' => $id]) }}">Remove</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}">Continue Shopping</a>
                        <button type="submit" class="btn btn-primary">Update Cart</button>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Order Summary</div>

                            <div class="d-flex justify-content-between text-muted">
                                <span>Total</span>
                                <span class="fw-semibold text-dark">{{ rupiah($total) }}</span>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <a class="btn btn-primary" href="{{ route('checkout') }}">Checkout</a>
                                <small class="text-muted">Pembayaran belum terhubung payment gateway.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection
