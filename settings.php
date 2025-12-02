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


// Configuration du mode debug
define('DEBUG_MODE', false); // Mettre à true pour activer le mode debug

// Configuration des sessions anonymes
define('ANONYMOUS_ID_LENGTH', 32);
define('SESSION_CODE_PREFIX', 'fx_');
define('USERNAME_PREFIX', 'Anonyme_');

// Configuration de la base de données (depuis config.php)
require_once 'config.php';

// Fonctions utilitaires pour la sécurité

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


// Fonction de validation et nettoyage des données d'entrée
function sanitizeInput($input, $type = 'string', $maxLength = null) {
    if ($input === null || $input === '') {
        return '';
    }
    
    switch ($type) {
        case 'string':
            $cleaned = trim(strip_tags($input));
            $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
            break;
            
        case 'message':
            $cleaned = trim($input);
            $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
            // Préserver les sauts de ligne pour les messages
            $cleaned = nl2br($cleaned);
            break;
            
        case 'session_name':
            $cleaned = trim(strip_tags($input));
            $cleaned = preg_replace('/[^a-zA-Z0-9\s\-_àáâäèéêëìíîïòóôöùúûüçñ]/u', '', $cleaned);
            $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
            break;
            
        case 'session_code':
            $cleaned = trim(strtolower($input));
            $cleaned = preg_replace('/[^a-f0-9_]/', '', $cleaned);
            break;
            
        case 'anonymous_id':
            $cleaned = trim(strtolower($input));
            $cleaned = preg_replace('/[^a-f0-9_]/', '', $cleaned);
            break;
            
        default:
            $cleaned = trim(strip_tags($input));
            $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
    }
    
    // Appliquer la limite de longueur si spécifiée
    if ($maxLength && strlen($cleaned) > $maxLength) {
        $cleaned = substr($cleaned, 0, $maxLength);
    }
    
    return $cleaned;
}

// Configuration des en-têtes de sécurité
function setSecurityHeaders() {
    // En-têtes de sécurité de base
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // En-têtes de sécurité avancés
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data:;');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    
    // Protection contre le clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Cache control pour les pages sensibles
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
}


// Fonction de monitoring des performances
function startPerformanceMonitoring() {
    if (!defined('PERF_START_TIME')) {
        define('PERF_START_TIME', microtime(true));
        define('PERF_START_MEMORY', memory_get_usage());
    }
}

function endPerformanceMonitoring($operation = 'Unknown') {
    if (defined('PERF_START_TIME')) {
        $executionTime = round((microtime(true) - PERF_START_TIME) * 1000, 2);
        $memoryUsage = round((memory_get_usage() - PERF_START_MEMORY) / 1024, 2);
        $peakMemory = round(memory_get_peak_usage() / 1024 / 1024, 2);
        
        $performanceData = [
            'operation' => $operation,
            'execution_time_ms' => $executionTime,
            'memory_used_kb' => $memoryUsage,
            'peak_memory_mb' => $peakMemory,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Log les performances si l'opération prend plus de 500ms ou utilise plus de 1MB
        if ($executionTime > 500 || $memoryUsage > 1024) {
            logActivity("Performance Alert - Operation: $operation, Time: {$executionTime}ms, Memory: {$memoryUsage}KB, Peak: {$peakMemory}MB", 'WARNING');
        }
        
        // Log normal pour toutes les opérations en mode debug
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            logActivity("Performance - Operation: $operation, Time: {$executionTime}ms, Memory: {$memoryUsage}KB", 'INFO');
        }
        
        return $performanceData;
    }
    return null;
}

// Fonction de logging pour le débogage
function logActivity($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Rotation des logs si le fichier devient trop volumineux (> 5MB)
    $logFile = 'logs/app.log';
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        $backupFile = 'logs/app_' . date('Y-m-d_H-i-s') . '.log';
        rename($logFile, $backupFile);
    }
    
    // Écriture du log avec verrouillage
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Log critique également dans le log système si niveau ERROR ou CRITICAL
    if (in_array($level, ['ERROR', 'CRITICAL'])) {
        error_log("FuraXx Network [$level]: $message");
    }
}


// Fonction globale de gestion d'erreurs
function handleError($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'FATAL ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
    ];
    
    $errorType = isset($errorTypes[$errno]) ? $errorTypes[$errno] : 'UNKNOWN ERROR';
    $errorMessage = "[$errorType] $errstr in $errfile on line $errline";
    
    // Log l'erreur
    logActivity($errorMessage, 'ERROR');
    
    // En mode développement, afficher l'erreur
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='color: red; background: #ffe6e6; padding: 10px; margin: 10px; border: 1px solid red;'>";
        echo "<strong>Erreur PHP:</strong> $errorMessage";
        echo "</div>";
    }
    
    // Ne pas arrêter l'exécution pour les erreurs non fatales
    return true;
}

// Activer la gestion d'erreurs personnalisée
set_error_handler('handleError');

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
