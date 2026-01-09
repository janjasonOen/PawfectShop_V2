@php
    $user = session('user');
    $isCustomer = is_array($user) && (($user['role'] ?? '') === 'customer');
@endphp

<footer class="mt-5 bg-light border-top">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="fw-bold mb-2">🐾 PawfectShop</div>
                <div class="text-muted small" style="max-width: 44ch;">
                    Menyediakan produk dan jasa terbaik untuk hewan kesayangan Anda.
                </div>

                <div class="mt-3 small text-muted">
                    <div class="mb-1">📍 Jakarta, Indonesia</div>
                    <div class="mb-1">📞 0812-xxxx-xxxx</div>
                    <div>✉️ petshop@email.com</div>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <div class="fw-semibold mb-2">Menu</div>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="{{ route('home') }}" class="text-decoration-none text-muted">Beranda</a></li>
                    <li class="mb-1"><a href="{{ route('catalog', ['type' => 'product']) }}" class="text-decoration-none text-muted">Produk</a></li>
                    <li class="mb-1"><a href="{{ route('catalog', ['type' => 'service']) }}" class="text-decoration-none text-muted">Layanan</a></li>
                    <li class="mb-1"><a href="{{ route('cart') }}" class="text-decoration-none text-muted">Keranjang</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <div class="fw-semibold mb-2">Info</div>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-1"><a href="{{ route('about') }}" class="text-decoration-none text-muted">Tentang Kami</a></li>
                    <li class="mb-1"><a href="{{ route('contact') }}" class="text-decoration-none text-muted">Kontak</a></li>
                    <li class="mb-1"><a href="{{ route('admin.login') }}" class="text-decoration-none text-muted">Admin</a></li>
                </ul>
            </div>

            <div class="col-12 col-lg-4">
                <div class="fw-semibold mb-2">Akun</div>

                @if ($isCustomer)
                    <div class="small text-muted mb-2">Halo, {{ e((string)($user['name'] ?? '')) }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('customer.orders') }}">Pesanan</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('customer.bookings') }}">Booking</a>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('customer.addresses') }}">Alamat</a>
                        <a class="btn btn-sm btn-outline-danger" href="{{ route('auth.logout') }}">Logout</a>
                    </div>
                @else
                    <div class="small text-muted mb-3">Masuk untuk checkout lebih cepat dan lihat riwayat pesanan.</div>
                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#authModal">
                        Masuk / Daftar
                    </button>
                @endif
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small">© {{ date('Y') }} PawfectShop</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    function showFlashToasts() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Toast) return;
        document.querySelectorAll('.toast[data-autoshow="1"]').forEach(function (el) {
            try {
                bootstrap.Toast.getOrCreateInstance(el).show();
            } catch (e) {
                // ignore
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showFlashToasts);
    } else {
        showFlashToasts();
    }
})();
</script>
