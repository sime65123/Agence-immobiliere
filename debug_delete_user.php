<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$pdo = getDbConnection();

// ID de l'utilisateur à tester (à modifier selon vos besoins)
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    echo "<p>Veuillez spécifier un ID d'utilisateur valide dans l'URL (ex: ?id=2)</p>";
    exit;
}

// Vérifier si l'utilisateur existe
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p>Aucun utilisateur trouvé avec l'ID: $user_id</p>";
    exit;
}

echo "<h2>Test de suppression de l'utilisateur ID: $user_id</h2>";
echo "<p>Nom: " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</p>";
echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
echo "<p>Rôle: " . htmlspecialchars($user['role']) . "</p>";

// Vérifier les dépendances
echo "<h3>Vérification des dépendances</h3>";

// Réservations
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE id_client = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$reservations_count = $stmt->fetchColumn();
echo "<p>Réservations associées: $reservations_count</p>";

// Messages
$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE id_expediteur = :id OR id_destinataire = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$messages_count = $stmt->fetchColumn();
echo "<p>Messages associés: $messages_count</p>";

// Propriétés
$stmt = $pdo->prepare("SELECT COUNT(*) FROM proprietes WHERE id_agent = :id");
$stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$properties_count = $stmt->fetchColumn();
echo "<p>Propriétés associées (en tant qu'agent): $properties_count</p>";

// Tester chaque étape de la suppression séparément
echo "<h3>Test des étapes de suppression</h3>";

try {
    // 1. Supprimer les réservations
    echo "<p>1. Suppression des réservations: ";
    $sql = "DELETE FROM reservations WHERE id_client = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    echo $result ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")";
    echo "</p>";
    
    // 2. Supprimer les messages
    echo "<p>2. Suppression des messages: ";
    $sql = "DELETE FROM messages WHERE id_expediteur = :id OR id_destinataire = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    echo $result ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")";
    echo "</p>";
    
    // 3. Vérifier si l'utilisateur a des propriétés
    if ($properties_count > 0) {
        echo "<p style='color:red;'>Impossible de supprimer l'utilisateur car il a des propriétés associées.</p>";
    } else {
        // 4. Supprimer l'utilisateur
        echo "<p>4. Suppression de l'utilisateur: ";
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $result = $stmt->execute();
        echo $result ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")";
        echo "</p>";
        
        if ($result) {
            echo "<p style='color:green;'>L'utilisateur a été supprimé avec succès!</p>";
        } else {
            echo "<p style='color:red;'>Échec de la suppression de l'utilisateur.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Exception: " . $e->getMessage() . "</p>";
}

// Bouton pour revenir à la liste des utilisateurs
echo "<p><a href='/fred/admin/utilisateurs.php' class='btn btn-primary'>Retour à la liste des utilisateurs</a></p>";
?>
