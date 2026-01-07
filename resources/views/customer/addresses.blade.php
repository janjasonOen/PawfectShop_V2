@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Alamat Saya</h2>

    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Tambah Alamat</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customer.addresses') }}">
                        @csrf
                        <input type="hidden" name="add_address" value="1">

                        <div class="mb-3">
                            <label class="form-label">Label (opsional)</label>
                            <input class="form-control" name="label" placeholder="Rumah / Kantor">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Penerima (opsional)</label>
                            <input class="form-control" name="recipient_name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. HP (opsional, disarankan)</label>
                            <input class="form-control" name="phone">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" rows="3" name="address" required></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="isDefault" name="is_default">
                            <label class="form-check-label" for="isDefault">Jadikan alamat utama</label>
                        </div>

                        <button class="btn btn-primary" type="submit">Simpan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">Daftar Alamat</div>
                <div class="card-body">
                    @if(empty($addresses))
                        <div class="alert alert-info mb-0">Belum ada alamat.</div>
                    @else
                        <div class="list-group">
                            @foreach($addresses as $a)
                                @php
                                    $id = (int)($a['id'] ?? 0);
                                    $isDefault = (int)($a['is_default'] ?? 0) === 1;
                                @endphp
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $a['label'] ?? 'Alamat' }}
                                                @if($isDefault)
                                                    <span class="badge text-bg-success">Utama</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">{{ $a['recipient_name'] ?? '' }} {{ !empty($a['phone']) ? '· '.$a['phone'] : '' }}</div>
                                            <div class="mt-2">{{ $a['address'] ?? '' }}</div>
                                        </div>
                                        <div class="text-end">
                                            @if(!$isDefault)
                                                <a class="btn btn-sm btn-outline-primary" href="{{ route('customer.addresses', ['set_default' => $id]) }}">Jadikan Utama</a>
                                            @endif
                                            <a class="btn btn-sm btn-outline-danger" href="{{ route('customer.addresses', ['deactivate' => $id]) }}" onclick="return confirm('Hapus alamat ini?')">Hapus</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
