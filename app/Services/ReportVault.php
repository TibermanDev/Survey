<?php

namespace App\Services;

use App\Models\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Brankas kunci laporan Pak Andy.
 *
 * Cara kerja singkat:
 * - Ada sepasang kunci: kunci PUBLIK (hanya bisa mengunci) dan kunci RAHASIA
 *   (satu-satunya yang bisa membuka jawaban).
 * - Semua jawaban karyawan dienkripsi dengan kunci publik.
 * - Setelah Pak Andy membuat kunci, kunci rahasia hanya tersimpan dalam keadaan
 *   terkunci oleh kunci Pak Andy dan oleh kode pemulihannya.
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

            $keypair = sodium_crypto_box_keypair();
            DB::table(self::TABLE)->insert([
                'public_key' => base64_encode(sodium_crypto_box_publickey($keypair)),
                'temp_secret' => Crypt::encryptString(base64_encode(sodium_crypto_box_secretkey($keypair))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            sodium_memzero($keypair);
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

            $keypair = sodium_crypto_box_keypair();
            $secret = sodium_crypto_box_secretkey($keypair);
            $publicKey = sodium_crypto_box_publickey($keypair);
            $recoveryCode = $this->generateRecoveryCode();

            if ($row) {
                $this->reencryptAll(base64_decode(Crypt::decryptString($row->temp_secret)), $publicKey);
                DB::table(self::TABLE)->delete();
            }

            DB::table(self::TABLE)->insert(array_merge(
                ['public_key' => base64_encode($publicKey), 'temp_secret' => null],
                $this->wrap('key', $secret, $key),
                $this->wrap('recovery', $secret, self::normalizeRecovery($recoveryCode)),
                ['created_at' => now(), 'updated_at' => now()],
            ));

            sodium_memzero($keypair);
            sodium_memzero($secret);

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

        sodium_memzero($secret);

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
        $publicKey = base64_decode(DB::table(self::TABLE)->sharedLock()->value('public_key') ?? '');

        if ($publicKey === '') {
            throw new RuntimeException('Kunci laporan belum ada.');
        }

        return base64_encode(sodium_crypto_box_seal(json_encode($payload), $publicKey));
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

    private function open(string $payload, string $secret): ?string
    {
        $keypair = sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $secret,
            sodium_crypto_box_publickey_from_secretkey($secret),
        );
        $plain = sodium_crypto_box_seal_open(base64_decode($payload), $keypair);

        return $plain === false ? null : $plain;
    }

    /** Memindahkan semua jawaban dari kunci sementara ke kunci publik baru. */
    private function reencryptAll(string $oldSecret, string $newPublicKey): void
    {
        Response::query()->lockForUpdate()->select(['id', 'payload'])->chunkById(200, function ($rows) use ($oldSecret, $newPublicKey) {
            foreach ($rows as $r) {
                $plain = $this->open($r->payload, $oldSecret);
                if ($plain === null) {
                    continue;
                }
                Response::whereKey($r->id)->update(['payload' => base64_encode(sodium_crypto_box_seal($plain, $newPublicKey))]);
                sodium_memzero($plain);
            }
        });

        sodium_memzero($oldSecret);
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
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $derived = $this->derive($password, $salt);
        $cipher = sodium_crypto_secretbox($secret, $nonce, $derived);
        sodium_memzero($derived);

        return [
            "{$prefix}_salt" => base64_encode($salt),
            "{$prefix}_nonce" => base64_encode($nonce),
            "{$prefix}_cipher" => base64_encode($cipher),
        ];
    }

    private function unwrap(object $row, string $prefix, string $password): ?string
    {
        $derived = $this->derive($password, base64_decode($row->{"{$prefix}_salt"}));
        $secret = sodium_crypto_secretbox_open(
            base64_decode($row->{"{$prefix}_cipher"}),
            base64_decode($row->{"{$prefix}_nonce"}),
            $derived,
        );
        sodium_memzero($derived);

        return $secret === false ? null : $secret;
    }

    private function derive(string $password, string $salt): string
    {
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $password,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_ALG_DEFAULT,
        );
    }
}
