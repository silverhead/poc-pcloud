# Gestionnaire de Sauvegardes pCloud

Une interface web moderne et intuitive pour gérer vos sauvegardes avec pCloud en utilisant rclone.

## Fonctionnalités

- 🗂️ **Navigation Bi-directionnelle** : Naviguez facilement dans vos fichiers locaux et pCloud
- 📁 **Dossiers Complets** : Sauvegardez des dossiers entiers avec tous leurs sous-dossiers
- 🗑️ **Suppression pCloud** : Supprimez des fichiers et dossiers directement sur pCloud
- ☁️ **Synchronisation Flexible** : Trois modes de synchronisation (sync vers pCloud, copie vers pCloud, sync depuis pCloud)
- 📊 **Progression Avancée** : Suivi détaillé fichier par fichier avec ETA et statuts en temps réel
- ⚙️ **Configuration Simple** : Interface de configuration pour définir vos chemins de base
- 🎨 **Interface Moderne** : Design sobre et moderne avec Bootstrap 5 et SweetAlert2
- 📱 **Responsive** : Compatible mobile et desktop
- 🔄 **Temps Réel** : Actualisation automatique et indicateurs de progression
- 💎 **Notifications Élégantes** : Popups et confirmations modernes avec SweetAlert2

## Prérequis

- Docker et Docker Compose
- rclone configuré avec un remote nommé `pcloud`

## Configuration de rclone

Avant d'utiliser l'application, vous devez configurer rclone avec pCloud :

```bash
rclone config
```

Suivez les instructions pour créer un remote de type `pcloud` nommé `pcloud`.

Pour tester votre configuration :
```bash
rclone lsd pcloud:
```

## Installation et Démarrage

1. **Cloner le projet** (si nécessaire)
```bash
git clone <votre-repo>
cd poc-pcloud
```

2. **Démarrer l'application**
```bash
make up
# ou
docker-compose up -d
```

3. **Installer les dépendances** (déjà fait si vous avez suivi les étapes précédentes)
```bash
docker exec pcloud-app composer install
```

4. **Accéder à l'application**
Ouvrez votre navigateur à l'adresse : `http://localhost:7080`

## Utilisation

### 1. Configuration Initiale

- Accédez à la page **Configuration** via le menu
- Définissez votre **Chemin de Base Local** (ex: `/home/user/documents`)
- Définissez votre **Chemin de Base pCloud** (ex: `/Backups`)
- Optionnel : Spécifiez le chemin vers votre fichier de configuration rclone
- Cliquez sur **Sauvegarder**

### 2. Navigation

- **Panneau Gauche** : Fichiers et dossiers de votre serveur local
- **Panneau Droit** : Fichiers et dossiers de votre pCloud
- Cliquez sur les dossiers pour naviguer
- Utilisez les fils d'Ariane pour revenir en arrière

### 3. Sélection de Fichiers et Dossiers

- **Fichiers :** Cliquez sur les fichiers pour les sélectionner
- **Dossiers :** Utilisez les cases à cocher pour sélectionner des dossiers complets
- **Navigation :** Cliquez sur la flèche → pour entrer dans un dossier
- **Sélection multiple :** Utilisez les boutons "Tout sélectionner" et "Désélectionner"
- Les éléments sélectionnés apparaissent en surbrillance

### 4. Actions de Synchronisation

#### Synchroniser vers pCloud
- Sélectionnez des fichiers ou **dossiers complets** locaux
- Cliquez sur "Synchroniser vers pCloud"
- ⚠️ **Attention** : Supprime les fichiers en trop sur pCloud (synchronisation exacte)
- 📁 **Dossiers** : Tout le contenu et les sous-dossiers sont synchronisés

#### Copier vers pCloud
- Sélectionnez des fichiers ou **dossiers complets** locaux  
- Cliquez sur "Copier vers pCloud"
- ✅ **Sécurisé** : Conserve tous les fichiers existants sur pCloud
- 📁 **Dossiers** : Structure complète préservée avec tous les sous-dossiers

#### Synchroniser depuis pCloud
- Sélectionnez des fichiers ou **dossiers complets** pCloud
- Cliquez sur "Synchroniser depuis pCloud"
- Récupère les éléments sélectionnés sur votre serveur local
- 📁 **Dossiers** : Télécharge l'intégralité du dossier et ses sous-dossiers

#### Supprimer de pCloud
- **Suppression individuelle** : Cliquez sur le bouton rouge 🗑️ à côté de chaque élément
- **Suppression multiple** : Sélectionnez plusieurs éléments et cliquez sur "Supprimer de pCloud"
- ⚠️ **ATTENTION** : Les suppressions sont définitives et irréversibles
- 🛡️ **Sécurité** : Double confirmation requise pour les dossiers
- 🗂️ **Dossiers complets** : Supprime le dossier et tout son contenu

## Structure du Projet

```
app/
├── src/
│   ├── Controller/
│   │   └── BackupController.php      # Contrôleur principal
│   └── Service/
│       └── RcloneService.php         # Service de gestion rclone
├── templates/
│   ├── base.html.twig               # Template de base
│   └── backup/
│       ├── index.html.twig          # Interface principale
│       └── config.html.twig         # Page de configuration
├── config/
│   ├── routes.yaml                  # Configuration des routes
│   └── services.yaml                # Configuration des services
└── public/
    └── index.php                    # Point d'entrée
```

## Variables d'Environnement

Copiez `.env.example` vers `.env` et ajustez les valeurs :

```bash
# Configuration des chemins de base
LOCAL_BASE_PATH=/home/user
PCLOUD_BASE_PATH=/
RCLONE_CONFIG_PATH=/path/to/rclone.conf
```

## API Endpoints

- `GET /backup/` - Interface principale
- `GET /backup/config` - Page de configuration  
- `POST /backup/config` - Sauvegarde de la configuration
- `GET /backup/local-files?path=` - Liste des fichiers locaux
- `GET /backup/pcloud-files?path=` - Liste des fichiers pCloud
- `POST /backup/sync-to-cloud` - Synchronisation vers pCloud
- `POST /backup/copy-to-cloud` - Copie vers pCloud
- `POST /backup/sync-from-cloud` - Synchronisation depuis pCloud
- `POST /backup/delete-from-cloud` - Suppression d'un élément sur pCloud
- `POST /backup/delete-multiple-from-cloud` - Suppression multiple sur pCloud

## Sécurité

- L'application utilise les permissions du système de fichiers local
- rclone utilise sa propre configuration d'authentification avec pCloud
- Assurez-vous que le container Docker a accès aux fichiers que vous voulez sauvegarder

## Dépannage

### Erreur "Remote not found"
- Vérifiez que rclone est configuré avec un remote nommé `pcloud`
- Testez avec : `docker exec pcloud-app rclone lsd pcloud:`

### Erreur de permissions
- Vérifiez que le container a accès au chemin local configuré
- Ajustez les permissions si nécessaire

### Interface ne charge pas
- Vérifiez que le container est démarré : `docker ps`
- Consultez les logs : `docker logs pcloud-app`

## Contribuer

1. Fork le projet
2. Créez une branche pour votre fonctionnalité
3. Commitez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

## Licence

Ce projet est sous licence MIT. 