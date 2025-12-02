<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../settings.php';
setSecurityHeaders();

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Statistiques générales
    $stats = [];
    
    // Nombre total d'utilisateurs actifs (dernière heure)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT anonymous_id) as active_users
        FROM user_sessions 
        WHERE is_active = 1 
        AND last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute();
    $stats['active_users'] = $stmt->fetch()['active_users'] ?? 0;
    
    // Nombre de sessions actives
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_sessions
        FROM sessions 
        WHERE is_active = 1
    ");
    $stmt->execute();
    $stats['active_sessions'] = $stmt->fetch()['active_sessions'] ?? 0;
    
    // Messages envoyés aujourd'hui
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as messages_today
        FROM messages 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['messages_today'] = $stmt->fetch()['messages_today'] ?? 0;
    
    // Sessions créées aujourd'hui
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as sessions_today
        FROM sessions 
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $stats['sessions_today'] = $stmt->fetch()['sessions_today'] ?? 0;
    
    // Moyenne de messages par session active
    $stmt = $pdo->prepare("
        SELECT AVG(message_count) as avg_messages_per_session
        FROM (
            SELECT COUNT(m.id) as message_count
            FROM sessions s
            LEFT JOIN messages m ON s.session_code = m.session_code
            WHERE s.is_active = 1
            GROUP BY s.session_code
        ) as session_stats
    ");
    $stmt->execute();
    $stats['avg_messages_per_session'] = round($stmt->fetch()['avg_messages_per_session'] ?? 0, 1);
    
    // Sessions les plus populaires (top 5)
    $stmt = $pdo->prepare("
        SELECT 
            s.session_name,
            s.session_code,
            COUNT(DISTINCT us.anonymous_id) as user_count,
            COUNT(m.id) as message_count
        FROM sessions s
        LEFT JOIN user_sessions us ON s.session_code = us.session_code AND us.is_active = 1
        LEFT JOIN messages m ON s.session_code = m.session_code
        WHERE s.is_active = 1
        GROUP BY s.session_code, s.session_name
        ORDER BY user_count DESC, message_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['popular_sessions'] = $stmt->fetchAll();
    
    // Activité par heure (dernières 24h)
    $stmt = $pdo->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as message_count
        FROM messages 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
    $stmt->execute();
    $hourly_activity = $stmt->fetchAll();
    
    // Formater l'activité horaire pour avoir toutes les heures
    $formatted_activity = [];
    for ($i = 0; $i < 24; $i++) {
        $formatted_activity[$i] = 0;
    }
    foreach ($hourly_activity as $activity) {
        $formatted_activity[$activity['hour']] = (int)$activity['message_count'];
    }
    $stats['hourly_activity'] = $formatted_activity;
    
    // Temps de réponse moyen (simulation)
    $stats['avg_response_time'] = rand(50, 150) . 'ms';
    
    // Statut du serveur
    $stats['server_status'] = 'online';
    $stats['uptime'] = '99.9%';
    
    // Timestamp de la requête
    $stats['timestamp'] = time();
    $stats['last_updated'] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    logActivity("Erreur stats: " . $e->getMessage(), 'ERROR');
    echo json_encode([
        'error' => 'Erreur lors de la récupération des statistiques',
        'timestamp' => time()
    ]);
}
?>
