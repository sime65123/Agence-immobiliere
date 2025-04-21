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

// Gestion des actions (marquer comme lu, supprimer)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_message = (int)$_GET['id'];
    $action = $_GET['action'];
    
    switch ($action) {
        case 'read':
            if (markMessageAsRead($pdo, $id_message)) {
                $success_message = "Le message a été marqué comme lu.";
            } else {
                $error_message = "Une erreur est survenue lors du marquage du message.";
            }
            break;
            
        case 'delete':
            if (deleteMessage($pdo, $id_message)) {
                $success_message = "Le message a été supprimé avec succès.";
            } else {
                $error_message = "Une erreur est survenue lors de la suppression du message.";
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

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = (int)$_GET['status'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Récupération des messages avec pagination
$messages = getMessages($pdo, $filters, $limit, $offset);
$total_messages = countMessages($pdo, $filters);
$total_pages = ceil($total_messages / $limit);

// Marquer tous les messages comme lus si demandé
if (isset($_GET['mark_all_read'])) {
    if (markAllMessagesAsRead($pdo)) {
        $success_message = "Tous les messages ont été marqués comme lus.";
    } else {
        $error_message = "Une erreur est survenue lors du marquage des messages.";
    }
}
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
                        <a href="/fred/admin/utilisateurs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Utilisateurs
                        </a>
                        <a href="/fred/admin/messages.php" class="list-group-item list-group-item-action active">
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
                <h1 class="h3 mb-0">Gestion des messages</h1>
                <a href="/fred/admin/messages.php?mark_all_read=1" class="btn btn-primary">
                    <i class="fas fa-check-double me-2"></i> Marquer tout comme lu
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
                        <div class="col-md-5">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Nom, email, sujet..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Statut</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Tous</option>
                                <option value="0" <?php echo (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>Non lu</option>
                                <option value="1" <?php echo (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Lu</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-2"></i> Filtrer
                            </button>
                            <a href="/fred/admin/messages.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Liste des messages -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th scope="col" class="ps-4">ID</th>
                                    <th scope="col">Expediteur</th>
                                    <th scope="col">Sujet</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">Aucun message trouvé.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                    <tr class="<?php echo $message['lu'] ? '' : 'table-light fw-bold'; ?>">
                                        <td class="ps-4"><?php echo $message['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($message['expediteur_nom']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                                        </td>
                                        <td>
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                                <?php echo htmlspecialchars($message['sujet']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($message['date_creation'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($message['lu']): ?>
                                            <span class="badge bg-success">Lu</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">Non lu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (!$message['lu']): ?>
                                                <a href="/fred/admin/messages.php?action=read&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="/fred/admin/messages.php?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Modal pour afficher le message complet -->
                                            <div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="messageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="messageModalLabel<?php echo $message['id']; ?>"><?php echo htmlspecialchars($message['sujet']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <strong>De :</strong> <?php echo htmlspecialchars($message['expediteur_nom']); ?> (<?php echo htmlspecialchars($message['email']); ?>)
                                                            </div>
                                                            <div class="mb-3">
                                                                <strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($message['date_creation'])); ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <strong>Téléphone :</strong> <?php echo htmlspecialchars($message['telephone'] ?: 'Non renseigné'); ?>
                                                            </div>
                                                            <hr>
                                                            <div>
                                                                <strong>Message :</strong>
                                                                <p class="mt-2"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <?php if (!$message['lu']): ?>
                                                            <a href="/fred/admin/messages.php?action=read&id=<?php echo $message['id']; ?>" class="btn btn-success">
                                                                <i class="fas fa-check me-2"></i> Marquer comme lu
                                                            </a>
                                                            <?php endif; ?>
                                                            <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>?subject=Re: <?php echo htmlspecialchars($message['sujet']); ?>" class="btn btn-primary">
                                                                <i class="fas fa-reply me-2"></i> Répondre
                                                            </a>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
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
            <nav aria-label="Pagination des messages" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" aria-label="Précédent">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : ''; ?><?php echo isset($_GET['search']) ? '&search=' . htmlspecialchars($_GET['search']) : ''; ?>" aria-label="Suivant">
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
