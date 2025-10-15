function initializeThemeSwitcher() {
    const themeSwitchers = document.querySelectorAll('.theme-switcher-input');
    const html = document.documentElement;
    const logoLight = document.getElementById('logo-light');
    const logoDark = document.getElementById('logo-dark');
    const sunIcon = document.getElementById('sun-icon');
    const moonIcon = document.getElementById('moon-icon');

    function applyTheme(theme) {
        if (theme === 'dark') {
            html.classList.add('dark');
            
            // Basculer les logos
            if (logoLight && logoDark) {
                logoLight.classList.add('hidden');
                logoDark.classList.remove('hidden');
            }
            
            // Animer les icônes - Afficher la lune, cacher le soleil
            if (sunIcon && moonIcon) {
                sunIcon.classList.add('opacity-0', 'rotate-180', 'scale-0');
                moonIcon.classList.remove('opacity-0', 'rotate-180', 'scale-0');
                moonIcon.classList.add('rotate-0', 'scale-100');
            }

        } else {
            html.classList.remove('dark');
            
            // Basculer les logos
            if (logoLight && logoDark) {
                logoLight.classList.remove('hidden');
                logoDark.classList.add('hidden');
            }

            // Animer les icônes - Afficher le soleil, cacher la lune
            if (sunIcon && moonIcon) {
                sunIcon.classList.remove('opacity-0', 'rotate-180', 'scale-0');
                sunIcon.classList.add('rotate-0', 'scale-100');
                moonIcon.classList.add('opacity-0', 'rotate-180', 'scale-0');
                moonIcon.classList.remove('rotate-0', 'scale-100');
            }

        }
        themeSwitchers.forEach(switcher => {
            switcher.checked = (theme === 'dark');
        });
    }

    // Appliquer le thème au chargement de la page
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    themeSwitchers.forEach(switcher => {
        switcher.addEventListener('change', () => {
            const newTheme = switcher.checked ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
            applyTheme(newTheme);
        });
    });
}

// Initialiser au chargement et lors des navigations Turbo
document.addEventListener('DOMContentLoaded', initializeThemeSwitcher);
document.addEventListener('turbo:load', initializeThemeSwitcher);
document.addEventListener('turbo:render', initializeThemeSwitcher);
