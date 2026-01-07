@php
    $fmt = function ($n) {
        $n = (float) $n;
        return 'Rp ' . number_format($n, 0, ',', '.');
    };
    $error = (string) request()->query('error', '');
@endphp

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Checkout</h2>

    @if($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if(!$isCustomerLoggedIn)
        <div class="alert alert-warning">
            Silakan login sebagai customer untuk melanjutkan checkout.
        </div>
    @endif

    @if($isCustomerLoggedIn && !$hasActiveAddresses)
        <div class="alert alert-warning">
            Anda belum punya alamat aktif. Tambahkan alamat dulu di
            <a href="{{ route('customer.addresses') }}">Alamat Saya</a>.
        </div>
    @endif

    @if($missingServiceSchedule)
        <div class="alert alert-warning">
            Ada layanan yang belum dipilih jadwalnya. Silakan pilih jadwal di halaman detail layanan.
        </div>
    @endif

    @if($invalidServiceSchedule)
        <div class="alert alert-danger">
            Jadwal layanan tidak valid. Silakan pilih ulang jadwal di halaman detail layanan.
        </div>
    @endif

    @if(count($lineItems) === 0)
        <div class="alert alert-info">Keranjang kosong.</div>
        <a class="btn btn-primary" href="{{ route('catalog', ['type' => 'product']) }}">Belanja Sekarang</a>
    @else
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Data Customer</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama</label>
                                <input class="form-control" value="{{ $prefillName }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input class="form-control" value="{{ $prefillEmail }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telepon</label>
                                <input class="form-control" value="{{ $prefillPhone }}" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Pembayaran</label>
                                <input class="form-control" value="{{ $paymentMethodLabel }}" disabled>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat</label>
                                @if(!$isCustomerLoggedIn)
                                    <textarea class="form-control" rows="3" disabled>Silakan login untuk memilih alamat.</textarea>
                                @elseif(!$hasActiveAddresses)
                                    <textarea class="form-control" rows="3" disabled>Belum ada alamat aktif. Tambahkan di Alamat Saya.</textarea>
                                @else
                                    <select class="form-select" id="addressSelect">
                                        @foreach($addresses as $addr)
                                            <option value="{{ (int)($addr['id'] ?? 0) }}" @selected((int)($addr['id'] ?? 0) === (int)$defaultAddressId)>
                                                {{ ($addr['label'] ?? 'Alamat') }} - {{ ($addr['recipient_name'] ?? '') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <textarea class="form-control" id="addressPreview" rows="3" disabled>{{ $prefillAddress }}</textarea>
                                    </div>
                                    <div class="mt-2">
                                        <a href="{{ route('customer.addresses') }}">Kelola Alamat</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">Catatan</div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" form="checkoutForm" rows="3" placeholder="(opsional)"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Ringkasan</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lineItems as $li)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $li['name'] }}</div>
                                                <div class="text-muted small">
                                                    {{ $li['type'] === 'service' ? 'Service' : 'Product' }}
                                                    · Qty: {{ (int)$li['qty'] }}
                                                    @if($li['type'] === 'service')
                                                        @php
                                                            $sid = (int)($li['service_slot_id'] ?? 0);
                                                            $slot = $slotsById[$sid] ?? null;
                                                            $startsAt = $slot['starts_at'] ?? '';
                                                        @endphp
                                                        @if($startsAt)
                                                            · Jadwal: {{ $startsAt }}
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">{{ $fmt($li['subtotal']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span>Total</span>
                            <span class="fw-semibold">{{ $fmt($total) }}</span>
                        </div>

                        @if($hasProduct)
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Shipping ({{ $shippingMethodLabel }})</span>
                                <span class="fw-semibold">{{ $fmt($shippingFee) }}</span>
                            </div>
                        @endif

                        <hr>
                        <div class="d-flex justify-content-between fs-5">
                            <span class="fw-bold">Grand Total</span>
                            <span class="fw-bold">{{ $fmt($grandTotal) }}</span>
                        </div>

                        <form id="checkoutForm" class="mt-3" method="POST" action="{{ route('checkout.place') }}">
                            @csrf
                            <input type="hidden" name="payment_method" value="{{ $paymentMethodKey }}">

                            @if($hasProduct)
                                <input type="hidden" name="shipping_method" value="{{ $shippingMethodKey }}">
                                <input type="hidden" name="shipping_fee" value="{{ (int)$storeShippingFee }}">
                            @endif

                            <input type="hidden" name="address_id" id="addressId" value="{{ (int)$defaultAddressId }}">

                            <button class="btn btn-primary w-100" type="submit" @disabled(!$canCheckout)>
                                Buat Pesanan
                            </button>

                            @if(!$canCheckout)
                                <div class="text-muted small mt-2">
                                    Pastikan sudah login, punya alamat aktif, dan jadwal service valid.
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="checkoutData" data-show-auth="{{ $isCustomerLoggedIn ? '0' : '1' }}"></div>
        <script type="application/json" id="addressesJson">{!! json_encode($addresses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

        <script>
            (function () {
                const dataEl = document.getElementById('checkoutData');
                const addressesJsonEl = document.getElementById('addressesJson');
                let addresses = [];
                try { addresses = JSON.parse(addressesJsonEl?.textContent || '[]'); } catch (e) { addresses = []; }
                const shouldShowAuthModal = dataEl ? (dataEl.dataset.showAuth === '1') : false;
                const select = document.getElementById('addressSelect');
                const preview = document.getElementById('addressPreview');
                const hiddenId = document.getElementById('addressId');

                function sync() {
                    if (!select || !preview || !hiddenId) return;
                    const id = parseInt(select.value || '0', 10);
                    hiddenId.value = id;
                    const found = addresses.find(a => parseInt(a.id, 10) === id);
                    if (found && found.address) {
                        preview.value = found.address;
                    }
                }

                if (select) {
                    select.addEventListener('change', sync);
                    // Only override prefill if addresses are parsed
                    if (addresses.length > 0) {
                        sync();
                    }
                }

                if (shouldShowAuthModal) {
                    // Auto open login modal if present
                    window.addEventListener('load', function () {
                        const modalEl = document.getElementById('authModal');
                        if (modalEl && window.bootstrap) {
                            const modal = new bootstrap.Modal(modalEl);
                            modal.show();
                        }
                    });
                }
            })();
        </script>
    @endif
</div>
@endsection
