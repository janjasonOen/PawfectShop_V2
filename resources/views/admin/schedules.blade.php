@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-1">Jadwal Service</h3>
    <div class="text-muted mb-3">Customer hanya bisa memilih jadwal yang admin buat di sini.</div>

    @if(!empty($error))
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Service</label>
                    <select name="service_item_id" class="form-select" required>
                        <option value="">Pilih service</option>
                        @foreach($services as $svc)
                            <option value="{{ (int)$svc->id }}">{{ $svc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jam</label>
                    <input type="time" name="time" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kapasitas</label>
                    <input type="number" name="capacity" class="form-control" min="1" value="1" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" name="add_slot" value="1" class="btn btn-primary">Tambah Slot</button>
                </div>
            </form>

            <hr class="my-4">

            <div class="fw-semibold mb-2">Generate Slots (Bulk)</div>
            <div class="text-muted small mb-3">Buat jadwal otomatis berdasarkan rentang tanggal, jam operasional, interval, dan kapasitas.</div>

            <form method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Service</label>
                    <select name="service_item_id" class="form-select" required>
                        <option value="">Pilih service</option>
                        @foreach($services as $svc)
                            <option value="{{ (int)$svc->id }}">{{ $svc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End date</label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Open</label>
                    <input type="time" name="start_time" class="form-control" value="09:00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Close</label>
                    <input type="time" name="end_time" class="form-control" value="17:00" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Interval (min)</label>
                    <input type="number" name="interval_min" class="form-control" min="5" value="60" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" min="1" value="1" required>
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="weekdays_only" id="weekdays_only" value="1" checked>
                        <label class="form-check-label" for="weekdays_only">Weekdays only (Mon–Fri)</label>
                    </div>
                </div>

                <div class="col-12 d-grid">
                    <button type="submit" name="generate_slots" value="1" class="btn btn-outline-primary">Generate Slots</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service</th>
                            <th>Mulai</th>
                            <th>Kapasitas</th>
                            <th>Booked</th>
                            <th>Status</th>
                            <th width="220">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($slots->count() === 0)
                            <tr><td colspan="7" class="text-center">Belum ada slot.</td></tr>
                        @endif

                        @foreach($slots as $s)
                            @php
                                $active = (int)($s->is_active ?? 0) === 1;
                                $booked = (int)($s->booked ?? 0);
                                $cap = (int)($s->capacity ?? 1);
                            @endphp
                            <tr>
                                <td>{{ (int)$s->id }}</td>
                                <td>{{ $s->service_name }}</td>
                                <td>{{ $s->starts_at }}</td>
                                <td>{{ $cap }}</td>
                                <td>{{ $booked }}</td>
                                <td>
                                    @if($active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($active)
                                        <a class="btn btn-outline-danger btn-sm"
                                           href="{{ route('admin.schedules', ['deactivate' => (int)$s->id]) }}"
                                           onclick="return confirm('Nonaktifkan slot ini?')">Deactivate</a>
                                    @else
                                        <a class="btn btn-outline-primary btn-sm"
                                           href="{{ route('admin.schedules', ['activate' => (int)$s->id]) }}">Activate</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
