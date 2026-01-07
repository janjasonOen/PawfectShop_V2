<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    private function requireAdmin(Request $request): bool
    {
        $user = $request->session()->get('user');
        return is_array($user) && (($user['role'] ?? '') === 'admin');
    }

    public function index(Request $request)
    {
        if (!$this->requireAdmin($request)) {
            return redirect()->route('admin.login');
        }

        $totalKategori = (int) DB::table('categories')->count();
        $totalProduk = (int) DB::table('items')->where('type', 'product')->count();
        $totalJasa = (int) DB::table('items')->where('type', 'service')->count();
        $totalUser = (int) DB::table('users')->count();
        $totalOrder = (int) DB::table('orders')->count();

        $user = $request->session()->get('user');

        return view('admin.dashboard', [
            'totalKategori' => $totalKategori,
            'totalProduk' => $totalProduk,
            'totalJasa' => $totalJasa,
            'totalUser' => $totalUser,
            'totalOrder' => $totalOrder,
            'user' => $user,
        ]);
    }
}
