# 🚀 Guide d'Utilisation Rapide - Gestionnaire pCloud

## ✅ Installation Terminée !

Votre interface de gestion des sauvegardes pCloud est maintenant opérationnelle !

## 🌐 Accès à l'Application

**URL :** http://localhost:7080

L'application se charge automatiquement sur l'interface de gestion des sauvegardes.

## 🔧 Première Configuration

### 1. Configurer rclone (OBLIGATOIRE)

```bash
# Entrer dans le container
docker exec -it pcloud-app bash

# Configurer rclone
rclone config

# Créer un nouveau remote :
# - Name: pcloud
# - Type: pcloud
# - Suivre les instructions d'authentification
```

### 2. Configurer l'application

1. Allez sur http://localhost:7080
2. Cliquez sur **"Configuration"** dans le menu
3. Définissez vos chemins :
   - **Chemin Local :** `/home/user` (ou votre dossier préféré)
   - **Chemin pCloud :** `/Backups` (ou votre dossier de sauvegarde)
4. Cliquez sur **"Sauvegarder"**

## 🎯 Utilisation

### Navigation
- **Panneau gauche :** Vos fichiers locaux
- **Panneau droit :** Vos fichiers pCloud
- Cliquez sur les dossiers pour naviguer

### Synchronisation
1. **Sélectionnez** les fichiers ou **dossiers complets** (cases à cocher)
2. **Choisissez l'action :**
   - 🔄 **Sync vers pCloud** : Synchronisation exacte (supprime les fichiers en trop)
   - 📋 **Copier vers pCloud** : Copie simple (garde tout) - **Recommandé pour les dossiers**
   - ⬇️ **Sync depuis pCloud** : Récupère depuis pCloud
   - 🗑️ **Supprimer de pCloud** : Supprime définitivement les éléments sélectionnés

### 🆕 Sauvegarde de Dossiers Complets
- ✅ **Sélectionnez un dossier** avec sa case à cocher
- ✅ **Tout le contenu** est sauvegardé automatiquement
- ✅ **Structure préservée** (tous les sous-dossiers)
- ✅ **Gestion automatique** des gros dossiers

### 🗑️ Suppression sur pCloud
- 🔴 **Bouton rouge individuel** à côté de chaque élément
- 📋 **Suppression multiple** via sélection + bouton "Supprimer de pCloud"
- ⚠️ **IRRÉVERSIBLE** : Double confirmation pour éviter les erreurs
- 🗂️ **Dossiers** : Tout le contenu est supprimé définitivement
- 🛡️ **Sécurité** : Le serveur local n'est jamais affecté

## 🛠️ Commandes Utiles

```bash
# Redémarrer l'application
make restart

# Voir les logs
docker logs pcloud-app

# Accéder au container
docker exec -it pcloud-app bash

# Tester rclone
docker exec pcloud-app rclone lsd pcloud:
```

## 🎨 Fonctionnalités de l'Interface

- ✨ **Design moderne** avec Bootstrap 5
- 📱 **Responsive** (mobile et desktop)
- 🔄 **Actualisation automatique**
- 📊 **Indicateurs de progression**
- 🗂️ **Navigation intuitive** avec fils d'Ariane
- ⚙️ **Configuration centralisée**

## 🔍 Dépannage Rapide

| Problème | Solution |
|----------|----------|
| Page ne charge pas | Vérifiez que le container est démarré : `docker ps` |
| Erreur rclone | Configurez rclone : `docker exec -it pcloud-app rclone config` |
| Pas de fichiers | Vérifiez les chemins dans la configuration |
| Erreur de permissions | Ajustez les permissions du dossier local |

## 🎉 Vous êtes prêt !

Votre gestionnaire de sauvegardes pCloud est maintenant opérationnel. 

**Profitez de votre nouvelle interface moderne pour gérer vos sauvegardes facilement !** 