@extends('admin.layouts.guest')

@section('content')
<div class="container py-5" style="max-width: 480px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h4 class="mb-1 text-center">🐾 Admin Petshop</h4>
            <p class="text-muted text-center mb-4">Silakan login untuk mengelola sistem</p>

            @if(!empty($error))
                <div class="alert alert-danger">{{ $error }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}" autocomplete="off">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email Admin</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>

    <p class="text-center text-muted mt-3">© {{ date('Y') }} PawfectShop</p>
</div>
@endsection
