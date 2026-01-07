@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
@endphp

@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Order #{{ (int)$orderId }}</h3>
    <div class="text-muted mb-3">Detail order (produk) dan status.</div>

    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif
    @if(!empty($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">Status</div>
                            <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>

                            @if(!empty($order->order_date))
                                <div class="small text-muted mt-2">Tanggal: {{ $order->order_date }}</div>
                            @endif
                        </div>

                        <div class="text-end">
                            <div class="small text-muted">Total</div>
                            <div class="fw-bold fs-5">{{ $fmt($orderTotal) }}</div>
                        </div>
                    </div>

                    <hr>

                    <div class="fw-semibold mb-2">Customer</div>
                    <div class="small text-muted">{{ $order->customer_name ?? '' }}</div>
                    <div class="small text-muted">{{ $order->customer_email ?? '' }}</div>
                    <div class="small text-muted">{{ $order->customer_phone ?? '' }}</div>
                    <div class="mt-2" style="white-space:pre-line;">{{ $order->customer_address ?? '' }}</div>

                    <hr>

                    <div class="fw-semibold mb-2">Payment</div>
                    <div class="small text-muted">Method: {{ $paymentMethodLabel !== '' ? $paymentMethodLabel : 'Manual Transfer Bank' }}</div>
                    <div class="small text-muted">
                        Proof:
                        @if($paymentProofPath !== '')
                            <a href="/{{ ltrim($paymentProofPath, '/') }}" target="_blank" rel="noopener">View</a>
                            @if(!empty($order->payment_proof_uploaded_at))
                                <span class="text-muted">({{ $order->payment_proof_uploaded_at }})</span>
                            @endif
                        @else
                            <span class="text-danger">Belum diupload</span>
                        @endif
                    </div>

                    <hr>

                    <div class="fw-semibold mb-2">Shipping</div>
                    @if($shippingFee > 0)
                        @php $sm = (string)($order->shipping_method ?? ''); @endphp
                        <div class="small text-muted">Courier: {{ $shippingLabel !== '' ? $shippingLabel : ($sm !== '' ? $sm : 'Store Shipping') }}</div>
                        <div class="small text-muted">Fee: {{ $fmt($shippingFee) }}</div>
                    @else
                        <div class="small text-muted">Tidak ada pengiriman.</div>
                    @endif

                    <hr>

                    <div class="fw-semibold mb-2">Update Status</div>
                    <form method="POST" action="{{ route('admin.order_detail', ['id' => (int)$orderId]) }}" class="d-flex gap-2" style="max-width:420px;">
                        @csrf
                        <select name="status" class="form-select" required>
                            @foreach($validStatuses as $s)
                                <option value="{{ $s }}" @selected($s === strtolower((string)($order->status ?? 'pending')))>
                                    {{ ucfirst($s) }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary" type="submit" name="update_status" value="1">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="fw-semibold mb-3">Items</div>

                    @if(count($items) === 0)
                        <div class="text-muted">Tidak ada item.</div>
                    @else
                        @foreach($items as $it)
                            @php
                                $qty = (int)($it['quantity'] ?? 0);
                                $price = (float)($it['price'] ?? 0);
                                $sub = $qty * $price;
                                $isService = ((string)($it['type'] ?? '') === 'service');
                                $startsAt = (string)($it['starts_at'] ?? '');
                            @endphp

                            <div class="d-flex justify-content-between align-items-start gap-3 py-2 border-top">
                                <div>
                                    <div class="fw-semibold">{{ $it['name'] ?? '' }}</div>
                                    <div class="small text-muted">{{ ucfirst((string)($it['type'] ?? '')) }} · {{ $qty }} × {{ $fmt($price) }}</div>
                                    @if($isService)
                                        <div class="small text-muted">Schedule: {{ $startsAt !== '' ? $startsAt : '-' }}</div>
                                    @endif
                                </div>
                                <div class="fw-semibold" style="white-space:nowrap;">{{ $fmt($sub) }}</div>
                            </div>
                        @endforeach

                        <hr>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">{{ $fmt($subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Shipping{{ $shippingLabel !== '' ? ' (' . $shippingLabel . ')' : '' }}</span>
                            <span class="fw-semibold">{{ $fmt($shippingFee) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">Total</span>
                            <span class="fw-bold fs-5">{{ $fmt($orderTotal) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
