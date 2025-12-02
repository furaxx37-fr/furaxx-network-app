<?php
// Configuration de la base de données - FuraXx Network
// Copiez ce fichier vers config.php et modifiez les valeurs

define('DB_HOST', 'localhost');
define('DB_NAME', 'furaxx_network');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

// Configuration de l'application
define('APP_NAME', 'FuraXx Network');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/furaxx');

// Sécurité
define('SESSION_LIFETIME', 3600); // 1 heure
define('MAX_SESSIONS_PER_USER', 5);
define('MESSAGE_MAX_LENGTH', 500);

// Configuration des médias
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov']);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                   DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonctions utilitaires
function generateAnonymousId() {
    return 'anon_' . bin2hex(random_bytes(8)) . '_' . time();
}

function generateSessionCode() {
    return 'fx_' . uniqid() . '_' . rand(1000, 9999);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
?>
