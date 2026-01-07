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
    <h2 class="mb-4">Checkout Success</h2>

    @if($uploaded)
        <div class="alert alert-success">Bukti pembayaran berhasil diupload.</div>
    @endif

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="row g-4">
        @if($order)
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Order #{{ (int)$orderId }}</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Status:</strong> {{ $order->status ?? '-' }}</div>
                        @if($orderPaymentMethodLabel)
                            <div class="mb-2"><strong>Payment:</strong> {{ $orderPaymentMethodLabel }}</div>
                        @endif
                        <div class="mb-2"><strong>Nama:</strong> {{ $order->customer_name ?? '-' }}</div>
                        <div class="mb-2"><strong>Email:</strong> {{ $order->customer_email ?? '-' }}</div>
                        <div class="mb-2"><strong>Telepon:</strong> {{ $order->customer_phone ?? '-' }}</div>
                        <div class="mb-3"><strong>Alamat:</strong><br>{{ $order->customer_address ?? '-' }}</div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orderItems as $row)
                                        @php
                                            $sub = ((float)($row['price'] ?? 0)) * ((int)($row['quantity'] ?? 0));
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $row['name'] ?? '' }}</div>
                                                <div class="text-muted small">Qty: {{ (int)($row['quantity'] ?? 0) }}</div>
                                            </td>
                                            <td class="text-end">{{ $fmt($sub) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span class="fw-semibold">{{ $fmt($orderSubtotal) }}</span>
                        </div>

                        @if($shippingFee > 0)
                            <div class="d-flex justify-content-between mt-1">
                                <span>Shipping {{ $shippingLabel ? '(' . $shippingLabel . ')' : '' }}</span>
                                <span class="fw-semibold">{{ $fmt($shippingFee) }}</span>
                            </div>
                        @endif

                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span class="fw-bold">Total Order</span>
                            <span class="fw-bold">{{ $fmt($orderTotal) }}</span>
                        </div>

                        <hr>
                        <div>
                            <div class="fw-semibold mb-2">Bukti Pembayaran</div>
                            @php
                                $canUploadOrder = strtolower((string) ($order->status ?? '')) === 'pending';
                            @endphp
                            @if($orderPaymentProofPath)
                                <div class="mb-2">
                                    <a href="/{{ ltrim($orderPaymentProofPath, '/') }}" target="_blank">Lihat bukti pembayaran</a>
                                </div>
                            @else
                                <div class="text-muted small mb-2">Belum ada bukti pembayaran.</div>
                            @endif

                            @if($canUploadOrder)
                                <form method="POST" action="{{ route('payment_proof.upload') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="type" value="order">
                                    <input type="hidden" name="order_id" value="{{ (int)$orderId }}">
                                    <input class="form-control mb-2" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <button class="btn btn-outline-primary" type="submit">Upload Bukti Order</button>
                                </form>
                            @else
                                @if(!$orderPaymentProofPath)
                                    <div class="text-muted small">Upload tidak tersedia karena status sudah {{ ucfirst((string) ($order->status ?? '-')) }}.</div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($booking)
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Booking #{{ (int)$bookingId }}</div>
                    <div class="card-body">
                        <div class="mb-2"><strong>Status:</strong> {{ $booking->status ?? '-' }}</div>
                        @if($bookingPaymentMethodLabel)
                            <div class="mb-2"><strong>Payment:</strong> {{ $bookingPaymentMethodLabel }}</div>
                        @endif
                        @if(!empty($booking->service_datetime))
                            <div class="mb-2"><strong>Service Datetime:</strong> {{ $booking->service_datetime }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bookingItems as $row)
                                        @php
                                            $sub = ((float)($row['price'] ?? 0)) * ((int)($row['quantity'] ?? 0));
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $row['name'] ?? '' }}</div>
                                                <div class="text-muted small">
                                                    Qty: {{ (int)($row['quantity'] ?? 0) }}
                                                    @if(!empty($row['starts_at']))
                                                        · Jadwal: {{ $row['starts_at'] }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $fmt($sub) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span class="fw-bold">Total Booking</span>
                            <span class="fw-bold">{{ $fmt($bookingTotal) }}</span>
                        </div>

                        <hr>
                        <div>
                            <div class="fw-semibold mb-2">Bukti Pembayaran</div>
                            @php
                                $canUploadBooking = strtolower((string) ($booking->status ?? '')) === 'pending';
                            @endphp
                            @if($bookingPaymentProofPath)
                                <div class="mb-2">
                                    <a href="/{{ ltrim($bookingPaymentProofPath, '/') }}" target="_blank">Lihat bukti pembayaran</a>
                                </div>
                            @else
                                <div class="text-muted small mb-2">Belum ada bukti pembayaran.</div>
                            @endif

                            @if($canUploadBooking)
                                <form method="POST" action="{{ route('payment_proof.upload') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="type" value="booking">
                                    <input type="hidden" name="booking_id" value="{{ (int)$bookingId }}">
                                    <input class="form-control mb-2" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <button class="btn btn-outline-primary" type="submit">Upload Bukti Booking</button>
                                </form>
                            @else
                                @if(!$bookingPaymentProofPath)
                                    <div class="text-muted small">Upload tidak tersedia karena status sudah {{ ucfirst((string) ($booking->status ?? '-')) }}.</div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-4">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="fw-semibold">Grand Total</div>
                <div class="fs-5 fw-bold">{{ $fmt($grandTotal) }}</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a class="btn btn-primary" href="{{ route('catalog', ['type' => 'product']) }}">Kembali Belanja</a>
        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'service']) }}">Booking Layanan</a>
    </div>
</div>
@endsection
