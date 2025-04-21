<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Connexion à la base de données
$pdo = getDbConnection();

// Traitement du formulaire de contact
$success_message = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $sujet = isset($_POST['sujet']) ? trim($_POST['sujet']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire.";
    }
    
    if (empty($email)) {
        $errors[] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'email n'est pas valide.";
    }
    
    if (empty($sujet)) {
        $errors[] = "Le sujet est obligatoire.";
    }
    
    if (empty($message)) {
        $errors[] = "Le message est obligatoire.";
    }
    
    // Si pas d'erreurs, enregistrement du message dans la base de données
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (nom, email, telephone, sujet, message, date_envoi, lu) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
            $result = $stmt->execute([$nom, $email, $telephone, $sujet, $message]);
            
            if ($result) {
                $success_message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
                // Réinitialisation des champs du formulaire
                $nom = $email = $telephone = $sujet = $message = '';
            } else {
                $error_message = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
            }
        } catch (PDOException $e) {
            $error_message = "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.";
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-3">Contactez-nous</h1>
            <p class="lead text-muted">Nous sommes à votre disposition pour répondre à toutes vos questions</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4">Nos coordonnées</h2>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-map-marker-alt text-white"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h3 class="h5 mb-1">Adresse</h3>
                            <p class="text-muted mb-0">123 Avenue des Champs-Élysées<br>75008 Paris, France</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-phone-alt text-white"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h3 class="h5 mb-1">Téléphone</h3>
                            <p class="text-muted mb-0">+33 1 23 45 67 89</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-envelope text-white"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h3 class="h5 mb-1">Email</h3>
                            <p class="text-muted mb-0">contact@realestate.com</p>
                        </div>
                    </div>
                    
                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-clock text-white"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h3 class="h5 mb-1">Horaires d'ouverture</h3>
                            <p class="text-muted mb-0">Lundi - Vendredi: 9h00 - 18h00<br>Samedi: 10h00 - 16h00<br>Dimanche: Fermé</p>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h2 class="h4 mb-3">Suivez-nous</h2>
                    <div class="d-flex">
                        <a href="#" class="btn btn-outline-primary me-2" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary me-2" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary me-2" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-primary" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 mb-4">Envoyez-nous un message</h2>
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom complet *</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo isset($telephone) ? htmlspecialchars($telephone) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="sujet" class="form-label">Sujet *</label>
                                <select class="form-select" id="sujet" name="sujet" required>
                                    <option value="" selected disabled>Choisissez un sujet</option>
                                    <option value="Information générale" <?php echo (isset($sujet) && $sujet === 'Information générale') ? 'selected' : ''; ?>>Information générale</option>
                                    <option value="Achat" <?php echo (isset($sujet) && $sujet === 'Achat') ? 'selected' : ''; ?>>Achat</option>
                                    <option value="Vente" <?php echo (isset($sujet) && $sujet === 'Vente') ? 'selected' : ''; ?>>Vente</option>
                                    <option value="Location" <?php echo (isset($sujet) && $sujet === 'Location') ? 'selected' : ''; ?>>Location</option>
                                    <option value="Estimation" <?php echo (isset($sujet) && $sujet === 'Estimation') ? 'selected' : ''; ?>>Estimation</option>
                                    <option value="Autre" <?php echo (isset($sujet) && $sujet === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">Envoyer le message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
