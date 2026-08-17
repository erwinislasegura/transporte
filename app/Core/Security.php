<?php

declare(strict_types=1);

namespace App\Core;

final class Security
{
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function createAccessToken(string $subject, ?int $expiresMinutes = null): string
    {
        $minutes = $expiresMinutes ?? (int) Env::get('ACCESS_TOKEN_EXPIRE_MINUTES', 60);
        $secret = (string) Env::get('JWT_SECRET_KEY', 'change_me');
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = ['sub' => $subject, 'exp' => time() + ($minutes * 60)];

        $segments = [
            self::base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES)),
            self::base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
