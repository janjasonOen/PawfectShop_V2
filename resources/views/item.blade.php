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

        <div class="row g-4 align-items-start">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm overflow-hidden">
                    <div class="position-relative">
                        @if (!empty($item->image))
                            <img src="{{ asset('uploads/items/' . $item->image) }}" alt="" class="w-100" style="object-fit:cover; max-height: 520px;">
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center" style="height:420px;">
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

                <div class="card shadow-sm mt-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="fw-semibold">Deskripsi</div>
                            <span class="badge text-bg-light">{{ e($item->category) }}</span>
                        </div>

                        @if (!empty($item->description))
                            <div class="text-muted" style="white-space:pre-line;">
                                {{ e($item->description) }}
                            </div>
                        @else
                            <div class="text-muted">Tidak ada deskripsi.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h2 class="mb-1" style="line-height:1.15;">{{ e($item->name) }}</h2>
                                <div class="text-muted">
                                    {{ $isProduct ? 'Produk untuk kebutuhan hewan kesayangan.' : 'Layanan profesional untuk hewan kesayangan.' }}
                                </div>
                            </div>

                            <div class="text-end">
                                <div class="fs-3 fw-bold">{{ rupiah($item->price) }}</div>
                                @if ($isProduct)
                                    <div class="small {{ $outOfStock ? 'text-danger' : 'text-muted' }}">
                                        {{ $outOfStock ? 'Stok habis' : ('Stok tersedia: ' . (int)$stock) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">Kategori</div>
                                    <div class="fw-semibold">{{ e($item->category) }}</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-muted">Tipe</div>
                                    <div class="fw-semibold">{{ $isProduct ? 'Product' : 'Service' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            @if ($isProduct)
                                <div class="fw-semibold mb-2">Beli Sekarang</div>
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

                                    <div class="col-12 d-grid">
                                        <a class="btn btn-outline-secondary" href="{{ route('cart') }}">View Cart</a>
                                    </div>
                                </form>
                            @else
                                <div class="fw-semibold mb-2">Pilih Jadwal</div>
                                <div class="text-muted small mb-3">Pilih tanggal dan jam, lalu tambahkan ke keranjang.</div>

                                <form method="POST" action="{{ route('cart.action') }}" class="m-0" id="serviceCartForm">
                                    @csrf
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="item_id" value="{{ (int)$item->id }}">

                                    @if (count($serviceSlotsByDate) > 0)
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted">Date</label>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary w-100 text-start d-flex align-items-center justify-content-between"
                                                    id="slot_date_button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#slot_calendar_collapse"
                                                    aria-expanded="false"
                                                    aria-controls="slot_calendar_collapse"
                                                >
                                                    <span id="slot_date_label">Select date</span>
                                                    <span class="text-muted">▾</span>
                                                </button>

                                                <div class="collapse mt-2" id="slot_calendar_collapse">
                                                    <div class="border rounded-3 p-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="slot_prev_month">Prev</button>
                                                            <div class="fw-semibold" id="slot_month_label"></div>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="slot_next_month">Next</button>
                                                        </div>

                                                        <div class="d-grid" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;">
                                                            @php
                                                                $dow = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                                                            @endphp
                                                            @foreach ($dow as $d)
                                                                <div class="text-center small text-muted">{{ $d }}</div>
                                                            @endforeach
                                                        </div>

                                                        <div id="slot_calendar_grid" class="d-grid mt-2" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;"></div>

                                                        <div class="small text-muted mt-2">
                                                            Pilih tanggal yang tersedia.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <label class="form-label small text-muted">Time</label>
                                                <select id="slot_time" class="form-select">
                                                    <option value="">Select time</option>
                                                </select>

                                                <div id="slot_summary" class="small text-muted mt-2"></div>

                                                <div id="slot_empty" class="small text-muted d-none mt-2"></div>
                                                <div id="slot_error" class="small text-danger d-none mt-2">Pilih jam terlebih dahulu.</div>
                                                <input type="hidden" name="slot_id" id="slot_id" value="">
                                            </div>
                                        </div>

                                        <script type="application/json" id="slotsByDateJson">{!! json_encode($serviceSlotsByDate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
                                        <script>
                                        document.addEventListener('DOMContentLoaded', () => {
                                            const slotIdEl = document.getElementById('slot_id');
                                            const timeEl = document.getElementById('slot_time');
                                            const slotSummaryEl = document.getElementById('slot_summary');
                                            const slotEmptyEl = document.getElementById('slot_empty');
                                            const slotErrorEl = document.getElementById('slot_error');
                                            const dateBtnEl = document.getElementById('slot_date_button');
                                            const dateLabelEl = document.getElementById('slot_date_label');
                                            const calCollapseEl = document.getElementById('slot_calendar_collapse');
                                            const calGridEl = document.getElementById('slot_calendar_grid');
                                            const calMonthLabelEl = document.getElementById('slot_month_label');
                                            const calPrevEl = document.getElementById('slot_prev_month');
                                            const calNextEl = document.getElementById('slot_next_month');
                                            const formEl = document.getElementById('serviceCartForm');
                                            if (!slotIdEl || !timeEl || !slotSummaryEl || !slotEmptyEl || !slotErrorEl) return;
                                            if (!dateBtnEl || !dateLabelEl || !calCollapseEl) return;
                                            if (!calGridEl || !calMonthLabelEl || !calPrevEl || !calNextEl) return;
                                            if (!formEl) return;

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
                                                timeEl.innerHTML = '<option value="">Select time</option>';
                                                slotEmptyEl.classList.remove('d-none');
                                                slotEmptyEl.textContent = 'Tidak ada slot tersedia.';
                                                return;
                                            }

                                            const availableDateSet = new Set(availableDates);
                                            const months = Array.from(new Set(availableDates.map(d => String(d).slice(0, 7)))).sort();
                                            let selectedDate = availableDates[0];
                                            let currentMonthIndex = Math.max(0, months.indexOf(String(selectedDate).slice(0, 7)));

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
                                                timeEl.innerHTML = '';
                                                const placeholder = document.createElement('option');
                                                placeholder.value = '';
                                                placeholder.textContent = 'Select time';
                                                timeEl.appendChild(placeholder);

                                                slotErrorEl.classList.add('d-none');

                                                let fullCount = 0;
                                                let availableSlotsCount = 0;
                                                let remainingTotal = 0;

                                                let firstAvailableId = '';

                                                for (const s of list) {
                                                    const cap = parseInt(s.capacity ?? 1, 10) || 1;
                                                    const booked = parseInt(s.booked ?? 0, 10) || 0;
                                                    const full = booked >= cap;
                                                    const remaining = Math.max(cap - booked, 0);
                                                    remainingTotal += remaining;
                                                    if (full) fullCount++;
                                                    else availableSlotsCount++;

                                                    const opt = document.createElement('option');
                                                    opt.value = String(s.id);
                                                    opt.disabled = full;
                                                    const timeLabel = formatTime(s.starts_at);
                                                    opt.textContent = `${timeLabel} · ${booked}/${cap}` + (full ? ' (Full)' : '');
                                                    timeEl.appendChild(opt);

                                                    if (!full && firstAvailableId === '') {
                                                        firstAvailableId = String(s.id);
                                                    }
                                                }

                                                if (list.length === 0) {
                                                    slotEmptyEl.classList.remove('d-none');
                                                    slotEmptyEl.textContent = 'Tidak ada slot tersedia untuk tanggal ini.';
                                                } else {
                                                    slotEmptyEl.classList.add('d-none');
                                                    slotEmptyEl.textContent = '';
                                                }

                                                slotSummaryEl.textContent = `${availableSlotsCount} slot tersedia · ${fullCount} full · sisa ${remainingTotal}`;

                                                // Auto-select first available slot for convenience
                                                if (firstAvailableId !== '') {
                                                    selectedSlotId = firstAvailableId;
                                                    slotIdEl.value = selectedSlotId;
                                                    timeEl.value = selectedSlotId;
                                                } else {
                                                    clearSelection();
                                                    timeEl.value = '';
                                                }
                                            }

                                            function pad2(n) {
                                                return String(n).padStart(2, '0');
                                            }

                                            function setMonthNavState() {
                                                calPrevEl.disabled = currentMonthIndex <= 0;
                                                calNextEl.disabled = currentMonthIndex >= (months.length - 1);
                                            }

                                            function formatMonthLabel(monthKey) {
                                                const parts = String(monthKey).split('-');
                                                const year = parseInt(parts[0] || '0', 10);
                                                const month = parseInt(parts[1] || '1', 10);
                                                const dt = new Date(year, Math.max(0, month - 1), 1);
                                                try {
                                                    return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(dt);
                                                } catch (e) {
                                                    return monthKey;
                                                }
                                            }

                                            function renderCalendar() {
                                                const monthKey = months[currentMonthIndex] || String(selectedDate).slice(0, 7);
                                                calMonthLabelEl.textContent = formatMonthLabel(monthKey);
                                                setMonthNavState();

                                                const parts = String(monthKey).split('-');
                                                const year = parseInt(parts[0] || '0', 10);
                                                const month = parseInt(parts[1] || '1', 10);
                                                const first = new Date(year, Math.max(0, month - 1), 1);
                                                const lastDay = new Date(year, Math.max(0, month - 1) + 1, 0).getDate();

                                                // Monday-first offset
                                                const weekdaySun0 = first.getDay();
                                                const offset = (weekdaySun0 + 6) % 7;

                                                calGridEl.innerHTML = '';

                                                for (let i = 0; i < offset; i++) {
                                                    const blank = document.createElement('div');
                                                    blank.className = 'p-2';
                                                    calGridEl.appendChild(blank);
                                                }

                                                for (let day = 1; day <= lastDay; day++) {
                                                    const dateStr = `${year}-${pad2(month)}-${pad2(day)}`;
                                                    const hasSlots = availableDateSet.has(dateStr);
                                                    const isSelected = dateStr === selectedDate;

                                                    const btn = document.createElement('button');
                                                    btn.type = 'button';
                                                    btn.className = 'btn btn-sm w-100 py-2 text-nowrap ' + (isSelected ? 'btn-primary' : (hasSlots ? 'btn-outline-primary' : 'btn-outline-secondary'));
                                                    btn.style.whiteSpace = 'nowrap';
                                                    btn.textContent = String(day);
                                                    btn.disabled = !hasSlots;

                                                    if (hasSlots) {
                                                        btn.addEventListener('click', () => {
                                                            selectedDate = dateStr;
                                                            clearSelection();
                                                            renderSlots(selectedDate);
                                                            renderCalendar();

                                                            // Update date label and close the calendar (if Bootstrap is present)
                                                            if (dateLabelEl) dateLabelEl.textContent = selectedDate;
                                                            try {
                                                                if (window.bootstrap && calCollapseEl) {
                                                                    const inst = window.bootstrap.Collapse.getOrCreateInstance(calCollapseEl, { toggle: false });
                                                                    inst.hide();
                                                                }
                                                            } catch (e) {
                                                                // ignore
                                                            }
                                                        });
                                                    }

                                                    calGridEl.appendChild(btn);
                                                }

                                                if (dateLabelEl) dateLabelEl.textContent = selectedDate;
                                            }

                                            function init() {
                                                selectedDate = selectedDate || availableDates[0];
                                                currentMonthIndex = Math.max(0, months.indexOf(String(selectedDate).slice(0, 7)));
                                                clearSelection();
                                                renderSlots(selectedDate);
                                                renderCalendar();
                                            }

                                            timeEl.addEventListener('change', () => {
                                                const v = String(timeEl.value || '');
                                                if (v === '') {
                                                    clearSelection();
                                                    return;
                                                }
                                                selectedSlotId = v;
                                                slotIdEl.value = v;
                                                slotErrorEl.classList.add('d-none');
                                            });

                                            calPrevEl.addEventListener('click', () => {
                                                if (currentMonthIndex <= 0) return;
                                                currentMonthIndex -= 1;
                                                renderCalendar();
                                            });

                                            calNextEl.addEventListener('click', () => {
                                                if (currentMonthIndex >= (months.length - 1)) return;
                                                currentMonthIndex += 1;
                                                renderCalendar();
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

                                        <div class="d-grid mt-3">
                                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary mb-0">Tidak ada slot tersedia.</div>
                                    @endif
                                </form>

                                <div class="d-grid mt-2">
                                    <a class="btn btn-outline-secondary" href="{{ route('cart') }}">View Cart</a>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <a class="btn btn-outline-secondary" href="{{ route('catalog', ['type' => $item->type]) }}">Back to {{ $isProduct ? 'Products' : 'Services' }}</a>
                            <a class="btn btn-outline-secondary" href="{{ route('home') }}">Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
