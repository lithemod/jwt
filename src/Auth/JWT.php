<?php

namespace Lithe\Auth;

use Exception;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Lithe\Http\{Request, Response};

class JWT
{
    private string $secretKey;
    private string $algorithm;
    private int $expirationTime;
    private array $revokedTokens = []; // List of revoked tokens

    /**
     * JWT constructor.
     * 
     * @param string $secretKey The secret key used for encoding/decoding tokens.
     * @param string $algorithm The algorithm used for signing tokens.
     * @param int $expirationTime The expiration time of the token in seconds.
     */
    public function __construct(string $secretKey = 'your-super-secret-key', string $algorithm = 'HS256', int $expirationTime = 3600) {
        $this->secretKey = $secretKey;
        $this->algorithm = $algorithm;
        $this->expirationTime = $expirationTime;
    }

    /**
     * Middleware function to check the JWT in the request.
     * 
     * @param \Lithe\Http\Request $req The HTTP request.
     * @param \Lithe\Http\Response $res The HTTP response.
     * @param callable $next The next middleware function to call.
     * @return mixed
     */
    public function __invoke(Request $req, Response $res, callable $next)
    {
        $authHeader = $req->header('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $res->status(401)->json(['error' => 'Token not provided']);
        }

        $token = $matches[1];

        if ($this->isTokenRevoked($token)) {
            return $res->status(401)->json(['error' => 'Token revoked']);
        }

        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $req->user = [
                'sub' => $decoded->sub,
                'role' => $decoded->role ?? null,
                'email' => $decoded->email ?? null,
            ];

            return $next($req, $res);
        } catch (Exception $e) {
            return $res->status(401)->json(['error' => 'Invalid or expired token']);
        }
    }

    /**
     * Generates a new JWT token for the user.
     * 
     * @param array $user The user data to encode in the token.
     * @return string The encoded JWT token.
     */
    public function generateToken(array $user)
    {
        $payload = [
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + $this->expirationTime
        ];

        if (isset($user['role'])) {
            $payload['role'] = $user['role'];
        }
        if (isset($user['email'])) {
            $payload['email'] = $user['email'];
        }

        return FirebaseJWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Revokes a JWT token.
     * 
     * @param string $token The token to revoke.
     */
    public function revokeToken($token) {
        $this->revokedTokens[] = $token; // Add the token to the revoked list
    }

    /**
     * Checks if a token has been revoked.
     * 
     * @param string $token The token to check.
     * @return bool True if the token is revoked, false otherwise.
     */
    public function isTokenRevoked(string $token) {
        return in_array($token, $this->revokedTokens); // Check if the token is in the revoked list
    }

    /**
     * Refreshes a JWT token.
     * 
     * @param string $token The token to refresh.
     * @return string The new token.
     * @throws Exception If the token cannot be refreshed.
     */
    public function refreshToken(string $token) {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));
            // Generate a new token with the same data but a new expiration date
            return $this->generateToken((array)$decoded);
        } catch (Exception $e) {
            throw new Exception('Cannot refresh token: ' . $e->getMessage());
        }
    }

    /**
     * Retrieves user data from the token.
     * 
     * @param string $token The token from which to get the user data.
     * @return array The user data.
     * @throws Exception If the token is invalid.
     */
    public function getUserFromToken(string $token) {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array)$decoded; // Return the decoded user data
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Validates a JWT token without decoding it.
     * 
     * @param string $token The token to validate.
     * @return bool True if the token is valid, false otherwise.
     */
    public function validateToken(string $token) {
        try {
            FirebaseJWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return true; // Return true if the token is valid
        } catch (Exception $e) {
            return false; // Return false if the token is not valid
        }
    }
}
