-- Base de données pour l'agence immobilière

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS agence_immobiliere;
USE agence_immobiliere;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    role ENUM('client', 'admin') DEFAULT 'client',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME,
    UNIQUE (email)
);

-- Table des catégories de propriétés
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- Table des propriétés
CREATE TABLE IF NOT EXISTS proprietes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10, 2) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    code_postal VARCHAR(20) NOT NULL,
    pays VARCHAR(100) DEFAULT 'France',
    superficie DECIMAL(10, 2),
    nb_chambres INT,
    nb_salles_bain INT,
    annee_construction INT,
    disponibilite BOOLEAN DEFAULT TRUE,
    est_vedette BOOLEAN DEFAULT FALSE,
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_categorie INT,
    FOREIGN KEY (id_categorie) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table des images de propriétés
CREATE TABLE IF NOT EXISTS images_proprietes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_propriete INT NOT NULL,
    url_image VARCHAR(255) NOT NULL,
    est_principale BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_propriete) REFERENCES proprietes(id) ON DELETE CASCADE
);

-- Table des caractéristiques
CREATE TABLE IF NOT EXISTS caracteristiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
);

-- Table de liaison entre propriétés et caractéristiques
CREATE TABLE IF NOT EXISTS proprietes_caracteristiques (
    id_propriete INT NOT NULL,
    id_caracteristique INT NOT NULL,
    PRIMARY KEY (id_propriete, id_caracteristique),
    FOREIGN KEY (id_propriete) REFERENCES proprietes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_caracteristique) REFERENCES caracteristiques(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_propriete INT NOT NULL,
    id_client INT NOT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_visite DATETIME NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'annulee', 'terminee') DEFAULT 'en_attente',
    commentaire TEXT,
    FOREIGN KEY (id_propriete) REFERENCES proprietes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_client) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_expediteur INT,
    id_destinataire INT,
    sujet VARCHAR(255) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_expediteur) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (id_destinataire) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertion de quelques catégories de base
INSERT INTO categories (nom, description) VALUES 
('Appartement', 'Logements en copropriété'),
('Maison', 'Maisons individuelles'),
('Villa', 'Grandes maisons luxueuses'),
('Terrain', 'Terrains constructibles'),
('Local commercial', 'Espaces pour commerces et bureaux');

