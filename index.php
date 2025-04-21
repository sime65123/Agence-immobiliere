<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Connexion à la base de données
$pdo = getDbConnection();

// Récupération des propriétés vedettes
$proprietes_vedettes = getProprietes($pdo, ['est_vedette' => 1, 'disponibilite' => 1], 6);
?>

<!-- Section Hero -->
<section class="hero py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Trouvez la propriété de vos rêves</h1>
                <p class="lead mb-4">Notre agence immobilière vous accompagne dans tous vos projets immobiliers. Achat, vente, location, nous sommes là pour vous aider à concrétiser vos rêves.</p>
                <div class="d-flex gap-3">
                    <a href="/fred/proprietes.php" class="btn btn-primary btn-lg">Voir nos propriétés</a>
                    <a href="/fred/contact.php" class="btn btn-outline-secondary btn-lg">Nous contacter</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="/fred/assets/images/hero-image.jpeg" alt="Propriété de luxe" class="img-fluid rounded shadow-lg">
            </div>
        </div>
    </div>
</section>

<!-- Section Recherche rapide -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h2 class="h4 mb-4">Recherche rapide</h2>
                <form action="/fred/proprietes.php" method="get">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="categorie" class="form-label">Type de bien</label>
                            <select class="form-select" id="categorie" name="categorie">
                                <option value="">Tous les types</option>
                                <?php 
                                $categories = getCategories($pdo);
                                foreach ($categories as $categorie): 
                                ?>
                                <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="ville" class="form-label">Ville</label>
                            <select class="form-select" id="ville" name="ville">
                                <option value="">Toutes les villes</option>
                                <?php 
                                $villes = getVillesDistinctes($pdo);
                                foreach ($villes as $ville): 
                                ?>
                                <option value="<?php echo htmlspecialchars($ville); ?>"><?php echo htmlspecialchars($ville); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="prix_max" class="form-label">Budget max</label>
                            <select class="form-select" id="prix_max" name="prix_max">
                                <option value="">Sans limite</option>
                                <option value="100000">100 000 €</option>
                                <option value="200000">200 000 €</option>
                                <option value="300000">300 000 €</option>
                                <option value="500000">500 000 €</option>
                                <option value="1000000">1 000 000 €</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Section Propriétés Vedettes -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-4">Nos propriétés vedettes</h2>
                <p class="text-center text-muted">Découvrez nos biens immobiliers les plus exceptionnels</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php if (empty($proprietes_vedettes)): ?>
                <div class="col-12 text-center">
                    <p>Aucune propriété vedette disponible pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($proprietes_vedettes as $propriete): ?>
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
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="/fred/proprietes.php" class="btn btn-lg btn-outline-primary">Voir toutes nos propriétés</a>
        </div>
    </div>
</section>

<!-- Section Services -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="mb-4">Nos services</h2>
                <p class="text-muted">Nous vous proposons une gamme complète de services immobiliers</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-home fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Achat de bien</h5>
                        <p class="card-text text-muted">Nous vous accompagnons dans la recherche et l'acquisition de votre bien immobilier idéal.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-key fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Vente de bien</h5>
                        <p class="card-text text-muted">Nous vous aidons à vendre votre propriété au meilleur prix et dans les meilleures conditions.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-chart-line fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Estimation</h5>
                        <p class="card-text text-muted">Nous réalisons une estimation précise de votre bien immobilier basée sur le marché actuel.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-file-contract fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title">Conseils juridiques</h5>
                        <p class="card-text text-muted">Nous vous apportons notre expertise juridique pour sécuriser toutes vos transactions immobilières.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Pourquoi nous choisir -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="/fred/assets/images/about-us.jpeg" alt="Notre équipe" class="img-fluid rounded shadow">
            </div>
            <div class="col-lg-6">
                <h2 class="mb-4">Pourquoi nous choisir ?</h2>
                <p class="lead mb-4">Avec plus de 15 ans d'expérience dans le secteur immobilier, notre agence s'engage à vous offrir un service personnalisé et de qualité.</p>
                
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h5>Expertise locale</h5>
                        <p class="text-muted">Notre connaissance approfondie du marché local nous permet de vous proposer les meilleures opportunités.</p>
                    </div>
                </div>
                
                <div class="d-flex mb-3">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h5>Accompagnement personnalisé</h5>
                        <p class="text-muted">Nous vous accompagnons à chaque étape de votre projet immobilier, de la recherche à la signature.</p>
                    </div>
                </div>
                
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle text-primary fa-2x"></i>
                    </div>
                    <div>
                        <h5>Transparence et confiance</h5>
                        <p class="text-muted">Nous privilégions une relation de confiance basée sur la transparence et l'honnêteté.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Témoignages -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="mb-4">Ce que disent nos clients</h2>
                <p class="text-muted">Découvrez les témoignages de nos clients satisfaits</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-3">"Grâce à l'équipe de Real Estate, nous avons trouvé la maison de nos rêves en seulement quelques semaines. Un service impeccable et des conseils avisés !"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <span class="fw-bold">SL</span>
                            </div>
                            <div>
                                <h6 class="mb-0">Sophie Leclerc</h6>
                                <small class="text-muted">Propriétaire à Paris</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-3">"Une agence professionnelle et réactive. J'ai pu vendre mon appartement rapidement et au bon prix. Je recommande vivement leurs services !"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <span class="fw-bold">TM</span>
                            </div>
                            <div>
                                <h6 class="mb-0">Thomas Martin</h6>
                                <small class="text-muted">Vendeur à Lyon</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="card-text mb-3">"L'accompagnement personnalisé et les conseils judicieux de l'équipe m'ont permis de réaliser un investissement immobilier rentable. Merci pour votre expertise !"</p>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <span class="fw-bold">LD</span>
                            </div>
                            <div>
                                <h6 class="mb-0">Laura Dubois</h6>
                                <small class="text-muted">Investisseur à Bordeaux</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section CTA -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-9">
                <h2 class="mb-3">Prêt à concrétiser votre projet immobilier ?</h2>
                <p class="lead mb-lg-0">Contactez-nous dès aujourd'hui pour bénéficier de notre expertise et de nos conseils personnalisés.</p>
            </div>
            <div class="col-lg-3 text-lg-end">
                <a href="/fred/contact.php" class="btn btn-light btn-lg">Nous contacter</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
