<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminOrdersController extends Controller
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
            return redirect()->route('admin.orders');
        }
        return redirect()->route('admin.orders', [$isError ? 'error' : 'success' => $msg]);
    }

    public function index(Request $request)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        if ($request->isMethod('post') && $request->has('update_status')) {
            $orderId = (int) $request->input('order_id', 0);
            $status = strtolower(trim((string) $request->input('status', '')));

            if ($orderId <= 0) {
                return $this->redirectSelf('Order ID tidak valid.', true);
            }
            if (!in_array($status, $this->validStatuses, true)) {
                return $this->redirectSelf('Status tidak valid.', true);
            }

            try {
                DB::table('orders')->where('id', $orderId)->update(['status' => $status]);
                return $this->redirectSelf('Status order #' . $orderId . ' diperbarui.');
            } catch (\Throwable $e) {
                return $this->redirectSelf('Gagal update status: ' . $e->getMessage(), true);
            }
        }

        $hasShippingColumns = Schema::hasColumn('orders', 'shipping_method') && Schema::hasColumn('orders', 'shipping_fee');

        $orders = [];
        try {
            $q = DB::table('orders as o')
                ->orderByDesc('o.order_date')
                ->orderByDesc('o.id')
                ->select(
                    'o.id',
                    'o.user_id',
                    'o.customer_name',
                    'o.customer_email',
                    'o.customer_phone',
                    'o.total_amount',
                    'o.order_date',
                    'o.status',
                    'o.payment_method',
                    'o.payment_proof',
                    'o.payment_proof_uploaded_at'
                );

            if ($hasShippingColumns) {
                $q->addSelect('o.shipping_method', 'o.shipping_fee');
            }

            if (Schema::hasTable('order_items') && Schema::hasTable('service_slots')) {
                $q->selectSub(function ($sub) {
                    $sub->from('order_items as oi')
                        ->join('service_slots as s', 's.id', '=', 'oi.service_slot_id')
                        ->whereColumn('oi.order_id', 'o.id')
                        ->selectRaw('MIN(s.starts_at)');
                }, 'first_service_at');
            }

            $orders = $q->get()->map(fn ($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            $orders = [];
        }

        $paymentMethods = ['bank_transfer' => 'Manual Transfer Bank'];
        foreach ($orders as &$o) {
            $status = (string) ($o['status'] ?? 'pending');
            [$badge, $label] = $this->statusBadge($status);
            $o['_badge'] = $badge;
            $o['_label'] = $label;

            $pm = trim((string) ($o['payment_method'] ?? ''));
            $o['_payment_method_label'] = ($pm !== '' && isset($paymentMethods[$pm])) ? $paymentMethods[$pm] : 'Manual Transfer Bank';

            if ($hasShippingColumns) {
                $shipMethod = trim((string) ($o['shipping_method'] ?? ''));
                $o['_shipping_label'] = ($shipMethod === 'store_shipping') ? 'Store Shipping' : '';
            }
        }

        return view('admin.orders', [
            'orders' => $orders,
            'validStatuses' => $this->validStatuses,
            'hasShippingColumns' => $hasShippingColumns,
            'error' => (string) $request->query('error', ''),
            'success' => (string) $request->query('success', ''),
        ]);
    }

    public function detail(Request $request, int $id)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        $orderId = (int) $id;
        if ($orderId <= 0) {
            return redirect()->route('admin.orders');
        }

        if ($request->isMethod('post') && $request->has('update_status')) {
            $status = strtolower(trim((string) $request->input('status', '')));
            if (!in_array($status, $this->validStatuses, true)) {
                return redirect()->route('admin.order_detail', ['id' => $orderId, 'error' => 'Status tidak valid.']);
            }

            try {
                DB::table('orders')->where('id', $orderId)->update(['status' => $status]);
                return redirect()->route('admin.order_detail', ['id' => $orderId, 'success' => 'Status order #' . $orderId . ' diperbarui.']);
            } catch (\Throwable $e) {
                return redirect()->route('admin.order_detail', ['id' => $orderId, 'error' => 'Gagal update status: ' . $e->getMessage()]);
            }
        }

        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) {
            return redirect()->route('admin.orders', ['error' => 'Order tidak ditemukan.']);
        }

        $items = [];
        try {
            $items = DB::table('order_items as oi')
                ->join('items as i', 'i.id', '=', 'oi.item_id')
                ->leftJoin('service_slots as s', 's.id', '=', 'oi.service_slot_id')
                ->where('oi.order_id', $orderId)
                ->select('oi.quantity', 'oi.price', 'oi.service_slot_id', 'i.name', 'i.type', 's.starts_at')
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

        $orderTotal = $subtotal;
        if (property_exists($order, 'total_amount') && $order->total_amount !== null) {
            $orderTotal = (float) $order->total_amount;
        }

        $shippingFee = 0.0;
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
        $pm = trim((string) ($order->payment_method ?? ''));
        $paymentMethodLabel = ($pm !== '' && isset($paymentMethods[$pm])) ? $paymentMethods[$pm] : '';

        $paymentProofPath = trim((string) ($order->payment_proof ?? ''));

        $status = (string) ($order->status ?? 'pending');
        [$badge, $label] = $this->statusBadge($status);

        return view('admin.order_detail', [
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
            'validStatuses' => $this->validStatuses,
            'error' => (string) $request->query('error', ''),
            'success' => (string) $request->query('success', ''),
        ]);
    }
}
