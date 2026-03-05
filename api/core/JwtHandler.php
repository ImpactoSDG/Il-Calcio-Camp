<?php

class JwtHandler {
    private static string $secret = "";

    private static function getSecret(): string {
        if (empty(self::$secret)) {
            self::$secret = $_ENV['JWT_SECRET'] ?? 'fallback-secret-key-change-me';
        }
        return self::$secret;
    }

    public static function encode(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + (60 * 60 * 24); // 24 horas
        $payloadStr = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payloadStr);

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::getSecret(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function decode(string $jwt): ?array {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) return null;

        list($header, $payload, $signature) = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, self::getSecret(), true);
        $validSignatureEncoded = self::base64UrlEncode($validSignature);

        if ($signature !== $validSignatureEncoded) return null;

        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null; // Token expirado
        }

        return $payloadData;
    }

    private static function base64UrlEncode(string $data): string {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
