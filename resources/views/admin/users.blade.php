@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Manajemen User</h3>
    <div class="text-muted mb-3">Tambah, edit, hapus user.</div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Nama" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-control" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add" value="1" class="btn btn-primary w-100">Tambah User</button>
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
                        <th>Email</th>
                        <th>Role</th>
                        <th width="220">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @if($users->count() === 0)
                        <tr>
                            <td colspan="5" class="text-center">Belum ada user</td>
                        </tr>
                    @endif

                    @foreach($users as $i => $u)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ ucfirst((string)$u->role) }}</td>
                            <td>
                                <button class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal{{ (int)$u->id }}">
                                    Edit
                                </button>

                                @if((int)$u->id !== (int)$currentId)
                                    <a href="{{ route('admin.users', ['delete' => (int)$u->id]) }}"
                                       onclick="return confirm('Hapus user ini?')"
                                       class="btn btn-danger btn-sm">
                                        Hapus
                                    </a>
                                @else
                                    <span class="text-muted">Current</span>
                                @endif
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal{{ (int)$u->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="{{ (int)$u->id }}">
                                            <div class="mb-2">
                                                <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                            </div>
                                            <div class="mb-2">
                                                <select name="role" class="form-control" required>
                                                    <option value="customer" @selected((string)$u->role === 'customer')>Customer</option>
                                                    <option value="admin" @selected((string)$u->role === 'admin')>Admin</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <input type="password" name="password" class="form-control" placeholder="Password baru (kosongkan jika tidak ganti)">
                                            </div>
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
