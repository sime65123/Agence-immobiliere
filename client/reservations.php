<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Vérifier si l'utilisateur est un client (non admin)
if ($_SESSION['user_role'] === 'admin') {
    redirect('../admin/dashboard.php');
}

// Connexion à la base de données
$pdo = getDbConnection();

// Récupérer les réservations de l'utilisateur
$reservations = getUserReservations($pdo, $_SESSION['user_id']);

// Traitement des actions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id_reservation'])) {
        $id_reservation = (int)$_POST['id_reservation'];
        
        // Vérifier que la réservation appartient bien à l'utilisateur connecté
        $reservation = getReservationById($pdo, $id_reservation);
        
        if ($reservation && $reservation['id_client'] == $_SESSION['user_id']) {
            if ($_POST['action'] === 'cancel') {
                // Annuler la réservation
                if (cancelReservation($pdo, $id_reservation)) {
                    $success_message = "Votre réservation a été annulée avec succès.";
                    // Rafraîchir la liste des réservations
                    $reservations = getUserReservations($pdo, $_SESSION['user_id']);
                } else {
                    $error_message = "Une erreur est survenue lors de l'annulation de la réservation.";
                }
            }
        } else {
            $error_message = "Réservation non trouvée ou vous n'êtes pas autorisé à effectuer cette action.";
        }
    }
}

// Filtres pour les réservations
$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';

// Filtrer les réservations selon les critères
$filtered_reservations = $reservations;

// Filtre par statut
if ($statut_filter !== 'all') {
    $filtered_reservations = array_filter($filtered_reservations, function($r) use ($statut_filter) {
        return $r['statut'] === $statut_filter;
    });
}

// Filtre par date
if ($date_filter !== 'all') {
    $today = new DateTime();
    $filtered_reservations = array_filter($filtered_reservations, function($r) use ($date_filter, $today) {
        $reservation_date = new DateTime($r['date_visite']);
        
        if ($date_filter === 'future') {
            return $reservation_date > $today;
        } elseif ($date_filter === 'past') {
            return $reservation_date < $today;
        } elseif ($date_filter === 'today') {
            return $reservation_date->format('Y-m-d') === $today->format('Y-m-d');
        }
        
        return true;
    });
}

// Trier les réservations par date (les plus récentes en premier)
usort($filtered_reservations, function($a, $b) {
    return strtotime($b['date_visite']) - strtotime($a['date_visite']);
});
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary text-white rounded-circle p-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user fa-lg"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Tableau de bord
                        </a>
                        <a href="reservations.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                        </a>
                        <a href="profil.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-edit me-2"></i> Mon profil
                        </a>
                        <a href="messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                        <a href="../logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="h3 mb-4">Mes réservations</h1>
                    
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
                    
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="d-flex">
                                <a href="reservations.php?statut=all&date=<?php echo $date_filter; ?>" class="btn btn-sm <?php echo $statut_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                    Toutes
                                </a>
                                <a href="reservations.php?statut=en_attente&date=<?php echo $date_filter; ?>" class="btn btn-sm <?php echo $statut_filter === 'en_attente' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                    En attente
                                </a>
                                <a href="reservations.php?statut=confirmee&date=<?php echo $date_filter; ?>" class="btn btn-sm <?php echo $statut_filter === 'confirmee' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                    Confirmées
                                </a>
                                <a href="reservations.php?statut=annulee&date=<?php echo $date_filter; ?>" class="btn btn-sm <?php echo $statut_filter === 'annulee' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Annulées
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-md-end">
                                <a href="reservations.php?statut=<?php echo $statut_filter; ?>&date=all" class="btn btn-sm <?php echo $date_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                    Toutes dates
                                </a>
                                <a href="reservations.php?statut=<?php echo $statut_filter; ?>&date=future" class="btn btn-sm <?php echo $date_filter === 'future' ? 'btn-primary' : 'btn-outline-primary'; ?> me-2">
                                    À venir
                                </a>
                                <a href="reservations.php?statut=<?php echo $statut_filter; ?>&date=past" class="btn btn-sm <?php echo $date_filter === 'past' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Passées
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($filtered_reservations)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Aucune réservation ne correspond à vos critères de recherche.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Propriété</th>
                                    <th>Date de visite</th>
                                    <th>Statut</th>
                                    <th>Détails</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_reservations as $reservation): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($reservation['image_principale'])): ?>
                                                <img src="<?php echo htmlspecialchars($reservation['image_principale']); ?>" class="rounded me-3" alt="" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-home text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($reservation['titre_propriete']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($reservation['ville']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $date = new DateTime($reservation['date_visite']);
                                        $today = new DateTime();
                                        $is_future = $date > $today;
                                        
                                        echo $date->format('d/m/Y à H:i');
                                        
                                        if ($is_future && $reservation['statut'] === 'confirmee') {
                                            $diff = $today->diff($date);
                                            if ($diff->days < 3) {
                                                echo '<br><span class="badge bg-danger">Bientôt</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($reservation['statut'] === 'confirmee'): ?>
                                            <span class="badge bg-success">Confirmée</span>
                                        <?php elseif ($reservation['statut'] === 'en_attente'): ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php elseif ($reservation['statut'] === 'annulee'): ?>
                                            <span class="badge bg-danger">Annulée</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $reservation['id']; ?>">
                                            <i class="fas fa-info-circle"></i> Détails
                                        </button>
                                        
                                        <!-- Modal de détails -->
                                        <div class="modal fade" id="detailModal<?php echo $reservation['id']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $reservation['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="detailModalLabel<?php echo $reservation['id']; ?>">Détails de la réservation</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-4">
                                                            <h6>Propriété</h6>
                                                            <p class="mb-0"><?php echo htmlspecialchars($reservation['titre_propriete']); ?></p>
                                                            <p class="text-muted">
                                                                <?php echo htmlspecialchars($reservation['adresse']); ?>, 
                                                                <?php echo htmlspecialchars($reservation['code_postal']); ?> 
                                                                <?php echo htmlspecialchars($reservation['ville']); ?>
                                                            </p>
                                                        </div>
                                                        
                                                        <div class="mb-4">
                                                            <h6>Date et heure de visite</h6>
                                                            <p><?php echo $date->format('d/m/Y à H:i'); ?></p>
                                                        </div>
                                                        
                                                        <div class="mb-4">
                                                            <h6>Statut</h6>
                                                            <p>
                                                                <?php if ($reservation['statut'] === 'confirmee'): ?>
                                                                    <span class="badge bg-success">Confirmée</span>
                                                                <?php elseif ($reservation['statut'] === 'en_attente'): ?>
                                                                    <span class="badge bg-warning text-dark">En attente</span>
                                                                <?php elseif ($reservation['statut'] === 'annulee'): ?>
                                                                    <span class="badge bg-danger">Annulée</span>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                        
                                                        <?php if (!empty($reservation['commentaire'])): ?>
                                                        <div class="mb-4">
                                                            <h6>Votre commentaire</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($reservation['commentaire'])); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($reservation['commentaire_agent'])): ?>
                                                        <div class="mb-4">
                                                            <h6>Commentaire de l'agent</h6>
                                                            <p><?php echo nl2br(htmlspecialchars($reservation['commentaire_agent'])); ?></p>
                                                        </div>
                                                        <?php endif; ?>
                                                        
                                                        <div>
                                                            <h6>Date de création</h6>
                                                            <p>
                                                                <?php 
                                                                $date_creation = new DateTime($reservation['date_creation']);
                                                                echo $date_creation->format('d/m/Y à H:i'); 
                                                                ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                        <a href="../propriete.php?id=<?php echo $reservation['id_propriete']; ?>" class="btn btn-primary">Voir la propriété</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../propriete.php?id=<?php echo $reservation['id_propriete']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la propriété">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                            <form action="reservations.php?statut=<?php echo $statut_filter; ?>&date=<?php echo $date_filter; ?>" method="post" class="d-inline">
                                                <input type="hidden" name="id_reservation" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Annuler la réservation" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="../proprietes.php" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i> Rechercher des propriétés
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
