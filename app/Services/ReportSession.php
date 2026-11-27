<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Menyimpan kunci rahasia laporan selama Pak Andy sedang membuka laporan.
 *
 * Kunci rahasia disimpan di sesi server dalam keadaan terkunci, dan kunci
 * pembukanya hanya ada di cookie browser Pak Andy. Jadi data sesi di server
 * saja tidak cukup untuk membaca jawaban.
 */
class ReportSession
{
    private const COOKIE = 'laporan_k';

    public function start(Request $request, string $secret): void
    {
        $k = random_bytes(32);

        $request->session()->regenerate();
        $request->session()->put('laporan', [
            'cipher' => base64_encode(Aes::encrypt($secret, $k)),
            'expires' => now()->addMinutes($this->minutes())->timestamp,
        ]);

        Cookie::queue(self::COOKIE, base64_encode($k), $this->minutes());
    }

    /** Kunci rahasia jika Pak Andy masih dalam sesi laporan, selain itu null. */
    public function secret(Request $request): ?string
    {
        $data = $request->session()->get('laporan');
        $k = base64_decode((string) $request->cookie(self::COOKIE));

        if (! $data || strlen($k) !== 32 || $data['expires'] < now()->timestamp) {
            return null;
        }

        $secret = Aes::decrypt(base64_decode($data['cipher'] ?? ''), $k);
        if ($secret === null) {
            return null;
        }

        // Perpanjang sesi selama Pak Andy masih aktif.
        $data['expires'] = now()->addMinutes($this->minutes())->timestamp;
        $request->session()->put('laporan', $data);
        Cookie::queue(self::COOKIE, base64_encode($k), $this->minutes());

        return $secret;
    }

    public function end(Request $request): void
    {
        $request->session()->forget('laporan');
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    private function minutes(): int
    {
        return (int) config('survei.report_session_minutes', 30);
    }
}
