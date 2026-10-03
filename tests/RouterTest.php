<?php

declare(strict_types=1);

namespace LipePool\Tests;

use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesMatchingMethodAndCapturesNumericParameter(): void
    {
        $router = new Router();
        $router->add('POST', '/items/{id}', static fn (Request $request, array $params): Response => Response::html((string) $params['id']));
        $response = $router->dispatch(new Request('POST', '/items/42'));
        self::assertSame(200, $response->status);
        self::assertSame('42', $response->body);
    }

    public function testDoesNotMatchDifferentMethodOrNonNumericId(): void
    {
        $router = new Router();
        $router->add('POST', '/items/{id}', static fn (): Response => Response::html('matched'));
        self::assertSame(404, $router->dispatch(new Request('GET', '/items/42'))->status);
        self::assertSame(404, $router->dispatch(new Request('POST', '/items/arbitrary'))->status);
    }
}
