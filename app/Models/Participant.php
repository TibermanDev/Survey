<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $guarded = [];

    // Tanpa huruf/angka yang mirip (0/O, 1/I/L) supaya mudah dibaca dan diketik.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public static function generateCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(?string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    /** Hari berikutnya yang harus diisi (1..6), atau null jika sudah selesai semua. */
    public function nextDay(): ?int
    {
        $done = $this->responses()->pluck('day')->all();
        for ($d = 1; $d <= 6; $d++) {
            if (! in_array($d, $done)) {
                return $d;
            }
        }

        return null;
    }
}
