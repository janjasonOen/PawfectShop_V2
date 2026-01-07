@extends('layouts.app')

@php
    function rupiah($n): string {
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    }
@endphp

@section('title', $item ? (string)$item->name : 'Item not found')

@section('content')
<div class="container mt-4">
    @if (!empty(request()->query('error')))
        <div class="alert alert-warning">
            {{ e((string)request()->query('error')) }}
        </div>
    @endif

    @if (!$item)
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-semibold">Item tidak ditemukan</div>
                        <div class="text-muted small">Mungkin item sudah dihapus atau link tidak valid.</div>
                    </div>
                    <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => 'product']) }}">Kembali ke katalog</a>
                </div>
            </div>
        </div>
    @else
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item">
                    <a href="{{ route('catalog', ['type' => $item->type]) }}">{{ $isProduct ? 'Products' : 'Services' }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">{{ e($item->name) }}</li>
            </ol>
        </nav>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="row g-4 align-items-start">
                    <div class="col-12 col-md-5">
                        <div class="position-relative">
                            @if (!empty($item->image))
                                <img src="{{ asset('uploads/items/' . $item->image) }}" alt="" class="w-100" style="border-radius:16px;object-fit:cover;max-height:420px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height:340px;border-radius:16px;">
                                    <div class="text-muted">No image</div>
                                </div>
                            @endif

                            <span class="badge text-bg-dark position-absolute top-0 start-0 m-3">
                                {{ $isProduct ? 'Product' : 'Service' }}
                            </span>
                            @if ($outOfStock)
                                <span class="badge text-bg-secondary position-absolute top-0 end-0 m-3">
                                    Out of stock
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 col-md-7">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge text-bg-light">{{ e($item->category) }}</span>
                            @if ($isProduct)
                                <span class="badge text-bg-light">Stok: {{ (int)$stock }}</span>
                            @endif
                        </div>

                        <h3 class="mb-2">{{ e($item->name) }}</h3>
                        <div class="fs-4 fw-bold mb-3">{{ rupiah($item->price) }}</div>

                        @if (!empty($item->description))
                            <div class="text-muted mb-4" style="white-space:pre-line;">
                                {{ e($item->description) }}
                            </div>
                        @else
                            <div class="text-muted mb-4">Tidak ada deskripsi.</div>
                        @endif

                        @if ($isProduct)
                            <form method="POST" action="{{ route('cart.action') }}" class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="item_id" value="{{ (int)$item->id }}">

                                <div class="col-12 col-sm-4">
                                    <label class="form-label small text-muted">Qty</label>
                                    <input type="number" class="form-control" name="qty" min="1" value="1" {{ $outOfStock ? 'disabled' : '' }}>
                                </div>

                                <div class="col-12 col-sm-8 d-grid">
                                    <button type="submit" class="btn btn-primary" {{ $outOfStock ? 'disabled' : '' }}>
                                        Add to Cart
                                    </button>
                                </div>

                                <div class="col-12">
                                    <a class="btn btn-outline-secondary w-100" href="{{ route('cart') }}">View Cart</a>
                                </div>
                            </form>
                        @else
                            <div class="d-grid gap-2">
                                <form method="POST" action="{{ route('cart.action') }}" class="m-0" id="serviceCartForm">
                                    @csrf
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="{{ (int)$item->id }}">

                                    <label class="form-label small text-muted">Schedule</label>
                                    @if (count($serviceSlotsByDate) > 0)

                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted">Date</label>
                                                <select id="slot_date" class="form-select" required>
                                                    @php
                                                        $dateKeys = array_keys($serviceSlotsByDate);
                                                        sort($dateKeys);
                                                    @endphp
                                                    @foreach ($dateKeys as $dk)
                                                        <option value="{{ e($dk) }}">{{ e($dk) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted">Slots</label>
                                                <div id="slot_summary" class="small text-muted mb-2"></div>
                                                <div id="slot_buttons" class="d-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px;"></div>
                                                <div id="slot_empty" class="small text-muted d-none mt-2"></div>
                                                <div id="slot_error" class="small text-danger d-none mt-2">Pilih jam terlebih dahulu.</div>
                                                <input type="hidden" name="slot_id" id="slot_id" value="">
                                            </div>
                                        </div>

                                        <script type="application/json" id="slotsByDateJson">{{ json_encode($serviceSlotsByDate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</script>
                                        <script>
                                        document.addEventListener('DOMContentLoaded', () => {
                                            const slotIdEl = document.getElementById('slot_id');
                                            const slotButtonsEl = document.getElementById('slot_buttons');
                                            const slotSummaryEl = document.getElementById('slot_summary');
                                            const slotEmptyEl = document.getElementById('slot_empty');
                                            const slotErrorEl = document.getElementById('slot_error');
                                            const dateEl = document.getElementById('slot_date');
                                            const formEl = document.getElementById('serviceCartForm');
                                            if (!slotIdEl || !slotButtonsEl || !slotSummaryEl || !slotEmptyEl || !slotErrorEl) return;
                                            if (!dateEl || !formEl) return;

                                            const slotsJsonEl = document.getElementById('slotsByDateJson');
                                            let slotsByDate = {};
                                            try {
                                                slotsByDate = JSON.parse(slotsJsonEl?.textContent || '{}');
                                            } catch (e) {
                                                slotsByDate = {};
                                            }
                                            const availableDates = Object.keys(slotsByDate).sort();
                                            if (availableDates.length === 0) {
                                                slotSummaryEl.textContent = 'Tidak ada slot';
                                                slotButtonsEl.innerHTML = '';
                                                slotEmptyEl.classList.remove('d-none');
                                                slotEmptyEl.textContent = 'Tidak ada slot tersedia.';
                                                return;
                                            }

                                            let selectedSlotId = '';

                                            function clearSelection() {
                                                selectedSlotId = '';
                                                slotIdEl.value = '';
                                            }

                                            function formatTime(startsAt) {
                                                const s = String(startsAt || '');
                                                if (s.length >= 19) return s.substring(11, 16);
                                                return s;
                                            }

                                            function renderSlots(dateKey) {
                                                const list = slotsByDate[dateKey] || [];
                                                slotButtonsEl.innerHTML = '';
                                                slotErrorEl.classList.add('d-none');

                                                let fullCount = 0;
                                                let availableSlotsCount = 0;
                                                let remainingTotal = 0;

                                                for (const s of list) {
                                                    const cap = parseInt(s.capacity ?? 1, 10) || 1;
                                                    const booked = parseInt(s.booked ?? 0, 10) || 0;
                                                    const full = booked >= cap;
                                                    const remaining = Math.max(cap - booked, 0);
                                                    remainingTotal += remaining;
                                                    if (full) fullCount++;
                                                    else availableSlotsCount++;

                                                    const btn = document.createElement('button');
                                                    btn.type = 'button';
                                                    btn.dataset.slotId = String(s.id);
                                                    btn.className = 'btn btn-sm ' + (full ? 'btn-outline-secondary' : 'btn-outline-primary');
                                                    btn.disabled = full;
                                                    btn.style.textAlign = 'left';

                                                    const timeLabel = formatTime(s.starts_at);
                                                    btn.textContent = `${timeLabel} · ${booked}/${cap}` + (full ? ' (Full)' : '');

                                                    btn.addEventListener('click', () => {
                                                        selectedSlotId = String(s.id);
                                                        slotIdEl.value = selectedSlotId;
                                                        slotErrorEl.classList.add('d-none');

                                                        for (const el of slotButtonsEl.querySelectorAll('button[data-slot-id]')) {
                                                            const isSelected = el.dataset.slotId === selectedSlotId;
                                                            el.classList.toggle('btn-primary', isSelected);
                                                            el.classList.toggle('btn-outline-primary', !isSelected);
                                                        }
                                                    });

                                                    slotButtonsEl.appendChild(btn);
                                                }

                                                if (list.length === 0) {
                                                    slotEmptyEl.classList.remove('d-none');
                                                    slotEmptyEl.textContent = 'Tidak ada slot tersedia untuk tanggal ini.';
                                                } else {
                                                    slotEmptyEl.classList.add('d-none');
                                                    slotEmptyEl.textContent = '';
                                                }

                                                slotSummaryEl.textContent = `${availableSlotsCount} slot tersedia · ${fullCount} full · sisa ${remainingTotal}`;
                                            }

                                            function init() {
                                                const dk = dateEl.value || availableDates[0];
                                                clearSelection();
                                                renderSlots(dk);
                                            }

                                            dateEl.addEventListener('change', () => {
                                                clearSelection();
                                                renderSlots(dateEl.value);
                                            });

                                            formEl.addEventListener('submit', (ev) => {
                                                if (!slotIdEl.value) {
                                                    ev.preventDefault();
                                                    slotErrorEl.classList.remove('d-none');
                                                }
                                            });

                                            init();
                                        });
                                        </script>
                                    @else
                                        <div class="alert alert-secondary mb-0">Tidak ada slot tersedia.</div>
                                    @endif

                                    <button type="submit" class="btn btn-primary mt-3">Add to Cart</button>
                                </form>

                                <a class="btn btn-outline-secondary" href="{{ route('cart') }}">View Cart</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
