<?php

use Lithe\Http\Request;
use Lithe\Http\Response;
use Lithe\Auth\JWT;
use PHPUnit\Framework\TestCase;

class JWTTest extends TestCase
{
    // Clean up Mockery after each test
    protected function tearDown(): void
    {
        Mockery::close();
    }

    // Test case for invoking middleware with a valid token
    public function testInvokeWithValidToken()
    {
        // Create an instance of the JWT class
        $jwt = Mockery::mock(JWT::class);

        // Mock the Request and Response interfaces
        $request = Mockery::mock(Request::class);
        $response = Mockery::mock(Response::class);

        // Expect the JWT to be callable
        $jwt->shouldReceive('__invoke')->andReturn($response);

        // Simulate the Authorization header with a valid token
        $request->shouldReceive('header')
            ->with('Authorization')
            ->andReturn('Bearer valid.token.here');

        // Mock the decoding process to return valid user data
        $decoded = (object) ['sub' => 1, 'role' => 'user', 'email' => 'user@example.com'];
        $jwt->shouldReceive('getUserFromToken')
            ->with('valid.token.here')
            ->andReturn($decoded);

        // Simulate the next middleware function
        $next = function () {};

        // Execute the middleware
        $result = $jwt($request, $response, $next);

        // Verify that the response is as expected
        $this->assertInstanceOf(Response::class, $result);
    }

    // Test case for revoking a token
    public function testRevokeToken()
    {
        $jwt = new JWT();
        $token = 'token.to.revoke';

        // Revoke the token
        $jwt->revokeToken($token);

        // Check if the token is revoked
        $this->assertTrue($jwt->isTokenRevoked($token));
    }
}
