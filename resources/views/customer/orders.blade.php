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
                <i class="bi bi-receipt fs-4"></i>
                <h3 class="section-title mb-0">Pesanan Saya</h3>
            </div>
            <div class="text-muted">Riwayat pesanan, status, dan bukti pembayaran.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('customer.bookings') }}"><i class="bi bi-calendar-check me-1"></i>Bookings</a>
            <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}"><i class="bi bi-bag me-1"></i>Belanja</a>
        </div>
    </div>

    @if(count($orders) === 0)
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex flex-column align-items-center text-center gap-2">
                    <i class="bi bi-receipt fs-1"></i>
                    <div class="fs-5 fw-semibold">Belum ada pesanan</div>
                    <div class="text-muted">Ayo mulai belanja atau booking service.</div>
                    <a class="btn btn-primary mt-2" href="{{ route('catalog', ['type' => 'product']) }}">Ke Katalog</a>
                </div>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($orders as $o)
                @php
                    $orderId = (int)($o['id'] ?? 0);
                    $totalAmount = (float)($o['total_amount'] ?? 0);
                    $orderDate = (string)($o['order_date'] ?? '');
                    $firstServiceAt = (string)($o['first_service_at'] ?? '');
                    $paymentProof = trim((string)($o['payment_proof'] ?? ''));
                    $badge = (string)($o['_badge'] ?? 'light');
                    $label = (string)($o['_label'] ?? (string)($o['status'] ?? 'pending'));
                    $statusRaw = (string) ($o['status'] ?? '');
                    $canUpload = strtolower($statusRaw) === 'pending';
                    $firstItemName = (string) ($o['first_item_name'] ?? '');
                    $firstItemImage = trim((string) ($o['first_item_image'] ?? ''));
                    $itemCount = (int) ($o['item_count'] ?? 0);
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
                                                <i class="bi bi-bag text-muted"></i>
                                            </div>
                                        @endif

                                        <div>
                                            <div class="fw-semibold">Order #{{ $orderId }}</div>
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
                                            @if($orderDate !== '')
                                                <span><i class="bi bi-clock me-1"></i>{{ $orderDate }}</span>
                                            @else
                                                <span><i class="bi bi-clock me-1"></i>-</span>
                                            @endif
                                            @if($firstServiceAt !== '')
                                                <span class="mx-2">•</span>
                                                <span><i class="bi bi-wrench-adjustable-circle me-1"></i>{{ $firstServiceAt }}</span>
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
                                            <a class="btn btn-primary" href="{{ route('customer.order_detail', ['id' => $orderId]) }}">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>

                                            @if (empty($paymentProof))
                                                @if ($canUpload)
                                                    <a class="btn btn-outline-secondary" href="{{ route('customer.order_detail', ['id' => $orderId]) }}#payment">
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
                                        <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}">
                                            <i class="bi bi-plus-circle me-1"></i>Belanja Lagi
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
