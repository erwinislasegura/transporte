<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    public function view(string $view, array $data = [], string $layout = 'app'): void
    {
        $viewFile = BASE_PATH . '/app/Views/' . $view . '.php';
        $layoutFile = BASE_PATH . '/app/Views/layouts/' . $layout . '.php';

        if (!is_file($viewFile) || !is_file($layoutFile)) {
            throw new \RuntimeException('No se encontró la vista solicitada.');
        }

        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        require $layoutFile;
        unset($_SESSION['_old']);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
