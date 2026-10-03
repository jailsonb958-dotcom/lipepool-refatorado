<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Security\Csrf;
use LipePool\Support\HttpException;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testGeneratedTokenIsAccepted(): void
    {
        $token = Csrf::token();
        self::assertSame(64, strlen($token));
        Csrf::verify($token);
        $this->assertTrue(true);
    }

    public function testMissingOrMismatchedTokenIsRejected(): void
    {
        Csrf::token();
        try {
            Csrf::verify('not-the-token');
            self::fail('Token divergente deveria ser rejeitado.');
        } catch (HttpException $error) {
            self::assertSame(419, $error->status);
        }
    }
}
