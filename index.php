<?php

declare(strict_types=1);

/*
 * Punto de entrada alternativo para hostings que obligan a publicar el
 * proyecto completo en public_html. Apache sirve los assets mediante el
 * .htaccess de la raíz y todas las demás solicitudes llegan al router MVC.
 */
$requestPath = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$assetMarker = '/assets/';
$assetPosition = strpos($requestPath, $assetMarker);

// Compatibilidad con `php -S localhost:8080 index.php`, que ignora .htaccess.
if (PHP_SAPI === 'cli-server' && $assetPosition !== false) {
    $relativeAsset = substr($requestPath, $assetPosition + strlen($assetMarker));
    $assetRoot = realpath(__DIR__ . '/public/assets');
    $assetFile = realpath(__DIR__ . '/public/assets/' . $relativeAsset);

    if ($assetRoot !== false && $assetFile !== false && str_starts_with($assetFile, $assetRoot . DIRECTORY_SEPARATOR) && is_file($assetFile)) {
        $extension = strtolower(pathinfo($assetFile, PATHINFO_EXTENSION));
        $contentTypes = [
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
        ];
        header('Content-Type: ' . ($contentTypes[$extension] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($assetFile));
        readfile($assetFile);
        exit;
    }
}

require __DIR__ . '/public/index.php';
