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

// Gestion des actions (confirmation, annulation, etc.)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_reservation = (int)$_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'confirm':
            if (updateReservationStatus($pdo, $id_reservation, 'confirmee')) {
                $success_message = "La réservation a été confirmée avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la confirmation de la réservation.";
            }
            break;
            
        case 'cancel':
            if (updateReservationStatus($pdo, $id_reservation, 'annulee')) {
                $success_message = "La réservation a été annulée avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de l'annulation de la réservation.";
            }
            break;
            
        case 'delete':
            if (deleteReservation($pdo, $id_reservation)) {
                $success_message = "La réservation a été supprimée avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la suppression de la réservation.";
            }
            break;
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$filters = [];

if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $filters['statut'] = $_GET['statut'];
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $filters['date_debut'] = $_GET['date_debut'];
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $filters['date_fin'] = $_GET['date_fin'];
}

// Récupération des réservations avec pagination
$reservations = getReservations($pdo, $filters, $limit, $offset);
$total_reservations = countReservations($pdo, $filters);
$total_pages = ceil($total_reservations / $limit);
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
                        <a href="/fred/admin/reservations.php" class="list-group-item list-group-item-action active">
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
            <h1 class="h3 mb-4">Gestion des réservations</h1>
            
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
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select name="statut" id="statut" class="form-select">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                                <option value="confirmee" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'confirmee') ? 'selected' : ''; ?>>Confirmée</option>
                                <option value="annulee" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?php echo isset($_GET['date_debut']) ? $_GET['date_debut'] : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?php echo isset($_GET['date_fin']) ? $_GET['date_fin'] : ''; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i> Filtrer
                            </button>
                            <a href="/fred/admin/reservations.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des réservations -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">ID</th>
                                    <th scope="col">Propriété</th>
                                    <th scope="col">Client</th>
                                    <th scope="col">Date réservation</th>
                                    <th scope="col">Date visite</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Aucune réservation trouvée.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td class="ps-4"><?php echo $reservation['id']; ?></td>
                                        <td>
                                            <a href="/fred/propriete.php?id=<?php echo $reservation['id_propriete']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($reservation['titre_propriete']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($reservation['client_email']); ?></small>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_visite'])); ?></td>
                                        <td>
                                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                            <?php elseif ($reservation['statut'] === 'confirmee'): ?>
                                            <span class="badge bg-success">Confirmée</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Annulée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/fred/admin/reservation_detail.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-primary" title="Détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($reservation['statut'] === 'en_attente'): ?>
                                                <a href="/fred/admin/reservations.php?action=confirm&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-success" title="Confirmer">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($reservation['statut'] !== 'annulee'): ?>
                                                <a href="/fred/admin/reservations.php?action=cancel&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-warning" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="/fred/admin/reservations.php?action=delete&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
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
            <nav aria-label="Pagination des réservations" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['statut']) ? '&statut=' . htmlspecialchars($_GET['statut']) : ''; ?><?php echo isset($_GET['date_debut']) ? '&date_debut=' . htmlspecialchars($_GET['date_debut']) : ''; ?><?php echo isset($_GET['date_fin']) ? '&date_fin=' . htmlspecialchars($_GET['date_fin']) : ''; ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['statut']) ? '&statut=' . htmlspecialchars($_GET['statut']) : ''; ?><?php echo isset($_GET['date_debut']) ? '&date_debut=' . htmlspecialchars($_GET['date_debut']) : ''; ?><?php echo isset($_GET['date_fin']) ? '&date_fin=' . htmlspecialchars($_GET['date_fin']) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['statut']) ? '&statut=' . htmlspecialchars($_GET['statut']) : ''; ?><?php echo isset($_GET['date_debut']) ? '&date_debut=' . htmlspecialchars($_GET['date_debut']) : ''; ?><?php echo isset($_GET['date_fin']) ? '&date_fin=' . htmlspecialchars($_GET['date_fin']) : ''; ?>" aria-label="Suivant">
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
