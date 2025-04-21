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
$id_categorie = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$categorie = [];
$mode = 'ajout';

if ($id_categorie > 0) {
    $categorie = getCategorieById($pdo, $id_categorie);
    $mode = 'edition';
    
    if (empty($categorie)) {
        redirect('/fred/admin/categories.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom de la catégorie est obligatoire.";
    }
    
    if (empty($errors)) {
        $data = [
            'nom' => $nom,
            'description' => $description
        ];
        
        if ($mode === 'ajout') {
            // Ajouter une nouvelle catégorie
            if (addCategorie($pdo, $data)) {
                $success_message = "La catégorie a été ajoutée avec succès.";
                // Réinitialiser les champs
                $nom = $description = '';
            } else {
                $error_message = "Une erreur est survenue lors de l'ajout de la catégorie.";
            }
        } else {
            // Modifier une catégorie existante
            if (updateCategorie($pdo, $id_categorie, $data)) {
                $success_message = "La catégorie a été mise à jour avec succès.";
                $categorie = getCategorieById($pdo, $id_categorie); // Rafraîchir les données
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour de la catégorie.";
            }
        }
    }
}

// Gestion de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_to_delete = (int)$_GET['id'];
    
    // Vérifier si la catégorie est utilisée par des propriétés
    $count_properties = countPropertiesByCategorie($pdo, $id_to_delete);
    
    if ($count_properties > 0) {
        $error_message = "Impossible de supprimer cette catégorie car elle est utilisée par {$count_properties} propriété(s).";
    } else {
        // Supprimer la catégorie
        if (deleteCategorie($pdo, $id_to_delete)) {
            $success_message = "La catégorie a été supprimée avec succès.";
        } else {
            $error_message = "Une erreur est survenue lors de la suppression de la catégorie.";
        }
    }
}

// Récupération de toutes les catégories
$categories = getCategories($pdo);
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
                        <a href="/fred/admin/categories.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tags me-2"></i> Catégories
                        </a>
                        <a href="/fred/admin/caracteristiques.php" class="list-group-item list-group-item-action">
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
            <h1 class="h3 mb-4">Gestion des catégories</h1>
            
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
                                <?php echo $mode === 'ajout' ? 'Ajouter une catégorie' : 'Modifier la catégorie'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($nom) ? htmlspecialchars($nom) : (isset($categorie['nom']) ? htmlspecialchars($categorie['nom']) : ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : (isset($categorie['description']) ? htmlspecialchars($categorie['description']) : ''); ?></textarea>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> <?php echo $mode === 'ajout' ? 'Ajouter' : 'Enregistrer les modifications'; ?>
                                    </button>
                                    <?php if ($mode === 'edition'): ?>
                                    <a href="/fred/admin/categories.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i> Annuler
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Liste des catégories -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">Liste des catégories</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="ps-4">ID</th>
                                            <th scope="col">Nom</th>
                                            <th scope="col">Description</th>
                                            <th scope="col">Propriétés</th>
                                            <th scope="col" class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($categories)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">Aucune catégorie trouvée.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td class="ps-4"><?php echo $cat['id']; ?></td>
                                                <td><?php echo htmlspecialchars($cat['nom']); ?></td>
                                                <td>
                                                    <?php if (!empty($cat['description'])): ?>
                                                    <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($cat['description']); ?>">
                                                        <?php echo htmlspecialchars($cat['description']); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted fst-italic">Non renseigné</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $count = countPropertiesByCategorie($pdo, $cat['id']);
                                                    echo $count;
                                                    ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="btn-group">
                                                        <a href="/fred/admin/categories.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if (countPropertiesByCategorie($pdo, $cat['id']) == 0): ?>
                                                        <a href="/fred/admin/categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                        <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Cette catégorie ne peut pas être supprimée car elle est utilisée par des propriétés">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                        <?php endif; ?>
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
