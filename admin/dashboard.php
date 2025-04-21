<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Vérifier si l'utilisateur est un administrateur
if ($_SESSION['user_role'] !== 'admin') {
    redirect('../index.php');
}

// Connexion à la base de données
$pdo = getDbConnection();

// Récupérer les statistiques pour le tableau de bord
$stats = [
    'proprietes' => countProprietes($pdo),
    'proprietes_disponibles' => countProprietes($pdo, ['disponibilite' => 1]),
    'reservations' => countReservations($pdo),
    'reservations_en_attente' => countReservations($pdo, ['statut' => 'en_attente']),
    'reservations_aujourd_hui' => countReservationsToday($pdo),
    'utilisateurs' => countUsers($pdo),
    'messages_non_lus' => countUnreadMessages($pdo, null, true) // Messages non lus pour les admins
];

// Récupérer les réservations récentes
$reservations_recentes = getRecentReservations($pdo, 5);

// Récupérer les propriétés récemment ajoutées
$proprietes_recentes = getRecentProperties($pdo, 5);

// Récupérer les messages récents
$messages_recents = getRecentMessages($pdo, 5, true); // Messages pour les admins
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
                        <a href="/fred/admin/dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                        </a>
                        <a href="/fred/admin/proprietes.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-home me-2"></i> Propriétés
                        </a>
                        <a href="/fred/admin/reservations.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i> Réservations
                            <?php if ($stats['reservations_en_attente'] > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-2"><?php echo $stats['reservations_en_attente']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/fred/admin/utilisateurs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Utilisateurs
                        </a>
                        <a href="/fred/admin/messages.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-envelope me-2"></i> Messages
                            <?php if ($stats['messages_non_lus'] > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-2"><?php echo $stats['messages_non_lus']; ?></span>
                            <?php endif; ?>
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
            <h1 class="h3 mb-4">Tableau de bord</h1>
            
            <!-- Statistiques -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-0">Propriétés</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['proprietes']; ?></h2>
                                    <p class="text-success mb-0">
                                        <small><?php echo $stats['proprietes_disponibles']; ?> disponibles</small>
                                    </p>
                                </div>
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-home text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-0">Réservations</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['reservations']; ?></h2>
                                    <p class="text-warning mb-0">
                                        <small><?php echo $stats['reservations_en_attente']; ?> en attente</small>
                                    </p>
                                </div>
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-calendar-check text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-0">Utilisateurs</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['utilisateurs']; ?></h2>
                                    <p class="text-info mb-0">
                                        <small>Clients enregistrés</small>
                                    </p>
                                </div>
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-users text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title text-muted mb-0">Aujourd'hui</h6>
                                    <h2 class="mt-2 mb-0"><?php echo $stats['reservations_aujourd_hui']; ?></h2>
                                    <p class="text-danger mb-0">
                                        <small>Visites programmées</small>
                                    </p>
                                </div>
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-clock text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Réservations récentes -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Réservations récentes</h5>
                                <a href="/fred/admin/reservations.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($reservations_recentes)): ?>
                            <div class="p-4 text-center">
                                <p class="text-muted mb-0">Aucune réservation récente</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Client</th>
                                            <th>Propriété</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservations_recentes as $reservation): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle p-2 me-2">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <p class="mb-0"><?php echo htmlspecialchars($reservation['client_prenom'] . ' ' . $reservation['client_nom']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($reservation['titre_propriete'], 0, 20)) . (strlen($reservation['titre_propriete']) > 20 ? '...' : ''); ?></td>
                                            <td>
                                                <?php 
                                                $date = new DateTime($reservation['date_visite']);
                                                echo $date->format('d/m/Y H:i'); 
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
                                                <a href="/fred/admin/reservation_detail.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Propriétés récentes -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Propriétés récentes</h5>
                                <a href="/fred/admin/proprietes.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($proprietes_recentes)): ?>
                            <div class="p-4 text-center">
                                <p class="text-muted mb-0">Aucune propriété récente</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Image</th>
                                            <th>Titre</th>
                                            <th>Prix</th>
                                            <th>Statut</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($proprietes_recentes as $propriete): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($propriete['image_principale'])): ?>
                                                    <img src="<?php echo htmlspecialchars($propriete['image_principale']); ?>" class="rounded" alt="" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-home text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($propriete['titre'], 0, 25)) . (strlen($propriete['titre']) > 25 ? '...' : ''); ?></td>
                                            <td><?php echo formatPrix($propriete['prix']); ?></td>
                                            <td>
                                                <?php if ($propriete['disponibilite']): ?>
                                                    <span class="badge bg-success">Disponible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Non disponible</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="/fred/admin/propriete_edit.php?id=<?php echo $propriete['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Messages récents -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Messages récents</h5>
                                <a href="/fred/admin/messages.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($messages_recents)): ?>
                            <div class="p-4 text-center">
                                <p class="text-muted mb-0">Aucun message récent</p>
                            </div>
                            <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($messages_recents as $message): ?>
                                <a href="/fred/admin/message_detail.php?id=<?php echo $message['id']; ?>" class="list-group-item list-group-item-action py-3 px-4 <?php echo !$message['lu'] ? 'bg-light' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">
                                            <?php if (!$message['lu']): ?>
                                            <span class="badge bg-primary me-2">Nouveau</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($message['sujet']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php 
                                            $date = new DateTime($message['date_envoi']);
                                            echo $date->format('d/m/Y H:i'); 
                                            ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">De: <?php echo htmlspecialchars($message['expediteur_nom'] . ' ' . $message['expediteur_prenom']); ?></p>
                                    <small class="text-truncate d-inline-block" style="max-width: 80%;">
                                        <?php echo htmlspecialchars(substr($message['contenu'], 0, 100)) . (strlen($message['contenu']) > 100 ? '...' : ''); ?>
                                    </small>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Actions rapides -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="mb-0">Actions rapides</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="/fred/admin/propriete_add.php" class="btn btn-primary w-100 p-3">
                                        <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                        <div>Ajouter une propriété</div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="/fred/admin/reservations.php?statut=en_attente" class="btn btn-warning w-100 p-3">
                                        <i class="fas fa-clock fa-2x mb-2"></i>
                                        <div>Réservations en attente</div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="/fred/admin/utilisateur_add.php" class="btn btn-success w-100 p-3">
                                        <i class="fas fa-user-plus fa-2x mb-2"></i>
                                        <div>Ajouter un utilisateur</div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="/fred/admin/messages.php?filter=unread" class="btn btn-info text-white w-100 p-3">
                                        <i class="fas fa-envelope-open-text fa-2x mb-2"></i>
                                        <div>Messages non lus</div>
                                    </a>
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
