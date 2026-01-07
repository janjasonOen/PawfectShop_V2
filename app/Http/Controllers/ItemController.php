<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function show(Request $request, int $id)
    {
        if ($id <= 0) {
            return redirect()->route('catalog', ['type' => 'product']);
        }

        $item = Item::query()
            ->select('items.*', 'categories.name as category')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->where('items.id', $id)
            ->first();

        if (!$item) {
            return response()->view('item', [
                'item' => null,
                'isProduct' => false,
                'stock' => null,
                'outOfStock' => false,
                'serviceSlotsByDate' => [],
            ], 404);
        }

        $isProduct = ($item->type ?? '') === 'product';
        $stock = $isProduct ? (int) ($item->stock ?? 0) : null;
        $outOfStock = $isProduct && $stock <= 0;

        $serviceSlotsByDate = [];
        if (!$isProduct) {
                        // Use app/PHP time instead of MySQL NOW() to avoid timezone mismatches
                        // between PHP (app.timezone) and the database server.
                        $now = now()->subMinute()->toDateTimeString();

            $sql = <<<'SQL'
SELECT s.id, s.starts_at, s.capacity,
       ((SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE oi.service_slot_id = s.id
           AND o.status IN ('pending','processed','completed'))
        +
        (SELECT COALESCE(SUM(bi.quantity),0) FROM booking_items bi
         JOIN bookings b ON b.id = bi.booking_id
         WHERE bi.service_slot_id = s.id
           AND b.status IN ('pending','processed','completed'))
       ) AS booked
FROM service_slots s
WHERE s.service_item_id = ? AND s.is_active = 1 AND s.starts_at >= ?
ORDER BY s.starts_at ASC
SQL;

            try {
                                $slots = DB::select($sql, [$item->id, $now]);
            } catch (\Throwable $e) {
                // Backward-compatible fallback if booking tables aren't present.
                $sql2 = <<<'SQL'
SELECT s.id, s.starts_at, s.capacity,
       (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE oi.service_slot_id = s.id
           AND o.status IN ('pending','processed','completed')) AS booked
FROM service_slots s
WHERE s.service_item_id = ? AND s.is_active = 1 AND s.starts_at >= ?
ORDER BY s.starts_at ASC
SQL;
                                $slots = DB::select($sql2, [$item->id, $now]);
            }

            foreach ($slots as $s) {
                $startsAt = (string) ($s->starts_at ?? '');
                if ($startsAt === '') {
                    continue;
                }
                $dateKey = substr($startsAt, 0, 10);
                if (!isset($serviceSlotsByDate[$dateKey])) {
                    $serviceSlotsByDate[$dateKey] = [];
                }
                $serviceSlotsByDate[$dateKey][] = [
                    'id' => (int) $s->id,
                    'starts_at' => $startsAt,
                    'capacity' => (int) ($s->capacity ?? 1),
                    'booked' => (int) ($s->booked ?? 0),
                ];
            }
        }

        return view('item', [
            'item' => $item,
            'isProduct' => $isProduct,
            'stock' => $stock,
            'outOfStock' => $outOfStock,
            'serviceSlotsByDate' => $serviceSlotsByDate,
        ]);
    }
}
