<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private string $algorithm  = 'HS256';
    private int    $access_ttl  = 10800;        // 3 hora
    private int    $refresh_ttl = 604800;      // 7 dias

    public function __construct()
    {
        $this->secret = env('JWT_SECRET');
    }

    public function generate_access_token(array $payload): string
    {
        $now = time();

        return JWT::encode([
            'iss'               => base_url(),
            'iat'               => $now,
            'exp'               => $now + $this->access_ttl,
            'id_usuario'        => $payload['id_usuario'],
            'id_instituicao'    => $payload['id_instituicao'],
            'email'             => $payload['email'],
            'roles'             => $payload['roles'],
            'permissions'       => $payload['permissions'],
        ], $this->secret, $this->algorithm);
    }

    public function generate_refresh_token(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function decode_token(string $token): object|null
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function get_refresh_ttl(): int
    {
        return $this->refresh_ttl;
    }
}
