# Changelog - Gestionnaire de Sauvegardes pCloud

## Version 2.0.1 - Interface SweetAlert (Dernière mise à jour)

### 🎨 Améliorations Interface

#### 💎 SweetAlert2 Intégré
- **Remplacement complet** des `alert()` et `confirm()` natifs par SweetAlert2
- **Confirmations élégantes** avec icônes, couleurs et animations
- **Double confirmations** visuellement distinctes pour les suppressions critiques
- **Styles personnalisés** harmonisés avec le thème Bootstrap de l'application
- **Timers automatiques** pour les messages de succès
- **Boutons inversés** pour une meilleure UX (focus sur Annuler par défaut)

#### 🔧 Améliorations Techniques
- Intégration via CDN de SweetAlert2 v11.7.32
- CSS personnalisé pour harmoniser avec le design existant
- Conversion de toutes les fonctions en `async/await` pour SweetAlert
- Configuration intelligente des couleurs selon le type d'action

---

## Version 2.0.0 - Fonctionnalités de Suppression

### 🆕 Nouvelles Fonctionnalités

#### 🗑️ Suppression sur pCloud
- **Suppression individuelle** : Bouton rouge 🗑️ à côté de chaque fichier/dossier pCloud
- **Suppression multiple** : Sélection multiple + bouton "Supprimer de pCloud"
- **Double confirmation** : Sécurité renforcée pour éviter les suppressions accidentelles
- **Gestion des dossiers complets** : Suppression récursive avec tout le contenu
- **Feedback utilisateur** : Messages de succès/erreur détaillés

#### 🔧 Améliorations Techniques
- Nouvelles routes API : `/backup/delete-from-cloud` et `/backup/delete-multiple-from-cloud`
- Méthodes `deleteFromCloud()` et `deleteMultipleFromCloud()` dans RcloneService
- Gestion intelligente : `rclone purge` pour dossiers, `rclone deletefile` pour fichiers
- Timeout adapté : 5 minutes pour les opérations de suppression
- Gestion d'erreurs robuste avec rapports détaillés

#### 🎨 Interface Utilisateur
- Boutons de suppression intégrés dans l'interface existante
- Réorganisation en 4 colonnes pour les actions principales
- Section d'aide mise à jour avec explications visuelles
- Messages de confirmation explicites avec emojis
- Indicateurs visuels clairs (couleurs, icônes)

### 🛡️ Sécurité

- **Confirmations multiples** : Double confirmation pour les dossiers
- **Messages explicites** : Avertissements clairs sur l'irréversibilité
- **Isolation** : Les suppressions n'affectent que pCloud, jamais le serveur local
- **Validation** : Vérification des paramètres côté serveur

### 📋 Actions Disponibles

1. **Suppression individuelle** : Clic sur le bouton rouge 🗑️
2. **Suppression multiple** : Sélection + bouton "Supprimer de pCloud"
3. **Navigation** : Clic sur la flèche → (inchangé)
4. **Sélection** : Cases à cocher (inchangé)

---

## Version 1.0.0 - Fonctionnalités de Base

### 🆕 Fonctionnalités Initiales

#### 📁 Sauvegarde de Dossiers Complets
- Sélection de dossiers entiers avec cases à cocher
- Sauvegarde récursive de tous les sous-dossiers
- Préservation de la structure d'arborescence
- Gestion automatique des gros dossiers

#### ☁️ Synchronisation
- **Sync vers pCloud** : Synchronisation exacte (supprime les fichiers en trop)
- **Copie vers pCloud** : Copie simple (conserve tous les fichiers)
- **Sync depuis pCloud** : Récupération depuis pCloud

#### 🗂️ Navigation
- Interface bi-directionnelle (local ↔ pCloud)
- Fils d'Ariane pour la navigation
- Affichage des métadonnées (taille, date)
- Tri automatique (dossiers puis fichiers)

#### ⚙️ Configuration
- Interface de configuration des chemins de base
- Support des variables d'environnement
- Validation en temps réel
- Test de configuration intégré

#### 🎨 Interface
- Design moderne avec Bootstrap 5
- Interface responsive (mobile + desktop)
- Indicateurs de progression
- Notifications toast
- Thème sobre et professionnel

---

## 🚀 Prochaines Améliorations Possibles

- [ ] Planification de sauvegardes automatiques
- [ ] Historique des opérations
- [ ] Compression avant sauvegarde
- [ ] Filtres avancés (par type, taille, date)
- [ ] Gestion des permissions
- [ ] Support multi-cloud (Dropbox, Google Drive)
- [ ] Interface en mode sombre
- [ ] API REST publique 