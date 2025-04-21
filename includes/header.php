<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est un administrateur
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Fonction pour rediriger vers une page
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real Estate - Agence Immobilière</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a980e88be8.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/fred/assets/css/style.css">
</head>
<body>
    <header class="sticky-top">
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand logo" href="/fred/index.php">Real Estate</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/index.php">Accueil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/proprietes.php">Propriétés</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/services.php">Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/contact.php">Contact</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <?php if (isLoggedIn()): ?>
                            <div class="dropdown">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_nom']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if (isAdmin()): ?>
                                        <li><a class="dropdown-item" href="/fred/admin/dashboard.php">Tableau de bord</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="/fred/client/dashboard.php">Mon compte</a></li>
                                        <li><a class="dropdown-item" href="/fred/client/reservations.php">Mes réservations</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    <li><a class="dropdown-item" href="/fred/logout.php">Déconnexion</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/fred/login.php" class="btn btn-outline-primary me-2">Connexion</a>
                            <a href="/fred/register.php" class="btn btn-primary">Inscription</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <main class="container py-4">
