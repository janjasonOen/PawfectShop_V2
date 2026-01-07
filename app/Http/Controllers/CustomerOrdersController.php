<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerOrdersController extends Controller
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

        if (!Schema::hasTable('orders')) {
            return redirect()->route('home');
        }

        $userId = (int) $auth['userId'];

        $orders = [];
        try {
            $hasOrderItems = Schema::hasTable('order_items');
            $hasServiceSlots = Schema::hasTable('service_slots');
            $hasItems = Schema::hasTable('items');

            $q = DB::table('orders as o')
                ->where('o.user_id', $userId)
                ->orderByDesc('o.order_date')
                ->orderByDesc('o.id')
                ->select('o.*');

            if ($hasOrderItems && $hasServiceSlots) {
                $q->selectSub(function ($sub) {
                    $sub->from('order_items as oi')
                        ->join('service_slots as s', 's.id', '=', 'oi.service_slot_id')
                        ->whereColumn('oi.order_id', 'o.id')
                        ->selectRaw('MIN(s.starts_at)');
                }, 'first_service_at');
            }

            if ($hasOrderItems) {
                $q->selectSub(function ($sub) {
                    $sub->from('order_items as oi')
                        ->whereColumn('oi.order_id', 'o.id')
                        ->selectRaw('COUNT(*)');
                }, 'item_count');
            }

            if ($hasOrderItems && $hasItems) {
                $q->selectSub(function ($sub) {
                    $sub->from('order_items as oi')
                        ->join('items as i', 'i.id', '=', 'oi.item_id')
                        ->whereColumn('oi.order_id', 'o.id')
                        ->orderBy('oi.id')
                        ->select('i.name')
                        ->limit(1);
                }, 'first_item_name');

                $q->selectSub(function ($sub) {
                    $sub->from('order_items as oi')
                        ->join('items as i', 'i.id', '=', 'oi.item_id')
                        ->whereColumn('oi.order_id', 'o.id')
                        ->orderBy('oi.id')
                        ->select('i.image')
                        ->limit(1);
                }, 'first_item_image');
            }

            $orders = $q->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            $orders = [];
        }

        foreach ($orders as &$o) {
            $status = (string) ($o['status'] ?? 'pending');
            [$badge, $label] = $this->statusBadge($status);
            $o['_badge'] = $badge;
            $o['_label'] = $label;
        }

        return view('customer.orders', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $auth = $this->requireCustomer($request);
        if (!$auth) {
            return redirect()->route('home');
        }

        $userId = (int) $auth['userId'];
        $orderId = (int) $id;
        if ($orderId <= 0) {
            return redirect()->route('customer.orders');
        }

        if (!Schema::hasTable('orders') || !Schema::hasTable('order_items') || !Schema::hasTable('items')) {
            return redirect()->route('customer.orders');
        }

        $order = null;
        try {
            $order = DB::table('orders')
                ->where('id', $orderId)
                ->where('user_id', $userId)
                ->first();
        } catch (\Throwable $e) {
            $order = null;
        }

        if (!$order) {
            return redirect()->route('customer.orders');
        }

        $items = [];
        try {
            $q = DB::table('order_items as oi')
                ->join('items as i', 'i.id', '=', 'oi.item_id')
                ->leftJoin('service_slots as s', 's.id', '=', 'oi.service_slot_id')
                ->where('oi.order_id', $orderId)
                ->select('oi.quantity', 'oi.price', 'oi.service_slot_id', 'i.id as item_id', 'i.name', 'i.type', 'i.image', 's.starts_at');

            $items = $q->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            $items = [];
        }

        $subtotal = 0.0;
        foreach ($items as $row) {
            $subtotal += ((float) ($row['price'] ?? 0) * (int) ($row['quantity'] ?? 0));
        }

        $orderTotal = $subtotal;
        $shippingFee = 0.0;
        if (property_exists($order, 'total_amount') && $order->total_amount !== null) {
            $orderTotal = (float) $order->total_amount;
        }
        if (property_exists($order, 'shipping_fee') && $order->shipping_fee !== null) {
            $shippingFee = (float) $order->shipping_fee;
        } else {
            $shippingFee = max(0.0, $orderTotal - $subtotal);
        }

        $shippingLabel = '';
        $sm = trim((string) ($order->shipping_method ?? ''));
        if ($sm === 'store_shipping') {
            $shippingLabel = 'Store Shipping';
        }

        $paymentMethods = ['bank_transfer' => 'Manual Transfer Bank'];
        $paymentMethodLabel = '';
        $pm = trim((string) ($order->payment_method ?? ''));
        if ($pm !== '' && isset($paymentMethods[$pm])) {
            $paymentMethodLabel = $paymentMethods[$pm];
        }

        $paymentProofPath = trim((string) ($order->payment_proof ?? ''));

        $status = (string) ($order->status ?? 'pending');
        [$badge, $label] = $this->statusBadge($status);

        return view('customer.order_detail', [
            'orderId' => $orderId,
            'order' => $order,
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingFee' => $shippingFee,
            'shippingLabel' => $shippingLabel,
            'orderTotal' => $orderTotal,
            'paymentMethodLabel' => $paymentMethodLabel,
            'paymentProofPath' => $paymentProofPath,
            'badge' => $badge,
            'label' => $label,
        ]);
    }
}
