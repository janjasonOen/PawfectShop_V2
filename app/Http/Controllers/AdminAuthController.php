<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $user = $request->session()->get('user');
        if (is_array($user) && (($user['role'] ?? '') === 'admin')) {
            return redirect()->route('admin.dashboard');
        }

        $error = (string) $request->query('error', '');
        return view('admin.login', ['error' => $error]);
    }

    public function login(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            return redirect()->route('admin.login', ['error' => 'Email dan password wajib diisi.']);
        }

        $admin = DB::table('users')
            ->where('email', $email)
            ->where('role', 'admin')
            ->first();

        if ($admin && isset($admin->password) && password_verify($password, (string) $admin->password)) {
            $request->session()->put('user', [
                'id' => (int) $admin->id,
                'name' => (string) ($admin->name ?? ''),
                'role' => 'admin',
            ]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.login', ['error' => 'Email atau password admin salah']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return redirect()->route('admin.login');
    }
}
