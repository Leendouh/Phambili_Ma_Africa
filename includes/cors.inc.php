<?php

/**
 * CORS for the React / Capacitor client.
 * Allowed origins: Vite dev server, Capacitor WebViews, and optional APP_ORIGIN from .env
 */
function pma_allowed_origins(): array
{
    $origins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://127.0.0.1:5174',
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:8081',
        'http://127.0.0.1:8081',
        'https://localhost',
        'capacitor://localhost',
        'ionic://localhost',
        'http://localhost:5175',
        'http://127.0.0.1:5175',
        'http://localhost:19006',
        'http://127.0.0.1:19006',
        'http://10.0.2.2',
        'http://10.0.2.2:19000',
        'http://10.0.2.2:19001',
        'http://10.0.2.2:19006',
        'exp://10.0.2.2:19000',
        'exp://127.0.0.1:19000',
        'exp://192.168.0.0:19000',
        'http://192.168.0.0:19000',
        'http://192.168.0.0:19006',
        'https://phambilimaafrica.infinityfree.me',
        'https://www.phambilimaafrica.infinityfree.me',
    ];

    $extra = env('APP_ORIGIN', '');
    if ($extra !== '') {
        $origins[] = rtrim($extra, '/');
    }

    return array_unique($origins);
}

function pma_handle_cors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowed = pma_allowed_origins();

    if ($origin !== '' && in_array($origin, $allowed, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    } elseif ($origin === '') {
        // Same-origin / Vite proxy requests have no Origin header
        header('Access-Control-Allow-Credentials: true');
    } else {
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
    header('Access-Control-Max-Age: 86400');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}
