<?php
require_once 'config.php';

try {
    $db = $pdo;
    
    // Table des utilisateurs anonymes
    $db->exec("
        CREATE TABLE IF NOT EXISTS anonymous_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anonymous_id VARCHAR(50) UNIQUE NOT NULL,
            session_code VARCHAR(10) DEFAULT NULL,
            is_host BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active'
        )
    ");
    
    // Table des sessions
    $db->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_code VARCHAR(10) UNIQUE NOT NULL,
            host_id VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
            status ENUM('active', 'expired') DEFAULT 'active',
            FOREIGN KEY (host_id) REFERENCES anonymous_users(anonymous_id) ON DELETE CASCADE
        )
    ");
    
    // Table des messages éphémères
    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id VARCHAR(50) NOT NULL,
            receiver_id VARCHAR(50) DEFAULT NULL,
            session_code VARCHAR(10) DEFAULT NULL,
            message_type ENUM('text', 'image', 'video') DEFAULT 'text',
            content TEXT NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 5 MINUTE),
            is_read BOOLEAN DEFAULT FALSE,
            status ENUM('active', 'expired', 'deleted') DEFAULT 'active',
            FOREIGN KEY (sender_id) REFERENCES anonymous_users(anonymous_id) ON DELETE CASCADE
        )
    ");
    
    // Table des connexions actives
    $db->exec("
        CREATE TABLE IF NOT EXISTS active_connections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anonymous_id VARCHAR(50) NOT NULL,
            session_code VARCHAR(10) DEFAULT NULL,
            last_ping TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (anonymous_id) REFERENCES anonymous_users(anonymous_id) ON DELETE CASCADE
        )
    ");
    
    // Index pour optimiser les performances
    $db->exec("CREATE INDEX idx_messages_expires ON messages(expires_at)");
    $db->exec("CREATE INDEX idx_messages_session ON messages(session_code)");
    $db->exec("CREATE INDEX idx_users_activity ON anonymous_users(last_activity)");
    
    echo "✅ Base de données configurée avec succès!\n";
    echo "📊 Tables créées:\n";
    echo "   - anonymous_users (utilisateurs anonymes)\n";
    echo "   - sessions (sessions temporaires)\n";
    echo "   - messages (messages éphémères)\n";
    echo "   - active_connections (connexions actives)\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur lors de la configuration: " . $e->getMessage() . "\n";
}
?>
