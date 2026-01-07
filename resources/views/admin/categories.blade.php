@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Data Kategori</h3>
    <div class="text-muted mb-3">Tambah, edit, hapus kategori.</div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <input type="text" name="name" class="form-control" placeholder="Nama kategori" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="add" value="1" class="btn btn-primary w-100">Tambah Kategori</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="60">No</th>
                        <th>Nama Kategori</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if($categories->count() === 0)
                        <tr>
                            <td colspan="3" class="text-center">Belum ada data</td>
                        </tr>
                    @endif

                    @foreach($categories as $i => $cat)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $cat->name }}</td>
                            <td>
                                <button
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal{{ (int)$cat->id }}">
                                    Edit
                                </button>

                                <a
                                    href="{{ route('admin.categories', ['delete' => (int)$cat->id]) }}"
                                    onclick="return confirm('Hapus kategori ini?')"
                                    class="btn btn-danger btn-sm">
                                    Hapus
                                </a>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal{{ (int)$cat->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Kategori</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="{{ (int)$cat->id }}">
                                            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
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
