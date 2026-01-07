<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSchedulesController extends Controller
{
    private function requireAdmin(Request $request): bool
    {
        $user = $request->session()->get('user');
        return is_array($user) && (($user['role'] ?? '') === 'admin');
    }

    private function redirectSelf(string $error = '')
    {
        if ($error === '') {
            return redirect()->route('admin.schedules');
        }
        return redirect()->route('admin.schedules', ['error' => $error]);
    }

    public function index(Request $request)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        $services = DB::table('items')
            ->where('type', 'service')
            ->where('status', 'active')
            ->orderBy('name')
            ->select('id', 'name')
            ->get();

        // ACTIONS (legacy GET)
        $deactivateId = (int) $request->query('deactivate', 0);
        if ($deactivateId > 0) {
            DB::table('service_slots')->where('id', $deactivateId)->update(['is_active' => 0]);
            return redirect()->route('admin.schedules');
        }

        $activateId = (int) $request->query('activate', 0);
        if ($activateId > 0) {
            DB::table('service_slots')->where('id', $activateId)->update(['is_active' => 1]);
            return redirect()->route('admin.schedules');
        }

        // CREATE SINGLE SLOT
        if ($request->isMethod('post') && $request->has('add_slot')) {
            $serviceItemId = (int) $request->input('service_item_id', 0);
            $date = trim((string) $request->input('date', ''));
            $time = trim((string) $request->input('time', ''));
            $capacity = (int) $request->input('capacity', 1);
            if ($capacity <= 0) {
                $capacity = 1;
            }

            if ($serviceItemId <= 0) {
                return $this->redirectSelf('Pilih service terlebih dahulu.');
            }
            if ($date === '' || $time === '') {
                return $this->redirectSelf('Tanggal dan jam wajib diisi.');
            }

            $startsAt = $date . ' ' . $time . ':00';
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $startsAt);
            if (!$dt) {
                return $this->redirectSelf('Format tanggal/jam tidak valid.');
            }

            $startsAt = $dt->format('Y-m-d H:i:s');

            try {
                DB::table('service_slots')->insert([
                    'service_item_id' => $serviceItemId,
                    'starts_at' => $startsAt,
                    'capacity' => $capacity,
                    'is_active' => 1,
                ]);
            } catch (\Throwable $e) {
                return $this->redirectSelf('Gagal menambah slot: ' . $e->getMessage());
            }

            return redirect()->route('admin.schedules');
        }

        // BULK GENERATE
        if ($request->isMethod('post') && $request->has('generate_slots')) {
            $serviceItemId = (int) $request->input('service_item_id', 0);
            $startDate = trim((string) $request->input('start_date', ''));
            $endDate = trim((string) $request->input('end_date', ''));
            $startTime = trim((string) $request->input('start_time', ''));
            $endTime = trim((string) $request->input('end_time', ''));
            $intervalMin = (int) $request->input('interval_min', 60);
            $capacity = (int) $request->input('capacity', 1);
            $weekdaysOnly = (string) $request->input('weekdays_only', '') === '1';

            if ($serviceItemId <= 0) {
                return $this->redirectSelf('Pilih service terlebih dahulu.');
            }
            if ($startDate === '' || $endDate === '' || $startTime === '' || $endTime === '') {
                return $this->redirectSelf('Tanggal dan jam operasional wajib diisi.');
            }
            if ($intervalMin <= 0) {
                $intervalMin = 60;
            }
            if ($capacity <= 0) {
                $capacity = 1;
            }

            $rangeStart = DateTime::createFromFormat('Y-m-d', $startDate);
            $rangeEnd = DateTime::createFromFormat('Y-m-d', $endDate);
            if (!$rangeStart || !$rangeEnd) {
                return $this->redirectSelf('Format tanggal tidak valid.');
            }
            $rangeStart->setTime(0, 0, 0);
            $rangeEnd->setTime(0, 0, 0);
            if ($rangeEnd < $rangeStart) {
                return $this->redirectSelf('End date harus >= start date.');
            }

            $testStart = DateTime::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $startTime);
            $testEnd = DateTime::createFromFormat('Y-m-d H:i', '2000-01-01 ' . $endTime);
            if (!$testStart || !$testEnd) {
                return $this->redirectSelf('Format jam tidak valid.');
            }
            if ($testEnd <= $testStart) {
                return $this->redirectSelf('Jam tutup harus lebih besar dari jam buka.');
            }

            try {
                $day = clone $rangeStart;
                while ($day <= $rangeEnd) {
                    if ($weekdaysOnly) {
                        $dow = (int) $day->format('N');
                        if ($dow >= 6) {
                            $day->modify('+1 day');
                            continue;
                        }
                    }

                    $dayStr = $day->format('Y-m-d');
                    $slot = DateTime::createFromFormat('Y-m-d H:i', $dayStr . ' ' . $startTime);
                    $end = DateTime::createFromFormat('Y-m-d H:i', $dayStr . ' ' . $endTime);

                    if ($slot && $end) {
                        while ($slot < $end) {
                            $startsAt = $slot->format('Y-m-d H:i:s');
                            try {
                                DB::table('service_slots')->insert([
                                    'service_item_id' => $serviceItemId,
                                    'starts_at' => $startsAt,
                                    'capacity' => $capacity,
                                    'is_active' => 1,
                                ]);
                            } catch (QueryException $e) {
                                // Ignore duplicates (legacy behavior)
                                if ((string) ($e->getCode()) !== '23000') {
                                    throw $e;
                                }
                            }

                            $slot->modify('+' . $intervalMin . ' minutes');
                        }
                    }

                    $day->modify('+1 day');
                }
            } catch (\Throwable $e) {
                return $this->redirectSelf('Gagal generate slot: ' . $e->getMessage());
            }

            return redirect()->route('admin.schedules');
        }

        // READ slots + booked (legacy counts orders only)
        $slots = DB::table('service_slots as s')
            ->join('items as i', 'i.id', '=', 's.service_item_id')
            ->select('s.*', 'i.name as service_name')
            ->selectSub(function ($sub) {
                $sub->from('order_items as oi')
                    ->join('orders as o', 'o.id', '=', 'oi.order_id')
                    ->whereColumn('oi.service_slot_id', 's.id')
                    ->whereIn('o.status', ['pending', 'processed', 'completed'])
                    ->selectRaw('COALESCE(SUM(oi.quantity),0)');
            }, 'booked')
            ->orderByDesc('s.starts_at')
            ->get();

        return view('admin.schedules', [
            'services' => $services,
            'slots' => $slots,
            'error' => (string) $request->query('error', ''),
        ]);
    }
}
