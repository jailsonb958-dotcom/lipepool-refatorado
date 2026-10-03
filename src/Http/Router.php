<?php

declare(strict_types=1);

namespace LipePool\Http;

final class Router
{
    /** @var list<array{method:string,pattern:string,handler:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $quoted = preg_quote($pattern, '~');
        $regex = preg_replace('/\\\\\{([a-zA-Z][a-zA-Z0-9_]*)\\\\\}/', '(?P<$1>[0-9]+)', $quoted);
        $this->routes[] = ['method' => strtoupper($method), 'pattern' => '~^' . $regex . '$~D', 'handler' => $handler];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method || !preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $params = array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            return ($route['handler'])($request, $params);
        }
        return Response::html('Página não encontrada.', 404);
    }
}
