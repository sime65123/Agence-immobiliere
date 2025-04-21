<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    redirect('/fred/index.php');
}

// Initialisation des variables
$nom = '';
$prenom = '';
$email = '';
$telephone = '';
$error = '';
$success = false;

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $nom = cleanInput($_POST['nom']);
    $prenom = cleanInput($_POST['prenom']);
    $email = cleanInput($_POST['email']);
    $telephone = cleanInput($_POST['telephone']);
    $password = $_POST['password']; // Pas de nettoyage pour le mot de passe
    $password_confirm = $_POST['password_confirm']; // Pas de nettoyage pour le mot de passe
    
    // Validation basique
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } elseif ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Connexion à la base de données
        $pdo = getDbConnection();
        
        // Vérifier si l'email existe déjà
        if (emailExists($pdo, $email)) {
            $error = "Cet email est déjà utilisé. Veuillez en choisir un autre ou vous connecter.";
        } else {
            // Hashage du mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Préparation des données pour l'insertion
            $user_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'password' => $hashed_password,
                'telephone' => $telephone,
                'role' => 'client' // Par défaut, tous les nouveaux utilisateurs sont des clients
            ];
            
            // Création de l'utilisateur
            $result = createUser($pdo, $user_data);
            
            if ($result) {
                $success = true;
                
                // Réinitialisation des champs
                $nom = '';
                $prenom = '';
                $email = '';
                $telephone = '';
            } else {
                $error = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h1 class="h3 mb-4 text-center">Créer un compte</h1>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle me-2"></i> Votre compte a été créé avec succès ! <a href="login.php" class="alert-link">Connectez-vous maintenant</a>.
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger mb-4">
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$success): ?>
                    <form action="register.php" method="post">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($telephone); ?>">
                            <div class="form-text">Optionnel, mais recommandé pour faciliter les contacts concernant vos réservations.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">8 caractères minimum</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">J'accepte les <a href="#">conditions d'utilisation</a> et la <a href="#">politique de confidentialité</a> *</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">S'inscrire</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <div class="text-center mt-4">
                        <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
