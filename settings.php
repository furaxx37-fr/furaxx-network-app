<?php
// FuraXx Network - Paramètres de l'application

// Configuration de sécurité
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS
ini_set('session.use_strict_mode', 1);

// Configuration de l'application
define('APP_NAME', 'FuraXx Network');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://34.162.165.109/furaxx');

// Limites de l'application
define('MAX_MESSAGE_LENGTH', 500);
define('MAX_SESSION_NAME_LENGTH', 50);
define('MAX_SESSIONS_PER_USER', 10);
define('SESSION_TIMEOUT_MINUTES', 60);
define('MESSAGE_REFRESH_INTERVAL', 2000); // millisecondes

// Configuration des sessions anonymes
define('ANONYMOUS_ID_LENGTH', 32);
define('SESSION_CODE_PREFIX', 'fx_');
define('USERNAME_PREFIX', 'Anonyme_');

// Configuration de la base de données (depuis config.php)
require_once 'config.php';

// Fonctions utilitaires pour la sécurité
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateAnonymousId($id) {
    return preg_match('/^anon_[a-f0-9]{16}_[0-9]{10}$/', $id);
}

function validateSessionCode($code) {
    return preg_match('/^fx_[a-f0-9]{13}_[0-9]{4}$/', $code);
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Configuration des en-têtes de sécurité
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Fonction de logging pour le débogage
function logActivity($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents('logs/app.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Créer le dossier de logs si nécessaire
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Configuration des fuseaux horaires
date_default_timezone_set('Europe/Paris');

// Messages de l'application
$app_messages = [
    'welcome' => 'Bienvenue sur FuraXx Network - Connexions 100% Anonymes',
    'session_created' => 'Session créée avec succès !',
    'session_joined' => 'Vous avez rejoint la session !',
    'message_sent' => 'Message envoyé !',
    'session_left' => 'Vous avez quitté la session.',
    'error_generic' => 'Une erreur est survenue. Veuillez réessayer.',
    'error_invalid_session' => 'Code de session invalide.',
    'error_session_full' => 'Cette session est pleine.',
    'error_message_too_long' => 'Message trop long (max ' . MAX_MESSAGE_LENGTH . ' caractères).',
];

// Configuration des couleurs et thèmes
$app_theme = [
    'primary_color' => '#e50914',
    'secondary_color' => '#0f0f0f',
    'accent_color' => '#1a1a1a',
    'text_color' => '#ffffff',
    'success_color' => '#00ff00',
    'error_color' => '#ff0000',
    'warning_color' => '#ffaa00'
];

?>
