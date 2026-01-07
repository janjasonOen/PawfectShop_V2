<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->session()->get('user');
        if (!is_array($user) || (($user['role'] ?? '') !== 'customer')) {
            return redirect()->route('home');
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId <= 0) {
            return redirect()->route('home');
        }

        if (!Schema::hasTable('user_addresses')) {
            return redirect()->route('home');
        }

        $error = (string) $request->query('error', '');

        // Actions: set default / deactivate
        if ($request->query('set_default')) {
            $addressId = (int) $request->query('set_default');
            if ($addressId > 0) {
                try {
                    DB::transaction(function () use ($userId, $addressId) {
                        DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => 0]);
                        DB::table('user_addresses')
                            ->where('id', $addressId)
                            ->where('user_id', $userId)
                            ->where('is_active', 1)
                            ->update(['is_default' => 1]);
                    });
                } catch (\Throwable $e) {
                    return redirect()->route('customer.addresses', ['error' => 'Gagal set alamat utama: ' . $e->getMessage()]);
                }
            }
            return redirect()->route('customer.addresses');
        }

        if ($request->query('deactivate')) {
            $addressId = (int) $request->query('deactivate');
            if ($addressId > 0) {
                try {
                    DB::transaction(function () use ($userId, $addressId) {
                        DB::table('user_addresses')
                            ->where('id', $addressId)
                            ->where('user_id', $userId)
                            ->update(['is_active' => 0, 'is_default' => 0]);

                        $hasDefault = (int) DB::table('user_addresses')
                            ->where('user_id', $userId)
                            ->where('is_active', 1)
                            ->where('is_default', 1)
                            ->count();

                        if ($hasDefault === 0) {
                            $newDefaultId = (int) (DB::table('user_addresses')
                                ->where('user_id', $userId)
                                ->where('is_active', 1)
                                ->orderByDesc('id')
                                ->value('id') ?? 0);

                            if ($newDefaultId > 0) {
                                DB::table('user_addresses')
                                    ->where('id', $newDefaultId)
                                    ->where('user_id', $userId)
                                    ->update(['is_default' => 1]);
                            }
                        }
                    });
                } catch (\Throwable $e) {
                    return redirect()->route('customer.addresses', ['error' => 'Gagal menghapus alamat: ' . $e->getMessage()]);
                }
            }
            return redirect()->route('customer.addresses');
        }

        // Add new address
        if ($request->isMethod('post') && $request->has('add_address')) {
            $label = trim((string) $request->input('label', ''));
            $recipientName = trim((string) $request->input('recipient_name', ''));
            $phone = trim((string) $request->input('phone', ''));
            $address = trim((string) $request->input('address', ''));
            $makeDefault = $request->has('is_default') ? 1 : 0;

            if ($address === '') {
                return redirect()->route('customer.addresses', ['error' => 'Alamat wajib diisi.']);
            }

            try {
                DB::transaction(function () use ($userId, $label, $recipientName, $phone, $address, $makeDefault) {
                    if ($makeDefault) {
                        DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => 0]);
                    }

                    DB::table('user_addresses')->insert([
                        'user_id' => $userId,
                        'label' => ($label === '' ? null : $label),
                        'recipient_name' => ($recipientName === '' ? null : $recipientName),
                        'phone' => ($phone === '' ? null : $phone),
                        'address' => $address,
                        'is_default' => $makeDefault,
                        'is_active' => 1,
                    ]);

                    if (!$makeDefault) {
                        $count = (int) DB::table('user_addresses')
                            ->where('user_id', $userId)
                            ->where('is_active', 1)
                            ->count();

                        if ($count === 1) {
                            DB::table('user_addresses')->where('user_id', $userId)->update(['is_default' => 1]);
                        }
                    }
                });

                return redirect()->route('customer.addresses');
            } catch (\Throwable $e) {
                return redirect()->route('customer.addresses', ['error' => 'Gagal menyimpan alamat: ' . $e->getMessage()]);
            }
        }

        $addresses = [];
        try {
            $addresses = DB::table('user_addresses')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        } catch (\Throwable $e) {
            $addresses = [];
        }

        return view('customer.addresses', [
            'addresses' => $addresses,
            'error' => $error,
        ]);
    }
}
