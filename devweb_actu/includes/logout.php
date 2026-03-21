<?php
// includes/logout.php

// Démarrer uniquement si une session existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 1. Vider les variables de session ─────────────────────────────────────
$_SESSION = [];

// ── 2. Supprimer le cookie de session côté navigateur ────────────────────
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 3600,          // convention : 1h dans le passé
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// ── 3. Détruire la session côté serveur ───────────────────────────────────
session_destroy();

// ── 4. Headers anti-cache (propres, sans doublon) ────────────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// ── 5. Redirection ────────────────────────────────────────────────────────
header('Location: ../pages/login.php');
exit();