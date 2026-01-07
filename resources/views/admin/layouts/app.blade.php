<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Petshop')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f5f6fa; }
        .sidebar { min-height:100vh; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-dark sidebar p-3">
            <h5 class="mb-4 text-white">🐾 Petshop Admin</h5>

            @php
                $active = function (string $routeName): string {
                    return request()->routeIs($routeName) ? 'bg-warning text-dark fw-bold' : 'text-white';
                };
                $activeAny = function (array $routeNames): string {
                    foreach ($routeNames as $r) {
                        if (request()->routeIs($r)) return 'bg-warning text-dark fw-bold';
                    }
                    return 'text-white';
                };
            @endphp

            <ul class="nav flex-column gap-1">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link rounded {{ $active('admin.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.categories') }}" class="nav-link rounded {{ $active('admin.categories') }}">Kategori</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.items') }}" class="nav-link rounded {{ $active('admin.items') }}">Produk &amp; Jasa</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.schedules') }}" class="nav-link rounded {{ $active('admin.schedules') }}">Jadwal Service</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.users') }}" class="nav-link rounded {{ $active('admin.users') }}">Manajemen User</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.orders') }}" class="nav-link rounded {{ $activeAny(['admin.orders','admin.order_detail']) }}">Orders</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.bookings') }}" class="nav-link rounded {{ $activeAny(['admin.bookings','admin.booking_detail']) }}">Bookings</a>
                </li>

                <li class="nav-item mt-3">
                    <a href="{{ route('admin.logout') }}" class="nav-link text-danger">Logout</a>
                </li>
            </ul>
        </div>

        <div class="col-md-10 p-4">
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
