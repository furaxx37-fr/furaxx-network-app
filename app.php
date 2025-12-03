<?php
require_once 'config.php';
require_once 'functions.php';

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
    $db = $pdo;
    
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
        
        /* Enhanced Message Bubbles */
        .message-bubble-sent {
            background: linear-gradient(135deg, #e50914, #b8070f);
            margin-left: auto;
            margin-right: 0;
            border-radius: 18px 18px 4px 18px;
            max-width: 70%;
            animation: slideInRight 0.3s ease-out;
        }
        
        .message-bubble-received {
            background: linear-gradient(135deg, #374151, #4b5563);
            margin-left: 0;
            margin-right: auto;
            border-radius: 18px 18px 18px 4px;
            max-width: 70%;
            animation: slideInLeft 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #374151;
            border-radius: 18px 18px 18px 4px;
            margin: 8px 0;
            max-width: 80px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typingBounce {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
        
        /* Message Status Indicators */
        .message-status {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 4px;
            text-align: right;
        }
        
        .status-sent { color: #6b7280; }
        .status-delivered { color: #10b981; }
        
        /* Enhanced Input Focus */
        .chat-input:focus {
            box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.3);
            border-color: #e50914;
        }
        
        /* Scroll Animations */
        .message-container {
            scroll-behavior: smooth;
        }
        
        /* Hover Effects */
        .message-bubble-sent:hover,
        .message-bubble-received:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.2s ease;
        }
        
        /* Enhanced Button Animations */
        .send-button {
            transition: all 0.2s ease;
        }
        
        .send-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(229, 9, 20, 0.4);
        }
        
        .send-button:active {
            transform: scale(0.95);
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

    <!-- Mobile Menu Toggle (visible only on mobile) -->
    <div class="lg:hidden fixed top-4 left-4 z-50">
        <button id="mobileMenuToggle" onclick="toggleMobileSidebar()" class="bg-gray-900 bg-opacity-90 text-white p-3 rounded-lg border border-gray-700">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="mobileOverlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="closeMobileSidebar()"></div>

    <div class="container mx-auto p-2 sm:p-4 flex flex-col lg:flex-row gap-4 lg:gap-6">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed lg:relative top-0 left-0 h-full lg:h-auto w-80 lg:w-80 xl:w-96 bg-gray-900 bg-opacity-95 lg:bg-opacity-50 rounded-none lg:rounded-2xl p-4 lg:p-6 border-r lg:border border-gray-700 z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 overflow-y-auto">
            <!-- Close button for mobile -->
            <div class="lg:hidden flex justify-between items-center mb-4 pb-4 border-b border-gray-700">
                <h2 class="text-lg font-bold netflix-red">Menu</h2>
                <button onclick="closeMobileSidebar()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <h2 class="text-lg lg:text-xl font-bold mb-4 lg:mb-6 netflix-red hidden lg:block">Actions Rapides</h2>
            
            <!-- Créer/Rejoindre Session -->
            <div class="space-y-3 lg:space-y-4 mb-6 lg:mb-8">
                <button onclick="showCreateSession()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 lg:py-3 px-3 lg:px-4 rounded-lg transition-colors text-sm lg:text-base">
                    <i class="fas fa-plus mr-2"></i>
                    Créer une Session
                </button>
                
                <div class="flex gap-2">
                    <input type="text" id="sessionCodeInput" placeholder="Code session" 
                           class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-2 lg:px-3 py-2 text-white placeholder-gray-400 focus:border-purple-400 focus:outline-none text-sm lg:text-base">
                    <button onclick="joinSession()" class="bg-green-600 hover:bg-green-700 text-white px-3 lg:px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-in-alt"></i>
                    </button>
                </div>

                <!-- Bouton de partage -->
                <button id="shareButton" onclick="shareSession()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 lg:py-3 px-3 lg:px-4 rounded-lg transition-colors hidden text-sm lg:text-base">
                    <i class="fas fa-share-alt mr-2"></i>
                    Partager la Session
                </button>
            </div>

            <!-- Sessions Actives -->
            <div class="mb-6 lg:mb-8">
                <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4 text-gray-300">Sessions Actives</h3>
                <div id="activeSessions" class="space-y-2">
                    <p class="text-gray-500 text-xs lg:text-sm">Aucune session active</p>
                </div>
            </div>

            <!-- Utilisateurs En Ligne -->
            <div>
                <h3 class="text-base lg:text-lg font-semibold mb-3 lg:mb-4 text-gray-300">Utilisateurs Anonymes</h3>
                <div id="onlineUsers" class="space-y-2">
                    <div class="flex items-center space-x-3 p-2 bg-gray-800 rounded-lg">
                        <div class="w-6 h-6 lg:w-8 lg:h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-xs lg:text-sm font-medium">Vous</p>
                            <p class="text-xs text-gray-400">En ligne</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de Chat Principal -->
        <div class="flex-1 bg-gray-900 bg-opacity-50 rounded-xl lg:rounded-2xl border border-gray-700 flex flex-col min-h-[calc(100vh-8rem)] lg:min-h-0">
            <!-- En-tête du Chat -->
            <div class="p-4 lg:p-6 border-b border-gray-700">
                <h2 class="text-lg lg:text-xl font-bold netflix-red">Chat Global Anonyme</h2>
                <p class="text-gray-400 text-xs lg:text-sm">Messages éphémères - Disparaissent après 5 minutes</p>
            </div>

            <!-- Messages -->
            <div id="messagesContainer" class="flex-1 p-4 lg:p-6 overflow-y-auto chat-container message-container">
                <div id="welcomeMessage" class="text-center text-gray-500 py-6 lg:py-8">
                    <i class="fas fa-comments text-3xl lg:text-4xl mb-4 opacity-50"></i>
                    <p class="text-sm lg:text-base">Aucun message pour le moment</p>
                    <p class="text-xs lg:text-sm">Commencez une conversation anonyme !</p>
                </div>
                <!-- Typing indicator (hidden by default) -->
                <div id="typingIndicator" class="typing-indicator hidden">
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                    <span class="ml-2 text-xs text-gray-400">Quelqu'un écrit...</span>
                </div>
            </div>

            <!-- Zone de Saisie -->
            <div class="p-4 lg:p-6 border-t border-gray-700">
                <div class="flex gap-2 lg:gap-3">
                    <input type="text" id="messageInput" placeholder="Tapez votre message anonyme..." 
                           class="flex-1 bg-gray-800 border border-gray-600 rounded-lg px-3 lg:px-4 py-2.5 lg:py-3 text-white placeholder-gray-400 chat-input text-sm lg:text-base"
                           onkeypress="handleKeyPress(event)"
                           oninput="handleTyping()">
                    <button onclick="sendMessage()" class="netflix-red-bg hover:bg-red-700 text-white px-4 lg:px-6 py-2.5 lg:py-3 rounded-lg send-button">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                    <button onclick="showMediaUpload()" class="bg-gray-700 hover:bg-gray-600 text-white px-3 lg:px-4 py-2.5 lg:py-3 rounded-lg transition-colors">
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
                
                <div>
                    <label class="block text-sm font-medium mb-2">Code de session personnalisé (optionnel)</label>
                    <input type="text" id="customCode" placeholder="Laissez vide pour génération automatique" 
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-purple-400 focus:outline-none"
                           maxlength="12" pattern="[a-zA-Z0-9]{4,12}">
                    <p class="text-xs text-gray-500 mt-1">4-12 caractères alphanumériques uniquement</p>
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
        let messageInterval;

        // Fonctions pour le menu mobile
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                // Show sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
            } else {
                // Hide sidebar
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
            }
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            overlay.classList.add('hidden');
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier s'il y a un code de session dans l'URL pour auto-join
            const urlParams = new URLSearchParams(window.location.search);
            const sessionCode = urlParams.get('session');
            
            if (sessionCode) {
                // Auto-join la session depuis le lien partagé
                autoJoinSession(sessionCode.toUpperCase());
            }
            
            loadMessages();
            loadOnlineUsers();
            startMessagePolling();
        });

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }
        
        function handleTyping() {
            // Show typing indicator functionality
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            // Clear previous typing timeout
            if (window.typingTimeout) {
                clearTimeout(window.typingTimeout);
            }
            
            // Send typing indicator if user is typing
            if (message.length > 0) {
                // You can extend this to send typing status to other users
                // For now, we'll just handle local UI feedback
                
                // Set timeout to clear typing status
                window.typingTimeout = setTimeout(() => {
                    // Clear typing indicator after 2 seconds of inactivity
                }, 2000);
            }
        }

        function showMediaUpload() {
            // Create media upload modal
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-white text-lg font-semibold mb-4">Partager un média</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-300 text-sm mb-2">Choisir un fichier</label>
                            <input type="file" id="mediaFile" accept="image/*,video/*" 
                                   class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white">
                        </div>
                        <div class="flex gap-3">
                            <button onclick="uploadMedia()" class="flex-1 netflix-red-bg hover:bg-red-700 text-white py-2 rounded">
                                Envoyer
                            </button>
                            <button onclick="closeMediaModal()" class="flex-1 bg-gray-600 hover:bg-gray-500 text-white py-2 rounded">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            window.currentMediaModal = modal;
        }

        function closeMediaModal() {
            if (window.currentMediaModal) {
                document.body.removeChild(window.currentMediaModal);
                window.currentMediaModal = null;
            }
        }

        function uploadMedia() {
            const fileInput = document.getElementById('mediaFile');
            const file = fileInput.files[0];
            
            if (!file) {
                showNotification('Veuillez sélectionner un fichier', 'error');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB limit
                showNotification('Fichier trop volumineux (max 10MB)', 'error');
                return;
            }
            
            // For now, show a placeholder message
            showNotification('Fonctionnalité de partage de médias en développement', 'info');
            closeMediaModal();
        }

        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.querySelector('button[onclick="sendMessage()"]');
            const message = messageInput.value.trim();
            
            if (!message || message.length > 500) {
                if (message.length > 500) {
                    showNotification('Message trop long (max 500 caractères)', 'error');
                }
                return;
            }
            
            // Disable input and button during sending
            messageInput.disabled = true;
            if (sendButton) {
                sendButton.disabled = true;
                sendButton.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Envoi...';
            }
            
            // Afficher le message immédiatement (optimistic UI)
            const tempMessage = {
                sender_id: anonymousId,
                content: message,
                created_at: new Date().toISOString(),
                message_type: 'text'
            };
            displayMessage(tempMessage, true);
            
            messageInput.value = '';
            
            // Envoyer au serveur avec gestion d'erreur
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
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors de l\'envoi');
                }
                // Message sent successfully
                showNotification('Message envoyé', 'success');
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showNotification('Erreur lors de l\'envoi du message', 'error');
            })
            .finally(() => {
                // Re-enable input and button
                messageInput.disabled = false;
                messageInput.focus();
                if (sendButton) {
                    sendButton.disabled = false;
                    sendButton.innerHTML = 'Envoyer';
                }
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform translate-x-full transition-all duration-300 ${
                type === 'success' ? 'bg-green-600' : 
                type === 'error' ? 'bg-red-600' : 'bg-blue-600'
            }`;
            
            notification.innerHTML = `
                <div class="flex items-center text-white">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        ${type === 'success' ? 
                            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' :
                            type === 'error' ?
                            '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>' :
                            '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>'
                        }
                    </svg>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Slide in animation
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
                notification.classList.add('translate-x-0');
            }, 100);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        function displayMessage(message, isOwn = false) {
            const container = document.getElementById('messagesContainer');
            const emptyState = container.querySelector('.text-center');
            if (emptyState) emptyState.remove();
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message-bubble mb-4 ${isOwn ? 'ml-auto' : 'mr-auto'} max-w-xs opacity-0 transform translate-y-4`;
            
            const senderColor = isOwn ? 'netflix-red-bg' : 'bg-gray-700';
            const alignment = isOwn ? 'text-right' : 'text-left';
            const animationClass = isOwn ? 'slideInFromRight' : 'slideInFromLeft';
            
            // Enhanced message bubble with better styling
            messageDiv.innerHTML = `
                <div class="${senderColor} p-4 rounded-2xl ${alignment} shadow-lg border border-opacity-20 border-white hover:shadow-xl transition-all duration-300">
                    <p class="text-white text-sm leading-relaxed">${escapeHtml(message.content)}</p>
                    <p class="text-xs opacity-75 mt-2 flex items-center ${isOwn ? 'justify-end' : 'justify-start'}">
                        <span class="inline-flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                            ${isOwn ? 'Vous' : 'Anonyme'} • ${formatTime(message.created_at)}
                        </span>
                    </p>
                </div>
            `;
            
            container.appendChild(messageDiv);
            
            // Smooth animation entrance
            setTimeout(() => {
                messageDiv.classList.remove('opacity-0', 'transform', 'translate-y-4');
                messageDiv.classList.add('opacity-100', animationClass);
            }, 50);
            
            // Auto-scroll with smooth behavior
            setTimeout(() => {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }, 200);
        }

        function loadMessages() {
            // Only load messages if we have an active session
            if (!currentSession) {
                const container = document.getElementById('messagesContainer');
                container.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-comments text-4xl mb-4 opacity-50"></i>
                        <p>Rejoignez ou créez une session pour voir les messages</p>
                        <p class="text-sm">Commencez une conversation anonyme !</p>
                    </div>
                `;
                return;
            }
            
            fetch(`api/get_messages.php?anonymous_id=${anonymousId}&session_code=${currentSession}`)
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
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    const container = document.getElementById('messagesContainer');
                    container.innerHTML = `
                        <div class="text-center text-red-500 py-8">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                            <p>Erreur lors du chargement des messages</p>
                        </div>
                    `;
                });
        }
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

        function showShareButton() {
            const shareButton = document.getElementById('shareButton');
            if (shareButton) {
                shareButton.classList.remove('hidden');
            }
        }

        function shareSession() {
            if (!currentSession) {
                alert('Aucune session active à partager');
                return;
            }
            
            // Call backend API to generate share link
            fetch('api/share_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_code: currentSession,
                    anonymous_id: anonymousId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const shareUrl = data.share_link;
                    const participantCount = data.participant_count;
                    
                    if (navigator.share) {
                        navigator.share({
                            title: 'Rejoindre ma session Furaxx Network',
                            text: `Rejoignez ma session avec le code: ${currentSession} (${participantCount} participants)`,
                            url: shareUrl
                        });
                    } else {
                        navigator.clipboard.writeText(shareUrl).then(() => {
                            alert(`Lien copié ! 
Code de session: ${currentSession}
Participants: ${participantCount}
Lien: ${shareUrl}`);
                        }).catch(() => {
                            prompt('Copiez ce lien:', shareUrl);
                        });
                    }
                } else {
                    alert('Erreur lors de la génération du lien de partage: ' + (data.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la génération du lien de partage');
            });
        }

        function createSession() {
            const sessionName = document.getElementById('sessionName').value || 'Session anonyme';
            const customCode = document.getElementById('customCode').value.trim();
            
            const requestData = {
                anonymous_id: anonymousId,
                session_name: sessionName
            };
            
            if (customCode) {
                requestData.custom_code = customCode;
            }
            
            fetch('api/create_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Session créée ! Code: ${data.session_code}`);
                    currentSession = data.session_code;
                    hideCreateSession();
                    loadMessages();
                    showShareButton();
                } else {
                    alert(data.message || 'Erreur lors de la création de la session');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la création de la session');
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

        // Auto-join function for shared session links
        function autoJoinSession(sessionCode) {
            if (!sessionCode) return;
            
            fetch('api/join_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_code: sessionCode,
                    anonymous_id: anonymousId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentSession = sessionCode;
                    document.getElementById('current-session').textContent = sessionCode;
                    document.getElementById('session-info').style.display = 'block';
                    hideCreateSession();
                    loadMessages();
                    startMessagePolling();
                    showNotification(`Connecté à la session: ${sessionCode}`, 'success');
                } else {
                    showNotification('Erreur lors de la connexion à la session: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion à la session', 'error');
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
