<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\File;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $featuredProducts = Item::query()
            ->select('items.*', 'categories.name as category')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->where('items.type', 'product')
            ->where('items.status', 'active')
            ->orderByDesc('items.id')
            ->limit(8)
            ->get();

        $featuredServices = Item::query()
            ->select('items.*', 'categories.name as category')
            ->join('categories', 'items.category_id', '=', 'categories.id')
            ->where('items.type', 'service')
            ->where('items.status', 'active')
            ->orderByDesc('items.id')
            ->limit(8)
            ->get();

        $heroImage = '';
        foreach ([$featuredProducts, $featuredServices] as $list) {
            foreach ($list as $it) {
                $img = (string)($it->image ?? '');
                if ($img !== '') {
                    $heroImage = $img;
                    break 2;
                }
            }
        }

        $bannerImages = [];
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $bannerFolders = [
            'uploads/banners',
            'assets/images/banners',
        ];

        foreach ($bannerFolders as $folder) {
            $bannerDir = public_path($folder);
            if (!File::exists($bannerDir)) {
                continue;
            }

            foreach (File::files($bannerDir) as $f) {
                $ext = strtolower((string) $f->getExtension());
                if (!in_array($ext, $allowedExt, true)) {
                    continue;
                }

                $bannerImages[] = trim($folder, '/') . '/' . $f->getFilename();
            }

            if (count($bannerImages) > 0) {
                break;
            }
        }

        // Fallback: if no banners folder images are provided, use a single item image (if any)
        if (count($bannerImages) === 0 && $heroImage !== '') {
            $bannerImages[] = 'uploads/items/' . $heroImage;
        }

        return view('home', [
            'featuredProducts' => $featuredProducts,
            'featuredServices' => $featuredServices,
            'heroImage' => $heroImage,
            'bannerImages' => $bannerImages,
        ]);
    }
}
