@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
@endphp

@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Bookings</h3>
    <div class="text-muted mb-3">Cek pembayaran dan update status booking service.</div>

    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif
    @if(!empty($success))
        <div class="alert alert-success">{{ $success }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            @if(count($bookings) === 0)
                <div class="text-muted">Belum ada booking.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th style="width:90px;">Booking</th>
                            <th>Customer</th>
                            <th style="width:170px;">Service</th>
                            <th style="width:150px;">Total</th>
                            <th style="width:260px;">Payment</th>
                            <th style="width:140px;">Status</th>
                            <th style="width:240px;">Update</th>
                            <th style="width:120px;">Detail</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($bookings as $b)
                            @php
                                $id = (int)($b['id'] ?? 0);
                                $proof = trim((string)($b['payment_proof'] ?? ''));
                                $proofAt = (string)($b['payment_proof_uploaded_at'] ?? '');
                                $serviceAt = (string)($b['first_service_at'] ?? '');
                                $badge = (string)($b['_badge'] ?? 'light');
                                $label = (string)($b['_label'] ?? (string)($b['status'] ?? 'pending'));
                                $pmLabel = (string)($b['_payment_method_label'] ?? 'Manual Transfer Bank');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">#{{ $id }}</div>
                                    <div class="small text-muted">{{ (string)($b['booking_date'] ?? '') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ (string)($b['customer_name'] ?? '') }}</div>
                                    <div class="small text-muted">{{ (string)($b['customer_email'] ?? '') }}</div>
                                    <div class="small text-muted">{{ (string)($b['customer_phone'] ?? '') }}</div>
                                </td>
                                <td>
                                    @if($serviceAt !== '')
                                        <div class="small">{{ $serviceAt }}</div>
                                    @else
                                        <div class="small text-muted">-</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $fmt((float)($b['total_amount'] ?? 0)) }}</div>
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
                                    <form method="POST" action="{{ route('admin.bookings') }}" class="d-flex gap-2">
                                        @csrf
                                        <input type="hidden" name="booking_id" value="{{ $id }}">
                                        <select name="status" class="form-select form-select-sm" required>
                                            @foreach($validStatuses as $s)
                                                <option value="{{ $s }}" @selected($s === strtolower((string)($b['status'] ?? 'pending')))>
                                                    {{ ucfirst($s) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit" name="update_status" value="1">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.booking_detail', ['id' => $id]) }}">View</a>
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
