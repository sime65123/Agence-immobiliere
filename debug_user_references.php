<?php
require_once 'config/database.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$pdo = getDbConnection();

// ID de l'utilisateur à vérifier
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

echo "<h2>Diagnostic pour l'utilisateur ID: $user_id</h2>";
echo "<p>Nom: " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</p>";
echo "<p>Email: " . htmlspecialchars($user['email']) . "</p>";
echo "<p>Rôle: " . htmlspecialchars($user['role']) . "</p>";

// Obtenir toutes les tables de la base de données
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Références à l'utilisateur dans la base de données</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table</th><th>Colonne</th><th>Nombre de références</th><th>Détails</th></tr>";

$references_found = false;

foreach ($tables as $table) {
    // Ignorer la table users elle-même
    if ($table === 'users') {
        continue;
    }
    
    // Obtenir les colonnes de la table
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Vérifier chaque colonne qui pourrait référencer un utilisateur
    foreach ($columns as $column) {
        // Vérifier les colonnes qui pourraient contenir des IDs d'utilisateur
        if (strpos($column, 'id_') === 0 || 
            strpos($column, 'user_') === 0 || 
            $column === 'id_client' || 
            $column === 'id_expediteur' || 
            $column === 'id_destinataire' || 
            $column === 'id_proprietaire' || 
            $column === 'id_agent' || 
            $column === 'user_id') {
            
            // Compter les références
            $sql = "SELECT COUNT(*) FROM `$table` WHERE `$column` = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $references_found = true;
                
                // Obtenir quelques exemples
                $sql = "SELECT * FROM `$table` WHERE `$column` = :user_id LIMIT 3";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $examples_str = '';
                foreach ($examples as $example) {
                    $examples_str .= '<pre>' . print_r($example, true) . '</pre>';
                }
                
                echo "<tr>";
                echo "<td>$table</td>";
                echo "<td>$column</td>";
                echo "<td>$count</td>";
                echo "<td>$examples_str</td>";
                echo "</tr>";
            }
        }
    }
}

echo "</table>";

if (!$references_found) {
    echo "<p>Aucune référence trouvée à cet utilisateur dans d'autres tables.</p>";
    
    // Test de suppression manuelle
    echo "<h3>Test de suppression manuelle</h3>";
    
    try {
        $pdo->beginTransaction();
        
        // Supprimer les réservations
        $sql = "DELETE FROM reservations WHERE id_client = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $result1 = $stmt->execute();
        echo "<p>Suppression des réservations: " . ($result1 ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")") . "</p>";
        
        // Supprimer les messages
        $sql = "DELETE FROM messages WHERE id_expediteur = :id OR id_destinataire = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $result2 = $stmt->execute();
        echo "<p>Suppression des messages: " . ($result2 ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")") . "</p>";
        
        // Supprimer l'utilisateur
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $result3 = $stmt->execute();
        echo "<p>Suppression de l'utilisateur: " . ($result3 ? "Réussi" : "Échec (" . implode(", ", $stmt->errorInfo()) . ")") . "</p>";
        
        if ($result1 && $result2 && $result3) {
            $pdo->commit();
            echo "<p style='color:green;font-weight:bold;'>Suppression manuelle réussie!</p>";
        } else {
            $pdo->rollBack();
            echo "<p style='color:red;font-weight:bold;'>Échec de la suppression manuelle. Transaction annulée.</p>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p style='color:red;font-weight:bold;'>Exception: " . $e->getMessage() . "</p>";
    }
}

// Bouton pour revenir à la liste des utilisateurs
echo "<p><a href='/fred/admin/utilisateurs.php' class='btn btn-primary'>Retour à la liste des utilisateurs</a></p>";
?>
