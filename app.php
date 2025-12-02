<?php
require_once 'config.php';

// Vérifier si l'utilisateur a un ID anonyme valide
if (!isset($_GET['id']) && !isset($_POST['anonymous_id'])) {
    header('Location: index.html');
    exit;
}

$anonymous_id = $_GET['id'] ?? $_POST['anonymous_id'] ?? '';

if (!isValidAnonymousId($anonymous_id)) {
    header('Location: index.html');
    exit;
}

// Enregistrer ou mettre à jour l'utilisateur dans la base de données
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("INSERT INTO anonymous_users (anonymous_id) VALUES (?) ON DUPLICATE KEY UPDATE last_activity = CURRENT_TIMESTAMP");
    $stmt->execute([$anonymous_id]);
    
    // Nettoyer les messages expirés
    $db->exec("DELETE FROM messages WHERE expires_at < NOW()");
    
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FuraXx Network - Interface</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 50%, #0f0f0f 100%);
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        .netflix-red { color: #e50914; }
        .netflix-red-bg { background-color: #e50914; }
        .logo-glow {
            text-shadow: 0 0 20px rgba(229, 9, 20, 0.5);
        }
        .message-bubble {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .online-indicator {
            animation: pulse 2s infinite;
        }
        .chat-container {
            height: calc(100vh - 200px);
        }
    </style>
</head>
<body class="min-h-screen text-white">
    <!-- Header -->
    <header class="bg-gray-900 bg-opacity-90 backdrop-blur-sm p-4 border-b border-gray-700">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 netflix-red-bg rounded-lg flex items-center justify-center logo-glow">
                    <i class="fas fa-ghost text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold netflix-red">FuraXx Network</h1>
                    <p class="text-xs text-gray-400">ID: <?php echo substr($anonymous_id, 0, 12); ?>...</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-400 rounded-full online-indicator"></div>
                    <span class="text-sm text-gray-300">En ligne</span>
                </div>
                <button onclick="disconnect()" class="text-red-400 hover:text-red-300 transition-colors">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="container mx-auto p-4 flex gap-6">
        <!-- Sidebar -->
        <div class="w-80 bg-gray-900 bg-opacity-50 rounded-2xl p-6 border border-gray-700">
            <h2 class="text-xl font-bold mb-6 netflix-red">Actions Rapides</h2>
            
            <!-- Créer/Rejoindre Session -->
            <div class="space-y-4 mb-8">
                <button onclick="showCreateSession()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Créer une Session
                </button>
                
                <div class="flex gap-2">
                    <input type="text" id="sessionCodeInput" placeholder="Code session" 
                           class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-purple-400 focus:outline-none">
                    <button onclick="joinSession()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-in-alt"></i>
                    </button>
                </div>
            </div>

            <!-- Sessions Actives -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4 text-gray-300">Sessions Actives</h3>
                <div id="activeSessions" class="space-y-2">
                    <p class="text-gray-500 text-sm">Aucune session active</p>
                </div>
            </div>

            <!-- Utilisateurs En Ligne -->
            <div>
                <h3 class="text-lg font-semibold mb-4 text-gray-300">Utilisateurs Anonymes</h3>
                <div id="onlineUsers" class="space-y-2">
                    <div class="flex items-center space-x-3 p-2 bg-gray-800 rounded-lg">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">Vous</p>
                            <p class="text-xs text-gray-400">En ligne</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de Chat Principal -->
        <div class="flex-1 bg-gray-900 bg-opacity-50 rounded-2xl border border-gray-700 flex flex-col">
            <!-- En-tête du Chat -->
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-xl font-bold netflix-red">Chat Global Anonyme</h2>
                <p class="text-gray-400 text-sm">Messages éphémères - Disparaissent après 5 minutes</p>
            </div>

            <!-- Messages -->
            <div id="messagesContainer" class="flex-1 p-6 overflow-y-auto chat-container">
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                    <p>Aucun message pour le moment</p>
                    <p class="text-sm">Commencez une conversation anonyme !</p>
                </div>
            </div>

            <!-- Zone de Saisie -->
            <div class="p-6 border-t border-gray-700">
                <div class="flex gap-3">
                    <input type="text" id="messageInput" placeholder="Tapez votre message anonyme..." 
                           class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-red-400 focus:outline-none"
                           onkeypress="handleKeyPress(event)">
                    <button onclick="sendMessage()" class="netflix-red-bg hover:bg-red-700 text-white px-6 py-3 rounded-lg transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button onclick="showMediaUpload()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-image"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer Session -->
    <div id="createSessionModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center hidden z-50">
        <div class="bg-gray-900 p-8 rounded-2xl border border-gray-700 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold mb-4 netflix-red">Créer une Session</h3>
            <p class="text-gray-400 mb-6">Générez un code unique pour inviter d'autres utilisateurs anonymes.</p>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Nom de la session (optionnel)</label>
                    <input type="text" id="sessionName" placeholder="Session anonyme" 
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-purple-400 focus:outline-none">
                </div>
                
                <div class="flex gap-3">
                    <button onclick="createSession()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-colors">
                        Créer
                    </button>
                    <button onclick="hideCreateSession()" class="px-6 py-3 border border-gray-600 text-gray-300 rounded-lg hover:bg-gray-800 transition-colors">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const anonymousId = '<?php echo $anonymous_id; ?>';
        let currentSession = null;
        let messageInterval;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            loadMessages();
            loadOnlineUsers();
            startMessagePolling();
        });

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            // Afficher le message immédiatement (optimistic UI)
            displayMessage({
                sender_id: anonymousId,
                content: message,
                created_at: new Date().toISOString(),
                message_type: 'text'
            }, true);
            
            messageInput.value = '';
            
            // Envoyer au serveur
            fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anonymous_id: anonymousId,
                    message: message,
                    session_code: currentSession
                })
            });
        }

        function displayMessage(message, isOwn = false) {
            const container = document.getElementById('messagesContainer');
            const emptyState = container.querySelector('.text-center');
            if (emptyState) emptyState.remove();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message-bubble mb-4 ${isOwn ? 'ml-auto' : 'mr-auto'} max-w-xs`;
            
            const senderColor = isOwn ? 'netflix-red-bg' : 'bg-gray-700';
            const alignment = isOwn ? 'text-right' : 'text-left';
            
            messageDiv.innerHTML = `
                <div class="${senderColor} p-3 rounded-lg ${alignment}">
                    <p class="text-white">${escapeHtml(message.content)}</p>
                    <p class="text-xs opacity-75 mt-1">
                        ${isOwn ? 'Vous' : 'Anonyme'} • ${formatTime(message.created_at)}
                    </p>
                </div>
            `;
            
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }

        function loadMessages() {
            fetch(`api/get_messages.php?anonymous_id=${anonymousId}&session=${currentSession || ''}`)
                .then(response => response.json())
                .then(messages => {
                    const container = document.getElementById('messagesContainer');
                    container.innerHTML = '';
                    
                    if (messages.length === 0) {
                        container.innerHTML = `
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                                <p>Aucun message pour le moment</p>
                                <p class="text-sm">Commencez une conversation anonyme !</p>
                            </div>
                        `;
                        return;
                    }
                    
                    messages.forEach(message => {
                        displayMessage(message, message.sender_id === anonymousId);
                    });
                });
        }

        function loadOnlineUsers() {
            fetch(`api/get_online_users.php?anonymous_id=${anonymousId}`)
                .then(response => response.json())
                .then(users => {
                    const container = document.getElementById('onlineUsers');
                    container.innerHTML = `
                        <div class="flex items-center space-x-3 p-2 bg-gray-800 rounded-lg">
                            <div class="w-8 h-8 netflix-red-bg rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-xs"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium">Vous</p>
                                <p class="text-xs text-gray-400">En ligne</p>
                            </div>
                        </div>
                    `;
                    
                    users.forEach(user => {
                        if (user.anonymous_id !== anonymousId) {
                            container.innerHTML += `
                                <div class="flex items-center space-x-3 p-2 bg-gray-800 rounded-lg">
                                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">Anonyme</p>
                                        <p class="text-xs text-gray-400">En ligne</p>
                                    </div>
                                </div>
                            `;
                        }
                    });
                });
        }

        function startMessagePolling() {
            messageInterval = setInterval(() => {
                loadMessages();
                loadOnlineUsers();
            }, 3000);
        }

        function showCreateSession() {
            document.getElementById('createSessionModal').classList.remove('hidden');
        }

        function hideCreateSession() {
            document.getElementById('createSessionModal').classList.add('hidden');
        }

        function createSession() {
            const sessionName = document.getElementById('sessionName').value || 'Session anonyme';
            
            fetch('api/create_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anonymous_id: anonymousId,
                    session_name: sessionName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Session créée ! Code: ${data.session_code}`);
                    currentSession = data.session_code;
                    hideCreateSession();
                    loadMessages();
                }
            });
        }

        function joinSession() {
            const code = document.getElementById('sessionCodeInput').value.trim().toUpperCase();
            if (!code) return;
            
            fetch('api/join_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    anonymous_id: anonymousId,
                    session_code: code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentSession = code;
                    document.getElementById('sessionCodeInput').value = '';
                    loadMessages();
                    alert('Session rejointe avec succès !');
                } else {
                    alert('Code de session invalide ou expiré');
                }
            });
        }

        function disconnect() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                clearInterval(messageInterval);
                localStorage.removeItem('furaxx_anonymous_id');
                window.location.href = 'index.html';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Nettoyage à la fermeture
        window.addEventListener('beforeunload', function() {
            clearInterval(messageInterval);
        });
    </script>
</body>
</html>
