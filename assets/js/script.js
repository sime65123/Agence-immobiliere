/**
 * Script principal pour l'application Real Estate
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded');
    
    // Initialisation des tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialisation des popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Gestion du modal de réservation - Approche directe
    var reservationBtns = document.querySelectorAll('[data-bs-target="#reservationModal"]');
    
    if (reservationBtns.length > 0) {
        console.log('Reservation buttons found:', reservationBtns.length);
        
        reservationBtns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                console.log('Reservation button clicked');
                
                // Initialiser le modal directement
                var reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));
                reservationModal.show();
            });
        });
    } else {
        console.log('No reservation buttons found');
    }
    
    // Ouvrir automatiquement le modal si nécessaire (par exemple après une redirection)
    if (window.location.hash === '#reservation') {
        var reservationModal = new bootstrap.Modal(document.getElementById('reservationModal'));
        reservationModal.show();
    }
});

// Validation du formulaire de réservation
function validateReservationForm() {
    var dateVisite = document.getElementById('date_visite');
    
    if (!dateVisite.value) {
        alert('Veuillez sélectionner une date de visite.');
        return false;
    }
    
    // Vérifier que la date est dans le futur
    var selectedDate = new Date(dateVisite.value);
    var now = new Date();
    
    if (selectedDate <= now) {
        alert('La date de visite doit être dans le futur.');
        return false;
    }
    
    return true;
}
