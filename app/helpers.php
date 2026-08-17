<?php

declare(strict_types=1);

use App\Core\Env;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) Env::get('APP_BASE_PATH', ''), '/');
    $path = '/' . ltrim($path, '/');

    return $base . ($path === '/' ? '/' : $path);
}

function asset(string $path): string
{
    return url($path);
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(App\Core\Csrf::token()) . '">';
}
