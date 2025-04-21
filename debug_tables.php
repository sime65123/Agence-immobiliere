<?php
require_once 'config/database.php';

$pdo = getDbConnection();

try {
    // Obtenir la liste des tables
    $stmt = $pdo->query("SHOW TABLES");
    echo "<h2>Tables dans la base de données</h2>";
    echo "<pre>";
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
    echo "</pre>";
    
    // Vérifier si la table utilisateurs existe
    $tableUtilisateurs = in_array('utilisateurs', $tables);
    $tableUsers = in_array('users', $tables);
    
    echo "<p>Table 'utilisateurs' existe: " . ($tableUtilisateurs ? 'Oui' : 'Non') . "</p>";
    echo "<p>Table 'users' existe: " . ($tableUsers ? 'Oui' : 'Non') . "</p>";
    
    // Si la table utilisateurs existe, afficher sa structure
    if ($tableUtilisateurs) {
        $stmt = $pdo->query("DESCRIBE utilisateurs");
        echo "<h2>Structure de la table 'utilisateurs'</h2>";
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    }
    
    // Si la table users existe, afficher sa structure
    if ($tableUsers) {
        $stmt = $pdo->query("DESCRIBE users");
        echo "<h2>Structure de la table 'users'</h2>";
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
