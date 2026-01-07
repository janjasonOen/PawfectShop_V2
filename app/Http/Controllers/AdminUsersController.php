<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersController extends Controller
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

        $sessionUser = $request->session()->get('user');
        $currentId = (int) ($sessionUser['id'] ?? 0);

        // DELETE (legacy ?delete=ID) with self-protection
        $deleteId = (int) $request->query('delete', 0);
        if ($deleteId > 0) {
            if ($deleteId !== $currentId) {
                DB::table('users')->where('id', $deleteId)->delete();
            }
            return redirect()->route('admin.users');
        }

        // CREATE
        if ($request->isMethod('post') && $request->has('add')) {
            $name = trim((string) $request->input('name', ''));
            $email = trim((string) $request->input('email', ''));
            $role = (string) $request->input('role', 'customer');
            $pass = (string) $request->input('password', '');

            $exists = DB::table('users')->where('email', $email)->exists();
            if (!$exists) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                DB::table('users')->insert([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hash,
                    'role' => $role,
                ]);
            }

            return redirect()->route('admin.users');
        }

        // UPDATE
        if ($request->isMethod('post') && $request->has('update')) {
            $id = (int) $request->input('id', 0);
            $name = trim((string) $request->input('name', ''));
            $role = (string) $request->input('role', 'customer');
            $pass = (string) $request->input('password', '');

            if ($id > 0) {
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    DB::table('users')->where('id', $id)->update([
                        'name' => $name,
                        'role' => $role,
                        'password' => $hash,
                    ]);
                } else {
                    DB::table('users')->where('id', $id)->update([
                        'name' => $name,
                        'role' => $role,
                    ]);
                }
            }

            return redirect()->route('admin.users');
        }

        $users = DB::table('users')
            ->select('id', 'name', 'email', 'role')
            ->orderByDesc('id')
            ->get();

        return view('admin.users', [
            'users' => $users,
            'currentId' => $currentId,
        ]);
    }
}
