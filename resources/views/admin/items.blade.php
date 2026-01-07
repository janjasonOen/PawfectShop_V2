@extends('admin.layouts.app')

@section('content')
@php
    $fmt = function ($n) {
        $n = (float) $n;
        return number_format($n, 0, ',', '.');
    };
@endphp

<div class="container py-4">
    <h3 class="mb-1">Data Produk & Jasa</h3>
    <div class="text-muted mb-3">Tambah dan hapus item.</div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Nama item" required>
                    </div>
                    <div class="col-md-2">
                        <select name="type" class="form-control" required>
                            <option value="">Jenis</option>
                            <option value="product">Produk</option>
                            <option value="service">Jasa</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="price" class="form-control" placeholder="Harga" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="stock" class="form-control" placeholder="Stok (produk)">
                    </div>
                    <div class="col-md-2">
                        <select name="category_id" class="form-control" required>
                            <option value="">Kategori</option>
                            @foreach($categories as $c)
                                <option value="{{ (int)$c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <textarea name="description" class="form-control" placeholder="Deskripsi"></textarea>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" name="add" value="1" class="btn btn-primary">Tambah Item</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Gambar</th>
                        <th>Jenis</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if($items->count() === 0)
                        <tr>
                            <td colspan="9" class="text-center">Belum ada data</td>
                        </tr>
                    @endif

                    @foreach($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td>
                                @if(!empty($item->image))
                                    <img src="{{ asset('uploads/items/' . $item->image) }}" width="60" alt="{{ $item->name }}">
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ ucfirst((string)$item->type) }}</td>
                            <td>{{ $item->category_name }}</td>
                            <td>
                                @php $st = (string)($item->status ?? 'active'); @endphp
                                @if($st === 'active')
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $fmt($item->price) }}</td>
                            <td>{{ $item->stock ?? '-' }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal{{ (int)$item->id }}">
                                    Edit
                                </button>

                                @if(((string)($item->status ?? 'active')) === 'active')
                                    <a href="{{ route('admin.items', ['deactivate' => (int)$item->id]) }}"
                                       onclick="return confirm('Nonaktifkan item ini?')"
                                       class="btn btn-outline-secondary btn-sm">
                                        Deactivate
                                    </a>
                                @else
                                    <a href="{{ route('admin.items', ['activate' => (int)$item->id]) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        Activate
                                    </a>
                                @endif

                                <a href="{{ route('admin.items', ['delete' => (int)$item->id]) }}"
                                   onclick="return confirm('Hapus item ini?')"
                                   class="btn btn-danger btn-sm">
                                    Hapus
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal{{ (int)$item->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Item</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="{{ (int)$item->id }}">

                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label">Nama</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Jenis</label>
                                                    <select name="type" class="form-control" required>
                                                        <option value="product" @selected((string)$item->type === 'product')>Produk</option>
                                                        <option value="service" @selected((string)$item->type === 'service')>Jasa</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="active" @selected((string)($item->status ?? 'active') === 'active')>Active</option>
                                                        <option value="inactive" @selected((string)($item->status ?? 'active') === 'inactive')>Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Kategori</label>
                                                    <select name="category_id" class="form-control" required>
                                                        @foreach($categories as $c)
                                                            <option value="{{ (int)$c->id }}" @selected((int)$item->category_id === (int)$c->id)>
                                                                {{ $c->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Harga</label>
                                                    <input type="number" name="price" class="form-control" value="{{ (float)$item->price }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Stok (produk)</label>
                                                    <input type="number" name="stock" class="form-control" value="{{ (int)($item->stock ?? 0) }}">
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Deskripsi</label>
                                                    <textarea name="description" class="form-control" rows="4">{{ (string)($item->description ?? '') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="text-muted small mt-2">Catatan: gambar tidak diubah (sesuai perilaku legacy).</div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="update" value="1" class="btn btn-primary">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
