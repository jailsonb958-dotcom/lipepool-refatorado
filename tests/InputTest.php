<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Support\Input;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
    public function testValidEmailIsNormalized(): void
    {
        self::assertSame('cliente@example.com', Input::email(' Cliente@Example.com '));
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Input::email('not-an-email');
    }

    public function testOnlyLegacyAllowedTimesAreAccepted(): void
    {
        self::assertSame('10:00:00', Input::appointmentTime('10:00'));
        $this->expectException(\InvalidArgumentException::class);
        Input::appointmentTime('10:00xx');
    }

    public function testPhoneMustContainDdd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Input::phone('123');
    }

    public function testStringRejectsArrayInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Input::string(['<script>'], 120, 'Nome');
    }
}
