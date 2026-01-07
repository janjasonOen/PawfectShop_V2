<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $type = (string) $request->query('type', 'product');
        if (!in_array($type, ['product', 'service'], true)) {
            $type = 'product';
        }

        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category_id', 0);
        $sort = (string) $request->query('sort', 'newest');

        $allowedSort = [
            'newest' => ['items.id', 'desc'],
            'price_asc' => ['items.price', 'asc'],
            'price_desc' => ['items.price', 'desc'],
            'name_asc' => ['items.name', 'asc'],
        ];
        $order = $allowedSort[$sort] ?? $allowedSort['newest'];

        $categories = Category::query()
            ->select('categories.id', 'categories.name')
            ->join('items', 'items.category_id', '=', 'categories.id')
            ->where('items.type', $type)
            ->distinct()
            ->orderBy('categories.name', 'asc')
            ->get();

        $itemsQuery = Item::query()
            ->select('items.*', 'categories.name as category')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->where('items.type', $type);

        if ($q !== '') {
            $itemsQuery->where(function ($sub) use ($q) {
                $sub->where('items.name', 'like', '%' . $q . '%')
                    ->orWhere('items.description', 'like', '%' . $q . '%');
            });
        }

        if ($categoryId > 0) {
            $itemsQuery->where('items.category_id', $categoryId);
        }

        $items = $itemsQuery
            ->orderBy($order[0], $order[1])
            ->get();

        return view('catalog', [
            'type' => $type,
            'q' => $q,
            'categoryId' => $categoryId,
            'sort' => $sort,
            'categories' => $categories,
            'items' => $items,
        ]);
    }
}
