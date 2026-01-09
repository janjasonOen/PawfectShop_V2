@php
    $success = session('success');
    $status = session('status');
    $error = session('error');
@endphp

@if (!empty($success) || !empty($status) || !empty($error))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        @if (!empty($success) || !empty($status))
            <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-autoshow="1" data-bs-delay="2500">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ $success ?: $status }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif

        @if (!empty($error))
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-autoshow="1" data-bs-delay="3500">
                <div class="d-flex">
                    <div class="toast-body">
                        {{ $error }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>
@endif
