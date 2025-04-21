<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Connexion à la base de données
$pdo = getDbConnection();
?>

<div class="container py-5">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-3">Nos Services</h1>
            <p class="lead text-muted">Découvrez les services proposés par notre agence immobilière</p>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-home fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Achat et Vente</h3>
                    <p class="text-muted">Notre équipe d'experts vous accompagne dans toutes les étapes de l'achat ou de la vente de votre bien immobilier.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-key fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Location</h3>
                    <p class="text-muted">Nous proposons un large choix de biens à louer et nous vous aidons à trouver le logement qui correspond à vos besoins.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Estimation</h3>
                    <p class="text-muted">Bénéficiez d'une estimation gratuite et précise de votre bien immobilier réalisée par nos experts du marché local.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Gestion locative</h3>
                    <p class="text-muted">Nous prenons en charge la gestion complète de vos biens locatifs : recherche de locataires, état des lieux, encaissement des loyers, etc.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-file-contract fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Conseil juridique</h3>
                    <p class="text-muted">Notre équipe juridique vous conseille sur tous les aspects légaux liés à vos transactions immobilières.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-camera fa-2x"></i>
                    </div>
                    <h3 class="h4 mb-3">Photographie professionnelle</h3>
                    <p class="text-muted">Nous réalisons des photos professionnelles de votre bien pour le mettre en valeur et attirer plus d'acheteurs potentiels.</p>
                    <a href="#" class="btn btn-outline-primary mt-3">En savoir plus</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h2 class="mb-3">Vous avez besoin d'un service personnalisé ?</h2>
                            <p class="lead mb-4">Notre équipe est à votre disposition pour répondre à toutes vos questions et vous proposer des solutions adaptées à vos besoins.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                            <a href="/fred/contact.php" class="btn btn-primary btn-lg">Contactez-nous</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
