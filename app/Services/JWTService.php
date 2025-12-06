<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
    private $secretKey;
    private $algorithm = 'HS256';
    private $expiration = 86400; // 24 heures par défaut

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET_KEY', 'your-secret-key-here');
        $this->expiration = (int) env('JWT_TIME_TO_LIVE', $this->expiration);
    }

    /**
     * Encoder les données en JWT
     */
    public function encode(array $payload): string
    {
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + $this->expiration; // Expiration

        try {
            return JWT::encode($payload, $this->secretKey, $this->algorithm);
        } catch (Exception $e) {
            throw new \RuntimeException('Erreur lors de la génération du token: ' . $e->getMessage());
        }
    }

    /**
     * Décoder le JWT
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            throw new \RuntimeException('Token invalide: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si le token est valide
     */
    public function verify(string $token): bool
    {
        try {
            $this->decode($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Extraire le payload sans vérification (pour debug)
     */
    public function getPayload(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = base64_decode($parts[1]);
            return json_decode($payload, true);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Définir une nouvelle durée d'expiration
     */
    public function setExpiration(int $seconds): void
    {
        $this->expiration = $seconds;
    }
}
