<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCategoriesController extends Controller
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

        // DELETE (legacy ?delete=ID)
        $deleteId = (int) $request->query('delete', 0);
        if ($deleteId > 0) {
            DB::table('categories')->where('id', $deleteId)->delete();
            return redirect()->route('admin.categories');
        }

        // CREATE
        if ($request->isMethod('post') && $request->has('add')) {
            $name = trim((string) $request->input('name', ''));
            if ($name !== '') {
                DB::table('categories')->insert(['name' => $name]);
            }
            return redirect()->route('admin.categories');
        }

        // UPDATE
        if ($request->isMethod('post') && $request->has('update')) {
            $id = (int) $request->input('id', 0);
            $name = trim((string) $request->input('name', ''));
            if ($id > 0 && $name !== '') {
                DB::table('categories')->where('id', $id)->update(['name' => $name]);
            }
            return redirect()->route('admin.categories');
        }

        $categories = DB::table('categories')->orderByDesc('id')->get();

        return view('admin.categories', [
            'categories' => $categories,
        ]);
    }
}
