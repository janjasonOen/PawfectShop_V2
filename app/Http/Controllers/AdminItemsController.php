<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminItemsController extends Controller
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

        // ACTIVATE/DEACTIVATE (for status=active/inactive)
        $activateId = (int) $request->query('activate', 0);
        if ($activateId > 0) {
            DB::table('items')->where('id', $activateId)->update(['status' => 'active']);
            return redirect()->route('admin.items');
        }

        $deactivateId = (int) $request->query('deactivate', 0);
        if ($deactivateId > 0) {
            DB::table('items')->where('id', $deactivateId)->update(['status' => 'inactive']);
            return redirect()->route('admin.items');
        }

        // DELETE (mirrors legacy ?delete=ID)
        $deleteId = (int) $request->query('delete', 0);
        if ($deleteId > 0) {
            DB::table('items')->where('id', $deleteId)->delete();
            return redirect()->route('admin.items');
        }

        // CREATE
        if ($request->isMethod('post') && $request->has('add')) {
            $categoryId = (int) $request->input('category_id', 0);
            $name = trim((string) $request->input('name', ''));
            $type = (string) $request->input('type', '');
            $price = (float) $request->input('price', 0);
            $stock = $type === 'product' ? (int) $request->input('stock', 0) : null;
            $description = (string) $request->input('description', '');

            $imageName = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $original = (string) $request->file('image')->getClientOriginalName();
                $safeOriginal = preg_replace('/[^A-Za-z0-9._-]+/', '_', $original) ?: 'image';
                $imageName = time() . '_' . $safeOriginal;

                $dest = public_path('uploads/items');
                if (!is_dir($dest)) {
                    @mkdir($dest, 0777, true);
                }

                $request->file('image')->move($dest, $imageName);
            }

            DB::table('items')->insert([
                'category_id' => $categoryId,
                'name' => $name,
                'type' => $type,
                'price' => $price,
                'stock' => $stock,
                'description' => $description,
                'image' => $imageName,
            ]);

            return redirect()->route('admin.items');
        }

        // UPDATE (legacy supports POST update, even if UI was minimal)
        if ($request->isMethod('post') && $request->has('update')) {
            $id = (int) $request->input('id', 0);
            $categoryId = (int) $request->input('category_id', 0);
            $name = trim((string) $request->input('name', ''));
            $type = (string) $request->input('type', '');
            $price = (float) $request->input('price', 0);
            $stock = $type === 'product' ? (int) $request->input('stock', 0) : null;
            $description = (string) $request->input('description', '');
            $status = (string) $request->input('status', 'active');
            if (!in_array($status, ['active', 'inactive'], true)) {
                $status = 'active';
            }

            if ($id > 0) {
                DB::table('items')->where('id', $id)->update([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'type' => $type,
                    'price' => $price,
                    'stock' => $stock,
                    'description' => $description,
                    'status' => $status,
                ]);
            }

            return redirect()->route('admin.items');
        }

        $items = DB::table('items')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->select('items.*', 'categories.name as category_name')
            ->orderByDesc('items.id')
            ->get();

        $categories = DB::table('categories')->orderBy('name')->get();

        return view('admin.items', [
            'items' => $items,
            'categories' => $categories,
        ]);
    }
}
