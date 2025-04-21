<?php
/**
 * Fonctions utilitaires pour l'application
 */

/**
 * Nettoie les données d'entrée
 * @param string $data Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Récupère toutes les propriétés avec filtrage optionnel
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @param int $limit Nombre maximum de résultats (optionnel)
 * @param int $offset Décalage pour la pagination (optionnel)
 * @return array Liste des propriétés
 */
function getProprietes($pdo, $filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT p.*, c.nom as categorie_nom, 
           (SELECT url_image FROM images_proprietes WHERE id_propriete = p.id AND est_principale = 1 LIMIT 1) as image_principale
           FROM proprietes p
           LEFT JOIN categories c ON p.id_categorie = c.id
           WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['categorie'])) {
        $sql .= " AND p.id_categorie = :categorie";
        $params[':categorie'] = $filters['categorie'];
    }
    
    if (!empty($filters['ville'])) {
        $sql .= " AND p.ville LIKE :ville";
        $params[':ville'] = '%' . $filters['ville'] . '%';
    }
    
    if (!empty($filters['prix_min'])) {
        $sql .= " AND p.prix >= :prix_min";
        $params[':prix_min'] = $filters['prix_min'];
    }
    
    if (!empty($filters['prix_max'])) {
        $sql .= " AND p.prix <= :prix_max";
        $params[':prix_max'] = $filters['prix_max'];
    }
    
    if (!empty($filters['nb_chambres_min'])) {
        $sql .= " AND p.nb_chambres >= :nb_chambres_min";
        $params[':nb_chambres_min'] = $filters['nb_chambres_min'];
    }
    
    if (isset($filters['disponibilite'])) {
        $sql .= " AND p.disponibilite = :disponibilite";
        $params[':disponibilite'] = $filters['disponibilite'];
    }
    
    if (isset($filters['est_vedette'])) {
        $sql .= " AND p.est_vedette = :est_vedette";
        $params[':est_vedette'] = $filters['est_vedette'];
    }
    
    // Tri
    $sql .= " ORDER BY " . (!empty($filters['tri']) ? $filters['tri'] : "p.date_publication DESC");
    
    // Pagination
    if ($limit !== null) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        if ($key == ':offset' || $key == ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère une propriété par son ID
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la propriété
 * @return array|false Données de la propriété ou false si non trouvée
 */
function getProprieteById($pdo, $id) {
    $sql = "SELECT p.*, c.nom as categorie_nom 
           FROM proprietes p
           LEFT JOIN categories c ON p.id_categorie = c.id
           WHERE p.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Récupère les images d'une propriété
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_propriete ID de la propriété
 * @return array Liste des images
 */
function getImagesPropriete($pdo, $id_propriete) {
    $sql = "SELECT * FROM images_proprietes WHERE id_propriete = :id_propriete ORDER BY est_principale DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère les caractéristiques d'une propriété
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_propriete ID de la propriété
 * @return array Liste des caractéristiques
 */
function getCaracteristiquesPropriete($pdo, $id_propriete) {
    $sql = "SELECT c.* 
           FROM caracteristiques c
           JOIN proprietes_caracteristiques pc ON c.id = pc.id_caracteristique
           WHERE pc.id_propriete = :id_propriete";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les catégories
 * @param PDO $pdo Connexion à la base de données
 * @return array Liste des catégories
 */
function getCategories($pdo) {
    $sql = "SELECT * FROM categories ORDER BY nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les caractéristiques
 * @param PDO $pdo Connexion à la base de données
 * @return array Liste des caractéristiques
 */
function getCaracteristiques($pdo) {
    $sql = "SELECT * FROM caracteristiques ORDER BY nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Crée une nouvelle réservation
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données de la réservation
 * @return int|false ID de la réservation créée ou false en cas d'échec
 */
function createReservation($pdo, $data) {
    $sql = "INSERT INTO reservations (id_propriete, id_client, date_visite, commentaire) 
           VALUES (:id_propriete, :id_client, :date_visite, :commentaire)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_propriete', $data['id_propriete'], PDO::PARAM_INT);
    $stmt->bindParam(':id_client', $data['id_client'], PDO::PARAM_INT);
    $stmt->bindParam(':date_visite', $data['date_visite']);
    $stmt->bindParam(':commentaire', $data['commentaire']);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Récupère les réservations d'un client
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_client ID du client
 * @return array Liste des réservations
 */
function getReservationsClient($pdo, $id_client) {
    $sql = "SELECT r.*, p.titre as propriete_titre, p.adresse, p.ville 
           FROM reservations r
           JOIN proprietes p ON r.id_propriete = p.id
           WHERE r.id_client = :id_client
           ORDER BY r.date_visite DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_client', $id_client, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les réservations (pour l'admin)
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres optionnels
 * @return array Liste des réservations
 */
function getAllReservations($pdo, $filters = []) {
    $sql = "SELECT r.*, p.titre as propriete_titre, u.nom as client_nom, u.prenom as client_prenom 
           FROM reservations r
           JOIN proprietes p ON r.id_propriete = p.id
           JOIN users u ON r.id_client = u.id
           WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['statut'])) {
        $sql .= " AND r.statut = :statut";
        $params[':statut'] = $filters['statut'];
    }
    
    if (!empty($filters['date_debut'])) {
        $sql .= " AND r.date_visite >= :date_debut";
        $params[':date_debut'] = $filters['date_debut'];
    }
    
    if (!empty($filters['date_fin'])) {
        $sql .= " AND r.date_visite <= :date_fin";
        $params[':date_fin'] = $filters['date_fin'];
    }
    
    $sql .= " ORDER BY r.date_visite DESC";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère les réservations avec filtrage optionnel
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @param int $limit Nombre maximum de résultats (optionnel)
 * @param int $offset Décalage pour la pagination (optionnel)
 * @return array Liste des réservations
 */
function getReservations($pdo, $filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT r.*, p.titre as titre_propriete, p.adresse, p.ville, p.code_postal,
           u.prenom as client_prenom, u.nom as client_nom, u.email as client_email, u.telephone as client_telephone
           FROM reservations r
           JOIN proprietes p ON r.id_propriete = p.id
           JOIN users u ON r.id_client = u.id
           WHERE 1=1 ";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['id_client'])) {
        $sql .= " AND r.id_client = :id_client";
        $params[':id_client'] = $filters['id_client'];
    }
    
    if (!empty($filters['id_propriete'])) {
        $sql .= " AND r.id_propriete = :id_propriete";
        $params[':id_propriete'] = $filters['id_propriete'];
    }
    
    if (!empty($filters['statut'])) {
        $sql .= " AND r.statut = :statut";
        $params[':statut'] = $filters['statut'];
    }
    
    if (!empty($filters['date_debut'])) {
        $sql .= " AND r.date_visite >= :date_debut";
        $params[':date_debut'] = $filters['date_debut'];
    }
    
    if (!empty($filters['date_fin'])) {
        $sql .= " AND r.date_visite <= :date_fin";
        $params[':date_fin'] = $filters['date_fin'];
    }
    
    // Tri
    $sql .= " ORDER BY " . (!empty($filters['tri']) ? $filters['tri'] : "r.date_reservation DESC");
    
    // Pagination
    if ($limit !== null) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        if ($key == ':offset' || $key == ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Met à jour le statut d'une réservation
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la réservation
 * @param string $statut Nouveau statut
 * @return bool Succès ou échec
 */
function updateReservationStatus($pdo, $id, $statut) {
    $sql = "UPDATE reservations SET statut = :statut WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':statut', $statut);
    
    return $stmt->execute();
}

/**
 * Annule une réservation en changeant son statut
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_reservation ID de la réservation
 * @return bool Succès ou échec
 */
function cancelReservation($pdo, $id_reservation) {
    $sql = "UPDATE reservations SET statut = 'annulee' WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id_reservation, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Supprime une réservation
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la réservation à supprimer
 * @return bool Succès ou échec
 */
function deleteReservation($pdo, $id) {
    try {
        $sql = "DELETE FROM reservations WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans deleteReservation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un email existe déjà dans la base de données
 * @param PDO $pdo Connexion à la base de données
 * @param string $email Email à vérifier
 * @return bool True si l'email existe, false sinon
 */
function emailExists($pdo, $email) {
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Crée un nouvel utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données de l'utilisateur
 * @return int|false ID de l'utilisateur créé ou false en cas d'échec
 */
function createUser($pdo, $data) {
    $sql = "INSERT INTO users (nom, prenom, email, password, telephone, role) 
           VALUES (:nom, :prenom, :email, :password, :telephone, :role)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nom', $data['nom']);
    $stmt->bindParam(':prenom', $data['prenom']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $data['password']);
    $stmt->bindParam(':telephone', $data['telephone']);
    $stmt->bindParam(':role', $data['role']);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Authentifie un utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param string $email Email de l'utilisateur
 * @param string $password Mot de passe en clair
 * @return array|false Données de l'utilisateur ou false si échec
 */
function authenticateUser($pdo, $email, $password) {
    $sql = "SELECT * FROM users WHERE email = :email";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Mise à jour de la dernière connexion
        $updateSql = "UPDATE users SET derniere_connexion = NOW() WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
        $updateStmt->execute();
        
        return $user;
    }
    
    return false;
}

/**
 * Récupère un utilisateur par son ID
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function getUserById($pdo, $id) {
    $sql = "SELECT * FROM users WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Met à jour les informations d'un utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de l'utilisateur
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updateUser($pdo, $id, $data) {
    $sql = "UPDATE users SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, role = :role";
    
    // Si un nouveau mot de passe est fourni
    if (!empty($data['password'])) {
        $sql .= ", password = :password";
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nom', $data['nom']);
    $stmt->bindParam(':prenom', $data['prenom']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':telephone', $data['telephone']);
    $stmt->bindParam(':role', $data['role']);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    if (!empty($data['password'])) {
        $stmt->bindParam(':password', $data['password']);
    }
    
    return $stmt->execute();
}

/**
 * Compte le nombre total de propriétés avec filtres optionnels
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @return int Nombre de propriétés
 */
function countProprietes($pdo, $filters = []) {
    $sql = "SELECT COUNT(*) FROM proprietes p WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['categorie'])) {
        $sql .= " AND p.id_categorie = :categorie";
        $params[':categorie'] = $filters['categorie'];
    }
    
    if (!empty($filters['ville'])) {
        $sql .= " AND p.ville LIKE :ville";
        $params[':ville'] = '%' . $filters['ville'] . '%';
    }
    
    if (!empty($filters['prix_min'])) {
        $sql .= " AND p.prix >= :prix_min";
        $params[':prix_min'] = $filters['prix_min'];
    }
    
    if (!empty($filters['prix_max'])) {
        $sql .= " AND p.prix <= :prix_max";
        $params[':prix_max'] = $filters['prix_max'];
    }
    
    if (!empty($filters['nb_chambres_min'])) {
        $sql .= " AND p.nb_chambres >= :nb_chambres_min";
        $params[':nb_chambres_min'] = $filters['nb_chambres_min'];
    }
    
    if (isset($filters['disponibilite'])) {
        $sql .= " AND p.disponibilite = :disponibilite";
        $params[':disponibilite'] = $filters['disponibilite'];
    }
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchColumn();
}

/**
 * Récupère les villes distinctes des propriétés
 * @param PDO $pdo Connexion à la base de données
 * @return array Liste des villes
 */
function getVillesDistinctes($pdo) {
    $sql = "SELECT DISTINCT ville FROM proprietes ORDER BY ville";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Formate un prix en euros
 * @param float $prix Prix à formater
 * @return string Prix formaté
 */
function formatPrix($prix) {
    return number_format($prix, 0, ',', ' ') . ' €';
}

/**
 * Génère un slug à partir d'un texte
 * @param string $text Texte à convertir en slug
 * @return string Slug
 */
function generateSlug($text) {
    // Remplacer les caractères non alphanumériques par des tirets
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Translittérer
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Supprimer les caractères indésirables
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remplacer les tirets multiples
    $text = preg_replace('~-+~', '-', $text);
    // Convertir en minuscules
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Compte le nombre de réservations avec filtres optionnels
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @return int Nombre de réservations
 */
function countReservations($pdo, $filters = []) {
    $sql = "SELECT COUNT(*) FROM reservations WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['statut'])) {
        $sql .= " AND statut = :statut";
        $params[':statut'] = $filters['statut'];
    }
    
    if (!empty($filters['id_client'])) {
        $sql .= " AND id_client = :id_client";
        $params[':id_client'] = $filters['id_client'];
    }
    
    if (!empty($filters['id_propriete'])) {
        $sql .= " AND id_propriete = :id_propriete";
        $params[':id_propriete'] = $filters['id_propriete'];
    }
    
    if (!empty($filters['date_debut']) && !empty($filters['date_fin'])) {
        $sql .= " AND date_visite BETWEEN :date_debut AND :date_fin";
        $params[':date_debut'] = $filters['date_debut'];
        $params[':date_fin'] = $filters['date_fin'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Compte le nombre de réservations pour aujourd'hui
 * @param PDO $pdo Connexion à la base de données
 * @return int Nombre de réservations
 */
function countReservationsToday($pdo) {
    $sql = "SELECT COUNT(*) FROM reservations 
           WHERE DATE(date_visite) = CURDATE() 
           AND statut = 'confirmée'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    return (int) $stmt->fetchColumn();
}

/**
 * Compte le nombre d'utilisateurs avec filtres optionnels
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @return int Nombre d'utilisateurs
 */
function countUsers($pdo, $filters = []) {
    $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['role'])) {
        $sql .= " AND role = :role";
        $params[':role'] = $filters['role'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Compte le nombre de messages non lus pour un utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param int|null $id_utilisateur ID de l'utilisateur (null pour admin)
 * @param bool $is_admin Indique si c'est pour un admin
 * @return int Nombre de messages non lus
 */
function countUnreadMessages($pdo, $id_utilisateur = null, $is_admin = false) {
    if ($is_admin) {
        // Pour l'admin, compter tous les messages non lus envoyés par les clients
        $sql = "SELECT COUNT(*) FROM messages 
               WHERE id_destinataire IN (SELECT id FROM users WHERE role = 'admin') 
               AND lu = 0";
        $params = [];
    } else {
        // Pour un client, compter ses messages non lus
        $sql = "SELECT COUNT(*) FROM messages 
               WHERE id_destinataire = :id_utilisateur 
               AND lu = 0";
        $params = [':id_utilisateur' => $id_utilisateur];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return (int) $stmt->fetchColumn();
}

/**
 * Récupère les réservations récentes
 * @param PDO $pdo Connexion à la base de données
 * @param int $limit Nombre maximum de résultats
 * @param int|null $id_client ID du client (optionnel)
 * @return array Liste des réservations récentes
 */
function getRecentReservations($pdo, $limit = 5, $id_client = null) {
    $sql = "SELECT r.*, p.titre as titre_propriete, 
           u.nom as client_nom, u.prenom as client_prenom 
           FROM reservations r 
           JOIN proprietes p ON r.id_propriete = p.id 
           JOIN users u ON r.id_client = u.id 
           WHERE 1=1 ";
    $params = [];
    
    if ($id_client !== null) {
        $sql .= " AND r.id_client = :id_client";
        $params[':id_client'] = $id_client;
    }
    
    $sql .= " ORDER BY r.date_reservation DESC LIMIT :limit";
    $params[':limit'] = $limit;
    
    $stmt = $pdo->prepare($sql);
    
    // Bind des paramètres
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère les propriétés récemment ajoutées
 * @param PDO $pdo Connexion à la base de données
 * @param int $limit Nombre maximum de résultats
 * @return array Liste des propriétés récentes
 */
function getRecentProperties($pdo, $limit = 5) {
    $sql = "SELECT p.*, c.nom as categorie_nom, 
           (SELECT url_image FROM images_proprietes WHERE id_propriete = p.id AND est_principale = 1 LIMIT 1) as image_principale 
           FROM proprietes p 
           LEFT JOIN categories c ON p.id_categorie = c.id 
           ORDER BY p.date_publication DESC 
           LIMIT :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère les messages récents
 * @param PDO $pdo Connexion à la base de données
 * @param int $limit Nombre maximum de résultats
 * @param bool $is_admin Indique si c'est pour un admin
 * @param int|null $id_utilisateur ID de l'utilisateur (pour les clients)
 * @return array Liste des messages récents
 */
function getRecentMessages($pdo, $limit = 5, $is_admin = false, $id_utilisateur = null) {
    if ($is_admin) {
        // Pour l'admin, récupérer les messages envoyés aux admins
        $sql = "SELECT m.*, 
               exp.nom as expediteur_nom, exp.prenom as expediteur_prenom, 
               dest.nom as destinataire_nom, dest.prenom as destinataire_prenom 
               FROM messages m 
               JOIN users exp ON m.id_expediteur = exp.id 
               JOIN users dest ON m.id_destinataire = dest.id 
               WHERE m.id_destinataire IN (SELECT id FROM users WHERE role = 'admin') 
               ORDER BY m.date_envoi DESC 
               LIMIT :limit";
        $params = [':limit' => $limit];
    } else {
        // Pour un client, récupérer ses messages
        $sql = "SELECT m.*, 
               exp.nom as expediteur_nom, exp.prenom as expediteur_prenom, 
               dest.nom as destinataire_nom, dest.prenom as destinataire_prenom 
               FROM messages m 
               JOIN users exp ON m.id_expediteur = exp.id 
               JOIN users dest ON m.id_destinataire = dest.id 
               WHERE (m.id_expediteur = :id_utilisateur OR m.id_destinataire = :id_utilisateur) 
               ORDER BY m.date_envoi DESC 
               LIMIT :limit";
        $params = [':id_utilisateur' => $id_utilisateur, ':limit' => $limit];
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Bind des paramètres
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère tous les utilisateurs avec filtrage optionnel
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @param int $limit Nombre maximum de résultats (optionnel)
 * @param int $offset Décalage pour la pagination (optionnel)
 * @return array Liste des utilisateurs
 */
function getUsers($pdo, $filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (!empty($filters['role'])) {
        $sql .= " AND role = :role";
        $params[':role'] = $filters['role'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (nom LIKE :search OR prenom LIKE :search OR email LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    // Tri
    $sql .= " ORDER BY date_inscription DESC";
    
    // Pagination
    if ($limit !== null) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        if ($key == ':offset' || $key == ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Supprime un utilisateur et toutes ses données associées
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de l'utilisateur à supprimer
 * @return bool Succès ou échec
 */
function deleteUser($pdo, $id) {
    try {
        // Désactiver temporairement les contraintes de clé étrangère
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // Commencer une transaction pour assurer l'intégrité des données
        $pdo->beginTransaction();
        
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            error_log("Tentative de suppression d'un utilisateur inexistant (ID: $id)");
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); // Réactiver les contraintes
            return false;
        }
        
        // Supprimer d'abord les réservations associées
        $sql_reservations = "DELETE FROM reservations WHERE id_client = :id";
        $stmt_reservations = $pdo->prepare($sql_reservations);
        $stmt_reservations->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_reservations->execute();
        
        // Supprimer les messages associés
        $sql_messages = "DELETE FROM messages WHERE id_expediteur = :id OR id_destinataire = :id";
        $stmt_messages = $pdo->prepare($sql_messages);
        $stmt_messages->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt_messages->execute();
        
        // Enfin, supprimer l'utilisateur
        $sql_user = "DELETE FROM users WHERE id = :id";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->bindParam(':id', $id, PDO::PARAM_INT);
        $result_user = $stmt_user->execute();
        
        if (!$result_user) {
            error_log('Erreur lors de la suppression de l\'utilisateur: ' . json_encode($stmt_user->errorInfo()));
            $pdo->rollBack();
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); // Réactiver les contraintes
            return false;
        }
        
        // Valider la transaction
        $pdo->commit();
        
        // Réactiver les contraintes de clé étrangère
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        return true;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        
        // Réactiver les contraintes de clé étrangère
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        error_log('Exception dans deleteUser: ' . $e->getMessage());
        return false;
    }
}

/**
 * Formate une date au format français
 * @param string $date Date à formater
 * @return string Date formatée
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formate une date et heure au format français
 * @param string $datetime Date et heure à formater
 * @return string Date et heure formatées
 */
function formatDateTime($datetime) {
    return date('d/m/Y à H:i', strtotime($datetime));
}

/**
 * Récupère les messages avec filtrage optionnel
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @param int $limit Nombre maximum de résultats (optionnel)
 * @param int $offset Décalage pour la pagination (optionnel)
 * @return array Liste des messages
 */
function getMessages($pdo, $filters = [], $limit = null, $offset = 0) {
    $sql = "SELECT m.*, 
           u.prenom as expediteur_prenom, u.nom as expediteur_nom, u.email as email,
           u.telephone as telephone, m.contenu as message,
           m.date_envoi as date_creation
           FROM messages m
           LEFT JOIN users u ON m.id_expediteur = u.id
           WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (isset($filters['lu'])) {
        $sql .= " AND m.lu = :lu";
        $params[':lu'] = $filters['lu'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (m.sujet LIKE :search OR m.contenu LIKE :search OR u.nom LIKE :search OR u.prenom LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['id_destinataire'])) {
        $sql .= " AND m.id_destinataire = :id_destinataire";
        $params[':id_destinataire'] = $filters['id_destinataire'];
    }
    
    // Tri
    $sql .= " ORDER BY m.date_envoi DESC";
    
    // Pagination
    if ($limit !== null) {
        $sql .= " LIMIT :offset, :limit";
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        if ($key == ':offset' || $key == ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Marque un message comme lu
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID du message
 * @return bool Succès ou échec
 */
function markMessageAsRead($pdo, $id) {
    $sql = "UPDATE messages SET lu = 1 WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Marque tous les messages comme lus
 * @param PDO $pdo Connexion à la base de données
 * @param int|null $id_destinataire ID du destinataire (optionnel)
 * @return bool Succès ou échec
 */
function markAllMessagesAsRead($pdo, $id_destinataire = null) {
    $sql = "UPDATE messages SET lu = 1 WHERE lu = 0";
    $params = [];
    
    if ($id_destinataire !== null) {
        $sql .= " AND id_destinataire = :id_destinataire";
        $params[':id_destinataire'] = $id_destinataire;
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    return $stmt->execute();
}

/**
 * Supprime un message
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID du message à supprimer
 * @return bool Succès ou échec
 */
function deleteMessage($pdo, $id) {
    $sql = "DELETE FROM messages WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    
    return $stmt->execute();
}

/**
 * Compte le nombre de messages avec filtrage optionnel
 * @param PDO $pdo Connexion à la base de données
 * @param array $filters Filtres à appliquer (optionnel)
 * @return int Nombre de messages
 */
function countMessages($pdo, $filters = []) {
    $sql = "SELECT COUNT(*) FROM messages WHERE 1=1";
    $params = [];
    
    // Appliquer les filtres
    if (isset($filters['lu'])) {
        $sql .= " AND lu = :lu";
        $params[':lu'] = $filters['lu'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (m.sujet LIKE :search OR m.contenu LIKE :search)";
        $params[':search'] = '%' . $filters['search'] . '%';
    }
    
    if (!empty($filters['id_destinataire'])) {
        $sql .= " AND id_destinataire = :id_destinataire";
        $params[':id_destinataire'] = $filters['id_destinataire'];
    }
    
    $stmt = $pdo->prepare($sql);
    
    // Liaison des paramètres
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    return $stmt->fetchColumn();
}

/**
 * Compte le nombre de propriétés par catégorie
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_categorie ID de la catégorie
 * @return int Nombre de propriétés
 */
function countPropertiesByCategorie($pdo, $id_categorie) {
    $sql = "SELECT COUNT(*) FROM proprietes WHERE id_categorie = :id_categorie";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}

/**
 * Compte le nombre de propriétés par caractéristique
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_caracteristique ID de la caractéristique
 * @return int Nombre de propriétés
 */
function countPropertiesByCaracteristique($pdo, $id_caracteristique) {
    $sql = "SELECT COUNT(*) FROM proprietes_caracteristiques WHERE id_caracteristique = :id_caracteristique";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_caracteristique', $id_caracteristique, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchColumn();
}

/**
 * Récupère une réservation par son ID
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la réservation
 * @return array|false Données de la réservation ou false si non trouvée
 */
function getReservationById($pdo, $id) {
    $sql = "SELECT r.*, p.titre as titre_propriete, p.adresse, p.ville, p.code_postal, p.description,
           u.prenom as client_prenom, u.nom as client_nom, u.email as client_email, u.telephone as client_telephone
           FROM reservations r
           JOIN proprietes p ON r.id_propriete = p.id
           JOIN users u ON r.id_client = u.id
           WHERE r.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Récupère toutes les réservations d'un utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_client ID de l'utilisateur
 * @return array Liste des réservations
 */
function getUserReservations($pdo, $id_client) {
    $sql = "SELECT r.*, p.titre as titre_propriete, p.adresse, p.ville, p.code_postal, p.description,
           u.prenom as client_prenom, u.nom as client_nom, u.email as client_email, u.telephone as client_telephone
           FROM reservations r
           JOIN proprietes p ON r.id_propriete = p.id
           JOIN users u ON r.id_client = u.id
           WHERE r.id_client = :id_client
           ORDER BY r.date_reservation DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_client', $id_client, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Récupère tous les messages d'un utilisateur
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_utilisateur ID de l'utilisateur
 * @return array Liste des messages
 */
function getUserMessages($pdo, $id_utilisateur) {
    $sql = "SELECT m.*, 
           e.prenom as expediteur_prenom, e.nom as expediteur_nom,
           d.prenom as destinataire_prenom, d.nom as destinataire_nom
           FROM messages m
           LEFT JOIN users e ON m.id_expediteur = e.id
           LEFT JOIN users d ON m.id_destinataire = d.id
           WHERE m.id_expediteur = :id_expediteur OR m.id_destinataire = :id_destinataire
           ORDER BY m.date_envoi DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_expediteur', $id_utilisateur, PDO::PARAM_INT);
    $stmt->bindParam(':id_destinataire', $id_utilisateur, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Crée un nouveau message
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données du message
 * @return int|bool ID du message créé ou false en cas d'échec
 */
function createMessage($pdo, $data) {
    // Si le destinataire n'est pas spécifié, on envoie à l'administrateur
    if (empty($data['id_destinataire'])) {
        // Récupérer l'ID d'un administrateur
        $sql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
        $stmt = $pdo->query($sql);
        $admin = $stmt->fetch();
        
        if ($admin) {
            $data['id_destinataire'] = $admin['id'];
        } else {
            // Si aucun admin n'est trouvé, on ne peut pas envoyer le message
            return false;
        }
    }
    
    $sql = "INSERT INTO messages (id_expediteur, id_destinataire, sujet, contenu, date_envoi, lu) 
           VALUES (:id_expediteur, :id_destinataire, :sujet, :contenu, NOW(), 0)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_expediteur', $data['id_expediteur'], PDO::PARAM_INT);
    $stmt->bindParam(':id_destinataire', $data['id_destinataire'], PDO::PARAM_INT);
    $stmt->bindParam(':sujet', $data['sujet']);
    $stmt->bindParam(':contenu', $data['contenu']);
    
    if ($stmt->execute()) {
        return $pdo->lastInsertId();
    }
    
    return false;
}

/**
 * Récupère un message par son ID
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_message ID du message
 * @return array|bool Données du message ou false si non trouvé
 */
function getMessageById($pdo, $id_message) {
    $sql = "SELECT m.*, 
           e.prenom as expediteur_prenom, e.nom as expediteur_nom,
           d.prenom as destinataire_prenom, d.nom as destinataire_nom
           FROM messages m
           LEFT JOIN users e ON m.id_expediteur = e.id
           LEFT JOIN users d ON m.id_destinataire = d.id
           WHERE m.id = :id_message";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_message', $id_message, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch();
}

/**
 * Récupère des propriétés similaires à une propriété donnée
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_propriete ID de la propriété actuelle (à exclure des résultats)
 * @param int $id_categorie ID de la catégorie pour trouver des propriétés similaires
 * @param string $ville Ville pour trouver des propriétés similaires
 * @param int $limit Nombre maximum de propriétés à retourner
 * @return array Liste des propriétés similaires
 */
function getProprietesSimilaires($pdo, $id_propriete, $id_categorie, $ville, $limit = 3) {
    // Version ultra-simplifiée sans aucune jointure complexe
    $sql = "SELECT p.*, c.nom as categorie_nom
           FROM proprietes p
           LEFT JOIN categories c ON p.id_categorie = c.id
           WHERE p.id <> ?
           AND p.disponibilite = 1
           AND (p.id_categorie = ? OR p.ville = ?)
           ORDER BY p.date_publication DESC
           LIMIT ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_propriete, $id_categorie, $ville, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur dans getProprietesSimilaires: ' . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute une nouvelle propriété
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données de la propriété
 * @return int|false ID de la propriété créée ou false en cas d'échec
 */
function addPropriete($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        // Insertion de la propriété
        $sql = "INSERT INTO proprietes (
            titre, description, prix, adresse, ville, code_postal, pays,
            superficie, nb_chambres, nb_salles_bain, annee_construction,
            disponibilite, est_vedette, id_categorie
        ) VALUES (
            :titre, :description, :prix, :adresse, :ville, :code_postal, :pays,
            :superficie, :nb_chambres, :nb_salles_bain, :annee_construction,
            :disponibilite, :est_vedette, :id_categorie
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':titre', $data['titre'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':prix', $data['prix'], PDO::PARAM_STR);
        $stmt->bindParam(':adresse', $data['adresse'], PDO::PARAM_STR);
        $stmt->bindParam(':ville', $data['ville'], PDO::PARAM_STR);
        $stmt->bindParam(':code_postal', $data['code_postal'], PDO::PARAM_STR);
        $stmt->bindParam(':pays', $data['pays'], PDO::PARAM_STR);
        $stmt->bindParam(':superficie', $data['superficie'], PDO::PARAM_STR);
        $stmt->bindParam(':nb_chambres', $data['nb_chambres'], PDO::PARAM_INT);
        $stmt->bindParam(':nb_salles_bain', $data['nb_salles_bain'], PDO::PARAM_INT);
        $stmt->bindParam(':annee_construction', $data['annee_construction'], PDO::PARAM_INT);
        $stmt->bindParam(':disponibilite', $data['disponibilite'], PDO::PARAM_BOOL);
        $stmt->bindParam(':est_vedette', $data['est_vedette'], PDO::PARAM_BOOL);
        $stmt->bindParam(':id_categorie', $data['id_categorie'], PDO::PARAM_INT);
        
        $stmt->execute();
        $id_propriete = $pdo->lastInsertId();
        
        // Ajout des caractéristiques si présentes
        if (!empty($data['caracteristiques'])) {
            $sql = "INSERT INTO proprietes_caracteristiques (id_propriete, id_caracteristique) VALUES (:id_propriete, :id_caracteristique)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($data['caracteristiques'] as $id_caracteristique) {
                $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
                $stmt->bindParam(':id_caracteristique', $id_caracteristique, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        $pdo->commit();
        return $id_propriete;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur dans addPropriete: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour une propriété existante
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la propriété
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updatePropriete($pdo, $id, $data) {
    try {
        $pdo->beginTransaction();
        
        // Mise à jour de la propriété
        $sql = "UPDATE proprietes SET 
            titre = :titre,
            description = :description,
            prix = :prix,
            adresse = :adresse,
            ville = :ville,
            code_postal = :code_postal,
            pays = :pays,
            superficie = :superficie,
            nb_chambres = :nb_chambres,
            nb_salles_bain = :nb_salles_bain,
            annee_construction = :annee_construction,
            disponibilite = :disponibilite,
            est_vedette = :est_vedette,
            id_categorie = :id_categorie
        WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':titre', $data['titre'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':prix', $data['prix'], PDO::PARAM_STR);
        $stmt->bindParam(':adresse', $data['adresse'], PDO::PARAM_STR);
        $stmt->bindParam(':ville', $data['ville'], PDO::PARAM_STR);
        $stmt->bindParam(':code_postal', $data['code_postal'], PDO::PARAM_STR);
        $stmt->bindParam(':pays', $data['pays'], PDO::PARAM_STR);
        $stmt->bindParam(':superficie', $data['superficie'], PDO::PARAM_STR);
        $stmt->bindParam(':nb_chambres', $data['nb_chambres'], PDO::PARAM_INT);
        $stmt->bindParam(':nb_salles_bain', $data['nb_salles_bain'], PDO::PARAM_INT);
        $stmt->bindParam(':annee_construction', $data['annee_construction'], PDO::PARAM_INT);
        $stmt->bindParam(':disponibilite', $data['disponibilite'], PDO::PARAM_BOOL);
        $stmt->bindParam(':est_vedette', $data['est_vedette'], PDO::PARAM_BOOL);
        $stmt->bindParam(':id_categorie', $data['id_categorie'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Suppression des caractéristiques existantes
        $sql = "DELETE FROM proprietes_caracteristiques WHERE id_propriete = :id_propriete";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_propriete', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Ajout des nouvelles caractéristiques si présentes
        if (!empty($data['caracteristiques'])) {
            $sql = "INSERT INTO proprietes_caracteristiques (id_propriete, id_caracteristique) VALUES (:id_propriete, :id_caracteristique)";
            $stmt = $pdo->prepare($sql);
            
            foreach ($data['caracteristiques'] as $id_caracteristique) {
                $stmt->bindParam(':id_propriete', $id, PDO::PARAM_INT);
                $stmt->bindParam(':id_caracteristique', $id_caracteristique, PDO::PARAM_INT);
                $stmt->execute();
            }
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur dans updatePropriete: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une propriété
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la propriété à supprimer
 * @return bool Succès ou échec
 */
function deletePropriete($pdo, $id) {
    try {
        // Les contraintes de clé étrangère avec ON DELETE CASCADE s'occupent des relations
        $sql = "DELETE FROM proprietes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans deletePropriete: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute une image à une propriété
 * @param PDO $pdo Connexion à la base de données
 * @param int $id_propriete ID de la propriété
 * @param string $url_image URL de l'image
 * @param bool $est_principale Indique si c'est l'image principale
 * @return int|false ID de l'image ajoutée ou false en cas d'échec
 */
function addImagePropriete($pdo, $id_propriete, $url_image, $est_principale = false) {
    try {
        // Si c'est l'image principale, on réinitialise d'abord les autres
        if ($est_principale) {
            $sql = "UPDATE images_proprietes SET est_principale = 0 WHERE id_propriete = :id_propriete";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Ajout de la nouvelle image
        $sql = "INSERT INTO images_proprietes (id_propriete, url_image, est_principale) VALUES (:id_propriete, :url_image, :est_principale)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
        $stmt->bindParam(':url_image', $url_image, PDO::PARAM_STR);
        $stmt->bindParam(':est_principale', $est_principale, PDO::PARAM_BOOL);
        $stmt->execute();
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log('Erreur dans addImagePropriete: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute une nouvelle catégorie
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données de la catégorie
 * @return bool Succès ou échec
 */
function addCategorie($pdo, $data) {
    try {
        $sql = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans addCategorie: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour une catégorie existante
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la catégorie
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updateCategorie($pdo, $id, $data) {
    try {
        $sql = "UPDATE categories SET nom = :nom, description = :description WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans updateCategorie: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une catégorie
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la catégorie à supprimer
 * @return bool Succès ou échec
 */
function deleteCategorie($pdo, $id) {
    try {
        // Vérifier si la catégorie est utilisée par des propriétés
        $sql = "SELECT COUNT(*) FROM proprietes WHERE id_categorie = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // La catégorie est utilisée, ne pas supprimer
            return false;
        }
        
        // Supprimer la catégorie
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans deleteCategorie: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute une nouvelle caractéristique
 * @param PDO $pdo Connexion à la base de données
 * @param array $data Données de la caractéristique
 * @return bool Succès ou échec
 */
function addCaracteristique($pdo, $data) {
    try {
        $sql = "INSERT INTO caracteristiques (nom) VALUES (:nom)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        $result = $stmt->execute();
        
        // Débogage - Enregistrer les erreurs SQL
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log('Erreur SQL dans addCaracteristique: ' . $errorInfo[2]);
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Exception PDO dans addCaracteristique: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour une caractéristique existante
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la caractéristique
 * @param array $data Nouvelles données
 * @return bool Succès ou échec
 */
function updateCaracteristique($pdo, $id, $data) {
    try {
        $sql = "UPDATE caracteristiques SET nom = :nom WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nom', $data['nom'], PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans updateCaracteristique: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une caractéristique
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la caractéristique à supprimer
 * @return bool Succès ou échec
 */
function deleteCaracteristique($pdo, $id) {
    try {
        // Vérifier si la caractéristique est utilisée
        $sql = "SELECT COUNT(*) FROM proprietes_caracteristiques WHERE id_caracteristique = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // La caractéristique est utilisée, ne pas supprimer
            return false;
        }
        
        // Supprimer la caractéristique
        $sql = "DELETE FROM caracteristiques WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('Erreur dans deleteCaracteristique: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère une caractéristique par son ID
 * @param PDO $pdo Connexion à la base de données
 * @param int $id ID de la caractéristique
 * @return array|false Données de la caractéristique ou false si non trouvée
 */
function getCaracteristiqueById($pdo, $id) {
    try {
        $sql = "SELECT * FROM caracteristiques WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur dans getCaracteristiqueById: ' . $e->getMessage());
        return false;
    }
}
