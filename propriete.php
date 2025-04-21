<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Connexion à la base de données
$pdo = getDbConnection();

// Vérifier si l'ID de la propriété est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/fred/proprietes.php');
}

$id_propriete = (int)$_GET['id'];

// Récupérer les informations de la propriété
$propriete = getProprieteById($pdo, $id_propriete);

// Si la propriété n'existe pas, rediriger vers la liste des propriétés
if (!$propriete) {
    redirect('/fred/proprietes.php');
}

// Récupérer les images de la propriété
$images = getImagesPropriete($pdo, $id_propriete);

// Récupérer les caractéristiques de la propriété
$caracteristiques = getCaracteristiquesPropriete($pdo, $id_propriete);

// Traitement du formulaire de réservation
$reservation_success = false;
$reservation_error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    // Vérifier si l'utilisateur est connecté
    if (!isLoggedIn()) {
        // Sauvegarder l'ID de la propriété dans la session pour rediriger après connexion
        $_SESSION['redirect_after_login'] = "/fred/propriete.php?id={$id_propriete}";
        redirect('/fred/login.php?redirect=reservation');
    }
    
    // Valider les données du formulaire
    $date_visite = cleanInput($_POST['date_visite']);
    $commentaire = cleanInput($_POST['commentaire']);
    
    // Vérifier si la date est valide (future)
    $date_visite_obj = new DateTime($date_visite);
    $now = new DateTime();
    
    if ($date_visite_obj <= $now) {
        $reservation_error = "La date de visite doit être dans le futur.";
    } else {
        // Créer la réservation
        $reservation_data = [
            'id_propriete' => $id_propriete,
            'id_client' => $_SESSION['user_id'],
            'date_visite' => $date_visite,
            'commentaire' => $commentaire
        ];
        
        $result = createReservation($pdo, $reservation_data);
        
        if ($result) {
            $reservation_success = true;
        } else {
            $reservation_error = "Une erreur est survenue lors de la réservation. Veuillez réessayer.";
        }
    }
}
?>

<div class="container py-4">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/fred/index.php">Accueil</a></li>
            <li class="breadcrumb-item"><a href="/fred/proprietes.php">Propriétés</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($propriete['titre']); ?></li>
        </ol>
    </nav>
    
    <?php if ($reservation_success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Réservation confirmée !</strong> Votre demande de visite a été enregistrée. Nous vous contacterons prochainement pour confirmer le rendez-vous.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($reservation_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Erreur !</strong> <?php echo $reservation_error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Galerie d'images -->
        <div class="col-lg-8 mb-4">
            <?php if (empty($images)): ?>
                <img src="/fred/assets/images/no-image.jpg" class="img-fluid rounded" alt="Pas d'image disponible">
            <?php else: ?>
                <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        <?php foreach ($images as $index => $image): ?>
                            <button type="button" data-bs-target="#propertyCarousel" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active" aria-current="true"' : ''; ?> aria-label="Slide <?php echo $index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="carousel-inner rounded">
                        <?php foreach ($images as $index => $image): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($image['url_image']); ?>" class="d-block w-100" alt="Image de la propriété" style="height: 500px; object-fit: cover;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Précédent</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Suivant</span>
                    </button>
                </div>
                
                <!-- Miniatures -->
                <div class="row mt-3 g-2">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="col-3">
                            <img src="<?php echo htmlspecialchars($image['url_image']); ?>" class="img-thumbnail" alt="Miniature" style="height: 80px; object-fit: cover; cursor: pointer;" onclick="$('#propertyCarousel').carousel(<?php echo $index; ?>)">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Informations et réservation -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-3"><?php echo htmlspecialchars($propriete['titre']); ?></h1>
                    <p class="text-muted mb-3">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <?php echo htmlspecialchars($propriete['adresse']); ?>, <?php echo htmlspecialchars($propriete['ville']); ?> <?php echo htmlspecialchars($propriete['code_postal']); ?>
                    </p>
                    <h2 class="h4 text-primary mb-4"><?php echo formatPrix($propriete['prix']); ?></h2>
                    
                    <div class="d-flex justify-content-between mb-4">
                        <?php if ($propriete['superficie']): ?>
                            <div class="text-center">
                                <i class="fas fa-ruler-combined d-block mb-2 fa-lg"></i>
                                <span><?php echo $propriete['superficie']; ?> m²</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($propriete['nb_chambres']): ?>
                            <div class="text-center">
                                <i class="fas fa-bed d-block mb-2 fa-lg"></i>
                                <span><?php echo $propriete['nb_chambres']; ?> chambres</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($propriete['nb_salles_bain']): ?>
                            <div class="text-center">
                                <i class="fas fa-bath d-block mb-2 fa-lg"></i>
                                <span><?php echo $propriete['nb_salles_bain']; ?> SdB</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="mb-3">Caractéristiques</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($caracteristiques)): ?>
                                <?php foreach ($caracteristiques as $caracteristique): ?>
                                    <span class="badge bg-light text-dark p-2">
                                        <i class="fas fa-check-circle me-1 text-primary"></i>
                                        <?php echo htmlspecialchars($caracteristique['nom']); ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small">Aucune caractéristique spécifiée</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($propriete['disponibilite']): ?>
                        <button type="button" class="btn btn-primary w-100" id="reservationButton">
                            <i class="far fa-calendar-alt me-2"></i> Réserver une visite
                        </button>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i> Ce bien n'est plus disponible
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations de contact -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Besoin d'informations ?</h5>
                    <p class="mb-3">N'hésitez pas à nous contacter pour toute question concernant ce bien.</p>
                    <div class="d-grid gap-2">
                        <a href="tel:+33123456789" class="btn btn-outline-primary">
                            <i class="fas fa-phone-alt me-2"></i> 01 23 45 67 89
                        </a>
                        <a href="/fred/contact.php" class="btn btn-outline-secondary">
                            <i class="fas fa-envelope me-2"></i> Nous contacter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description détaillée -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h4 mb-4">Description</h2>
                    <div class="mb-4">
                        <?php if (!empty($propriete['description'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($propriete['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Aucune description disponible pour ce bien.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($propriete['annee_construction']) || !empty($propriete['etage']) || !empty($propriete['nb_etages']) || !empty($propriete['type_chauffage'])): ?>
                    <h3 class="h5 mb-3">Détails techniques</h3>
                    <div class="row mb-4">
                        <?php if (!empty($propriete['annee_construction'])): ?>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Année de construction</small>
                                    <span><?php echo $propriete['annee_construction']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($propriete['etage'])): ?>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-building me-2 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Étage</small>
                                    <span><?php echo $propriete['etage']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($propriete['nb_etages'])): ?>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Nombre d'étages</small>
                                    <span><?php echo $propriete['nb_etages']; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($propriete['type_chauffage'])): ?>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-fire me-2 text-primary"></i>
                                <div>
                                    <small class="text-muted d-block">Type de chauffage</small>
                                    <span><?php echo htmlspecialchars($propriete['type_chauffage']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($propriete['latitude']) && !empty($propriete['longitude'])): ?>
                    <h3 class="h5 mb-3">Localisation</h3>
                    <div id="property-map" style="height: 300px;" class="mb-3 rounded"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var map = L.map('property-map').setView([<?php echo $propriete['latitude']; ?>, <?php echo $propriete['longitude']; ?>], 15);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                            }).addTo(map);
                            L.marker([<?php echo $propriete['latitude']; ?>, <?php echo $propriete['longitude']; ?>]).addTo(map)
                                .bindPopup("<?php echo htmlspecialchars($propriete['titre']); ?>");
                        });
                    </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Propriétés similaires -->
    <?php 
    // Récupérer des propriétés similaires (même catégorie, même ville, prix similaire...)
    $proprietes_similaires = getProprietesSimilaires($pdo, $id_propriete, $propriete['id_categorie'], $propriete['ville'], 3);
    if (!empty($proprietes_similaires)):
    ?>
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="h4 mb-4">Propriétés similaires</h2>
            <div class="row g-4">
                <?php foreach ($proprietes_similaires as $propriete_similaire): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="position-relative">
                            <?php if (!empty($propriete_similaire['image_principale'])): ?>
                                <img src="<?php echo htmlspecialchars($propriete_similaire['image_principale']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($propriete_similaire['titre']); ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <img src="/fred/assets/images/no-image.jpg" class="card-img-top" alt="Pas d'image disponible" style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <span class="badge bg-primary position-absolute top-0 end-0 m-2"><?php echo htmlspecialchars($propriete_similaire['categorie_nom']); ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($propriete_similaire['titre']); ?></h5>
                            <p class="card-text text-muted mb-2">
                                <i class="fas fa-map-marker-alt me-1"></i> 
                                <?php echo htmlspecialchars($propriete_similaire['ville']); ?>
                            </p>
                            <p class="card-text fw-bold text-primary"><?php echo formatPrix($propriete_similaire['prix']); ?></p>
                            <a href="/fred/propriete.php?id=<?php echo $propriete_similaire['id']; ?>" class="btn btn-outline-primary w-100">Voir le détail</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de réservation -->
<?php if ($propriete['disponibilite']): ?>
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">Réserver une visite</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/fred/propriete.php?id=<?php echo $id_propriete; ?>" method="post" onsubmit="return validateReservationForm()">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date_visite" class="form-label">Date et heure souhaitées *</label>
                        <input type="datetime-local" class="form-control" id="date_visite" name="date_visite" required>
                    </div>
                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire (facultatif)</label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3" placeholder="Précisez vos disponibilités ou toute information utile pour la visite"></textarea>
                    </div>
                    <p class="small text-muted">* Champ obligatoire</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="reserver" class="btn btn-primary">Confirmer la demande</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function validateReservationForm() {
        // Validation côté client pour le formulaire de réservation
        // Vous pouvez ajouter vos règles de validation ici
        return true; // Si la validation échoue, retournez false pour empêcher l'envoi du formulaire
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const reservationButton = document.getElementById('reservationButton');
        
        if (reservationButton) {
            reservationButton.addEventListener('click', function() {
                const reservationModal = document.getElementById('reservationModal');
                const modal = bootstrap.Modal.getOrCreateInstance(reservationModal);
                modal.show();
            });
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
