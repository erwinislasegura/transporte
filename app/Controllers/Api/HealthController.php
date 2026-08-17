<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

final class HealthController extends Controller
{
    public function show(): void
    {
        $this->json(['status' => 'ok', 'service' => 'BGV Enterprise API']);
    }
}
