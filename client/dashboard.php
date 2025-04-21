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
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-home me-2"></i> Tableau de bord
                        </a>
                        <a href="reservations.php" class="list-group-item list-group-item-action">
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
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-4">Tableau de bord</h1>
                    
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
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-0">Réservations</h6>
                                            <h2 class="mt-2 mb-0"><?php echo count($reservations); ?></h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-0">Confirmées</h6>
                                            <h2 class="mt-2 mb-0">
                                                <?php 
                                                $confirmed = array_filter($reservations, function($r) {
                                                    return $r['statut'] === 'confirmee';
                                                });
                                                echo count($confirmed);
                                                ?>
                                            </h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-0">En attente</h6>
                                            <h2 class="mt-2 mb-0">
                                                <?php 
                                                $pending = array_filter($reservations, function($r) {
                                                    return $r['statut'] === 'en_attente';
                                                });
                                                echo count($pending);
                                                ?>
                                            </h2>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock fa-3x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Réservations récentes</h5>
                    
                    <?php if (empty($reservations)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore de réservations. Consultez nos <a href="../proprietes.php" class="alert-link">propriétés disponibles</a> pour planifier une visite.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Propriété</th>
                                    <th>Date de visite</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Afficher seulement les 5 dernières réservations
                                $recent_reservations = array_slice($reservations, 0, 5);
                                foreach ($recent_reservations as $reservation): 
                                ?>
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
                                        echo $date->format('d/m/Y à H:i'); 
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
                                        <div class="btn-group">
                                            <a href="../propriete.php?id=<?php echo $reservation['id_propriete']; ?>" class="btn btn-sm btn-outline-primary" title="Voir la propriété">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                            <form action="dashboard.php" method="post" class="d-inline">
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
                    
                    <?php if (count($reservations) > 5): ?>
                    <div class="text-center mt-3">
                        <a href="reservations.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i> Voir toutes mes réservations
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Propriétés recommandées -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Propriétés recommandées</h5>
                    
                    <?php
                    // Récupérer des propriétés recommandées (les plus récentes disponibles)
                    $proprietes_recommandees = getProprietes($pdo, ['disponibilite' => 1], 3);
                    
                    if (empty($proprietes_recommandees)): 
                    ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Aucune propriété recommandée pour le moment.
                    </div>
                    <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($proprietes_recommandees as $propriete): ?>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <?php if (!empty($propriete['image_principale'])): ?>
                                        <img src="<?php echo htmlspecialchars($propriete['image_principale']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($propriete['titre']); ?>" style="height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="../assets/images/no-image.jpg" class="card-img-top" alt="Pas d'image disponible" style="height: 150px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($propriete['titre']); ?></h6>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        <?php echo htmlspecialchars($propriete['ville']); ?>
                                    </p>
                                    <p class="card-text fw-bold text-primary"><?php echo formatPrix($propriete['prix']); ?></p>
                                    <a href="../propriete.php?id=<?php echo $propriete['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Voir le détail</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
