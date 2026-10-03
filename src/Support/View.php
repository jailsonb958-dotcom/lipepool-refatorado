<?php

declare(strict_types=1);

namespace LipePool\Support;

use LipePool\Http\Response;

final class View
{
    public function __construct(private readonly string $templateRoot) {}

    public function render(string $template, array $data = [], int $status = 200): Response
    {
        if (!preg_match('/^[a-zA-Z0-9_\/-]+$/', $template)) {
            throw new \InvalidArgumentException('Nome de template inválido.');
        }
        $file = $this->templateRoot . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Template não encontrado.');
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        ob_start();
        require $this->templateRoot . '/layout.php';
        return Response::html((string) ob_get_clean(), $status);
    }

    public static function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
