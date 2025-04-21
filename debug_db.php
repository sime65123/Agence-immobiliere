<?php
require_once 'config/database.php';

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
$pdo = getDbConnection();

// Obtenir toutes les tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Diagnostic de la base de données</h1>";

// Afficher les tables
echo "<h2>Tables dans la base de données</h2>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// Vérifier les contraintes de clé étrangère
echo "<h2>Contraintes de clé étrangère</h2>";
$sql = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
        ORDER BY TABLE_NAME, COLUMN_NAME";

try {
    $stmt = $pdo->query($sql);
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($constraints) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Table</th><th>Colonne</th><th>Contrainte</th><th>Table référencée</th><th>Colonne référencée</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . $constraint['TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['COLUMN_NAME'] . "</td>";
            echo "<td>" . $constraint['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $constraint['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucune contrainte de clé étrangère trouvée dans la base de données.</p>";
    }
} catch (PDOException $e) {
    echo "<p>Erreur lors de la recherche des contraintes: " . $e->getMessage() . "</p>";
}

// Vérifier les colonnes qui pourraient référencer des utilisateurs
echo "<h2>Colonnes qui pourraient référencer des utilisateurs</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Table</th><th>Colonne</th><th>Type</th></tr>";

foreach ($tables as $table) {
    // Obtenir les colonnes de la table
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        // Vérifier les colonnes qui pourraient contenir des IDs d'utilisateur
        if (strpos($column['Field'], 'id_') === 0 || 
            strpos($column['Field'], 'user_') === 0 || 
            $column['Field'] === 'id_client' || 
            $column['Field'] === 'id_expediteur' || 
            $column['Field'] === 'id_destinataire' || 
            $column['Field'] === 'id_proprietaire' || 
            $column['Field'] === 'id_agent' || 
            $column['Field'] === 'user_id') {
            
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "</tr>";
        }
    }
}

echo "</table>";

// Test de suppression d'un utilisateur
$user_id = isset($_GET['test_delete']) ? (int)$_GET['test_delete'] : 0;

if ($user_id > 0) {
    echo "<h2>Test de suppression de l'utilisateur ID: $user_id</h2>";
    
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<p>Aucun utilisateur trouvé avec l'ID: $user_id</p>";
        } else {
            echo "<p>Utilisateur: " . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . "</p>";
            
            // Activer le mode debug pour PDO
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $pdo->beginTransaction();
            
            // Supprimer les réservations
            $sql = "DELETE FROM reservations WHERE id_client = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            echo "<p>Réservations supprimées.</p>";
            
            // Supprimer les messages
            $sql = "DELETE FROM messages WHERE id_expediteur = :id OR id_destinataire = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            echo "<p>Messages supprimés.</p>";
            
            // Supprimer l'utilisateur
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            echo "<p>Utilisateur supprimé.</p>";
            
            $pdo->commit();
            echo "<p style='color:green;font-weight:bold;'>Suppression réussie!</p>";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p style='color:red;font-weight:bold;'>Erreur: " . $e->getMessage() . "</p>";
    }
}

// Lien pour tester la suppression
echo "<h2>Tester la suppression d'un utilisateur</h2>";
echo "<p>Cliquez sur un ID pour tester la suppression:</p>";
echo "<ul>";
foreach ([2, 4, 5] as $id) {
    echo "<li><a href='?test_delete=$id'>Tester la suppression de l'utilisateur ID $id</a></li>";
}
echo "</ul>";

// Bouton pour revenir à la liste des utilisateurs
echo "<p><a href='/fred/admin/utilisateurs.php' style='display:inline-block; padding:10px 15px; background-color:#007bff; color:white; text-decoration:none; border-radius:5px;'>Retour à la liste des utilisateurs</a></p>";
?>
