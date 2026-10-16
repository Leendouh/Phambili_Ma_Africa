<?php

/**
 * Shared bootstrap for JSON APIs used by the React / Capacitor app.
 * Loads CORS, sessions, DB, and hydrates login from Bearer remember-me tokens.
 */
require_once __DIR__ . '/env_loader.inc.php';
require_once __DIR__ . '/cors.inc.php';
pma_handle_cors();

require_once __DIR__ . '/config_session.inc.php';
require_once __DIR__ . '/dbh.inc.php';
require_once __DIR__ . '/login_model.inc.php';

pma_hydrate_bearer_auth($pdo);

function pma_current_user_payload(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [
            'success' => true,
            'authenticated' => false,
            'user' => null,
        ];
    }

    $role = $_SESSION['user_role'] ?? 'customer';
    $user = [
        'id' => (int) $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $role,
    ];

    if ($role === 'admin') {
        $user['admin_id'] = (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id']);
        $user['admin_role'] = $_SESSION['admin_role'] ?? 'sub_admin';
        $user['full_name'] = $_SESSION['admin_full_name'] ?? $user['name'];
    }

    return [
        'success' => true,
        'authenticated' => true,
        'user' => $user,
        'csrf_token' => getCSRFToken(),
    ];
}

function pma_establish_session(array $result): void
{
    $userType = $result['user_type'] ?? 'customer';

    if ($userType === 'admin') {
        $_SESSION['user_id'] = $result['ID'];
        $_SESSION['user_email'] = htmlspecialchars($result['Email']);
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_name'] = htmlspecialchars($result['Full_Name'] ?? 'Administrator');
        $_SESSION['admin_id'] = $result['ID'];
        $_SESSION['admin_email'] = htmlspecialchars($result['Email']);
        $_SESSION['admin_role'] = $result['Role'] ?? 'sub_admin';
        $_SESSION['admin_full_name'] = htmlspecialchars($result['Full_Name'] ?? '');
    } else {
        $_SESSION['user_id'] = $result['ID'];
        $_SESSION['user_email'] = htmlspecialchars($result['Email']);
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = htmlspecialchars($result['Full_Name'] ?? 'Customer');
    }

    $_SESSION['last_regeneration'] = time();
    session_regenerate_id(true);
}

function pma_issue_auth_token(object $pdo, array $result, string $userType): string
{
    $selector = generate_selector();
    $validator = generate_validator();
    $hashedValidator = hash_validator($validator);
    create_remember_me_token($pdo, (int) $result['ID'], $userType, $selector, $hashedValidator, 30);
    return $selector . ':' . $validator;
}

function pma_hydrate_bearer_auth(object $pdo): void
{
    if (isset($_SESSION['user_id'])) {
        return;
    }

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return;
    }

    $parts = explode(':', $matches[1]);
    if (count($parts) !== 2) {
        return;
    }

    [$selector, $validator] = $parts;
    $token = get_remember_me_token($pdo, $selector);
    if (!$token) {
        return;
    }

    if (!hash_equals($token['validator'], hash_validator($validator))) {
        delete_remember_me_token($pdo, $selector);
        return;
    }

    $user = get_user_by_id($pdo, (int) $token['user_id']);
    if (!$user || $user['user_type'] !== $token['user_type']) {
        return;
    }

    pma_establish_session($user);
}
