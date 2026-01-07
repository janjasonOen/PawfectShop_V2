<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckoutController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->session()->get('user');
        $isCustomerLoggedIn = is_array($user) && (($user['role'] ?? '') === 'customer');

        $prefillName = '';
        $prefillEmail = '';
        $prefillPhone = '';
        $prefillAddress = '';
        $addresses = [];
        $defaultAddressId = 0;

        $paymentMethodKey = 'bank_transfer';
        $paymentMethodLabel = 'Manual Transfer Bank';

        if ($isCustomerLoggedIn) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId > 0) {
                $u = DB::table('users')
                    ->select('name', 'email', 'phone', 'address')
                    ->where('id', $userId)
                    ->where('role', 'customer')
                    ->first();

                if ($u) {
                    $prefillName = (string) ($u->name ?? '');
                    $prefillEmail = (string) ($u->email ?? '');
                    $prefillPhone = (string) ($u->phone ?? '');
                    $prefillAddress = (string) ($u->address ?? '');
                }

                if (Schema::hasTable('user_addresses')) {
                    $addresses = DB::table('user_addresses')
                        ->select('id', 'label', 'recipient_name', 'phone', 'address', 'is_default')
                        ->where('user_id', $userId)
                        ->where('is_active', 1)
                        ->orderByDesc('is_default')
                        ->orderByDesc('id')
                        ->get()
                        ->map(fn ($r) => (array) $r)
                        ->all();

                    if (count($addresses) > 0) {
                        $default = $addresses[0];
                        foreach ($addresses as $candidate) {
                            if ((int) ($candidate['is_default'] ?? 0) === 1) {
                                $default = $candidate;
                                break;
                            }
                        }

                        $defaultAddressId = (int) ($default['id'] ?? 0);
                        if (!empty($default['address'])) {
                            $prefillAddress = (string) $default['address'];
                        }
                        if (!empty($default['phone'])) {
                            $prefillPhone = (string) $default['phone'];
                        }
                        if (!empty($default['recipient_name'])) {
                            $prefillName = (string) $default['recipient_name'];
                        }
                    }
                }
            }
        }

        $cart = $request->session()->get('cart', []);
        if (!is_array($cart)) {
            $cart = [];
        }

        $normalizedCart = [];
        foreach ($cart as $id => $entry) {
            $itemId = (int) $id;
            if ($itemId <= 0) {
                continue;
            }
            if (is_array($entry)) {
                $qty = (int) ($entry['qty'] ?? 1);
                if ($qty <= 0) {
                    $qty = 1;
                }
                $slotId = (int) ($entry['slot_id'] ?? 0);
                $normalizedCart[$itemId] = ['qty' => $qty, 'slot_id' => $slotId];
            } else {
                $qty = (int) $entry;
                if ($qty <= 0) {
                    $qty = 1;
                }
                $normalizedCart[$itemId] = ['qty' => $qty, 'slot_id' => 0];
            }
        }

        $itemIds = array_keys($normalizedCart);
        $itemsById = [];
        if (count($itemIds) > 0) {
            $rows = Item::query()
                ->select('id', 'name', 'type', 'price', 'stock', 'status', 'image')
                ->whereIn('id', $itemIds)
                ->get();

            foreach ($rows as $row) {
                $itemsById[(int) $row->id] = $row;
            }

            foreach ($itemIds as $id) {
                if (!isset($itemsById[(int) $id])) {
                    unset($cart[(int) $id]);
                    unset($normalizedCart[(int) $id]);
                }
            }

            $request->session()->put('cart', $cart);
        }

        $shippingMethodKey = 'store_shipping';
        $shippingMethodLabel = 'Store Shipping';
        $storeShippingFee = 20000;

        $total = 0.0;
        $hasService = false;
        $hasProduct = false;
        $lineItems = [];

        $missingServiceSchedule = false;
        $invalidServiceSchedule = false;

        $slotIds = [];
        foreach ($normalizedCart as $entry) {
            $sid = (int) ($entry['slot_id'] ?? 0);
            if ($sid > 0) {
                $slotIds[$sid] = true;
            }
        }

        $slotsById = [];
        if (count($slotIds) > 0 && Schema::hasTable('service_slots')) {
            $rows = DB::table('service_slots')
                ->select('id', 'service_item_id', 'starts_at', 'is_active')
                ->whereIn('id', array_keys($slotIds))
                ->get();

            foreach ($rows as $row) {
                $slotsById[(int) $row->id] = (array) $row;
            }
        }

        $hasActiveAddresses = $isCustomerLoggedIn && count($addresses) > 0;
        $canCheckout = $isCustomerLoggedIn && $hasActiveAddresses;

        foreach ($normalizedCart as $id => $entry) {
            $id = (int) $id;
            $qty = (int) ($entry['qty'] ?? 1);
            if ($qty <= 0) {
                continue;
            }
            $slotId = (int) ($entry['slot_id'] ?? 0);

            $item = $itemsById[$id] ?? null;
            if (!$item) {
                continue;
            }

            $type = (string) ($item->type ?? 'product');
            if ($type === 'service') {
                $hasService = true;
                if ($slotId <= 0) {
                    $missingServiceSchedule = true;
                } else {
                    $slot = $slotsById[$slotId] ?? null;
                    if (!$slot || (int) ($slot['is_active'] ?? 0) !== 1 || (int) ($slot['service_item_id'] ?? 0) !== $id) {
                        $invalidServiceSchedule = true;
                    }
                }
            } else {
                $hasProduct = true;
            }

            $price = (float) ($item->price ?? 0);
            $subtotal = $price * $qty;
            $total += $subtotal;

            $lineItems[] = [
                'id' => $id,
                'name' => (string) ($item->name ?? ''),
                'type' => $type,
                'price' => $price,
                'qty' => $qty,
                'subtotal' => $subtotal,
                'service_slot_id' => ($type === 'service' ? $slotId : 0),
            ];
        }

        $canCheckout = $canCheckout && !$missingServiceSchedule && !$invalidServiceSchedule;

        $shippingFee = $hasProduct ? $storeShippingFee : 0;
        $grandTotal = $total + $shippingFee;

        return view('checkout', [
            'isCustomerLoggedIn' => $isCustomerLoggedIn,
            'prefillName' => $prefillName,
            'prefillEmail' => $prefillEmail,
            'prefillPhone' => $prefillPhone,
            'prefillAddress' => $prefillAddress,
            'addresses' => $addresses,
            'defaultAddressId' => $defaultAddressId,
            'paymentMethodKey' => $paymentMethodKey,
            'paymentMethodLabel' => $paymentMethodLabel,
            'normalizedCart' => $normalizedCart,
            'itemsById' => $itemsById,
            'lineItems' => $lineItems,
            'slotsById' => $slotsById,
            'shippingMethodKey' => $shippingMethodKey,
            'shippingMethodLabel' => $shippingMethodLabel,
            'storeShippingFee' => $storeShippingFee,
            'hasService' => $hasService,
            'hasProduct' => $hasProduct,
            'missingServiceSchedule' => $missingServiceSchedule,
            'invalidServiceSchedule' => $invalidServiceSchedule,
            'hasActiveAddresses' => $hasActiveAddresses,
            'canCheckout' => $canCheckout,
            'total' => $total,
            'shippingFee' => $shippingFee,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function place(Request $request)
    {
        $user = $request->session()->get('user');
        if (!is_array($user) || (($user['role'] ?? '') !== 'customer')) {
            return redirect()->route('checkout', ['error' => 'Silakan login sebagai customer untuk checkout.']);
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return redirect()->route('checkout', ['error' => 'Session user tidak valid. Silakan login ulang.']);
        }

        $paymentMethodKey = 'bank_transfer';
        $paymentMethodLabel = 'Manual Transfer Bank';

        $shippingMethodKey = 'store_shipping';
        $shippingMethodLabel = 'Store Shipping';
        $storeShippingFee = 20000;

        $cart = $request->session()->get('cart', []);
        if (!is_array($cart) || count($cart) === 0) {
            return redirect()->route('checkout', ['error' => 'Keranjang kosong.']);
        }

        $normalizedCart = [];
        foreach ($cart as $id => $entry) {
            $itemId = (int) $id;
            if ($itemId <= 0) {
                continue;
            }
            if (is_array($entry)) {
                $qty = (int) ($entry['qty'] ?? 1);
                if ($qty <= 0) {
                    $qty = 1;
                }
                $slotId = (int) ($entry['slot_id'] ?? 0);
                $normalizedCart[$itemId] = ['qty' => $qty, 'slot_id' => $slotId];
            } else {
                $qty = (int) $entry;
                if ($qty <= 0) {
                    $qty = 1;
                }
                $normalizedCart[$itemId] = ['qty' => $qty, 'slot_id' => 0];
            }
        }

        $uRow = DB::table('users')
            ->select('name', 'email', 'phone')
            ->where('id', $userId)
            ->where('role', 'customer')
            ->first();

        if (!$uRow) {
            return redirect()->route('checkout', ['error' => 'User tidak ditemukan.']);
        }

        $customerEmail = (string) ($uRow->email ?? '');
        if ($customerEmail === '') {
            return redirect()->route('checkout', ['error' => 'Email user tidak valid.']);
        }

        $customerName = (string) ($uRow->name ?? '');
        $userPhone = (string) ($uRow->phone ?? '');

        $notes = trim((string) $request->input('notes', ''));

        $addressId = (int) $request->input('address_id', 0);
        if ($addressId <= 0) {
            return redirect()->route('checkout', ['error' => 'Pilih alamat untuk checkout.']);
        }

        if (!Schema::hasTable('user_addresses')) {
            return redirect()->route('checkout', ['error' => 'Alamat belum siap. Jalankan update database (user_addresses).']);
        }

        $aRow = DB::table('user_addresses')
            ->select('recipient_name', 'phone', 'address')
            ->where('id', $addressId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->first();

        if (!$aRow) {
            return redirect()->route('checkout', ['error' => 'Alamat tidak ditemukan.']);
        }

        $customerAddress = trim((string) ($aRow->address ?? ''));
        if ($customerAddress === '') {
            return redirect()->route('checkout', ['error' => 'Alamat tidak valid.']);
        }

        $addrPhone = trim((string) ($aRow->phone ?? ''));
        $customerPhone = $addrPhone !== '' ? $addrPhone : trim($userPhone);
        if ($customerPhone === '') {
            return redirect()->route('checkout', ['error' => 'Nomor telepon belum tersedia. Tambahkan di alamat.']);
        }

        $recipient = trim((string) ($aRow->recipient_name ?? ''));
        if ($recipient !== '') {
            $customerName = $recipient;
        }

        $itemIds = array_keys($normalizedCart);
        $items = Item::query()
            ->select('id', 'name', 'type', 'price', 'stock', 'status')
            ->whereIn('id', $itemIds)
            ->get();

        $itemsById = [];
        foreach ($items as $row) {
            $itemsById[(int) $row->id] = $row;
        }

        $hasService = false;
        $hasProduct = false;
        $lineItems = [];
        $total = 0.0;

        foreach ($normalizedCart as $id => $entry) {
            $id = (int) $id;
            $qty = (int) ($entry['qty'] ?? 1);
            $slotId = (int) ($entry['slot_id'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $item = $itemsById[$id] ?? null;
            if (!$item) {
                return redirect()->route('checkout', ['error' => 'Ada item di keranjang yang sudah tidak tersedia. Silakan update cart.']);
            }

            if (($item->status ?? 'active') !== 'active') {
                return redirect()->route('checkout', ['error' => 'Ada item di keranjang yang tidak aktif. Silakan update cart.']);
            }

            $type = (string) ($item->type ?? 'product');
            $price = (float) ($item->price ?? 0);

            if ($type === 'service') {
                $hasService = true;
                if ($slotId <= 0) {
                    return redirect()->route('checkout', ['error' => 'Ada service yang belum dipilih jadwalnya. Pilih jadwal di halaman detail service.']);
                }
            } else {
                $hasProduct = true;
                $stock = (int) ($item->stock ?? 0);
                if ($qty > $stock) {
                    return redirect()->route('checkout', ['error' => 'Stok tidak cukup untuk: ' . (string) ($item->name ?? 'item') . '.']);
                }
            }

            $total += ($price * $qty);
            $lineItems[] = [
                'item_id' => $id,
                'qty' => $qty,
                'price' => $price,
                'type' => $type,
                'service_slot_id' => ($type === 'service' ? $slotId : null),
            ];
        }

        $shippingFee = 0;
        $shippingMethod = null;
        if ($hasProduct) {
            $shippingMethod = $shippingMethodKey;
            $shippingFee = $storeShippingFee;

            $postedShippingMethod = trim((string) $request->input('shipping_method', ''));
            $postedShippingFee = (int) $request->input('shipping_fee', 0);

            if ($postedShippingMethod !== '' && $postedShippingMethod !== $shippingMethodKey) {
                return redirect()->route('checkout', ['error' => 'Shipping method tidak valid.']);
            }
            if ($postedShippingFee !== 0 && $postedShippingFee !== $storeShippingFee) {
                return redirect()->route('checkout', ['error' => 'Shipping fee tidak valid.']);
            }
        }

        $productLineItems = array_values(array_filter($lineItems, fn ($li) => ($li['type'] ?? '') !== 'service'));
        $serviceLineItems = array_values(array_filter($lineItems, fn ($li) => ($li['type'] ?? '') === 'service'));

        $productsTotal = 0.0;
        foreach ($productLineItems as $li) {
            $productsTotal += ((float) ($li['price'] ?? 0) * (int) ($li['qty'] ?? 0));
        }

        $servicesTotal = 0.0;
        foreach ($serviceLineItems as $li) {
            $servicesTotal += ((float) ($li['price'] ?? 0) * (int) ($li['qty'] ?? 0));
        }

        try {
            $result = DB::transaction(function () use (
                $userId,
                $customerName,
                $customerEmail,
                $customerPhone,
                $customerAddress,
                $notes,
                $paymentMethodKey,
                $shippingMethod,
                $shippingFee,
                $productLineItems,
                $serviceLineItems,
                $productsTotal,
                $servicesTotal
            ) {
                $createdOrderId = null;
                $createdBookingId = null;

                if (count($productLineItems) > 0) {
                    $orderId = DB::table('orders')->insertGetId([
                        'user_id' => $userId,
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_phone' => $customerPhone,
                        'customer_address' => $customerAddress,
                        'notes' => ($notes === '' ? null : $notes),
                        'payment_method' => $paymentMethodKey,
                        'shipping_method' => $shippingMethod,
                        'shipping_fee' => $shippingFee,
                        'service_datetime' => null,
                        'total_amount' => ($productsTotal + $shippingFee),
                        'status' => 'pending',
                    ]);

                    foreach ($productLineItems as $li) {
                        $qty = (int) ($li['qty'] ?? 0);
                        $itemId = (int) ($li['item_id'] ?? 0);
                        if ($qty <= 0 || $itemId <= 0) {
                            continue;
                        }

                        DB::table('order_items')->insert([
                            'order_id' => $orderId,
                            'item_id' => $itemId,
                            'service_slot_id' => null,
                            'quantity' => $qty,
                            'price' => (float) ($li['price'] ?? 0),
                        ]);

                        $affected = DB::update(
                            'UPDATE items SET stock = stock - ? WHERE id = ? AND stock IS NOT NULL AND stock >= ?',
                            [$qty, $itemId, $qty]
                        );

                        if ($affected === 0) {
                            throw new \RuntimeException('Stok berubah. Silakan ulangi checkout.');
                        }
                    }

                    $createdOrderId = (int) $orderId;
                }

                if (count($serviceLineItems) > 0) {
                    if (!Schema::hasTable('bookings') || !Schema::hasTable('booking_items') || !Schema::hasTable('service_slots')) {
                        throw new \RuntimeException('Table bookings belum ada. Import/update database untuk fitur booking.');
                    }

                    $bookingId = DB::table('bookings')->insertGetId([
                        'user_id' => $userId,
                        'customer_name' => $customerName,
                        'customer_email' => $customerEmail,
                        'customer_phone' => $customerPhone,
                        'customer_address' => $customerAddress,
                        'notes' => ($notes === '' ? null : $notes),
                        'payment_method' => $paymentMethodKey,
                        'service_datetime' => null,
                        'total_amount' => $servicesTotal,
                        'status' => 'pending',
                    ]);

                    $bookingServiceDatetime = null;

                    foreach ($serviceLineItems as $li) {
                        $svcItemId = (int) ($li['item_id'] ?? 0);
                        $serviceSlotId = (int) ($li['service_slot_id'] ?? 0);
                        $requestedQty = (int) ($li['qty'] ?? 1);
                        if ($requestedQty <= 0) {
                            $requestedQty = 1;
                        }

                        if ($svcItemId <= 0 || $serviceSlotId <= 0) {
                            throw new \RuntimeException('Slot jadwal tidak valid.');
                        }

                        $slot = DB::table('service_slots')
                            ->where('id', $serviceSlotId)
                            ->lockForUpdate()
                            ->first();

                        if (!$slot || (int) ($slot->is_active ?? 0) !== 1) {
                            throw new \RuntimeException('Slot jadwal tidak valid.');
                        }

                        if ((int) ($slot->service_item_id ?? 0) !== $svcItemId) {
                            throw new \RuntimeException('Slot tidak sesuai dengan service yang dipilih.');
                        }

                        $cap = (int) ($slot->capacity ?? 1);
                        if ($cap <= 0) {
                            $cap = 1;
                        }

                        $bookedRow = DB::selectOne(
                            'SELECT '
                            . '  (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi '
                            . '   JOIN orders o ON o.id = oi.order_id '
                            . '   WHERE oi.service_slot_id = ? '
                            . '   AND o.status IN ("pending","processed","completed")) '
                            . ' +'
                            . '  (SELECT COALESCE(SUM(bi.quantity),0) FROM booking_items bi '
                            . '   JOIN bookings b ON b.id = bi.booking_id '
                            . '   WHERE bi.service_slot_id = ? '
                            . '   AND b.status IN ("pending","processed","completed")) AS booked',
                            [$serviceSlotId, $serviceSlotId]
                        );

                        $booked = (int) (($bookedRow->booked ?? 0));
                        if (($booked + $requestedQty) > $cap) {
                            throw new \RuntimeException('Jadwal sudah penuh. Silakan pilih slot lain.');
                        }

                        if ($bookingServiceDatetime === null) {
                            $bookingServiceDatetime = (string) ($slot->starts_at ?? null);
                        }

                        DB::table('booking_items')->insert([
                            'booking_id' => $bookingId,
                            'item_id' => $svcItemId,
                            'service_slot_id' => $serviceSlotId,
                            'quantity' => $requestedQty,
                            'price' => (float) ($li['price'] ?? 0),
                        ]);
                    }

                    if ($bookingServiceDatetime !== null) {
                        DB::table('bookings')->where('id', $bookingId)->update(['service_datetime' => $bookingServiceDatetime]);
                    }

                    $createdBookingId = (int) $bookingId;
                }

                return ['order_id' => $createdOrderId, 'booking_id' => $createdBookingId];
            });

            $request->session()->forget('cart');

            $qs = [];
            if (!empty($result['order_id'])) {
                $qs['order_id'] = (int) $result['order_id'];
            }
            if (!empty($result['booking_id'])) {
                $qs['booking_id'] = (int) $result['booking_id'];
            }

            return redirect()->route('checkout.success', $qs);
        } catch (\Throwable $e) {
            return redirect()->route('checkout', ['error' => 'Gagal membuat order: ' . $e->getMessage()]);
        }
    }

    public function success(Request $request)
    {
        $orderId = (int) $request->query('order_id', 0);
        $bookingId = (int) $request->query('booking_id', 0);

        if ($orderId <= 0 && $bookingId <= 0) {
            return redirect()->route('catalog', ['type' => 'product']);
        }

        $order = null;
        if ($orderId > 0 && Schema::hasTable('orders')) {
            $order = DB::table('orders')->where('id', $orderId)->first();
        }

        $booking = null;
        if ($bookingId > 0 && Schema::hasTable('bookings')) {
            $booking = DB::table('bookings')->where('id', $bookingId)->first();
        }

        $paymentMethods = [
            'bank_transfer' => 'Manual Transfer Bank',
        ];

        $paymentMethodLabel = function ($row) use ($paymentMethods): string {
            if (!$row) {
                return '';
            }
            $pm = trim((string) ($row->payment_method ?? ''));
            if ($pm !== '' && isset($paymentMethods[$pm])) {
                return $paymentMethods[$pm];
            }

            $notes = (string) ($row->notes ?? '');
            if ($notes !== '') {
                foreach ($paymentMethods as $k => $label) {
                    if (stripos($notes, 'Payment Method: ' . $label) !== false) {
                        return $label;
                    }
                }
            }

            return '';
        };

        $orderPaymentMethodLabel = $paymentMethodLabel($order);
        $bookingPaymentMethodLabel = $paymentMethodLabel($booking);

        $orderItems = [];
        if ($orderId > 0 && Schema::hasTable('order_items')) {
            $orderItems = DB::table('order_items as oi')
                ->join('items as i', 'i.id', '=', 'oi.item_id')
                ->where('oi.order_id', $orderId)
                ->select('oi.quantity', 'oi.price', 'i.name', 'i.type')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        $bookingItems = [];
        if ($bookingId > 0 && Schema::hasTable('booking_items')) {
            $bookingItems = DB::table('booking_items as bi')
                ->join('items as i', 'i.id', '=', 'bi.item_id')
                ->leftJoin('service_slots as s', 's.id', '=', 'bi.service_slot_id')
                ->where('bi.booking_id', $bookingId)
                ->select('bi.quantity', 'bi.price', 'bi.service_slot_id', 'i.id as item_id', 'i.name', 'i.type', 's.starts_at')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        $orderSubtotal = 0.0;
        foreach ($orderItems as $row) {
            $orderSubtotal += ((float) ($row['price'] ?? 0) * (int) ($row['quantity'] ?? 0));
        }

        $orderTotal = $orderSubtotal;
        $shippingFee = 0.0;
        if ($order) {
            if ($order->total_amount !== null) {
                $orderTotal = (float) $order->total_amount;
            }
            if (property_exists($order, 'shipping_fee') && $order->shipping_fee !== null) {
                $shippingFee = (float) $order->shipping_fee;
            } else {
                $shippingFee = max(0.0, $orderTotal - $orderSubtotal);
            }
        }

        $shippingLabel = '';
        if ($order) {
            $sm = trim((string) ($order->shipping_method ?? ''));
            if ($sm === 'store_shipping') {
                $shippingLabel = 'Store Shipping';
            }
        }

        $bookingTotal = 0.0;
        foreach ($bookingItems as $row) {
            $bookingTotal += ((float) ($row['price'] ?? 0) * (int) ($row['quantity'] ?? 0));
        }
        if ($booking && $booking->total_amount !== null) {
            $bookingTotal = (float) $booking->total_amount;
        }

        $grandTotal = $orderTotal + $bookingTotal;

        $orderPaymentProofPath = $order ? trim((string) ($order->payment_proof ?? '')) : '';
        $bookingPaymentProofPath = $booking ? trim((string) ($booking->payment_proof ?? '')) : '';

        return view('checkout_success', [
            'orderId' => $orderId,
            'bookingId' => $bookingId,
            'order' => $order,
            'booking' => $booking,
            'orderItems' => $orderItems,
            'bookingItems' => $bookingItems,
            'orderSubtotal' => $orderSubtotal,
            'orderTotal' => $orderTotal,
            'shippingFee' => $shippingFee,
            'shippingLabel' => $shippingLabel,
            'bookingTotal' => $bookingTotal,
            'grandTotal' => $grandTotal,
            'orderPaymentMethodLabel' => $orderPaymentMethodLabel,
            'bookingPaymentMethodLabel' => $bookingPaymentMethodLabel,
            'orderPaymentProofPath' => $orderPaymentProofPath,
            'bookingPaymentProofPath' => $bookingPaymentProofPath,
        ]);
    }
}
