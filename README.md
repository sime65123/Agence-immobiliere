# Real Estate - Agence Immobilière

Une application web de gestion d'agence immobilière permettant de présenter des propriétés, gérer des réservations de visites et faciliter la communication entre clients et agents immobiliers.

## Table des matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Structure du projet](#structure-du-projet)
- [Technologies utilisées](#technologies-utilisées)

## Aperçu

Cette application web permet à une agence immobilière de présenter son catalogue de propriétés, de gérer les réservations de visites et d'interagir avec les clients. Elle dispose d'une interface d'administration pour les agents immobiliers et d'un espace client pour les utilisateurs.

## Fonctionnalités

- **Catalogue de propriétés** : Affichage et recherche de biens immobiliers
- **Système de réservation** : Prise de rendez-vous pour visiter les propriétés
- **Messagerie interne** : Communication entre clients et agents
- **Espace client** : Gestion des réservations et du profil utilisateur
- **Interface d'administration** : Gestion complète des propriétés, utilisateurs, réservations et messages

## Prérequis

- [XAMPP](https://www.apachefriends.org/fr/index.html) (version 7.4 ou supérieure) incluant :
  - PHP 7.4+
  - MySQL 5.7+
  - Apache
- Navigateur web moderne (Chrome, Firefox, Edge, Safari)

## Installation

1. **Cloner ou télécharger le projet** dans le répertoire `htdocs` de XAMPP :
   ```
   cd C:/xampp/htdocs/
   git clone https://github.com/sime65123/Agence-immobiliere.git
   ```
   Ou décompressez l'archive du projet dans `C:/xampp/htdocs/fred/`

2. **Démarrer les services XAMPP** :
   - Lancez le panneau de contrôle XAMPP
   - Démarrez les services Apache et MySQL

3. **Créer la base de données** :
   - Ouvrez phpMyAdmin à l'adresse http://localhost/phpmyadmin
   - Importez le fichier `database/db_schema.sql` pour créer la structure de la base de données
   - Importez le fichier `database/seed_data.sql` pour ajouter des données de test

## Configuration

1. **Configuration de la base de données** :
   - Ouvrez le fichier `config/database.php`
   - Modifiez les constantes suivantes selon votre configuration :
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'agence_immobiliere');
     define('DB_USER', 'root'); // À modifier selon votre configuration
     define('DB_PASS', ''); // À modifier selon votre configuration
     ```

2. **Configuration des chemins** (si nécessaire) :
   - Si votre projet n'est pas installé dans le dossier `fred`, vous devrez modifier les chemins dans les fichiers PHP

## Utilisation

1. **Accéder à l'application** :
   - Ouvrez votre navigateur et accédez à http://localhost/fred/

2. **Comptes utilisateurs** :
   - **Administrateur** :
     - Email : admin@realestate.com
     - Mot de passe : password
   - **Client** :
     - Email : jean.dupont@example.com
     - Mot de passe : password

3. **Interface publique** :
   - Parcourir les propriétés
   - Effectuer des recherches
   - Consulter les détails des propriétés
   - S'inscrire ou se connecter

4. **Espace client** :
   - Gérer son profil
   - Consulter et gérer ses réservations
   - Communiquer avec l'agence

5. **Interface d'administration** :
   - Gérer les propriétés (ajout, modification, suppression)
   - Gérer les utilisateurs
   - Gérer les réservations
   - Gérer les messages
   - Consulter les statistiques

## Structure du projet

```
fred/
├── admin/                  # Interface d'administration
├── assets/                 # Ressources statiques (images, CSS, JS)
├── client/                 # Espace client
├── config/                 # Configuration de l'application
├── database/               # Scripts SQL
├── includes/               # Fichiers inclus (header, footer, fonctions)
├── index.php               # Page d'accueil
├── login.php               # Page de connexion
├── logout.php              # Déconnexion
├── propriete.php           # Détail d'une propriété
├── proprietes.php          # Liste des propriétés
├── register.php            # Inscription
└── services.php            # Page des services
```

## Technologies utilisées

- **Backend** : PHP
- **Base de données** : MySQL
- **Frontend** : HTML, CSS, JavaScript
- **Framework CSS** : Bootstrap 5
- **Icônes** : Font Awesome
- **Serveur web** : Apache (via XAMPP)
