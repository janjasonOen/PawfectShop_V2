@php
    $user = session('user');
    $onCatalog = request()->routeIs('catalog');
@endphp

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('home') }}">
            <i class="bi bi-shop-window"></i>
            <span>PawfectShop</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0 gap-lg-3">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
                        <i class="bi bi-house-door"></i><span>Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ $onCatalog ? 'active' : '' }}" href="{{ route('catalog', ['type' => 'product']) }}">
                        <i class="bi bi-bag"></i><span>Products</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ $onCatalog ? 'active' : '' }}" href="{{ route('catalog', ['type' => 'service']) }}">
                        <i class="bi bi-scissors"></i><span>Services</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">
                        <i class="bi bi-info-circle"></i><span>About</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 {{ request()->routeIs('contact') ? 'active' : '' }}" href="{{ route('contact') }}">
                        <i class="bi bi-envelope"></i><span>Contact</span>
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto flex-row align-items-center gap-3">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="{{ route('cart') }}" aria-label="Cart" title="Cart">
                        <i class="bi bi-cart3" style="font-size: 1.2rem;"></i>
                    </a>
                </li>

                @if (!is_array($user) || (($user['role'] ?? '') !== 'customer'))
                    <li class="nav-item">
                        <button class="btn btn-link nav-link p-0" type="button"
                                data-bs-toggle="modal" data-bs-target="#authModal"
                                aria-label="Account" title="Account">
                            <i class="bi bi-person-circle" style="font-size: 1.25rem;"></i>
                        </button>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false" title="Account">
                            <i class="bi bi-person-circle" style="font-size: 1.25rem;"></i>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <span class="dropdown-item-text fw-semibold">
                                    <i class="bi bi-person"></i>
                                    Halo, {{ e((string)($user['name'] ?? '')) }}
                                </span>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('customer.orders') }}">Pesanan Saya</a></li>
                            <li><a class="dropdown-item" href="{{ route('customer.bookings') }}">Booking Saya</a></li>
                               <li><a class="dropdown-item" href="{{ route('customer.addresses') }}">Alamat Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="{{ route('auth.logout') }}">Logout</a></li>
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

<div class="modal fade" id="authModal" tabindex="-1" aria-hidden="true"
    data-check-email-url="{{ route('auth.checkEmail') }}"
    data-customer-auth-url="{{ route('auth.customer') }}">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="row g-0">
                <div class="col-md-5 d-none d-md-block">
                    <div class="h-100 p-4 bg-light">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="fs-3">🐾</div>
                            <div>
                                <div class="fw-bold">PawfectShop</div>
                                <div class="text-muted small">Petshop Sehat & Ceria</div>
                            </div>
                        </div>

                        <div class="fw-semibold mb-2">Masuk untuk pengalaman lebih cepat</div>
                        <ul class="text-muted small mb-0 ps-3">
                            <li>Checkout lebih cepat & praktis</li>
                            <li>Lihat riwayat pesanan</li>
                            <li>Simpan alamat pengiriman</li>
                        </ul>

                        <hr>
                        <div class="small text-muted">
                            Kami akan cek email kamu. Jika sudah terdaftar, lanjutkan login. Jika belum, buat akun dalam 1 menit.
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title" id="authTitle">Masuk / Daftar</h5>
                            <div class="text-muted small" id="authSubtitle">Gunakan email untuk melanjutkan.</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body pt-3">
                        <div id="authError" class="alert alert-danger d-none" role="alert"></div>

                        <form id="authForm" class="vstack gap-3">
                            <div class="form-floating">
                                <input type="email" id="email" class="form-control" placeholder="name@example.com" required>
                                <label for="email">Email</label>
                            </div>

                            <div class="form-floating d-none" id="nameField">
                                <input type="text" id="name" class="form-control" placeholder="Nama lengkap">
                                <label for="name">Nama</label>
                            </div>

                            <div class="form-floating d-none" id="passwordField">
                                <input type="password" id="password" class="form-control" placeholder="Password">
                                <label for="password">Password</label>
                            </div>

                            <input type="hidden" id="mode">

                            <button type="submit" class="btn btn-primary w-100 py-2" id="submitBtn" disabled>
                                Lanjut
                            </button>

                            <div class="small text-muted">
                                Dengan melanjutkan, kamu setuju dengan kebijakan penggunaan dan privasi PawfectShop.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const emailInput = document.getElementById('email');
const nameField = document.getElementById('nameField');
const passField = document.getElementById('passwordField');
const submitBtn = document.getElementById('submitBtn');
const modeInput = document.getElementById('mode');
const authError = document.getElementById('authError');
const authSubtitle = document.getElementById('authSubtitle');

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function showAuthError(message) {
    if (!authError) return;
    authError.textContent = message;
    authError.classList.remove('d-none');
}

function clearAuthError() {
    if (!authError) return;
    authError.textContent = '';
    authError.classList.add('d-none');
}

function resetAuthUi() {
    nameField.classList.add('d-none');
    passField.classList.add('d-none');
    modeInput.value = '';
    submitBtn.innerText = 'Lanjut';
    submitBtn.disabled = !emailInput.value.trim();
    clearAuthError();
    if (authSubtitle) authSubtitle.textContent = 'Gunakan email untuk melanjutkan.';
}

resetAuthUi();
emailInput.addEventListener('input', () => resetAuthUi());

async function checkEmailAndUpdateUi() {
    const email = emailInput.value.trim();
    if (!email) {
        resetAuthUi();
        return;
    }

    try {
        const url = authModal?.dataset.checkEmailUrl || '';
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: 'email=' + encodeURIComponent(email)
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            showAuthError(data.error || 'Gagal cek email');
            return;
        }

        // If exists -> login mode, else -> register mode
        if (data.exists) {
            modeInput.value = 'login';
            passField.classList.remove('d-none');
            submitBtn.innerText = 'Login';
            submitBtn.disabled = false;
            if (authSubtitle) authSubtitle.textContent = 'Masukkan password untuk login.';
        } else {
            modeInput.value = 'register';
            nameField.classList.remove('d-none');
            passField.classList.remove('d-none');
            submitBtn.innerText = 'Daftar';
            submitBtn.disabled = false;
            if (authSubtitle) authSubtitle.textContent = 'Buat akun baru.';
        }

        clearAuthError();
    } catch (e) {
        showAuthError('Server error');
    }
}

emailInput.addEventListener('blur', () => {
    if (!emailInput.value.trim()) return;
    checkEmailAndUpdateUi();
});

const authForm = document.getElementById('authForm');
authForm?.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    clearAuthError();

    const email = emailInput.value.trim();
    const mode = modeInput.value;
    const name = document.getElementById('name')?.value.trim() || '';
    const password = document.getElementById('password')?.value || '';

    if (!email || !mode) {
        showAuthError('Email tidak valid');
        return;
    }

    const body = new URLSearchParams();
    body.set('email', email);
    body.set('mode', mode);
    if (mode === 'register') body.set('name', name);
    body.set('password', password);

    try {
        const url = authModal?.dataset.customerAuthUrl || '';
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: body.toString(),
        });

        const data = await res.json();
        if (!res.ok || !data.ok) {
            showAuthError(data.error || 'Gagal');
            return;
        }

        // Reload to update navbar state
        window.location.reload();
    } catch (e) {
        showAuthError('Server error');
    }
});
</script>
