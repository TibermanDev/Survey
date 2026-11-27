<?php

namespace App\Services;

/**
 * Enkripsi simetris AES-256-GCM (OpenSSL). Hasil: iv(12) || tag(16) || ciphertext.
 */
class Aes
{
    private const CIPHER = 'aes-256-gcm';

    public static function encrypt(string $plain, string $key): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        if ($cipher === false) {
            throw new \RuntimeException('Enkripsi gagal.');
        }

        return $iv.$tag.$cipher;
    }

    public static function decrypt(string $blob, string $key): ?string
    {
        if (strlen($blob) < 28) {
            return null;
        }

        $plain = openssl_decrypt(substr($blob, 28), self::CIPHER, $key, OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16));

        return $plain === false ? null : $plain;
    }
}
