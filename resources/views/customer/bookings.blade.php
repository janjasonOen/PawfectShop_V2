@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
@endphp

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-calendar-check fs-4"></i>
                <h3 class="section-title mb-0">Booking Saya</h3>
            </div>
            <div class="text-muted">Riwayat booking service, status, dan bukti pembayaran.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('customer.orders') }}"><i class="bi bi-receipt me-1"></i>Orders</a>
            <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'service']) }}"><i class="bi bi-search me-1"></i>Services</a>
        </div>
    </div>

    @if(count($bookings) === 0)
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column align-items-center text-center gap-2">
                    <i class="bi bi-calendar2-week fs-1"></i>
                    <div class="fs-5 fw-semibold">Belum ada booking</div>
                    <div class="text-muted">Pilih service dan tentukan jadwal di halaman detail service.</div>
                    <a class="btn btn-accent mt-2" href="{{ route('catalog', ['type' => 'service']) }}">Ke Services</a>
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($bookings as $b)
                @php
                    $bookingId = (int)($b['id'] ?? 0);
                    $totalAmount = (float)($b['total_amount'] ?? 0);
                    $bookingDate = (string)($b['booking_date'] ?? '');
                    $firstServiceAt = (string)($b['first_service_at'] ?? '');
                    $paymentProof = trim((string)($b['payment_proof'] ?? ''));
                    $badge = (string)($b['_badge'] ?? 'light');
                    $label = (string)($b['_label'] ?? (string)($b['status'] ?? 'pending'));
                    $statusRaw = (string) ($b['status'] ?? '');
                    $canUpload = strtolower($statusRaw) === 'pending';
                    $firstItemName = (string) ($b['first_item_name'] ?? '');
                    $firstItemImage = trim((string) ($b['first_item_image'] ?? ''));
                    $itemCount = (int) ($b['item_count'] ?? 0);
                @endphp

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="p-4 border-bottom">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div class="d-flex align-items-start gap-3">
                                        @if($firstItemImage !== '')
                                            <img src="{{ asset('uploads/items/' . $firstItemImage) }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:12px;">
                                        @else
                                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                                <i class="bi bi-scissors text-muted"></i>
                                            </div>
                                        @endif

                                        <div>
                                            <div class="fw-semibold">Booking #{{ $bookingId }}</div>
                                            @if($firstItemName !== '')
                                                <div class="small text-muted">
                                                    {{ $firstItemName }}
                                                    @if($itemCount > 1)
                                                        <span class="mx-1">•</span>
                                                        +{{ $itemCount - 1 }} item
                                                    @endif
                                                </div>
                                            @endif

                                            <div class="small text-muted">
                                            @if($bookingDate !== '')
                                                <span><i class="bi bi-clock me-1"></i>{{ $bookingDate }}</span>
                                            @else
                                                <span><i class="bi bi-clock me-1"></i>-</span>
                                            @endif
                                            @if($firstServiceAt !== '')
                                                <span class="mx-2">•</span>
                                                <span><i class="bi bi-scissors me-1"></i>{{ $firstServiceAt }}</span>
                                            @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-end">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                                            @if($paymentProof !== '')
                                                <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Paid Proof</span>
                                            @else
                                                <span class="badge text-bg-warning"><i class="bi bi-exclamation-circle me-1"></i>Need Proof</span>
                                            @endif
                                        </div>
                                        <div class="fw-bold mt-2">{{ $fmt($totalAmount) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-4">
                                <div class="row g-3 align-items-center">
                                    <div class="col-12 col-lg-8">
                                        <div class="d-flex flex-wrap gap-2">
                                            <a class="btn btn-primary" href="{{ route('customer.booking_detail', ['id' => $bookingId]) }}">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>

                                            @if (empty($paymentProof))
                                                @if ($canUpload)
                                                    <a class="btn btn-outline-secondary" href="{{ route('customer.booking_detail', ['id' => $bookingId]) }}#payment">
                                                        <i class="bi bi-upload me-1"></i>Upload Bukti
                                                    </a>
                                                @else
                                                    <span class="text-muted small align-self-center">Upload tidak tersedia (status: {{ ucfirst($statusRaw !== '' ? $statusRaw : '-') }})</span>
                                                @endif
                                            @else
                                                <span class="text-muted small align-self-center">Bukti pembayaran sudah diupload.</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4 text-lg-end">
                                        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'service']) }}">
                                            <i class="bi bi-plus-circle me-1"></i>Booking Lagi
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
