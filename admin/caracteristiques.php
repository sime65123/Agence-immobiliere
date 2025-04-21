<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/fred/login.php');
}

// Vérifier si l'utilisateur est un administrateur
if ($_SESSION['user_role'] !== 'admin') {
    redirect('/fred/index.php');
}

// Connexion à la base de données
$pdo = getDbConnection();

// Traitement du formulaire d'ajout/modification
$id_caracteristique = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$caracteristique = [];
$mode = 'ajout';

if ($id_caracteristique > 0) {
    $caracteristique = getCaracteristiqueById($pdo, $id_caracteristique);
    $mode = 'edition';
    
    if (empty($caracteristique)) {
        redirect('/fred/admin/caracteristiques.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom de la caractéristique est obligatoire.";
    }
    
    if (empty($errors)) {
        $data = [
            'nom' => $nom
        ];
        
        if ($mode === 'ajout') {
            // Ajouter une nouvelle caractéristique
            if (addCaracteristique($pdo, $data)) {
                $success_message = "La caractéristique a été ajoutée avec succès.";
                // Réinitialiser les champs
                $nom = '';
            } else {
                $error_message = "Une erreur est survenue lors de l'ajout de la caractéristique.";
            }
        } else {
            // Modifier une caractéristique existante
            if (updateCaracteristique($pdo, $id_caracteristique, $data)) {
                $success_message = "La caractéristique a été mise à jour avec succès.";
                $caracteristique = getCaracteristiqueById($pdo, $id_caracteristique); // Rafraîchir les données
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour de la caractéristique.";
            }
        }
    }
}

// Gestion de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    
    // Vérifier si la caractéristique est utilisée par des propriétés
    $count_properties = countPropertiesByCaracteristique($pdo, $id_to_delete);
    
    if ($count_properties > 0) {
        $error_message = "Impossible de supprimer cette caractéristique car elle est utilisée par {$count_properties} propriété(s).";
    } else {
        // Supprimer la caractéristique
        if (deleteCaracteristique($pdo, $id_to_delete)) {
            $success_message = "La caractéristique a été supprimée avec succès.";
        } else {
            $error_message = "Une erreur est survenue lors de la suppression de la caractéristique.";
        }
    }
}

// Récupération de toutes les caractéristiques
$caracteristiques = getCaracteristiques($pdo);

// Liste d'icônes FontAwesome pour le sélecteur
$fontawesome_icons = [
    'fa-bath', 'fa-bed', 'fa-car', 'fa-wifi', 'fa-swimming-pool', 'fa-tv',
    'fa-snowflake', 'fa-utensils', 'fa-dumbbell', 'fa-hot-tub', 'fa-parking',
    'fa-wheelchair', 'fa-baby', 'fa-paw', 'fa-smoking-ban', 'fa-coffee',
    'fa-concierge-bell', 'fa-door-closed', 'fa-couch', 'fa-chair', 'fa-shower'
];
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user-shield fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0">Administration</h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></p>
                        </div>
                    </div>
                    
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
                        <a href="/fred/admin/utilisateurs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Utilisateurs
                        </a>
                        <a href="/fred/admin/messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                        <a href="/fred/admin/categories.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tags me-2"></i> Catégories
                        </a>
                        <a href="/fred/admin/caracteristiques.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-list-ul me-2"></i> Caractéristiques
                        </a>
                        <a href="/fred/index.php" class="list-group-item list-group-item-action text-primary">
                            <i class="fas fa-eye me-2"></i> Voir le site
                        </a>
                        <a href="/fred/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <h1 class="h3 mb-4">Gestion des caractéristiques</h1>
            
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Formulaire d'ajout/modification -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <?php echo $mode === 'ajout' ? 'Ajouter une caractéristique' : 'Modifier la caractéristique'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($nom) ? htmlspecialchars($nom) : (isset($caracteristique['nom']) ? htmlspecialchars($caracteristique['nom']) : ''); ?>" required>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> <?php echo $mode === 'ajout' ? 'Ajouter' : 'Enregistrer les modifications'; ?>
                                    </button>
                                    <?php if ($mode === 'edition'): ?>
                                    <a href="/fred/admin/caracteristiques.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i> Annuler
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des caractéristiques -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Liste des caractéristiques</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="ps-4">ID</th>
                                            <th scope="col">Nom</th>
                                            <th scope="col">Propriétés</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($caracteristiques)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">Aucune caractéristique trouvée.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($caracteristiques as $carac): ?>
                                            <tr>
                                                <td class="ps-4"><?php echo $carac['id']; ?></td>
                                                <td><?php echo htmlspecialchars($carac['nom']); ?></td>
                                                <td>
                                                    <?php 
                                                    $count = countPropertiesByCaracteristique($pdo, $carac['id']);
                                                    echo $count;
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/fred/admin/caracteristiques.php?id=<?php echo $carac['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $carac['id']; ?>" <?php echo countPropertiesByCaracteristique($pdo, $carac['id']) > 0 ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Modal de confirmation de suppression -->
                                                    <div class="modal fade" id="deleteModal<?php echo $carac['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $carac['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="deleteModalLabel<?php echo $carac['id']; ?>">Confirmation de suppression</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    Êtes-vous sûr de vouloir supprimer la caractéristique "<?php echo htmlspecialchars($carac['nom']); ?>" ?
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <a href="/fred/admin/caracteristiques.php?action=delete&id=<?php echo $carac['id']; ?>" class="btn btn-danger">Supprimer</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
