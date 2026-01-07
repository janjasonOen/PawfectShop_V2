<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminBookingsController extends Controller
{
    private array $validStatuses = ['pending', 'processed', 'completed', 'cancelled'];

    private function requireAdmin(Request $request): bool
    {
        $user = $request->session()->get('user');
        return is_array($user) && (($user['role'] ?? '') === 'admin');
    }

    private function statusBadge(string $status): array
    {
        $s = strtolower(trim($status));
        return match ($s) {
            'pending' => ['warning', 'Pending'],
            'processed' => ['info', 'Processed'],
            'completed' => ['success', 'Completed'],
            'cancelled' => ['secondary', 'Cancelled'],
            default => ['light', $status],
        };
    }

    private function redirectSelf(string $msg = '', bool $isError = false)
    {
        if ($msg === '') {
            return redirect()->route('admin.bookings');
        }
        return redirect()->route('admin.bookings', [$isError ? 'error' : 'success' => $msg]);
    }

    public function index(Request $request)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        if ($request->isMethod('post') && $request->has('update_status')) {
            $bookingId = (int) $request->input('booking_id', 0);
            $status = strtolower(trim((string) $request->input('status', '')));

            if ($bookingId <= 0) {
                return $this->redirectSelf('Booking ID tidak valid.', true);
            }
            if (!in_array($status, $this->validStatuses, true)) {
                return $this->redirectSelf('Status tidak valid.', true);
            }

            try {
                DB::table('bookings')->where('id', $bookingId)->update(['status' => $status]);
                return $this->redirectSelf('Status booking #' . $bookingId . ' diperbarui.');
            } catch (\Throwable $e) {
                return $this->redirectSelf('Gagal update status: ' . $e->getMessage(), true);
            }
        }

        $bookings = [];
        try {
            $q = DB::table('bookings as b')
                ->orderByDesc('b.booking_date')
                ->orderByDesc('b.id')
                ->select(
                    'b.id',
                    'b.user_id',
                    'b.customer_name',
                    'b.customer_email',
                    'b.customer_phone',
                    'b.total_amount',
                    'b.booking_date',
                    'b.status',
                    'b.payment_method',
                    'b.payment_proof',
                    'b.payment_proof_uploaded_at'
                );

            if (Schema::hasTable('booking_items') && Schema::hasTable('service_slots')) {
                $q->selectSub(function ($sub) {
                    $sub->from('booking_items as bi')
                        ->join('service_slots as s', 's.id', '=', 'bi.service_slot_id')
                        ->whereColumn('bi.booking_id', 'b.id')
                        ->selectRaw('MIN(s.starts_at)');
                }, 'first_service_at');
            }

            $bookings = $q->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            $bookings = [];
        }

        $paymentMethods = ['bank_transfer' => 'Manual Transfer Bank'];
        foreach ($bookings as &$b) {
            $status = (string) ($b['status'] ?? 'pending');
            [$badge, $label] = $this->statusBadge($status);
            $b['_badge'] = $badge;
            $b['_label'] = $label;

            $pm = trim((string) ($b['payment_method'] ?? ''));
            $b['_payment_method_label'] = ($pm !== '' && isset($paymentMethods[$pm])) ? $paymentMethods[$pm] : 'Manual Transfer Bank';
        }

        return view('admin.bookings', [
            'bookings' => $bookings,
            'validStatuses' => $this->validStatuses,
            'error' => (string) $request->query('error', ''),
            'success' => (string) $request->query('success', ''),
        ]);
    }

    public function detail(Request $request, int $id)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        $bookingId = (int) $id;
        if ($bookingId <= 0) {
            return redirect()->route('admin.bookings');
        }

        if ($request->isMethod('post') && $request->has('update_status')) {
            $status = strtolower(trim((string) $request->input('status', '')));
            if (!in_array($status, $this->validStatuses, true)) {
                return redirect()->route('admin.booking_detail', ['id' => $bookingId, 'error' => 'Status tidak valid.']);
            }

            try {
                DB::table('bookings')->where('id', $bookingId)->update(['status' => $status]);
                return redirect()->route('admin.booking_detail', ['id' => $bookingId, 'success' => 'Status booking #' . $bookingId . ' diperbarui.']);
            } catch (\Throwable $e) {
                return redirect()->route('admin.booking_detail', ['id' => $bookingId, 'error' => 'Gagal update status: ' . $e->getMessage()]);
            }
        }

        $booking = DB::table('bookings')->where('id', $bookingId)->first();
        if (!$booking) {
            return redirect()->route('admin.bookings', ['error' => 'Booking tidak ditemukan.']);
        }

        $items = [];
        try {
            $items = DB::table('booking_items as bi')
                ->join('items as i', 'i.id', '=', 'bi.item_id')
                ->leftJoin('service_slots as s', 's.id', '=', 'bi.service_slot_id')
                ->where('bi.booking_id', $bookingId)
                ->select('bi.quantity', 'bi.price', 'bi.service_slot_id', 'i.name', 'i.type', 's.starts_at')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Throwable $e) {
            $items = [];
        }

        $subtotal = 0.0;
        foreach ($items as $row) {
            $subtotal += ((float) ($row['price'] ?? 0) * (int) ($row['quantity'] ?? 0));
        }

        $total = $subtotal;
        if (property_exists($booking, 'total_amount') && $booking->total_amount !== null) {
            $total = (float) $booking->total_amount;
        }

        $paymentMethods = ['bank_transfer' => 'Manual Transfer Bank'];
        $pm = trim((string) ($booking->payment_method ?? ''));
        $paymentMethodLabel = ($pm !== '' && isset($paymentMethods[$pm])) ? $paymentMethods[$pm] : '';

        $paymentProofPath = trim((string) ($booking->payment_proof ?? ''));

        $status = (string) ($booking->status ?? 'pending');
        [$badge, $label] = $this->statusBadge($status);

        return view('admin.booking_detail', [
            'bookingId' => $bookingId,
            'booking' => $booking,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $total,
            'paymentMethodLabel' => $paymentMethodLabel,
            'paymentProofPath' => $paymentProofPath,
            'badge' => $badge,
            'label' => $label,
            'validStatuses' => $this->validStatuses,
            'error' => (string) $request->query('error', ''),
            'success' => (string) $request->query('success', ''),
        ]);
    }
}
