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

// Récupérer les messages de l'utilisateur
$messages = getUserMessages($pdo, $_SESSION['user_id']);

// Traitement de l'envoi d'un nouveau message
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $sujet = cleanInput($_POST['sujet']);
    $contenu = cleanInput($_POST['contenu']);
    $id_propriete = isset($_POST['id_propriete']) ? (int)$_POST['id_propriete'] : null;
    
    // Validation basique
    if (empty($sujet) || empty($contenu)) {
        $error_message = "Le sujet et le contenu du message sont obligatoires.";
    } else {
        // Préparation des données pour l'insertion
        $message_data = [
            'id_expediteur' => $_SESSION['user_id'],
            'id_destinataire' => null, // Sera défini comme l'administrateur dans la fonction
            'sujet' => $sujet,
            'contenu' => $contenu,
            'id_propriete' => $id_propriete
        ];
        
        // Création du message
        $result = createMessage($pdo, $message_data);
        
        if ($result) {
            $success_message = "Votre message a été envoyé avec succès.";
            // Rafraîchir la liste des messages
            $messages = getUserMessages($pdo, $_SESSION['user_id']);
        } else {
            $error_message = "Une erreur est survenue lors de l'envoi du message.";
        }
    }
}

// Marquer un message comme lu
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $id_message = (int)$_GET['read'];
    
    // Vérifier que le message appartient bien à l'utilisateur connecté
    $message = getMessageById($pdo, $id_message);
    
    if ($message && $message['id_destinataire'] == $_SESSION['user_id']) {
        markMessageAsRead($pdo, $id_message);
        // Rafraîchir la liste des messages
        $messages = getUserMessages($pdo, $_SESSION['user_id']);
    }
}

// Récupérer les propriétés pour le formulaire d'envoi de message
$proprietes = getProprietes($pdo, ['disponibilite' => 1], 100);
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
                        <a href="reservations.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i> Mes réservations
                        </a>
                        <a href="profil.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-edit me-2"></i> Mon profil
                        </a>
                        <a href="messages.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-envelope me-2"></i> Messages
                            <?php 
                            // Compter les messages non lus
                            $unread_count = count(array_filter($messages, function($m) {
                                return $m['id_destinataire'] == $_SESSION['user_id'] && !$m['lu'];
                            }));
                            
                            if ($unread_count > 0): 
                            ?>
                            <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Messages</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMessageModal">
                            <i class="fas fa-plus me-2"></i> Nouveau message
                        </button>
                    </div>
                    
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
                    
                    <!-- Onglets pour les messages -->
                    <ul class="nav nav-tabs mb-4" id="messagesTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button" role="tab" aria-controls="inbox" aria-selected="true">
                                Boîte de réception
                                <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab" aria-controls="sent" aria-selected="false">
                                Messages envoyés
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="messagesTabContent">
                        <!-- Boîte de réception -->
                        <div class="tab-pane fade show active" id="inbox" role="tabpanel" aria-labelledby="inbox-tab">
                            <?php 
                            // Filtrer les messages reçus
                            $received_messages = array_filter($messages, function($m) {
                                return $m['id_destinataire'] == $_SESSION['user_id'];
                            });
                            
                            if (empty($received_messages)): 
                            ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Vous n'avez pas de messages dans votre boîte de réception.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($received_messages as $message): ?>
                                <a href="#" class="list-group-item list-group-item-action <?php echo !$message['lu'] ? 'bg-light' : ''; ?>" data-bs-toggle="modal" data-bs-target="#viewMessageModal<?php echo $message['id']; ?>">
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
                                
                                <!-- Modal pour voir le message -->
                                <div class="modal fade" id="viewMessageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="viewMessageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewMessageModalLabel<?php echo $message['id']; ?>"><?php echo htmlspecialchars($message['sujet']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <p class="mb-1"><strong>De:</strong> <?php echo htmlspecialchars($message['expediteur_nom'] . ' ' . $message['expediteur_prenom']); ?></p>
                                                    <p class="mb-1"><strong>Date:</strong> <?php echo $date->format('d/m/Y H:i'); ?></p>
                                                    <?php if ($message['id_propriete']): ?>
                                                    <p class="mb-3">
                                                        <strong>Propriété concernée:</strong> 
                                                        <a href="../propriete.php?id=<?php echo $message['id_propriete']; ?>">
                                                            <?php echo htmlspecialchars($message['titre_propriete'] ?? 'Voir la propriété'); ?>
                                                        </a>
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                                <hr>
                                                <div class="message-content">
                                                    <?php echo nl2br(htmlspecialchars($message['contenu'])); ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyMessageModal<?php echo $message['id']; ?>">
                                                    <i class="fas fa-reply me-2"></i> Répondre
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal pour répondre au message -->
                                <div class="modal fade" id="replyMessageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="replyMessageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="replyMessageModalLabel<?php echo $message['id']; ?>">Répondre: <?php echo htmlspecialchars($message['sujet']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="messages.php" method="post">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="sujet" class="form-label">Sujet</label>
                                                        <input type="text" class="form-control" id="sujet" name="sujet" value="RE: <?php echo htmlspecialchars($message['sujet']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="contenu" class="form-label">Message</label>
                                                        <textarea class="form-control" id="contenu" name="contenu" rows="6" required></textarea>
                                                    </div>
                                                    <?php if ($message['id_propriete']): ?>
                                                    <input type="hidden" name="id_propriete" value="<?php echo $message['id_propriete']; ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php 
                                // Marquer le message comme lu lorsqu'il est ouvert
                                if (!$message['lu']) {
                                    echo "<script>document.addEventListener('DOMContentLoaded', function() {
                                        var modal = document.getElementById('viewMessageModal{$message['id']}');
                                        modal.addEventListener('shown.bs.modal', function() {
                                            window.location.href = 'messages.php?read={$message['id']}';
                                        });
                                    });</script>";
                                }
                                ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Messages envoyés -->
                        <div class="tab-pane fade" id="sent" role="tabpanel" aria-labelledby="sent-tab">
                            <?php 
                            // Filtrer les messages envoyés
                            $sent_messages = array_filter($messages, function($m) {
                                return $m['id_expediteur'] == $_SESSION['user_id'];
                            });
                            
                            if (empty($sent_messages)): 
                            ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Vous n'avez pas encore envoyé de messages.
                            </div>
                            <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($sent_messages as $message): ?>
                                <a href="#" class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#viewSentMessageModal<?php echo $message['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($message['sujet']); ?></h6>
                                        <small class="text-muted">
                                            <?php 
                                            $date = new DateTime($message['date_envoi']);
                                            echo $date->format('d/m/Y H:i'); 
                                            ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">À: <?php echo htmlspecialchars($message['destinataire_nom'] . ' ' . $message['destinataire_prenom']); ?></p>
                                    <small class="text-truncate d-inline-block" style="max-width: 80%;">
                                        <?php echo htmlspecialchars(substr($message['contenu'], 0, 100)) . (strlen($message['contenu']) > 100 ? '...' : ''); ?>
                                    </small>
                                    <?php if ($message['lu']): ?>
                                    <span class="badge bg-success float-end">Lu</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary float-end">Non lu</span>
                                    <?php endif; ?>
                                </a>
                                
                                <!-- Modal pour voir le message envoyé -->
                                <div class="modal fade" id="viewSentMessageModal<?php echo $message['id']; ?>" tabindex="-1" aria-labelledby="viewSentMessageModalLabel<?php echo $message['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="viewSentMessageModalLabel<?php echo $message['id']; ?>"><?php echo htmlspecialchars($message['sujet']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <p class="mb-1"><strong>À:</strong> <?php echo htmlspecialchars($message['destinataire_nom'] . ' ' . $message['destinataire_prenom']); ?></p>
                                                    <p class="mb-1"><strong>Date:</strong> <?php echo $date->format('d/m/Y H:i'); ?></p>
                                                    <?php if ($message['id_propriete']): ?>
                                                    <p class="mb-3">
                                                        <strong>Propriété concernée:</strong> 
                                                        <a href="../propriete.php?id=<?php echo $message['id_propriete']; ?>">
                                                            <?php echo htmlspecialchars($message['titre_propriete'] ?? 'Voir la propriété'); ?>
                                                        </a>
                                                    </p>
                                                    <?php endif; ?>
                                                </div>
                                                <hr>
                                                <div class="message-content">
                                                    <?php echo nl2br(htmlspecialchars($message['contenu'])); ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            </div>
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
    </div>
</div>

<!-- Modal pour nouveau message -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-labelledby="newMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newMessageModalLabel">Nouveau message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="messages.php" method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet</label>
                        <input type="text" class="form-control" id="sujet" name="sujet" required>
                    </div>
                    <div class="mb-3">
                        <label for="id_propriete" class="form-label">Propriété concernée (optionnel)</label>
                        <select class="form-select" id="id_propriete" name="id_propriete">
                            <option value="">-- Sélectionner une propriété --</option>
                            <?php foreach ($proprietes as $propriete): ?>
                            <option value="<?php echo $propriete['id']; ?>">
                                <?php echo htmlspecialchars($propriete['titre'] . ' - ' . $propriete['ville']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Message</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="6" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="send_message" class="btn btn-primary">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
