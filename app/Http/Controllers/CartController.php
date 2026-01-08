<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
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
                ->select('id', 'name', 'type', 'price', 'image')
                ->whereIn('id', $itemIds)
                ->get();

            foreach ($rows as $row) {
                $itemsById[(int) $row->id] = $row;
            }

            // Remove invalid ids that no longer exist.
            foreach ($itemIds as $id) {
                if (!isset($itemsById[(int) $id])) {
                    unset($cart[(int) $id]);
                    unset($normalizedCart[(int) $id]);
                }
            }
            $request->session()->put('cart', $cart);
        }

        $slotIds = [];
        foreach ($normalizedCart as $entry) {
            $sid = (int) ($entry['slot_id'] ?? 0);
            if ($sid > 0) {
                $slotIds[$sid] = true;
            }
        }

        $slotsById = [];
        if (count($slotIds) > 0) {
            $rows = DB::table('service_slots')
                ->select('id', 'starts_at')
                ->whereIn('id', array_keys($slotIds))
                ->get();

            foreach ($rows as $row) {
                $slotsById[(int) $row->id] = (array) $row;
            }
        }

        return view('cart', [
            'normalizedCart' => $normalizedCart,
            'itemsById' => $itemsById,
            'slotsById' => $slotsById,
        ]);
    }

    public function action(Request $request)
    {
        $action = (string) ($request->input('action') ?? $request->query('action', ''));

        $cart = $request->session()->get('cart', []);
        if (!is_array($cart)) {
            $cart = [];
        }

        $redirectTo = fn () => redirect()->route('catalog', ['type' => 'product']);

        if ($action === 'add') {
            $itemId = (int) ($request->input('item_id') ?? $request->query('item_id', 0));
            if ($itemId <= 0) {
                return $redirectTo();
            }

            $item = Item::query()->select('id', 'type', 'status')->where('id', $itemId)->first();
            if (!$item || (($item->status ?? 'active') !== 'active')) {
                return $redirectTo();
            }

            $type = (string) ($item->type ?? 'product');
            if ($type === 'service') {
                $slotId = (int) $request->input('slot_id', 0);
                if ($slotId <= 0) {
                    return redirect()->route('item.show', ['id' => $itemId, 'error' => 'Pilih jadwal terlebih dahulu.']);
                }

                $ok = DB::table('service_slots')
                    ->where('id', $slotId)
                    ->where('service_item_id', $itemId)
                    ->where('is_active', 1)
                    ->where('starts_at', '>=', DB::raw('NOW()'))
                    ->exists();

                if (!$ok) {
                    return redirect()->route('item.show', ['id' => $itemId, 'error' => 'Slot jadwal tidak valid / sudah tidak tersedia.']);
                }

                $cart[$itemId] = ['qty' => 1, 'slot_id' => $slotId];
                $request->session()->put('cart', $cart);
                return redirect()->route('cart');
            }

            $qty = (int) ($request->input('qty') ?? $request->query('qty', 1));
            if ($qty < 1) {
                $qty = 1;
            }

            $existingQty = 0;
            if (array_key_exists($itemId, $cart)) {
                $existing = $cart[$itemId];
                $existingQty = is_array($existing) ? (int) ($existing['qty'] ?? 0) : (int) $existing;
                if ($existingQty < 0) {
                    $existingQty = 0;
                }
            }

            $cart[$itemId] = $existingQty + $qty;
            $request->session()->put('cart', $cart);
            return redirect()->route('cart');
        }

        if ($action === 'update') {
            $qtyById = $request->input('qty', []);
            if (is_array($qtyById)) {
                foreach ($qtyById as $id => $qty) {
                    $itemId = (int) $id;
                    $qty = (int) $qty;
                    if ($itemId <= 0) {
                        continue;
                    }

                    $existing = $cart[$itemId] ?? null;

                    if ($qty <= 0) {
                        unset($cart[$itemId]);
                        continue;
                    }

                    if (is_array($existing)) {
                        $slotId = (int) ($existing['slot_id'] ?? 0);
                        $cart[$itemId] = ['qty' => 1, 'slot_id' => $slotId];
                    } else {
                        $cart[$itemId] = $qty;
                    }
                }
            }

            $request->session()->put('cart', $cart);
            return redirect()->route('cart');
        }

        if ($action === 'remove') {
            $itemId = (int) ($request->input('item_id') ?? $request->query('item_id', 0));
            if ($itemId > 0) {
                unset($cart[$itemId]);
            }
            $request->session()->put('cart', $cart);
            return redirect()->route('cart');
        }

        if ($action === 'clear') {
            $request->session()->put('cart', []);
            return redirect()->route('cart');
        }

        return $redirectTo();
    }
}
