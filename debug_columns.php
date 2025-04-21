<?php
require_once 'config/database.php';

$pdo = getDbConnection();
$tableName = 'proprietes';

try {
    echo "<h2>Colonnes de la table $tableName</h2>";
    echo "<ul>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM $tableName");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li><strong>" . $row['Field'] . "</strong> (" . $row['Type'] . ")</li>";
    }
    
    echo "</ul>";
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
