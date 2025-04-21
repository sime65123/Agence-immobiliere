<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /fred/login.php');
    exit;
}

// Connexion à la base de données
$pdo = getDbConnection();

// Vérifier si l'ID est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: /fred/admin/proprietes.php');
    exit;
}

$id_propriete = intval($_GET['id']);
$propriete = getProprieteById($pdo, $id_propriete);

// Si la propriété n'existe pas, rediriger
if (!$propriete) {
    header('Location: /fred/admin/proprietes.php');
    exit;
}

$categories = getCategories($pdo);
$caracteristiques = getCaracteristiques($pdo);
$propriete_caracteristiques = getCaracteristiquesPropriete($pdo, $id_propriete);
$images = getImagesPropriete($pdo, $id_propriete);

// Transformer le tableau de caractéristiques pour faciliter la vérification
$selected_caracteristiques = [];
foreach ($propriete_caracteristiques as $carac) {
    $selected_caracteristiques[] = $carac['id'];
}

$success_message = null;
$error_message = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $data = [
        'titre' => cleanInput($_POST['titre']),
        'description' => cleanInput($_POST['description']),
        'prix' => floatval(str_replace(',', '.', cleanInput($_POST['prix']))),
        'adresse' => cleanInput($_POST['adresse']),
        'ville' => cleanInput($_POST['ville']),
        'code_postal' => cleanInput($_POST['code_postal']),
        'pays' => cleanInput($_POST['pays']),
        'superficie' => floatval(str_replace(',', '.', cleanInput($_POST['superficie']))),
        'nb_chambres' => intval(cleanInput($_POST['nb_chambres'])),
        'nb_salles_bain' => intval(cleanInput($_POST['nb_salles_bain'])),
        'annee_construction' => !empty($_POST['annee_construction']) ? intval(cleanInput($_POST['annee_construction'])) : null,
        'disponibilite' => isset($_POST['disponibilite']) ? 1 : 0,
        'est_vedette' => isset($_POST['est_vedette']) ? 1 : 0,
        'id_categorie' => intval(cleanInput($_POST['id_categorie'])),
        'caracteristiques' => isset($_POST['caracteristiques']) ? $_POST['caracteristiques'] : []
    ];
    
    // Validation des données
    $errors = [];
    
    if (empty($data['titre'])) {
        $errors[] = "Le titre est obligatoire";
    }
    
    if (empty($data['prix']) || $data['prix'] <= 0) {
        $errors[] = "Le prix doit être un nombre positif";
    }
    
    if (empty($data['adresse'])) {
        $errors[] = "L'adresse est obligatoire";
    }
    
    if (empty($data['ville'])) {
        $errors[] = "La ville est obligatoire";
    }
    
    if (empty($data['code_postal'])) {
        $errors[] = "Le code postal est obligatoire";
    }
    
    if (empty($data['id_categorie'])) {
        $errors[] = "La catégorie est obligatoire";
    }
    
    // Si pas d'erreurs, on met à jour la propriété
    if (empty($errors)) {
        if (updatePropriete($pdo, $id_propriete, $data)) {
            // Traitement des images
            $upload_dir = '../assets/images/properties/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Vérifier si des fichiers ont été uploadés
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $files = $_FILES['images'];
                $file_count = count($files['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($files['error'][$i] === 0) {
                        $file_name = $files['name'][$i];
                        $file_tmp = $files['tmp_name'][$i];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        // Vérifier l'extension
                        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            // Générer un nom unique
                            $new_file_name = 'property_' . $id_propriete . '_' . uniqid() . '.' . $file_ext;
                            $destination = $upload_dir . $new_file_name;
                            
                            if (move_uploaded_file($file_tmp, $destination)) {
                                // Définir comme image principale si aucune image n'existe
                                $est_principale = (count($images) === 0) ? true : false;
                                $image_url = '/fred/assets/images/properties/' . $new_file_name;
                                
                                // Ajouter l'image à la base de données
                                addImagePropriete($pdo, $id_propriete, $image_url, $est_principale);
                            }
                        }
                    }
                }
            }
            
            // Gestion des images existantes
            if (isset($_POST['image_principale']) && !empty($_POST['image_principale'])) {
                $id_image_principale = intval($_POST['image_principale']);
                
                // Mettre à jour l'image principale
                try {
                    // Réinitialiser toutes les images
                    $sql = "UPDATE images_proprietes SET est_principale = 0 WHERE id_propriete = :id_propriete";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id_propriete', $id_propriete, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    // Définir la nouvelle image principale
                    $sql = "UPDATE images_proprietes SET est_principale = 1 WHERE id = :id_image";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id_image', $id_image_principale, PDO::PARAM_INT);
                    $stmt->execute();
                } catch (PDOException $e) {
                    error_log('Erreur lors de la mise à jour de l\'image principale: ' . $e->getMessage());
                }
            }
            
            // Supprimer les images sélectionnées
            if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $id_image) {
                    try {
                        // Récupérer l'URL de l'image
                        $sql = "SELECT url_image FROM images_proprietes WHERE id = :id_image";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':id_image', $id_image, PDO::PARAM_INT);
                        $stmt->execute();
                        $image = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($image) {
                            // Supprimer le fichier physique
                            $file_path = str_replace('/fred', '..', $image['url_image']);
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                            
                            // Supprimer l'entrée dans la base de données
                            $sql = "DELETE FROM images_proprietes WHERE id = :id_image";
                            $stmt = $pdo->prepare($sql);
                            $stmt->bindParam(':id_image', $id_image, PDO::PARAM_INT);
                            $stmt->execute();
                        }
                    } catch (PDOException $e) {
                        error_log('Erreur lors de la suppression de l\'image: ' . $e->getMessage());
                    }
                }
            }
            
            $success_message = "La propriété a été mise à jour avec succès";
            
            // Rafraîchir les données
            $propriete = getProprieteById($pdo, $id_propriete);
            $propriete_caracteristiques = getCaracteristiquesPropriete($pdo, $id_propriete);
            $images = getImagesPropriete($pdo, $id_propriete);
            
            // Mettre à jour le tableau des caractéristiques sélectionnées
            $selected_caracteristiques = [];
            foreach ($propriete_caracteristiques as $carac) {
                $selected_caracteristiques[] = $carac['id'];
            }
        } else {
            $error_message = "Une erreur est survenue lors de la mise à jour de la propriété";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded p-3 me-3">
                            <i class="fas fa-user-shield text-white"></i>
                        </div>
                        <div style="min-width: 0">
                            <h6 class="mb-0 text-truncate" title="Admin">Admin</h6>
                            <p class="mb-0 text-muted text-truncate" title="<?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>">
                                <?php echo $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']; ?>
                            </p>
                        </div>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/fred/admin/proprietes.php">
                                <i class="fas fa-home me-2"></i> Propriétés
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/reservations.php">
                                <i class="fas fa-calendar-alt me-2"></i> Réservations
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/utilisateurs.php">
                                <i class="fas fa-users me-2"></i> Utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/messages.php">
                                <i class="fas fa-envelope me-2"></i> Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/categories.php">
                                <i class="fas fa-tags me-2"></i> Catégories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/admin/caracteristiques.php">
                                <i class="fas fa-list-ul me-2"></i> Caractéristiques
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/fred/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Modifier la propriété</h1>
                <a href="/fred/admin/proprietes.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Retour à la liste
                </a>
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
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h5 class="mb-3">Informations générales</h5>
                                <div class="mb-3">
                                    <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="titre" name="titre" value="<?php echo htmlspecialchars($propriete['titre']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($propriete['description']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="prix" class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="prix" name="prix" step="0.01" value="<?php echo htmlspecialchars($propriete['prix']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="id_categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_categorie" name="id_categorie" required>
                                            <option value="">Sélectionnez une catégorie</option>
                                            <?php foreach ($categories as $categorie): ?>
                                            <option value="<?php echo $categorie['id']; ?>" <?php echo ($categorie['id'] == $propriete['id_categorie']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categorie['nom']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h5 class="mb-3">Images actuelles</h5>
                                <?php if (empty($images)): ?>
                                <p class="text-muted">Aucune image disponible</p>
                                <?php else: ?>
                                <div class="row">
                                    <?php foreach ($images as $image): ?>
                                    <div class="col-6 mb-3">
                                        <div class="card">
                                            <img src="<?php echo htmlspecialchars($image['url_image']); ?>" class="card-img-top" alt="Image de la propriété" style="height: 120px; object-fit: cover;">
                                            <div class="card-body p-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="image_principale" id="image_<?php echo $image['id']; ?>" value="<?php echo $image['id']; ?>" <?php echo $image['est_principale'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="image_<?php echo $image['id']; ?>">
                                                        Principale
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_images[]" id="delete_<?php echo $image['id']; ?>" value="<?php echo $image['id']; ?>">
                                                    <label class="form-check-label" for="delete_<?php echo $image['id']; ?>">
                                                        Supprimer
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <h5 class="mb-3 mt-4">Ajouter des images</h5>
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*">
                                    <div class="form-text">Formats acceptés: JPG, JPEG, PNG, GIF. Max 5 Mo par image.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="disponibilite" name="disponibilite" <?php echo $propriete['disponibilite'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="disponibilite">Disponible</label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="est_vedette" name="est_vedette" <?php echo $propriete['est_vedette'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="est_vedette">Mettre en vedette</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">Adresse</h5>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="adresse" class="form-label">Adresse <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="adresse" name="adresse" value="<?php echo htmlspecialchars($propriete['adresse']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="ville" class="form-label">Ville <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($propriete['ville']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="code_postal" class="form-label">Code postal <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($propriete['code_postal']); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="pays" class="form-label">Pays</label>
                                        <input type="text" class="form-control" id="pays" name="pays" value="<?php echo htmlspecialchars($propriete['pays']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">Caractéristiques</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="superficie" class="form-label">Superficie (m²)</label>
                                        <input type="number" class="form-control" id="superficie" name="superficie" step="0.01" value="<?php echo htmlspecialchars($propriete['superficie']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="nb_chambres" class="form-label">Nombre de chambres</label>
                                        <input type="number" class="form-control" id="nb_chambres" name="nb_chambres" min="0" value="<?php echo htmlspecialchars($propriete['nb_chambres']); ?>">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="nb_salles_bain" class="form-label">Nombre de salles de bain</label>
                                        <input type="number" class="form-control" id="nb_salles_bain" name="nb_salles_bain" min="0" value="<?php echo htmlspecialchars($propriete['nb_salles_bain']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="annee_construction" class="form-label">Année de construction</label>
                                        <input type="number" class="form-control" id="annee_construction" name="annee_construction" min="1800" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($propriete['annee_construction']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">Équipements et services</h5>
                                <div class="row">
                                    <?php foreach ($caracteristiques as $caracteristique): ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="carac_<?php echo $caracteristique['id']; ?>" name="caracteristiques[]" value="<?php echo $caracteristique['id']; ?>" <?php echo in_array($caracteristique['id'], $selected_caracteristiques) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="carac_<?php echo $caracteristique['id']; ?>">
                                                <?php echo htmlspecialchars($caracteristique['nom']); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="/fred/admin/proprietes.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-2"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
