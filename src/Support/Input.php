<?php

declare(strict_types=1);

namespace LipePool\Support;

final class Input
{
    public static function string(mixed $value, int $maxLength, string $label, bool $required = true): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException($label . ' inválido.');
        }
        $value = trim($value);
        if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new \InvalidArgumentException($label . ' é obrigatório e deve ter até ' . $maxLength . ' caracteres.');
        }
        return $value;
    }

    public static function email(mixed $value): string
    {
        $email = strtolower(self::string($value, 254, 'E-mail'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Informe um e-mail válido.');
        }
        return $email;
    }

    public static function phone(mixed $value): string
    {
        $phone = self::string($value, 24, 'Telefone');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 10 || strlen($digits) > 13) {
            throw new \InvalidArgumentException('Informe um telefone válido com DDD.');
        }
        return $phone;
    }

    public static function date(mixed $value): string
    {
        $date = self::string($value, 10, 'Data');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
            throw new \InvalidArgumentException('Escolha uma data válida a partir de hoje.');
        }
        return $date;
    }

    public static function appointmentTime(mixed $value): string
    {
        $time = self::string($value, 8, 'Horário');
        if (!preg_match('/^(08|10|13|15|17|19):00(?::00)?$/D', $time)) {
            throw new \InvalidArgumentException('Escolha um dos horários disponíveis.');
        }
        return substr($time, 0, 5) . ':00';
    }
}
