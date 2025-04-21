<?php
require_once 'includes/header.php';

// Destruction de la session
session_unset();
session_destroy();

// Redirection vers la page d'accueil
redirect('index.php');
?>
