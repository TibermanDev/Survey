<?php

namespace App\Services;

use App\Models\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Brankas kunci laporan Pak Andy (memakai OpenSSL, tersedia di hampir semua hosting).
 *
 * Cara kerja singkat:
 * - Ada sepasang kunci RSA: kunci PUBLIK (hanya bisa mengunci) dan kunci RAHASIA
 *   (satu-satunya yang bisa membuka jawaban).
 * - Setiap jawaban dienkripsi dengan kunci AES acak, lalu kunci AES itu dikunci
 *   dengan kunci publik RSA (RSA-OAEP + AES-256-GCM).
 * - Setelah Pak Andy membuat kunci, kunci rahasia hanya tersimpan dalam keadaan
 *   terkunci oleh kunci Pak Andy dan oleh kode pemulihannya (PBKDF2 + AES-256-GCM).
 *
 * Kunci sementara:
 * - Jika panitia membuka survei sebelum Pak Andy membuat kunci, sistem membuat
 *   pasangan kunci sementara (kunci rahasianya disimpan di server, terenkripsi
 *   dengan APP_KEY). Jawaban tetap dienkripsi.
 * - Saat Pak Andy membuat kunci, sistem membuat pasangan kunci BARU, mengenkripsi
 *   ulang semua jawaban ke kunci baru itu, lalu menghapus kunci sementara.
 */
class ReportVault
{
    private const TABLE = 'report_vault';

    private const RECOVERY_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const KDF = 'pbkdf2-sha256';

    private const KDF_ITERATIONS = 310000;

    /** Sudah ada kunci (sementara atau milik Pak Andy), jadi jawaban bisa dienkripsi. */
    public function hasPublicKey(): bool
    {
        return DB::table(self::TABLE)->exists();
    }

    /** Pak Andy sudah membuat kuncinya sendiri. */
    public function hasOwnerKey(): bool
    {
        return DB::table(self::TABLE)->whereNotNull('key_cipher')->exists();
    }

    /** Masih memakai kunci sementara. */
    public function isTemporary(): bool
    {
        return DB::table(self::TABLE)->whereNull('key_cipher')->exists();
    }

    public function createdAt(): ?string
    {
        return DB::table(self::TABLE)->whereNotNull('key_cipher')->value('created_at');
    }

    /** Membuat kunci sementara jika belum ada kunci apa pun. */
    public function ensureTemporary(): void
    {
        DB::transaction(function () {
            if (DB::table(self::TABLE)->lockForUpdate()->exists()) {
                return;
            }

            [$publicKey, $secret] = $this->newKeypair();
            DB::table(self::TABLE)->insert([
                'public_key' => $publicKey,
                'temp_secret' => Crypt::encryptString($secret),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Pak Andy membuat kunci. Jika ada kunci sementara, semua jawaban dipindah
     * ke kunci baru dan kunci sementara dihapus. Mengembalikan kode pemulihan.
     */
    public function setup(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $row = DB::table(self::TABLE)->lockForUpdate()->first();

            if ($row && $row->key_cipher !== null) {
                throw new RuntimeException('Kunci laporan sudah pernah dibuat.');
            }

            [$publicKey, $secret] = $this->newKeypair();
            $recoveryCode = $this->generateRecoveryCode();

            if ($row) {
                $this->reencryptAll(Crypt::decryptString($row->temp_secret), $publicKey);
                DB::table(self::TABLE)->delete();
            }

            DB::table(self::TABLE)->insert(array_merge(
                ['public_key' => $publicKey, 'temp_secret' => null],
                $this->wrap('key', $secret, $key),
                $this->wrap('recovery', $secret, self::normalizeRecovery($recoveryCode)),
                ['created_at' => now(), 'updated_at' => now()],
            ));

            return $recoveryCode;
        });
    }

    /** Membuka kunci rahasia dengan kunci Pak Andy. Null jika kunci salah. */
    public function unlock(string $key): ?string
    {
        $row = DB::table(self::TABLE)->whereNotNull('key_cipher')->first();

        return $row ? $this->unwrap($row, 'key', $key) : null;
    }

    /**
     * Lupa kunci: buka dengan kode pemulihan, pasang kunci baru, dan buat kode
     * pemulihan baru. Mengembalikan kode pemulihan baru, atau null jika kode salah.
     */
    public function recover(string $recoveryCode, string $newKey): ?string
    {
        $row = DB::table(self::TABLE)->whereNotNull('key_cipher')->first();
        $secret = $row ? $this->unwrap($row, 'recovery', self::normalizeRecovery($recoveryCode)) : null;

        if ($secret === null) {
            return null;
        }

        $newRecovery = $this->generateRecoveryCode();

        DB::table(self::TABLE)->where('id', $row->id)->update(array_merge(
            $this->wrap('key', $secret, $newKey),
            $this->wrap('recovery', $secret, self::normalizeRecovery($newRecovery)),
            ['updated_at' => now()],
        ));

        return $newRecovery;
    }

    /** Ganti kunci saat sudah masuk (kunci rahasia sudah terbuka). */
    public function changeKey(string $secret, string $newKey): void
    {
        DB::table(self::TABLE)->whereNotNull('key_cipher')->update(array_merge(
            $this->wrap('key', $secret, $newKey),
            ['updated_at' => now()],
        ));
    }

    /**
     * Mengenkripsi jawaban dengan kunci publik (tidak butuh kunci Pak Andy).
     * Dipanggil di dalam transaksi supaya tidak bentrok dengan pemindahan kunci.
     */
    public function encrypt(array $payload): string
    {
        $publicKey = DB::table(self::TABLE)->sharedLock()->value('public_key');

        if (! $publicKey) {
            throw new RuntimeException('Kunci laporan belum ada.');
        }

        return $this->seal(json_encode($payload), $publicKey);
    }

    public function decrypt(string $payload, string $secret): ?array
    {
        $plain = $this->open($payload, $secret);

        return $plain === null ? null : json_decode($plain, true);
    }

    public static function normalizeRecovery(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    /** @return array{0: string, 1: string} [kunci publik PEM, kunci rahasia PEM] */
    private function newKeypair(): array
    {
        $config = ['config' => resource_path('openssl.cnf')];
        $key = openssl_pkey_new($config + ['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($key === false || ! openssl_pkey_export($key, $secret, null, $config)) {
            throw new RuntimeException('Gagal membuat pasangan kunci: '.openssl_error_string());
        }

        return [openssl_pkey_get_details($key)['key'], $secret];
    }

    /** RSA-OAEP membungkus kunci AES acak; AES-256-GCM mengenkripsi isinya. */
    private function seal(string $plain, string $publicKey): string
    {
        $aesKey = random_bytes(32);

        if (! openssl_public_encrypt($aesKey, $wrapped, $publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new RuntimeException('Enkripsi gagal.');
        }

        return base64_encode(pack('n', strlen($wrapped)).$wrapped.Aes::encrypt($plain, $aesKey));
    }

    private function open(string $payload, string $secret): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 2) {
            return null;
        }

        $len = unpack('n', substr($raw, 0, 2))[1];
        if (! @openssl_private_decrypt(substr($raw, 2, $len), $aesKey, $secret, OPENSSL_PKCS1_OAEP_PADDING)) {
            return null;
        }

        return Aes::decrypt(substr($raw, 2 + $len), $aesKey);
    }

    /** Memindahkan semua jawaban dari kunci sementara ke kunci publik baru. */
    private function reencryptAll(string $oldSecret, string $newPublicKey): void
    {
        Response::query()->lockForUpdate()->select(['id', 'payload'])->chunkById(200, function ($rows) use ($oldSecret, $newPublicKey) {
            foreach ($rows as $r) {
                $plain = $this->open($r->payload, $oldSecret);
                if ($plain !== null) {
                    Response::whereKey($r->id)->update(['payload' => $this->seal($plain, $newPublicKey)]);
                }
            }
        });
    }

    private function generateRecoveryCode(): string
    {
        $groups = [];
        for ($g = 0; $g < 4; $g++) {
            $part = '';
            for ($i = 0; $i < 4; $i++) {
                $part .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
            }
            $groups[] = $part;
        }

        return implode('-', $groups);
    }

    /** Mengunci $secret memakai turunan dari $password. */
    private function wrap(string $prefix, string $secret, string $password): array
    {
        $salt = random_bytes(16);

        return [
            "{$prefix}_salt" => base64_encode($salt),
            "{$prefix}_nonce" => self::KDF.':'.self::KDF_ITERATIONS,
            "{$prefix}_cipher" => base64_encode(Aes::encrypt($secret, $this->derive($password, $salt))),
        ];
    }

    private function unwrap(object $row, string $prefix, string $password): ?string
    {
        $derived = $this->derive($password, base64_decode($row->{"{$prefix}_salt"}));

        return Aes::decrypt(base64_decode($row->{"{$prefix}_cipher"}), $derived);
    }

    private function derive(string $password, string $salt): string
    {
        return hash_pbkdf2('sha256', $password, $salt, self::KDF_ITERATIONS, 32, true);
    }
}
