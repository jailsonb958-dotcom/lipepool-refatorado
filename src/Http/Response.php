<?php

declare(strict_types=1);

namespace LipePool\Http;

final class Response
{
    public function __construct(
        public readonly string $body = '',
        public readonly int $status = 200,
        public readonly array $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {}

    public static function redirect(string $location, int $status = 303): self
    {
        if (!str_starts_with($location, '/') || str_starts_with($location, '//')) {
            throw new \InvalidArgumentException('Redirecionamento deve ser um caminho interno.');
        }
        return new self('', $status, ['Location' => $location]);
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
        exit;
    }
}
