-- Script d'insertion de données de test
USE agence_immobiliere;

-- Insertion d'utilisateurs
INSERT INTO users (nom, prenom, email, password, telephone, role) VALUES
('Admin', 'System', 'admin@realestate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin'), -- mot de passe: password
('Dupont', 'Jean', 'jean.dupont@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', 'client'), -- mot de passe: password
('Martin', 'Sophie', 'sophie.martin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0687654321', 'client'); -- mot de passe: password

-- Insertion de catu00e9gories
INSERT INTO categories (nom, description) VALUES
('Appartement', 'Logements en copropriété dans des immeubles collectifs'),
('Maison', 'Logements individuels avec terrain privé'),
('Villa', 'Maisons de luxe avec prestations haut de gamme'),
('Studio', 'Petits logements d\'une seule pièce'),
('Loft', 'Espaces ouverts généralement aménagés dans d\'anciens locaux industriels');

-- Insertion de caractéristiques
INSERT INTO caracteristiques (nom) VALUES
('Piscine'),
('Jardin'),
('Garage'),
('Balcon'),
('Terrasse'),
('Ascenseur'),
('Parking'),
('Climatisation'),
('Chauffage central'),
('Sécurité');

-- Insertion de propriétés
INSERT INTO proprietes (titre, description, prix, adresse, ville, code_postal, superficie, nb_chambres, nb_salles_bain, annee_construction, disponibilite, id_categorie, date_publication) VALUES
('Appartement moderne au centre-ville', 'Superbe appartement rénové avec vue imprenable sur la ville. Cuisine équipée, salon lumineux, deux chambres spacieuses.', 250000, '15 rue de la Paix', 'Paris', '75001', 75, 2, 1, 2010, 1, 1, NOW()),
('Maison familiale avec jardin', 'Belle maison familiale dans un quartier calme. Grand séjour, cuisine aménagée, quatre chambres, jardin arboré.', 350000, '8 allée des Chênes', 'Lyon', '69002', 120, 4, 2, 2005, 1, 2, NOW()),
('Studio pour étudiant', 'Studio fonctionnel proche des universités. Idéal pour étudiant ou investissement locatif.', 95000, '3 rue des Étudiants', 'Bordeaux', '33000', 30, 1, 1, 2015, 1, 4, NOW()),
('Villa de luxe avec piscine', 'Magnifique villa contemporaine avec piscine chauffée, jardin paysager, et prestations haut de gamme.', 750000, '25 avenue des Palmiers', 'Nice', '06000', 200, 5, 3, 2018, 1, 3, NOW()),
('Loft industriel rénové', 'Ancien atelier transformé en loft spacieux et lumineux. Volumes généreux, matériaux nobles, emplacement idéal.', 420000, '42 quai des Usines', 'Marseille', '13002', 150, 3, 2, 2012, 1, 5, NOW()),
('Appartement avec vue mer', 'Appartement en front de mer avec terrasse. Vue panoramique, accès direct à la plage, résidence sécurisée.', 380000, '10 promenade des Flots', 'Cannes', '06400', 85, 2, 2, 2008, 1, 1, NOW());

-- Insertion des images de propriétés
INSERT INTO images_proprietes (id_propriete, url_image, is_principale) VALUES
(1, 'assets/images/placeholder.html', 1),
(1, 'assets/images/placeholder.html', 0),
(1, 'assets/images/placeholder.html', 0),
(2, 'assets/images/placeholder.html', 1),
(2, 'assets/images/placeholder.html', 0),
(3, 'assets/images/placeholder.html', 1),
(4, 'assets/images/placeholder.html', 1),
(4, 'assets/images/placeholder.html', 0),
(5, 'assets/images/placeholder.html', 1),
(6, 'assets/images/placeholder.html', 1);

-- Liaison entre propriétés et caractéristiques
INSERT INTO proprietes_caracteristiques (id_propriete, id_caracteristique) VALUES
(1, 4), -- Appartement avec balcon
(1, 6), -- Appartement avec ascenseur
(2, 2), -- Maison avec jardin
(2, 3), -- Maison avec garage
(3, 8), -- Studio avec climatisation
(4, 1), -- Villa avec piscine
(4, 2), -- Villa avec jardin
(4, 7), -- Villa avec parking
(4, 8), -- Villa avec climatisation
(4, 10), -- Villa avec sécurité
(5, 5), -- Loft avec terrasse
(5, 9), -- Loft avec chauffage central
(6, 4), -- Appartement avec balcon
(6, 5), -- Appartement avec terrasse
(6, 8); -- Appartement avec climatisation

-- Insertion de réservations
INSERT INTO reservations (id_propriete, id_client, date_visite, commentaire, statut, date_creation) VALUES
(1, 2, DATE_ADD(NOW(), INTERVAL 2 DAY), 'Je souhaiterais visiter l\'appartement en fin de journée si possible.', 'confirmée', NOW()),
(3, 3, DATE_ADD(NOW(), INTERVAL 3 DAY), 'Intéressée par ce studio pour ma fille étudiante.', 'en_attente', NOW()),
(4, 2, DATE_ADD(NOW(), INTERVAL 5 DAY), 'Très intéressé par cette villa, plusieurs questions à poser lors de la visite.', 'confirmée', NOW()),
(2, 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Visite effectuée, en réflexion pour une offre.', 'confirmée', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Insertion de messages
INSERT INTO messages (id_expediteur, id_destinataire, sujet, contenu, id_propriete, date_envoi, lu) VALUES
(2, 1, 'Question sur l\'appartement du centre-ville', 'Bonjour, je souhaiterais savoir si le prix est négociable et si des travaux récents ont été effectués. Merci d\'avance pour votre réponse.', 1, NOW(), 0),
(1, 2, 'Réponse à votre demande', 'Bonjour, merci pour votre intérêt. Le prix peut être discuté et la cuisine a été entièrement refaite il y a 6 mois. N\'hésitez pas si vous avez d\'autres questions.', 1, NOW(), 1),
(3, 1, 'Disponibilité du studio', 'Bonjour, le studio est-il toujours disponible ? Ma fille commence ses études en septembre et nous cherchons activement. Cordialement.', 3, NOW(), 0);
