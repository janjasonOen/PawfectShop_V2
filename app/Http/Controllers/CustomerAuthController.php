<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        if ($email === '') {
            return response()->json(['ok' => false, 'error' => 'Invalid request'], 400);
        }

        $exists = User::query()
            ->whereRaw('LOWER(email) = LOWER(?)', [$email])
            ->exists();

        return response()->json(['ok' => true, 'exists' => $exists]);
    }

    public function customerAuth(Request $request)
    {
        $email = trim((string) $request->input('email', ''));
        $pass = (string) $request->input('password', '');
        $mode = trim((string) $request->input('mode', ''));

        if ($email === '' || ($mode !== 'login' && $mode !== 'register')) {
            return response()->json(['ok' => false, 'error' => 'Invalid request'], 400);
        }

        if ($mode === 'login') {
            if (trim($pass) === '') {
                return response()->json(['ok' => false, 'error' => 'Password is required'], 400);
            }

            $user = User::query()
                ->whereRaw('LOWER(email) = LOWER(?)', [$email])
                ->where('role', 'customer')
                ->first();

            if ($user && Hash::check($pass, (string) $user->password)) {
                $request->session()->put('user', [
                    'id' => (int) $user->id,
                    'name' => (string) $user->name,
                    'email' => (string) $user->email,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'role' => 'customer',
                ]);

                return response()->json(['ok' => true, 'mode' => 'login']);
            }

            return response()->json(['ok' => false, 'error' => 'Email / password salah'], 401);
        }

        // REGISTER
        $name = trim((string) $request->input('name', ''));
        if ($name === '' || trim($pass) === '') {
            return response()->json(['ok' => false, 'error' => 'Nama dan password wajib diisi'], 400);
        }

        $existing = User::query()
            ->whereRaw('LOWER(email) = LOWER(?)', [$email])
            ->first();

        if ($existing) {
            return response()->json(['ok' => false, 'error' => 'Email sudah terdaftar'], 409);
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($pass);
        $user->role = 'customer';
        $user->save();

        $request->session()->put('user', [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => null,
            'address' => null,
            'role' => 'customer',
        ]);

        return response()->json(['ok' => true, 'mode' => 'register']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');
        return redirect()->route('home');
    }
}
