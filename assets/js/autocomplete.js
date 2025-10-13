/**
 * Système d'autocomplétion générique
 */
export class Autocomplete {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minLength: options.minLength || 2,
            debounceTime: options.debounceTime || 300,
            apiUrl: options.apiUrl,
            onSelect: options.onSelect || (() => {}),
            formatResult: options.formatResult || ((item) => item.label),
            extraParams: options.extraParams || {},
            placeholder: options.placeholder || 'Rechercher...'
        };

        this.resultsContainer = null;
        this.debounceTimer = null;
        this.selectedIndex = -1;
        this.results = [];

        this.init();
    }

    init() {
        // Créer le conteneur de résultats
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'autocomplete-results';
        this.resultsContainer.style.display = 'none';
        this.input.parentNode.style.position = 'relative';
        this.input.parentNode.appendChild(this.resultsContainer);

        // Événements
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('focus', () => {
            if (this.results.length > 0) {
                this.showResults();
            }
        });

        // Fermer au clic extérieur
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.hideResults();
            }
        });
    }

    handleInput(e) {
        const value = e.target.value.trim();

        clearTimeout(this.debounceTimer);

        if (value.length < this.options.minLength) {
            this.hideResults();
            return;
        }

        this.debounceTimer = setTimeout(() => {
            this.search(value);
        }, this.options.debounceTime);
    }

    handleKeydown(e) {
        if (!this.resultsContainer || this.resultsContainer.style.display === 'none') {
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
                this.updateSelection();
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectResult(this.results[this.selectedIndex]);
                }
                break;
            case 'Escape':
                this.hideResults();
                break;
        }
    }

    async search(query) {
        try {
            const params = new URLSearchParams({
                q: query,
                ...this.options.extraParams
            });

            const response = await fetch(`${this.options.apiUrl}?${params}`);

            if (!response.ok) {
                console.error('Erreur autocomplétion: Erreur lors de la recherche');
                this.hideResults();
                return;
            }

            this.results = await response.json();
            this.displayResults();
        } catch (error) {
            console.error('Erreur autocomplétion:', error);
            this.hideResults();
        }
    }

    displayResults() {
        if (this.results.length === 0) {
            this.hideResults();
            return;
        }

        this.resultsContainer.innerHTML = '';
        this.selectedIndex = -1;

        this.results.forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = this.options.formatResult(result);
            item.dataset.index = index;

            item.addEventListener('click', () => {
                this.selectResult(result);
            });

            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelection();
            });

            this.resultsContainer.appendChild(item);
        });

        this.showResults();
    }

    updateSelection() {
        const items = this.resultsContainer.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    selectResult(result) {
        this.options.onSelect(result);
        this.hideResults();
    }

    showResults() {
        this.resultsContainer.style.display = 'block';
    }

    hideResults() {
        this.resultsContainer.style.display = 'none';
        this.selectedIndex = -1;
    }

    destroy() {
        if (this.resultsContainer) {
            this.resultsContainer.remove();
        }
        clearTimeout(this.debounceTimer);
    }
}
