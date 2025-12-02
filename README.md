# FuraXx Network 🚀

Une application de messagerie anonyme inspirée de Snapchat avec un design Netflix élégant.

## 🌟 Fonctionnalités

- **Messagerie Anonyme** : Communication 100% anonyme sans inscription
- **Sessions Temporaires** : Créez ou rejoignez des sessions avec des codes uniques
- **Design Netflix** : Interface moderne et élégante inspirée de Netflix
- **API RESTful** : Backend robuste avec APIs complètes
- **Sécurité Avancée** : Protection contre XSS, CSRF et injection SQL
- **Temps Réel** : Notifications et statistiques en temps réel

## 🛠️ Technologies Utilisées

- **Frontend** : HTML5, CSS3 (Tailwind), JavaScript (Vanilla)
- **Backend** : PHP 8+, MySQL
- **Serveur** : Apache2
- **Sécurité** : Headers sécurisés, validation des données, sanitisation

## 📁 Structure du Projet

```
furaxx/
├── index.html          # Page d'accueil
├── app.php            # Application principale
├── config.php         # Configuration base de données
├── settings.php       # Paramètres et sécurité
├── setup_database.php # Installation BDD
├── css/
│   └── styles.css     # Styles Netflix
├── js/
│   └── app.js         # Logique frontend
├── api/
│   ├── create_session.php
│   ├── join_session.php
│   ├── send_message.php
│   ├── get_messages.php
│   ├── list_sessions.php
│   ├── leave_session.php
│   ├── notifications.php
│   └── stats.php
└── logs/              # Fichiers de logs
```

## 🚀 Installation

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Apache2
- Extensions PHP : pdo, pdo_mysql

### Configuration

1. **Cloner le projet**
```bash
git clone https://github.com/votre-username/furaxx-network.git
cd furaxx-network
```

2. **Configuration de la base de données**
```bash
# Modifier config.php avec vos paramètres
# Puis exécuter :
php setup_database.php
```

3. **Permissions**
```bash
chmod 755 logs/
chown -R www-data:www-data .
```

### Variables d'environnement

Modifiez `config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'furaxx_network');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

## 📖 Utilisation

### Interface Utilisateur
1. Accédez à `index.html`
2. Générez un ID anonyme
3. Créez une session ou rejoignez avec un code
4. Commencez à chatter anonymement !

### API Endpoints

#### Sessions
- `POST /api/create_session.php` - Créer une session
- `POST /api/join_session.php` - Rejoindre une session
- `POST /api/leave_session.php` - Quitter une session
- `GET /api/list_sessions.php` - Lister les sessions actives

#### Messages
- `POST /api/send_message.php` - Envoyer un message
- `GET /api/get_messages.php` - Récupérer les messages

#### Monitoring
- `GET /api/notifications.php` - Notifications utilisateur
- `GET /api/stats.php` - Statistiques application

## 🔒 Sécurité

- **Validation stricte** des entrées utilisateur
- **Protection XSS** avec htmlspecialchars
- **Headers sécurisés** (CSP, HSTS, etc.)
- **IDs anonymes** générés cryptographiquement
- **Sanitisation** de toutes les données

## 🎨 Design

Interface inspirée de Netflix avec :
- **Couleurs sombres** (#141414, #E50914)
- **Typographie moderne** (Helvetica Neue)
- **Animations fluides** CSS3
- **Responsive design** mobile-first
- **Logo personnalisé** FuraXx Network

## 📊 Base de Données

### Tables principales
- `anonymous_users` - Utilisateurs anonymes
- `sessions` - Sessions de chat
- `messages` - Messages échangés
- `session_participants` - Participants aux sessions

## 🤝 Contribution

1. Fork le projet
2. Créez une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Committez (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push (`git push origin feature/nouvelle-fonctionnalite`)
5. Ouvrez une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 🐛 Signaler un Bug

Ouvrez une issue sur GitHub avec :
- Description détaillée
- Étapes de reproduction
- Environnement (OS, PHP version, etc.)
- Logs d'erreur si disponibles

## 🚀 Roadmap

- [ ] Upload de fichiers/images
- [ ] Messages éphémères (auto-destruction)
- [ ] Salles de chat thématiques
- [ ] Modération automatique
- [ ] Application mobile (PWA)
- [ ] Chiffrement end-to-end

## 👥 Équipe

Développé avec ❤️ par l'équipe FuraXx Network

---

**FuraXx Network** - *Connectez-vous anonymement, communiquez librement* 🌐
