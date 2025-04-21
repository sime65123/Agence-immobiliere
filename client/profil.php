<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Vérifier si l'utilisateur est un client (non admin)
if ($_SESSION['user_role'] === 'admin') {
    redirect('../admin/dashboard.php');
}

// Connexion à la base de données
$pdo = getDbConnection();

// Récupérer les informations de l'utilisateur
$user = getUserById($pdo, $_SESSION['user_id']);

// Initialisation des variables
$success_message = '';
$error_message = '';
$password_error = '';

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Récupération et nettoyage des données
    $nom = cleanInput($_POST['nom']);
    $prenom = cleanInput($_POST['prenom']);
    $telephone = cleanInput($_POST['telephone']);
    
    // Validation basique
    if (empty($nom) || empty($prenom)) {
        $error_message = "Les champs Nom et Prénom sont obligatoires.";
    } else {
        // Préparation des données pour la mise à jour
        $user_data = [
            'id' => $_SESSION['user_id'],
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone
        ];
        
        // Mise à jour du profil
        $result = updateUserProfile($pdo, $user_data);
        
        if ($result) {
            $success_message = "Votre profil a été mis à jour avec succès.";
            
            // Mettre à jour les informations de session
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            
            // Rafraîchir les informations de l'utilisateur
            $user = getUserById($pdo, $_SESSION['user_id']);
        } else {
            $error_message = "Une erreur est survenue lors de la mise à jour de votre profil.";
        }
    }
}

// Traitement du formulaire de changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password']; // Pas de nettoyage pour le mot de passe
    $new_password = $_POST['new_password']; // Pas de nettoyage pour le mot de passe
    $confirm_password = $_POST['confirm_password']; // Pas de nettoyage pour le mot de passe
    
    // Validation basique
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Tous les champs sont obligatoires.";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (strlen($new_password) < 8) {
        $password_error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier le mot de passe actuel
        if (password_verify($current_password, $user['password'])) {
            // Hashage du nouveau mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Mise à jour du mot de passe
            $result = updateUserPassword($pdo, $_SESSION['user_id'], $hashed_password);
            
            if ($result) {
                $success_message = "Votre mot de passe a été mis à jour avec succès.";
            } else {
                $password_error = "Une erreur est survenue lors de la mise à jour de votre mot de passe.";
            }
        } else {
            $password_error = "Le mot de passe actuel est incorrect.";
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Tableau de bord
                        </a>
                        <a href="reservations.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                        </a>
                        <a href="profil.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-user-edit me-2"></i> Mon profil
                        </a>
                        <a href="messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                        <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Informations personnelles -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Informations personnelles</h5>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="profil.php" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            <div class="form-text">L'adresse email ne peut pas être modifiée. Contactez l'administrateur pour tout changement.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="date_inscription" class="form-label">Date d'inscription</label>
                            <input type="text" class="form-control" id="date_inscription" value="<?php echo (new DateTime($user['date_inscription']))->format('d/m/Y'); ?>" readonly>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Enregistrer les modifications
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Changement de mot de passe -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Changer mon mot de passe</h5>
                    
                    <?php if ($password_error): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $password_error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="profil.php" method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">8 caractères minimum</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key me-2"></i> Changer mon mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
