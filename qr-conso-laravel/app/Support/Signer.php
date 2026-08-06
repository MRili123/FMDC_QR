<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Signature et jetons. La clé est APP_KEY, déjà gérée par Laravel : rien de plus
 * à provisionner au déploiement.
 */
class Signer
{
    public static function hmac(string $payload): string
    {
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /** Comparaison qui ne révèle pas combien de caractères correspondent. */
    public static function safeEquals(string $a, string $b): bool
    {
        return hash_equals($a, $b);
    }

    public static function randomToken(int $bytes = 24): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /**
     * Slug court et lisible pour un QR. Alphabet sans caractères ambigus (0/O,
     * 1/I/l) car ces codes sont parfois recopiés à la main depuis une affiche.
     */
    public static function randomSlug(int $length = 10): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }

    public static function draftId(): string
    {
        return (string) Str::uuid();
    }
}
