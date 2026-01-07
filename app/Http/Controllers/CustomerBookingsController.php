<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerBookingsController extends Controller
{
    private function requireCustomer(Request $request): ?array
    {
        $user = $request->session()->get('user');
        if (!is_array($user) || (($user['role'] ?? '') !== 'customer')) {
            return null;
        }
        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return null;
        }
        return ['userId' => $userId, 'user' => $user];
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

    public function index(Request $request)
    {
        $auth = $this->requireCustomer($request);
        if (!$auth) {
            return redirect()->route('home');
        }

        if (!Schema::hasTable('bookings')) {
            return redirect()->route('home');
        }

        $userId = (int) $auth['userId'];

        $bookings = [];
        try {
            $hasBookingItems = Schema::hasTable('booking_items');
            $hasServiceSlots = Schema::hasTable('service_slots');
            $hasItems = Schema::hasTable('items');

            $q = DB::table('bookings as b')
                ->where('b.user_id', $userId)
                ->orderByDesc('b.booking_date')
                ->orderByDesc('b.id')
                ->select('b.*');

            if ($hasBookingItems && $hasServiceSlots) {
                $q->selectSub(function ($sub) {
                    $sub->from('booking_items as bi')
                        ->join('service_slots as s', 's.id', '=', 'bi.service_slot_id')
                        ->whereColumn('bi.booking_id', 'b.id')
                        ->selectRaw('MIN(s.starts_at)');
                }, 'first_service_at');
            }

            if ($hasBookingItems) {
                $q->selectSub(function ($sub) {
                    $sub->from('booking_items as bi')
                        ->whereColumn('bi.booking_id', 'b.id')
                        ->selectRaw('COUNT(*)');
                }, 'item_count');
            }

            if ($hasBookingItems && $hasItems) {
                $q->selectSub(function ($sub) {
                    $sub->from('booking_items as bi')
                        ->join('items as i', 'i.id', '=', 'bi.item_id')
                        ->whereColumn('bi.booking_id', 'b.id')
                        ->orderBy('bi.id')
                        ->select('i.name')
                        ->limit(1);
                }, 'first_item_name');

                $q->selectSub(function ($sub) {
                    $sub->from('booking_items as bi')
                        ->join('items as i', 'i.id', '=', 'bi.item_id')
                        ->whereColumn('bi.booking_id', 'b.id')
                        ->orderBy('bi.id')
                        ->select('i.image')
                        ->limit(1);
                }, 'first_item_image');
            }

            $bookings = $q->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            $bookings = [];
        }

        foreach ($bookings as &$b) {
            $status = (string) ($b['status'] ?? 'pending');
            [$badge, $label] = $this->statusBadge($status);
            $b['_badge'] = $badge;
            $b['_label'] = $label;
        }

        return view('customer.bookings', [
            'bookings' => $bookings,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $auth = $this->requireCustomer($request);
        if (!$auth) {
            return redirect()->route('home');
        }

        $userId = (int) $auth['userId'];
        $bookingId = (int) $id;
        if ($bookingId <= 0) {
            return redirect()->route('customer.bookings');
        }

        if (!Schema::hasTable('bookings') || !Schema::hasTable('booking_items') || !Schema::hasTable('items')) {
            return redirect()->route('customer.bookings');
        }

        $booking = null;
        try {
            $booking = DB::table('bookings')
                ->where('id', $bookingId)
                ->where('user_id', $userId)
                ->first();
        } catch (\Throwable $e) {
            $booking = null;
        }

        if (!$booking) {
            return redirect()->route('customer.bookings');
        }

        $items = [];
        try {
            $q = DB::table('booking_items as bi')
                ->join('items as i', 'i.id', '=', 'bi.item_id')
                ->leftJoin('service_slots as s', 's.id', '=', 'bi.service_slot_id')
                ->where('bi.booking_id', $bookingId)
                ->select('bi.quantity', 'bi.price', 'bi.service_slot_id', 'i.id as item_id', 'i.name', 'i.type', 'i.image', 's.starts_at');

            $items = $q->get()->map(fn ($r) => (array) $r)->all();
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
        $paymentMethodLabel = '';
        $pm = trim((string) ($booking->payment_method ?? ''));
        if ($pm !== '' && isset($paymentMethods[$pm])) {
            $paymentMethodLabel = $paymentMethods[$pm];
        }

        $paymentProofPath = trim((string) ($booking->payment_proof ?? ''));

        $status = (string) ($booking->status ?? 'pending');
        [$badge, $label] = $this->statusBadge($status);

        return view('customer.booking_detail', [
            'bookingId' => $bookingId,
            'booking' => $booking,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => $total,
            'paymentMethodLabel' => $paymentMethodLabel,
            'paymentProofPath' => $paymentProofPath,
            'badge' => $badge,
            'label' => $label,
        ]);
    }
}
