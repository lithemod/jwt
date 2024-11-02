# Lithe Auth JWT

The JWT middleware for Lithe is responsible for user authentication, providing secure token generation, validation, and revocation.

## 1. Installing the JWT Middleware

To use the JWT middleware in Lithe, install via Composer:

```bash
composer require lithemod/jwt
```

## 2. Configuring the JWT Middleware

You can use the middleware directly in your routes. Here's how:

### Configuration Example:

```php
use Lithe\Auth\JWT;

// Route configuration with JWT
$app->get('/protected', new JWT('your-secret-key', 'HS256', 3600), function ($req, $res) {
    return $res->send('Access granted!');
});
```

## 3. Using JWT Tokens

### 3.1 Generating a JWT Token

Generate a JWT token for an authenticated user. The token will contain relevant information such as user ID and role.

```php
$app->post('/login', function ($req, $res) {
    $user = ['id' => 1, 'role' => 'admin', 'email' => 'user@example.com']; // Example user
    $token = (new JWT())->generateToken($user);
    return $res->send(['token' => $token]);
});
```

### 3.2 Validating a JWT Token

Use the JWT middleware to protect routes. The middleware will check the token validity on each request.

```php
$app->get('/protected-route', new JWT(), function ($req, $res) {
    return $res->send('Access to protected route.');
});
```

### 3.3 Revoking a Token

Revoke a token when a user logs out. This ensures the token can no longer be used.

```php
$app->post('/logout', function ($req, $res) {
    $token = $req->header('Authorization');
    (new JWT())->revokeToken($token);
    return $res->send('Token revoked.');
});
```

### 3.4 Refreshing a Token

Implement an endpoint for token refresh, allowing users to obtain a new token without needing to log in again.

```php
$app->post('/refresh', function ($req, $res) {
    $token = $req->header('Authorization');
    $newToken = (new JWT())->refreshToken($token);
    return $res->send(['token' => $newToken]);
});
```

### 3.5 Retrieving User Data from the Token

Extract user information from the JWT for use in protected routes.

```php
$app->get('/user', new JWT(), function ($req, $res) {
    return $res->send($req->user);
});
```

### 3.6 Validating a Token without Decoding

Validate a token without fully decoding it. This can be useful for quickly checking the authenticity of a token.

```php
$app->post('/validate', function ($req, $res) {
    $token = $req->header('Authorization');
    $isValid = (new JWT())->validateToken($token);
    return $res->send(['valid' => $isValid]);
});
```

## 4. Methods of the JWT Class

### 4.1 `__construct()`

**Description**: Initializes the JWT class with a secret key, algorithm, and expiration time.

**Parameters**:
- `$secretKey`: Secret key for encoding/decoding.
- `$algorithm`: Algorithm for signing the token.
- `$expirationTime`: Token expiration time in seconds.

### 4.2 `__invoke()`

**Description**: Middleware to check the JWT in a request.

**Parameters**:
- `$req`: The HTTP request.
- `$res`: The HTTP response.
- `$next`: Next middleware function to call.

### 4.3 `generateToken()`

**Description**: Generates a new JWT token for a user.

**Parameters**:
- `$user`: User data to encode in the token.

### 4.4 `revokeToken()`

**Description**: Revokes a JWT token by adding it to the revoked list.

**Parameters**:
- `$token`: The token to revoke.

### 4.5 `isTokenRevoked()`

**Description**: Checks if a token has been revoked.

**Parameters**:
- `$token`: The token to check.

### 4.6 `refreshToken()`

**Description**: Updates an expired JWT token by generating a new one.

**Parameters**:
- `$token`: The token to refresh.

### 4.7 `getUserFromToken()`

**Description**: Retrieves user data from a JWT token.

**Parameters**:
- `$token`: The token from which to get user data.

### 4.8 `validateToken()`

**Description**: Validates a JWT token without decoding it.

**Parameters**:
- `$token`: The token to validate.

## Final Considerations

- **Token Revocation**: The middleware supports token revocation.
- **Error Handling**: Validation errors and token expiration are automatically handled, returning appropriate messages to the client.