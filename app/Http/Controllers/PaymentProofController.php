<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentProofController extends Controller
{
    public function upload(Request $request)
    {
        $user = $request->session()->get('user');
        if (!is_array($user) || (($user['role'] ?? '') !== 'customer')) {
            return redirect()->route('checkout', ['error' => 'Silakan login sebagai customer.']);
        }

        $userId = (int) ($user['id'] ?? 0);
        $type = strtolower(trim((string) $request->input('type', 'order')));
        if (!in_array($type, ['order', 'booking'], true)) {
            $type = 'order';
        }

        $id = (int) $request->input($type === 'booking' ? 'booking_id' : 'order_id', 0);
        if ($userId <= 0 || $id <= 0) {
            return redirect()->route('catalog', ['type' => 'product']);
        }

        $status = '';
        try {
            if ($type === 'booking') {
                if (!Schema::hasTable('bookings')) {
                    return redirect()->route('checkout.success', ['booking_id' => $id, 'error' => 'Booking tidak ditemukan.']);
                }
                $row = DB::table('bookings')->select('id', 'user_id', 'status')->where('id', $id)->first();
                if (!$row || (int) ($row->user_id ?? 0) !== $userId) {
                    return redirect()->route('checkout.success', ['booking_id' => $id, 'error' => 'Booking tidak ditemukan.']);
                }
                $status = (string) ($row->status ?? '');
            } else {
                if (!Schema::hasTable('orders')) {
                    return redirect()->route('checkout.success', ['order_id' => $id, 'error' => 'Order tidak ditemukan.']);
                }
                $row = DB::table('orders')->select('id', 'user_id', 'status')->where('id', $id)->first();
                if (!$row || (int) ($row->user_id ?? 0) !== $userId) {
                    return redirect()->route('checkout.success', ['order_id' => $id, 'error' => 'Order tidak ditemukan.']);
                }
                $status = (string) ($row->status ?? '');
            }
        } catch (\Throwable $e) {
            return redirect()->route('checkout.success', [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Gagal memuat data.']);
        }

        $normalizedStatus = strtolower(trim($status));
        if ($normalizedStatus !== '' && $normalizedStatus !== 'pending') {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Status sudah diproses. Upload bukti pembayaran tidak tersedia.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $file = $request->file('payment_proof');
        if (!$file) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'File bukti pembayaran wajib diupload.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        if (!$file->isValid()) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Upload gagal.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $maxBytes = 5 * 1024 * 1024;
        if ($file->getSize() <= 0 || $file->getSize() > $maxBytes) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Ukuran file maksimal 5MB.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array($ext, $allowedExt, true)) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Format file harus JPG/PNG/PDF.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $mime = (string) ($file->getMimeType() ?? '');
        $allowedMime = ['image/jpeg', 'image/png', 'application/pdf'];
        if ($mime !== '' && !in_array($mime, $allowedMime, true)) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'Tipe file tidak didukung.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $targetDir = public_path('uploads/payment_proofs');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $filename = $type . '_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($targetDir, $filename);

        $relativePath = 'uploads/payment_proofs/' . $filename;

        try {
            if ($type === 'booking') {
                DB::table('bookings')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->update([
                        'payment_proof' => $relativePath,
                        'payment_proof_uploaded_at' => DB::raw('NOW()'),
                    ]);
            } else {
                DB::table('orders')
                    ->where('id', $id)
                    ->where('user_id', $userId)
                    ->where('status', 'pending')
                    ->update([
                        'payment_proof' => $relativePath,
                        'payment_proof_uploaded_at' => DB::raw('NOW()'),
                    ]);
            }
        } catch (\Throwable $e) {
            $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'error' => 'File tersimpan, tapi gagal menyimpan ke database.'];
            return $this->safeRedirectBack($request, $type, $id, $qs);
        }

        $qs = [$type === 'booking' ? 'booking_id' : 'order_id' => $id, 'uploaded' => 1];
        return $this->safeRedirectBack($request, $type, $id, $qs);
    }

    private function safeRedirectBack(Request $request, string $type, int $id, array $qs)
    {
        $referer = (string) $request->headers->get('referer', '');
        $base = rtrim((string) url('/'), '/');
        if ($referer !== '' && $base !== '' && str_starts_with($referer, $base)) {
            $sep = str_contains($referer, '?') ? '&' : '?';
            return redirect()->to($referer . $sep . http_build_query($qs));
        }

        // Fallbacks
        if ($type === 'booking') {
            return redirect()->route('customer.booking_detail', ['id' => $id] + $qs);
        }
        return redirect()->route('customer.order_detail', ['id' => $id] + $qs);
    }
}
