<?php
/**
 * Configuration de la connexion u00e0 la base de donnu00e9es
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'agence_immobiliere');
define('DB_USER', 'root'); // u00c0 modifier selon votre configuration
define('DB_PASS', ''); // u00c0 modifier selon votre configuration

/**
 * u00c9tablit une connexion u00e0 la base de donnu00e9es
 * @return PDO Instance de connexion PDO
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En production, il faudrait logger l'erreur plutu00f4t que de l'afficher
        die("Erreur de connexion u00e0 la base de donnu00e9es: " . $e->getMessage());
    }
}
