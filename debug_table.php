<?php
require_once 'config/database.php';

$pdo = getDbConnection();
$tableName = 'caracteristiques';

try {
    // Obtenir la structure de la table
    $stmt = $pdo->query("DESCRIBE $tableName");
    echo "<h2>Structure de la table $tableName</h2>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
    // Obtenir quelques données
    $stmt = $pdo->query("SELECT * FROM $tableName LIMIT 5");
    echo "<h2>Données de la table $tableName (max 5 lignes)</h2>";
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
