<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isLoggedIn() || !isAdmin()) {
    redirect('/fred/login.php');
}

$pdo = getDbConnection();
$error_message = '';
$success_message = '';

// Vérifier si un ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/fred/admin/utilisateurs.php');
}

$id = (int) $_GET['id'];
$utilisateur = getUserById($pdo, $id);

// Vérifier si l'utilisateur existe
if (!$utilisateur) {
    redirect('/fred/admin/utilisateurs.php');
}

// Empêcher la modification de son propre compte (pour éviter de se retirer les droits admin)
if ($utilisateur['id'] == $_SESSION['user_id']) {
    $error_message = 'Vous ne pouvez pas modifier votre propre compte depuis cette interface.';
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message)) {
    $nom = cleanInput($_POST['nom'] ?? '');
    $prenom = cleanInput($_POST['prenom'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $telephone = cleanInput($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = cleanInput($_POST['role'] ?? 'client');
    
    // Validation des champs
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error_message = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error_message = 'Les mots de passe ne correspondent pas.';
    } elseif ($email !== $utilisateur['email'] && emailExists($pdo, $email)) {
        $error_message = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
    } else {
        // Mise à jour de l'utilisateur
        $user_data = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone,
            'role' => $role
        ];
        
        // Ajouter le mot de passe uniquement s'il est fourni
        if (!empty($password)) {
            $user_data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $success = updateUser($pdo, $id, $user_data);
        
        if ($success) {
            $success_message = "L'utilisateur a été mis à jour avec succès.";
            // Mettre à jour les données affichées
            $utilisateur = getUserById($pdo, $id);
        } else {
            $error_message = "Une erreur est survenue lors de la mise à jour de l'utilisateur.";
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <h5 class="card-title mb-3">Administration</h5>
                    <div class="list-group list-group-flush">
                        <a href="/fred/admin/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                        </a>
                        <a href="/fred/admin/proprietes.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Propriétés
                        </a>
                        <a href="/fred/admin/reservations.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i> Réservations
                        </a>
                        <a href="/fred/admin/utilisateurs.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-users me-2"></i> Utilisateurs
                        </a>
                        <a href="/fred/admin/messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                        <a href="/fred/admin/categories.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tags me-2"></i> Catégories
                        </a>
                        <a href="/fred/admin/caracteristiques.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-list-ul me-2"></i> Caractéristiques
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenu principal -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Modifier l'utilisateur</h1>
                <a href="/fred/admin/utilisateurs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Retour à la liste
                </a>
            </div>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Laisser vide pour conserver l'actuel">
                                <small class="form-text text-muted">Laissez vide pour ne pas modifier le mot de passe actuel.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle</label>
                            <select class="form-select" id="role" name="role">
                                <option value="client" <?php echo ($utilisateur['role'] === 'client') ? 'selected' : ''; ?>>Client</option>
                                <option value="admin" <?php echo ($utilisateur['role'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="/fred/admin/utilisateurs.php" class="btn btn-light">Annuler</a>
                            <button type="submit" class="btn btn-primary" <?php echo ($utilisateur['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
