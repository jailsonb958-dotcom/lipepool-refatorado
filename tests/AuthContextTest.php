<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Auth\AuthContext;
use LipePool\Support\HttpException;
use PHPUnit\Framework\TestCase;

final class AuthContextTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCustomerCannotAccessAdmin(): void
    {
        $_SESSION['user'] = ['id' => 3, 'role' => 'customer'];
        try {
            AuthContext::requireAdmin();
            self::fail('A rota deveria negar acesso ao cliente.');
        } catch (HttpException $error) {
            self::assertSame(403, $error->status);
        }
    }

    public function testAnonymousCannotAccessAccount(): void
    {
        try {
            AuthContext::requireLogin();
            self::fail('A rota deveria exigir autenticação.');
        } catch (HttpException $error) {
            self::assertSame(401, $error->status);
        }
    }
}
