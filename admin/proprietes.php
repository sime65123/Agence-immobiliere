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

// Gestion de la suppression d'une propriété
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_propriete = (int)$_GET['id'];
    
    // Supprimer la propriété
    if (deletePropriete($pdo, $id_propriete)) {
        $success_message = "La propriété a été supprimée avec succès.";
    } else {
        $error_message = "Une erreur est survenue lors de la suppression de la propriété.";
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$filters = [];
if (isset($_GET['categorie']) && !empty($_GET['categorie'])) {
    $filters['categorie'] = (int)$_GET['categorie'];
}

if (isset($_GET['disponibilite']) && $_GET['disponibilite'] !== '') {
    $filters['disponibilite'] = (int)$_GET['disponibilite'];
}

// Récupération des propriétés avec pagination
$proprietes = getProprietes($pdo, $filters, $limit, $offset);
$total_proprietes = countProprietes($pdo, $filters);
$total_pages = ceil($total_proprietes / $limit);

// Récupération des catégories pour le filtre
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
                        <div class="flex-grow-1 ms-3" style="min-width: 0;">
                            <h5 class="mb-0 text-truncate">Admin</h5>
                            <p class="text-muted mb-0 text-truncate" title="<?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?>"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="/fred/admin/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                        </a>
                        <a href="/fred/admin/proprietes.php" class="list-group-item list-group-item-action active">
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Gestion des propriétés</h1>
                <a href="/fred/admin/propriete_add.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Ajouter une propriété
                </a>
            </div>
            
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
            
            <!-- Filtres -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-4">
                            <label for="categorie" class="form-label">Catégorie</label>
                            <select name="categorie" id="categorie" class="form-select">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id']; ?>" <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == $categorie['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="disponibilite" class="form-label">Disponibilité</label>
                            <select name="disponibilite" id="disponibilite" class="form-select">
                                <option value="">Toutes</option>
                                <option value="1" <?php echo (isset($_GET['disponibilite']) && $_GET['disponibilite'] == '1') ? 'selected' : ''; ?>>Disponible</option>
                                <option value="0" <?php echo (isset($_GET['disponibilite']) && $_GET['disponibilite'] == '0') ? 'selected' : ''; ?>>Non disponible</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i> Filtrer
                            </button>
                            <a href="/fred/admin/proprietes.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des propriétés -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">ID</th>
                                    <th scope="col">Image</th>
                                    <th scope="col">Titre</th>
                                    <th scope="col">Catégorie</th>
                                    <th scope="col">Ville</th>
                                    <th scope="col">Prix</th>
                                    <th scope="col">Disponibilité</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($proprietes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">Aucune propriété trouvée.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($proprietes as $propriete): ?>
                                    <tr>
                                        <td class="ps-4"><?php echo $propriete['id']; ?></td>
                                        <td>
                                            <?php if (!empty($propriete['image_principale'])): ?>
                                            <img src="<?php echo htmlspecialchars($propriete['image_principale']); ?>" alt="<?php echo htmlspecialchars($propriete['titre']); ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php else: ?>
                                            <img src="/fred/assets/images/no-image.jpg" alt="Pas d'image" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($propriete['titre']); ?></td>
                                        <td><?php echo htmlspecialchars($propriete['categorie_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($propriete['ville']); ?></td>
                                        <td><?php echo formatPrix($propriete['prix']); ?></td>
                                        <td>
                                            <?php if ($propriete['disponibilite']): ?>
                                            <span class="badge bg-success">Disponible</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/fred/admin/propriete_edit.php?id=<?php echo $propriete['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/fred/propriete.php?id=<?php echo $propriete['id']; ?>" class="btn btn-sm btn-outline-info" title="Voir" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $propriete['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <div class="modal fade" id="deleteModal<?php echo $propriete['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $propriete['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $propriete['id']; ?>">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer la propriété "<?php echo htmlspecialchars($propriete['titre']); ?>" ?
                                                            <br><br>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i> Cette action est irréversible et supprimera également toutes les réservations associées à cette propriété.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <a href="/fred/admin/proprietes.php?action=delete&id=<?php echo $propriete['id']; ?>" class="btn btn-danger">Supprimer</a>
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
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Pagination des propriétés" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/fred/admin/proprietes.php?page=<?php echo $page - 1; ?><?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="/fred/admin/proprietes.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/fred/admin/proprietes.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Suivant">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
