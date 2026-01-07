<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PageController extends Controller
{
    public function about(Request $request)
    {
        $productCount = Item::query()
            ->where('type', 'product')
            ->where('status', 'active')
            ->count();

        $serviceCount = Item::query()
            ->where('type', 'service')
            ->where('status', 'active')
            ->count();

        $aboutImage = $this->pickPublicImage([
            'uploads/about',
            'uploads/banners',
            'assets/images/about',
            'assets/images/banners',
        ]);

        return view('about', [
            'aboutImage' => $aboutImage,
            'productCount' => $productCount,
            'serviceCount' => $serviceCount,
        ]);
    }

    public function contact(Request $request)
    {
        $contactImage = $this->pickPublicImage([
            'uploads/contact',
            'uploads/banners',
            'assets/images/contact',
            'assets/images/banners',
        ]);

        return view('contact', [
            'contactImage' => $contactImage,
        ]);
    }

    private function pickPublicImage(array $folders): string
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        foreach ($folders as $folder) {
            $dir = public_path(trim($folder, '/'));
            if (!File::exists($dir)) {
                continue;
            }

            $files = File::files($dir);
            foreach ($files as $f) {
                $ext = strtolower((string) $f->getExtension());
                if (!in_array($ext, $allowedExt, true)) {
                    continue;
                }

                return trim($folder, '/') . '/' . $f->getFilename();
            }
        }

        return '';
    }
}
