@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
@endphp

@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Orders</h3>
    <div class="text-muted mb-3">Cek pembayaran dan update status order.</div>

    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif
    @if(!empty($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if(count($orders) === 0)
                <div class="text-muted">Belum ada order.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width:90px;">Order</th>
                            <th>Customer</th>
                            <th style="width:170px;">Service</th>
                            <th style="width:170px;">Total</th>
                            <th style="width:260px;">Payment</th>
                            <th style="width:140px;">Status</th>
                            <th style="width:240px;">Update</th>
                            <th style="width:120px;">Detail</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $o)
                            @php
                                $id = (int)($o['id'] ?? 0);
                                $proof = trim((string)($o['payment_proof'] ?? ''));
                                $proofAt = (string)($o['payment_proof_uploaded_at'] ?? '');
                                $serviceAt = (string)($o['first_service_at'] ?? '');
                                $shipFee = $hasShippingColumns ? (float)($o['shipping_fee'] ?? 0) : 0.0;
                                $shipLabel = $hasShippingColumns ? (string)($o['_shipping_label'] ?? '') : '';
                                $shipMethod = $hasShippingColumns ? (string)($o['shipping_method'] ?? '') : '';
                                $badge = (string)($o['_badge'] ?? 'light');
                                $label = (string)($o['_label'] ?? (string)($o['status'] ?? 'pending'));
                                $pmLabel = (string)($o['_payment_method_label'] ?? 'Manual Transfer Bank');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">#{{ $id }}</div>
                                    <div class="small text-muted">{{ (string)($o['order_date'] ?? '') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ (string)($o['customer_name'] ?? '') }}</div>
                                    <div class="small text-muted">{{ (string)($o['customer_email'] ?? '') }}</div>
                                    <div class="small text-muted">{{ (string)($o['customer_phone'] ?? '') }}</div>
                                </td>
                                <td>
                                    @if($serviceAt !== '')
                                        <div class="small">{{ $serviceAt }}</div>
                                    @else
                                        <div class="small text-muted">-</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $fmt((float)($o['total_amount'] ?? 0)) }}</div>
                                    @if($hasShippingColumns && $shipFee > 0)
                                        <div class="small text-muted">
                                            Shipping: {{ $shipLabel !== '' ? $shipLabel : $shipMethod }} ({{ $fmt($shipFee) }})
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small"><span class="text-muted">Method:</span> {{ $pmLabel }}</div>
                                    <div class="small">
                                        <span class="text-muted">Proof:</span>
                                        @if($proof !== '')
                                            <a href="/{{ ltrim($proof, '/') }}" target="_blank" rel="noopener">View</a>
                                            @if($proofAt !== '')
                                                <span class="text-muted">({{ $proofAt }})</span>
                                            @endif
                                        @else
                                            <span class="text-danger">Belum diupload</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.orders') }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="order_id" value="{{ $id }}">
                                        <select name="status" class="form-select form-select-sm" required>
                                            @foreach($validStatuses as $s)
                                                <option value="{{ $s }}" @selected($s === strtolower((string)($o['status'] ?? 'pending')))>
                                                    {{ ucfirst($s) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit" name="update_status" value="1">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.order_detail', ['id' => $id]) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
