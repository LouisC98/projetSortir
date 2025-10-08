import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Menu mobile toggle (slide depuis la droite)
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menu-toggle');
    const menuClose = document.getElementById('menu-close');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileOverlay = document.getElementById('mobile-overlay');

    function openMenu() {
        if (mobileMenu && mobileOverlay) {
            mobileMenu.classList.remove('translate-x-full');
            mobileOverlay.classList.remove('hidden');
            // Empêcher le scroll du body quand le menu est ouvert
            document.body.style.overflow = 'hidden';
        }
    }

    function closeMenu() {
        if (mobileMenu && mobileOverlay) {
            mobileMenu.classList.add('translate-x-full');
            mobileOverlay.classList.add('hidden');
            // Réactiver le scroll du body
            document.body.style.overflow = '';
        }
    }

    // Ouvrir le menu
    if (menuToggle) {
        menuToggle.addEventListener('click', openMenu);
    }

    // Fermer le menu avec le bouton X
    if (menuClose) {
        menuClose.addEventListener('click', closeMenu);
    }

    // Fermer le menu en cliquant sur l'overlay
    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMenu);
    }

    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
});
