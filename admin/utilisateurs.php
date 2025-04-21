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

// Gestion de la suppression d'un utilisateur
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_utilisateur = (int)$_GET['id'];
    
    // Vérifier que l'utilisateur n'est pas en train de se supprimer lui-même
    if ($id_utilisateur === (int)$_SESSION['user_id']) {
        $error_message = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        // Mode de débogage direct
        try {
            // Désactiver temporairement les contraintes de clé étrangère
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Supprimer les réservations
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id_client = ?");
            $stmt->execute([$id_utilisateur]);
            
            // Supprimer les messages
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id_expediteur = ? OR id_destinataire = ?");
            $stmt->execute([$id_utilisateur, $id_utilisateur]);
            
            // Supprimer l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id_utilisateur]);
            
            if ($result && $stmt->rowCount() > 0) {
                // Valider la transaction
                $pdo->commit();
                
                // Réactiver les contraintes
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                
                $success_message = "L'utilisateur a été supprimé avec succès.";
            } else {
                // Annuler la transaction
                $pdo->rollBack();
                
                // Réactiver les contraintes
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                
                $error_message = "Aucun utilisateur n'a été supprimé. L'utilisateur n'existe peut-être plus.";
            }
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            // Réactiver les contraintes
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            $error_message = "Erreur lors de la suppression: " . $e->getMessage();
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$filters = [];

if (isset($_GET['role']) && !empty($_GET['role'])) {
    $filters['role'] = $_GET['role'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Récupération des utilisateurs avec pagination
$utilisateurs = getUsers($pdo, $filters, $limit, $offset);
$total_utilisateurs = countUsers($pdo, $filters);
$total_pages = ceil($total_utilisateurs / $limit);
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
                <h1 class="h3 mb-0">Gestion des utilisateurs</h1>
                <a href="/fred/admin/utilisateur_add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i> Ajouter un utilisateur
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
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Nom, prénom, email..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="role" class="form-label">Rôle</label>
                            <select name="role" id="role" class="form-select">
                                <option value="">Tous les rôles</option>
                                <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                                <option value="client" <?php echo (isset($_GET['role']) && $_GET['role'] == 'client') ? 'selected' : ''; ?>>Client</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i> Filtrer
                            </button>
                            <a href="/fred/admin/utilisateurs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des utilisateurs -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">ID</th>
                                    <th scope="col">Nom</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Téléphone</th>
                                    <th scope="col">Rôle</th>
                                    <th scope="col">Date d'inscription</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($utilisateurs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Aucun utilisateur trouvé.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($utilisateurs as $utilisateur): ?>
                                    <tr>
                                        <td class="ps-4"><?php echo $utilisateur['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($utilisateur['email']); ?></td>
                                        <td><?php echo htmlspecialchars($utilisateur['telephone']); ?></td>
                                        <td>
                                            <?php if ($utilisateur['role'] == 'admin'): ?>
                                            <span class="badge bg-primary">Administrateur</span>
                                            <?php else: ?>
                                            <span class="badge bg-info">Client</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($utilisateur['date_inscription']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/fred/admin/utilisateur_edit.php?id=<?php echo $utilisateur['id']; ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ((int)$utilisateur['id'] !== (int)$_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Supprimer" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $utilisateur['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Modal de confirmation de suppression -->
                                            <?php if ((int)$utilisateur['id'] !== (int)$_SESSION['user_id']): ?>
                                            <div class="modal fade" id="deleteModal<?php echo $utilisateur['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $utilisateur['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $utilisateur['id']; ?>">Confirmation de suppression</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Êtes-vous sûr de vouloir supprimer l'utilisateur "<?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>" ?
                                                            <br><br>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i> Cette action est irréversible et supprimera également toutes les réservations associées à cet utilisateur.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <a href="/fred/admin/utilisateurs.php?action=delete&id=<?php echo $utilisateur['id']; ?>" class="btn btn-danger">Supprimer</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
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
            <nav aria-label="Pagination des utilisateurs" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/fred/admin/utilisateurs.php?page=<?php echo $page - 1; ?><?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="/fred/admin/utilisateurs.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="/fred/admin/utilisateurs.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Suivant">
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
