<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/fred/index.php');
}

// Initialisation des variables
$email = '';
$error = '';
$redirect = isset($_GET['redirect']) && $_GET['redirect'] === 'reservation';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password']; // Pas de nettoyage pour le mot de passe
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Connexion à la base de données
        $pdo = getDbConnection();
        
        // Authentification de l'utilisateur
        $user = authenticateUser($pdo, $email, $password);
        
        if ($user) {
            // Création de la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirection
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                redirect($redirect_url);
            } else {
                if ($user['role'] === 'admin') {
                    redirect('/fred/admin/dashboard.php');
                } else {
                    redirect('/fred/client/dashboard.php');
                }
            }
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h1 class="h3 mb-4 text-center">Connexion</h1>
                    
                    <?php if ($redirect): ?>
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i> Veuillez vous connecter pour réserver une visite.
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="login.php<?php echo $redirect ? '?redirect=reservation' : ''; ?>" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Se connecter</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Vous n'avez pas de compte ? <a href="register.php">Créer un compte</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
