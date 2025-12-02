class FuraXxApp {
    constructor() {
        this.currentSession = null;
        this.messageInterval = null;
        this.sessionListInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadSessions();
        this.startSessionListRefresh();
    }

    bindEvents() {
        // Create session
        document.getElementById('createSessionBtn').addEventListener('click', () => {
            this.showCreateSessionModal();
        });

        // Join session
        document.getElementById('joinSessionBtn').addEventListener('click', () => {
            this.showJoinSessionModal();
        });

        // Send message
        document.getElementById('sendMessageBtn').addEventListener('click', () => {
            this.sendMessage();
        });

        // Enter key to send message
        document.getElementById('messageInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Leave session
        document.getElementById('leaveSessionBtn').addEventListener('click', () => {
            this.leaveSession();
        });

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.closeModal(e.target.closest('.modal'));
            });
        });

        // Click outside modal to close
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
    }

    async loadSessions() {
        try {
            const response = await fetch('api/list_sessions.php');
            const data = await response.json();
            
            if (data.sessions) {
                this.displaySessions(data.sessions);
            }
        } catch (error) {
            console.error('Error loading sessions:', error);
        }
    }

    displaySessions(sessions) {
        const container = document.getElementById('sessionsList');
        
        if (sessions.length === 0) {
            container.innerHTML = `
                <div class="text-center text-gray-400 py-8">
                    <p>Aucune session active</p>
                    <p class="text-sm mt-2">Créez une nouvelle session pour commencer</p>
                </div>
            `;
            return;
        }

        container.innerHTML = sessions.map(session => `
            <div class="bg-gray-800 rounded-lg p-4 hover:bg-gray-700 transition-colors cursor-pointer session-item" 
                 data-session-id="${session.session_id}">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-semibold text-white truncate">${this.escapeHtml(session.session_name)}</h3>
                    <span class="text-xs text-gray-400">${session.created_time}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-300">${session.user_count} utilisateur(s)</span>
                    ${session.is_member ? 
                        '<span class="text-xs bg-red-600 text-white px-2 py-1 rounded">Connecté</span>' : 
                        '<span class="text-xs bg-gray-600 text-gray-300 px-2 py-1 rounded">Rejoindre</span>'
                    }
                </div>
            </div>
        `).join('');

        // Add click events to session items
        container.querySelectorAll('.session-item').forEach(item => {
            item.addEventListener('click', () => {
                const sessionId = item.dataset.sessionId;
                this.joinSessionById(sessionId);
            });
        });
    }

    showCreateSessionModal() {
        document.getElementById('createSessionModal').classList.remove('hidden');
        document.getElementById('sessionNameInput').focus();
    }

    showJoinSessionModal() {
        document.getElementById('joinSessionModal').classList.remove('hidden');
        document.getElementById('sessionIdInput').focus();
    }

    closeModal(modal) {
        modal.classList.add('hidden');
        // Clear inputs
        modal.querySelectorAll('input').forEach(input => input.value = '');
    }

    async createSession() {
        const sessionName = document.getElementById('sessionNameInput').value.trim();
        
        if (!sessionName) {
            this.showNotification('Veuillez entrer un nom de session', 'error');
            return;
        }

        try {
            const response = await fetch('api/create_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ session_name: sessionName })
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeModal(document.getElementById('createSessionModal'));
                this.joinSession(data.session_id, data.session_name);
                this.showNotification('Session créée avec succès!', 'success');
            } else {
                this.showNotification(data.error || 'Erreur lors de la création', 'error');
            }
        } catch (error) {
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    async joinSessionById(sessionId) {
        try {
            const response = await fetch('api/join_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ session_id: sessionId })
            });

            const data = await response.json();
            
            if (data.success) {
                this.joinSession(data.session_id, data.session_name);
                this.showNotification('Session rejointe!', 'success');
            } else {
                this.showNotification(data.error || 'Erreur lors de la connexion', 'error');
            }
        } catch (error) {
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    joinSession(sessionId, sessionName) {
        this.currentSession = { id: sessionId, name: sessionName };
        
        // Switch to chat view
        document.getElementById('sessionsList').style.display = 'none';
        document.getElementById('chatInterface').style.display = 'block';
        document.getElementById('currentSessionName').textContent = sessionName;
        
        // Start message polling
        this.startMessagePolling();
        this.loadMessages();
        
        // Stop session list refresh
        if (this.sessionListInterval) {
            clearInterval(this.sessionListInterval);
        }
    }

    async leaveSession() {
        if (!this.currentSession) return;

        try {
            await fetch('api/leave_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ session_id: this.currentSession.id })
            });
        } catch (error) {
            console.error('Error leaving session:', error);
        }

        // Stop message polling
        if (this.messageInterval) {
            clearInterval(this.messageInterval);
        }

        // Switch back to session list
        document.getElementById('chatInterface').style.display = 'none';
        document.getElementById('sessionsList').style.display = 'block';
        
        this.currentSession = null;
        this.loadSessions();
        this.startSessionListRefresh();
        
        this.showNotification('Session quittée', 'info');
    }

    async sendMessage() {
        if (!this.currentSession) return;

        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message) return;

        try {
            const response = await fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.currentSession.id,
                    message: message
                })
            });

            const data = await response.json();
            
            if (data.success) {
                messageInput.value = '';
                this.loadMessages();
            } else {
                this.showNotification(data.error || 'Erreur lors de l\'envoi', 'error');
            }
        } catch (error) {
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    async loadMessages() {
        if (!this.currentSession) return;

        try {
            const response = await fetch(`api/get_messages.php?session_id=${this.currentSession.id}`);
            const data = await response.json();
            
            if (data.messages) {
                this.displayMessages(data.messages);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    displayMessages(messages) {
        const container = document.getElementById('messagesContainer');
        
        container.innerHTML = messages.map(msg => `
            <div class="mb-4 ${msg.is_own ? 'text-right' : 'text-left'}">
                <div class="inline-block max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                    msg.is_own 
                        ? 'bg-red-600 text-white' 
                        : 'bg-gray-700 text-white'
                }">
                    <div class="text-xs opacity-75 mb-1">
                        ${msg.is_own ? 'Vous' : this.escapeHtml(msg.username)} • ${msg.timestamp}
                    </div>
                    <div>${this.escapeHtml(msg.message)}</div>
                </div>
            </div>
        `).join('');
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    startMessagePolling() {
        if (this.messageInterval) {
            clearInterval(this.messageInterval);
        }
        
        this.messageInterval = setInterval(() => {
            this.loadMessages();
        }, 2000);
    }

    startSessionListRefresh() {
        if (this.sessionListInterval) {
            clearInterval(this.sessionListInterval);
        }
        
        this.sessionListInterval = setInterval(() => {
            if (!this.currentSession) {
                this.loadSessions();
            }
        }, 5000);
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
            type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
            'bg-blue-600'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FuraXxApp();
});
