@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
    $error = (string) request()->query('error', '');
    $uploaded = (int) request()->query('uploaded', 0);
@endphp

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-receipt fs-4"></i>
                <h3 class="section-title mb-0">Order #{{ (int)$orderId }}</h3>
            </div>
            <div class="text-muted">Detail pesanan dan ringkasan pembayaran.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('customer.orders') }}"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}"><i class="bi bi-bag me-1"></i>Belanja</a>
        </div>
    </div>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if($uploaded)
        <div class="alert alert-success">Bukti pembayaran berhasil diupload.</div>
    @endif

    @php
        $canUpload = strtolower((string) ($order->status ?? '')) === 'pending';
    @endphp

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-semibold">Items</div>
                            <div class="text-muted small">Cek item, qty, dan jadwal service (jika ada).</div>
                        </div>
                        <div class="text-end">
                            <div class="d-flex justify-content-end gap-2 flex-wrap">
                                <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                                @if($paymentProofPath !== '')
                                    <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Paid Proof</span>
                                @else
                                    <span class="badge text-bg-warning"><i class="bi bi-exclamation-circle me-1"></i>Need Proof</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>

                    @if(count($items) === 0)
                        <div class="text-muted">Tidak ada item.</div>
                    @else
                        <div class="d-flex flex-column gap-3">
                            @foreach($items as $it)
                                @php
                                    $qty = (int)($it['quantity'] ?? 0);
                                    $price = (float)($it['price'] ?? 0);
                                    $sub = $qty * $price;
                                    $isService = ((string)($it['type'] ?? '') === 'service');
                                    $startsAt = (string)($it['starts_at'] ?? '');
                                    $itemId = (int)($it['item_id'] ?? 0);
                                @endphp

                                <div class="border rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div class="d-flex align-items-start gap-3">
                                            @php $img = trim((string) ($it['image'] ?? '')); @endphp
                                            @if($img !== '')
                                                <img src="{{ asset('uploads/items/' . $img) }}" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:14px;">
                                            @else
                                                <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center" style="width:72px;height:72px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            @endif

                                            <div>
                                                <div class="fw-semibold">
                                                    <a class="text-decoration-none" href="{{ route('item.show', ['id' => $itemId]) }}">{{ $it['name'] ?? '' }}</a>
                                                </div>
                                            <div class="text-muted small">
                                                {{ ucfirst((string)($it['type'] ?? '')) }} · Qty: {{ $qty }} · {{ $fmt($price) }}
                                            </div>
                                            @if($isService)
                                                <div class="text-muted small mt-1"><i class="bi bi-calendar-event me-1"></i>Schedule: {{ $startsAt !== '' ? $startsAt : 'Belum ada' }}</div>
                                            @endif
                                            </div>
                                        </div>
                                        <div class="fw-semibold" style="white-space:nowrap;">{{ $fmt($sub) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="d-flex flex-column gap-3">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-2">Order Summary</div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">{{ $fmt($subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted">Shipping{{ $shippingLabel !== '' ? ' (' . $shippingLabel . ')' : '' }}</span>
                            <span class="fw-semibold">{{ $fmt($shippingFee) }}</span>
                        </div>
                        <hr class="my-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-bold fs-5">{{ $fmt($orderTotal) }}</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-2">Status & Info</div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted">Status</span>
                            <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                        </div>
                        @if(!empty($order->order_date))
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="text-muted">Tanggal</span>
                                <span class="small">{{ $order->order_date }}</span>
                            </div>
                        @endif
                        @if(!empty($order->service_datetime))
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <span class="text-muted">Service</span>
                                <span class="small">{{ $order->service_datetime }}</span>
                            </div>
                        @endif
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="text-muted">Payment</span>
                            <span class="small">{{ $paymentMethodLabel !== '' ? $paymentMethodLabel : 'Manual Transfer Bank' }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="text-muted">Proof</span>
                            <span class="small">{{ $paymentProofPath !== '' ? 'Sudah diupload' : 'Belum diupload' }}</span>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm" id="payment">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-2">Bukti Pembayaran</div>

                        @if($paymentProofPath !== '')
                            <div class="small text-muted mb-2">
                                Bukti sudah diupload:
                                <a href="/{{ ltrim($paymentProofPath, '/') }}" target="_blank" rel="noopener">Lihat file</a>
                            </div>
                            @if(!$canUpload)
                                <div class="text-muted small">Upload ulang tidak tersedia karena status sudah {{ ucfirst((string) ($order->status ?? '-')) }}.</div>
                            @endif
                        @else
                            <div class="small text-muted mb-2">Format: JPG/PNG/PDF (maks 5MB)</div>
                        @endif

                        @if($canUpload)
                            <form method="POST" action="{{ route('payment_proof.upload') }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                                @csrf
                                <input type="hidden" name="type" value="order">
                                <input type="hidden" name="order_id" value="{{ (int)$orderId }}">
                                <input class="form-control" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Upload Bukti</button>
                            </form>
                        @else
                            @if($paymentProofPath === '')
                                <div class="text-muted small">Upload bukti pembayaran tidak tersedia karena status sudah {{ ucfirst((string) ($order->status ?? '-')) }}.</div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="fw-semibold mb-2">Customer & Shipping</div>
                        <div class="small text-muted">{{ $order->customer_name ?? '' }}</div>
                        <div class="small text-muted">{{ $order->customer_email ?? '' }}</div>
                        <div class="small text-muted">{{ $order->customer_phone ?? '' }}</div>
                        <div class="mt-2 small" style="white-space:pre-line;">{{ $order->customer_address ?? '' }}</div>

                        <hr>

                        @if($shippingFee > 0)
                            <div class="small text-muted">Courier: {{ $shippingLabel !== '' ? $shippingLabel : 'Store Shipping' }}</div>
                            <div class="small text-muted">Fee: {{ $fmt($shippingFee) }}</div>
                        @else
                            <div class="small text-muted">Tidak ada pengiriman (service only).</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
