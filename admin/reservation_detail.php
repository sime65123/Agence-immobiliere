<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: /fred/login.php');
    exit;
}

// Vérifier si l'utilisateur est un administrateur
if (!isAdmin()) {
    header('Location: /fred/index.php');
    exit;
}

// Connexion à la base de données
$pdo = getDbConnection();

// Récupérer l'ID de la réservation
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: /fred/admin/reservations.php');
    exit;
}

// Récupérer les détails de la réservation
$reservation = getReservationById($pdo, $id);

if (!$reservation) {
    $_SESSION['error'] = "La réservation demandée n'existe pas.";
    header('Location: /fred/admin/reservations.php');
    exit;
}

// Traitement des actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'confirm':
            // Confirmer la réservation
            $sql = "UPDATE reservations SET statut = 'confirmee' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "La réservation a été confirmée avec succès.";
                // Recharger les détails de la réservation
                $reservation = getReservationById($pdo, $id);
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de la confirmation de la réservation.";
            }
            break;
            
        case 'cancel':
            // Annuler la réservation
            $sql = "UPDATE reservations SET statut = 'annulee' WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "La réservation a été annulée avec succès.";
                // Recharger les détails de la réservation
                $reservation = getReservationById($pdo, $id);
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de l'annulation de la réservation.";
            }
            break;
            
        case 'delete':
            // Supprimer la réservation
            $sql = "DELETE FROM reservations WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "La réservation a été supprimée avec succès.";
                header('Location: /fred/admin/reservations.php');
                exit;
            } else {
                $_SESSION['error'] = "Une erreur est survenue lors de la suppression de la réservation.";
            }
            break;
    }
}

// Titre de la page
$page_title = "Détails de la réservation #" . $reservation['id'];

// Le header.php est déjà inclus au début du fichier, pas besoin de l'inclure à nouveau
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Détails de la réservation #<?php echo $reservation['id']; ?></h5>
                        <div>
                            <a href="/fred/admin/reservations.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour
                            </a>
                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                            <a href="/fred/admin/reservation_detail.php?id=<?php echo $reservation['id']; ?>&action=confirm" class="btn btn-sm btn-success ms-1" onclick="return confirm('Êtes-vous sûr de vouloir confirmer cette réservation ?');">
                                <i class="fas fa-check me-1"></i> Confirmer
                            </a>
                            <?php endif; ?>
                            <?php if ($reservation['statut'] !== 'annulee'): ?>
                            <a href="/fred/admin/reservation_detail.php?id=<?php echo $reservation['id']; ?>&action=cancel" class="btn btn-sm btn-warning ms-1" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                <i class="fas fa-ban me-1"></i> Annuler
                            </a>
                            <?php endif; ?>
                            <a href="/fred/admin/reservation_detail.php?id=<?php echo $reservation['id']; ?>&action=delete" class="btn btn-sm btn-danger ms-1" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ? Cette action est irréversible.');">
                                <i class="fas fa-trash-alt me-1"></i> Supprimer
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-white border-0 py-3">
                                    <h6 class="mb-0">Informations de la réservation</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Statut</p>
                                        <?php if ($reservation['statut'] === 'confirmee'): ?>
                                            <span class="badge bg-success">Confirmée</span>
                                        <?php elseif ($reservation['statut'] === 'en_attente'): ?>
                                            <span class="badge bg-warning text-dark">En attente</span>
                                        <?php elseif ($reservation['statut'] === 'annulee'): ?>
                                            <span class="badge bg-danger">Annulée</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Date de réservation</p>
                                        <p class="mb-0 fw-bold"><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Date de visite</p>
                                        <p class="mb-0 fw-bold"><?php echo date('d/m/Y H:i', strtotime($reservation['date_visite'])); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Message</p>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($reservation['commentaire'] ?? 'Aucun commentaire')); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-white border-0 py-3">
                                    <h6 class="mb-0">Informations du client</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Nom complet</p>
                                        <p class="mb-0 fw-bold"><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Email</p>
                                        <p class="mb-0">
                                            <a href="mailto:<?php echo htmlspecialchars($reservation['client_email']); ?>">
                                                <?php echo htmlspecialchars($reservation['client_email']); ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Téléphone</p>
                                        <p class="mb-0">
                                            <a href="tel:<?php echo htmlspecialchars($reservation['client_telephone']); ?>">
                                                <?php echo htmlspecialchars($reservation['client_telephone']); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 py-3">
                                    <h6 class="mb-0">Informations de la propriété</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Titre</p>
                                        <p class="mb-0 fw-bold">
                                            <a href="/fred/propriete.php?id=<?php echo $reservation['id_propriete']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($reservation['titre_propriete']); ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Adresse</p>
                                        <p class="mb-0"><?php echo htmlspecialchars($reservation['adresse'] . ', ' . $reservation['code_postal'] . ' ' . $reservation['ville']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="mb-1 text-muted">Description</p>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars(substr($reservation['description'], 0, 200) . (strlen($reservation['description']) > 200 ? '...' : ''))); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
