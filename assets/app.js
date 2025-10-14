import './bootstrap.js';
import './js/sortie_form.js';

/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// Menu mobile toggle (slide depuis la droite)
function initializeMobileMenu() {
    const menuToggle = document.getElementById('menu-toggle');
    const menuClose = document.getElementById('menu-close');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileOverlay = document.getElementById('mobile-overlay');

    if (!menuToggle || !mobileMenu || !mobileOverlay) return;

    function openMenu() {
        mobileMenu.classList.remove('translate-x-full');
        mobileOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        mobileMenu.classList.add('translate-x-full');
        mobileOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Ouvrir le menu
    menuToggle.addEventListener('click', openMenu);

    // Fermer le menu avec le bouton X
    if (menuClose) {
        menuClose.addEventListener('click', closeMenu);
    }

    // Fermer le menu en cliquant sur l'overlay
    mobileOverlay.addEventListener('click', closeMenu);

    // Fermer le menu avec la touche Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });
}

// Initialiser au chargement et lors des navigations Turbo
document.addEventListener('DOMContentLoaded', initializeMobileMenu);
document.addEventListener('turbo:load', initializeMobileMenu);
document.addEventListener('turbo:render', initializeMobileMenu);

import './js/comment_manager.js';