<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Connexion à la base de données
$pdo = getDbConnection();

// Récupération des filtres depuis l'URL
$filters = [];

if (!empty($_GET['categorie'])) {
    $filters['categorie'] = (int)$_GET['categorie'];
}

if (!empty($_GET['ville'])) {
    $filters['ville'] = cleanInput($_GET['ville']);
}

if (!empty($_GET['prix_min'])) {
    $filters['prix_min'] = (float)$_GET['prix_min'];
}

if (!empty($_GET['prix_max'])) {
    $filters['prix_max'] = (float)$_GET['prix_max'];
}

if (!empty($_GET['nb_chambres_min'])) {
    $filters['nb_chambres_min'] = (int)$_GET['nb_chambres_min'];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9; // Nombre de propriétés par page
$offset = ($page - 1) * $limit;

// Compter le nombre total de propriétés avec les filtres appliqués
$total_proprietes = countProprietes($pdo, $filters);
$total_pages = ceil($total_proprietes / $limit);

// S'assurer que la page demandée est valide
if ($page < 1) {
    $page = 1;
} elseif ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

// Récupération des propriétés avec pagination et filtres
$proprietes = getProprietes($pdo, $filters, $limit, $offset);

// Récupération des catégories pour le filtre
$categories = getCategories($pdo);

// Récupération des villes distinctes pour le filtre
$villes = getVillesDistinctes($pdo);
?>

<!-- Bannière de page -->
<section class="bg-primary text-white py-5 mb-5">
    <div class="container">
        <h1 class="fw-bold">Nos propriétés</h1>
        <p class="lead">Découvrez notre sélection de biens immobiliers disponibles</p>
    </div>
</section>

<div class="container">
    <div class="row">
        <!-- Sidebar filtres -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Filtres de recherche</h5>
                </div>
                <div class="card-body">
                    <form action="proprietes.php" method="get" id="filter-form">
                        <div class="mb-3">
                            <label for="categorie" class="form-label">Type de bien</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Tous les types</option>
                                <?php foreach ($categories as $categorie): ?>
                                <option value="<?php echo $categorie['id']; ?>" <?php echo (isset($filters['categorie']) && $filters['categorie'] == $categorie['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categorie['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ville" class="form-label">Ville</label>
                            <select class="form-select" id="ville" name="ville">
                                <option value="">Toutes les villes</option>
                                <?php foreach ($villes as $ville): ?>
                                <option value="<?php echo htmlspecialchars($ville); ?>" <?php echo (isset($filters['ville']) && $filters['ville'] == $ville) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ville); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="prix_min" class="form-label">Prix minimum</label>
                            <input type="number" class="form-control" id="prix_min" name="prix_min" value="<?php echo isset($filters['prix_min']) ? $filters['prix_min'] : ''; ?>" placeholder="Ex: 100000">
                        </div>
                        
                        <div class="mb-3">
                            <label for="prix_max" class="form-label">Prix maximum</label>
                            <input type="number" class="form-control" id="prix_max" name="prix_max" value="<?php echo isset($filters['prix_max']) ? $filters['prix_max'] : ''; ?>" placeholder="Ex: 500000">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nb_chambres_min" class="form-label">Chambres (min)</label>
                            <select class="form-select" id="nb_chambres_min" name="nb_chambres_min">
                                <option value="">Indifférent</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($filters['nb_chambres_min']) && $filters['nb_chambres_min'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i . ($i == 5 ? '+' : ''); ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
                        <a href="proprietes.php" class="btn btn-outline-secondary w-100 mt-2">Réinitialiser</a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Liste des propriétés -->
        <div class="col-lg-9">
            <?php if (empty($proprietes)): ?>
                <div class="alert alert-info">
                    <p class="mb-0">Aucune propriété ne correspond à vos critères de recherche.</p>
                </div>
            <?php else: ?>
                <!-- Résultats et tri -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0"><?php echo $total_proprietes; ?> propriété<?php echo $total_proprietes > 1 ? 's' : ''; ?> trouvée<?php echo $total_proprietes > 1 ? 's' : ''; ?></p>
                    <div class="d-flex align-items-center">
                        <label for="tri" class="me-2">Trier par:</label>
                        <select class="form-select form-select-sm" id="tri" name="tri" onchange="document.getElementById('filter-form').submit();">
                            <option value="date_publication DESC" <?php echo (!isset($filters['tri']) || $filters['tri'] == 'date_publication DESC') ? 'selected' : ''; ?>>Plus récents</option>
                            <option value="prix ASC" <?php echo (isset($filters['tri']) && $filters['tri'] == 'prix ASC') ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="prix DESC" <?php echo (isset($filters['tri']) && $filters['tri'] == 'prix DESC') ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="superficie DESC" <?php echo (isset($filters['tri']) && $filters['tri'] == 'superficie DESC') ? 'selected' : ''; ?>>Superficie</option>
                        </select>
                    </div>
                </div>
                
                <!-- Grille de propriétés -->
                <div class="row g-4">
                    <?php foreach ($proprietes as $propriete): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <?php if (!empty($propriete['image_principale'])): ?>
                                        <img src="<?php echo htmlspecialchars($propriete['image_principale']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($propriete['titre']); ?>" style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <img src="/fred/assets/images/no-image.jpg" class="card-img-top" alt="Pas d'image disponible" style="height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                    <span class="badge bg-primary position-absolute top-0 end-0 m-2"><?php echo htmlspecialchars($propriete['categorie_nom']); ?></span>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($propriete['titre']); ?></h5>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                        <?php echo htmlspecialchars($propriete['ville']); ?>, <?php echo htmlspecialchars($propriete['code_postal']); ?>
                                    </p>
                                    <p class="card-text fw-bold text-primary fs-5"><?php echo formatPrix($propriete['prix']); ?></p>
                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                        <?php if ($propriete['superficie']): ?>
                                            <span><i class="fas fa-ruler-combined me-1"></i> <?php echo $propriete['superficie']; ?> m²</span>
                                        <?php endif; ?>
                                        <?php if ($propriete['nb_chambres']): ?>
                                            <span><i class="fas fa-bed me-1"></i> <?php echo $propriete['nb_chambres']; ?> ch.</span>
                                        <?php endif; ?>
                                        <?php if ($propriete['nb_salles_bain']): ?>
                                            <span><i class="fas fa-bath me-1"></i> <?php echo $propriete['nb_salles_bain']; ?> SdB</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="/fred/propriete.php?id=<?php echo $propriete['id']; ?>" class="btn btn-outline-primary w-100">Voir le détail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Pagination des propriétés" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Précédent">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo http_build_query(array_filter($filters)); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters)); ?>" aria-label="Suivant">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
