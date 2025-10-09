let sortieFormInitialized = false;

function initializeSortieForm() {
    const citySelect = document.querySelector('#sortie_form_city');
    const placeSelect = document.querySelector('#sortie_form_place');

    if (!citySelect || !placeSelect) {
        sortieFormInitialized = false;
        return;
    }

    if (sortieFormInitialized) return;
    sortieFormInitialized = true;

    const addCityBtn = document.getElementById('add-city-btn');
    const cityModal = document.getElementById('city-modal');
    const cityModalOverlay = document.getElementById('city-modal-overlay');
    const closeModal = document.getElementById('close-modal');
    const cancelCity = document.getElementById('cancel-city');
    const saveCityBtn = document.getElementById('save-city');
    const cityError = document.getElementById('city-error');
    const cityErrorText = document.getElementById('city-error-text');

    const addPlaceBtn = document.getElementById('add-place-btn');
    const placeModal = document.getElementById('place-modal');
    const placeModalOverlay = document.getElementById('place-modal-overlay');
    const closePlaceModalBtn = document.getElementById('close-place-modal');
    const cancelPlace = document.getElementById('cancel-place');
    const savePlaceBtn = document.getElementById('save-place');
    const placeError = document.getElementById('place-error');
    const placeErrorText = document.getElementById('place-error-text');
    const placeDetails = document.getElementById('place-details');

    // Validation pour la ville
    function validateCity(name, postalCode) {
        if (!name || name.length < 2) {
            return 'Le nom de la ville doit contenir au moins 2 caractères';
        }
        if (name.length > 100) {
            return 'Le nom de la ville ne peut pas dépasser 100 caractères';
        }
        if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(name)) {
            return 'Le nom de la ville ne peut contenir que des lettres, espaces, traits d\'union et apostrophes';
        }
        if (!postalCode) {
            return 'Le code postal est requis';
        }
        if (!/^\d{5}$/.test(postalCode)) {
            return 'Le code postal doit contenir exactement 5 chiffres';
        }
        return null;
    }

    // Validation pour le lieu
    function validatePlace(name, street, latitude, longitude) {
        if (!name || name.length < 2) {
            return 'Le nom du lieu doit contenir au moins 2 caractères';
        }
        if (name.length > 255) {
            return 'Le nom du lieu ne peut pas dépasser 255 caractères';
        }
        if (!street || street.length < 5) {
            return 'L\'adresse doit contenir au moins 5 caractères';
        }
        if (street.length > 255) {
            return 'L\'adresse ne peut pas dépasser 255 caractères';
        }
        if (latitude && !/^-?\d+(\.\d+)?$/.test(latitude)) {
            return 'La latitude doit être un nombre valide';
        }
        if (latitude && (parseFloat(latitude) < -90 || parseFloat(latitude) > 90)) {
            return 'La latitude doit être entre -90 et 90';
        }
        if (longitude && !/^-?\d+(\.\d+)?$/.test(longitude)) {
            return 'La longitude doit être un nombre valide';
        }
        if (longitude && (parseFloat(longitude) < -180 || parseFloat(longitude) > 180)) {
            return 'La longitude doit être entre -180 et 180';
        }
        return null;
    }

    // Fonction pour fermer le modal ville
    function closeCityModal() {
        cityModal.classList.add('hidden');
        cityModalOverlay.classList.add('hidden');
        document.getElementById('new-city-name').value = '';
        document.getElementById('new-city-postal').value = '';
        cityError.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Fonction pour ouvrir le modal ville
    function openCityModal() {
        cityModal.classList.remove('hidden');
        cityModalOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.getElementById('new-city-name').focus();
    }

    // Fonction pour fermer le modal lieu
    function closePlaceModal() {
        placeModal.classList.add('hidden');
        placeModalOverlay.classList.add('hidden');
        document.getElementById('new-place-name').value = '';
        document.getElementById('new-place-street').value = '';
        document.getElementById('new-place-latitude').value = '';
        document.getElementById('new-place-longitude').value = '';
        placeError.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Fonction pour ouvrir le modal lieu
    function openPlaceModal() {
        if (!citySelect.value) {
            alert('Veuillez d\'abord sélectionner une ville');
            return;
        }
        placeModal.classList.remove('hidden');
        placeModalOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.getElementById('new-place-name').focus();
    }

    // Gestion du modal ville
    if (addCityBtn && cityModal && cityModalOverlay) {
        addCityBtn.addEventListener('click', openCityModal);
        closeModal?.addEventListener('click', closeCityModal);
        cancelCity?.addEventListener('click', closeCityModal);
        cityModalOverlay.addEventListener('click', closeCityModal);

        // Validation en temps réel pour la ville
        const cityNameInput = document.getElementById('new-city-name');
        const cityPostalInput = document.getElementById('new-city-postal');

        cityNameInput?.addEventListener('input', () => {
            cityError.classList.add('hidden');
        });

        cityPostalInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 5);
            cityError.classList.add('hidden');
        });

        saveCityBtn?.addEventListener('click', async () => {
            const name = document.getElementById('new-city-name').value.trim();
            const postalCode = document.getElementById('new-city-postal').value.trim();

            const validationError = validateCity(name, postalCode);
            if (validationError) {
                cityErrorText.textContent = validationError;
                cityError.classList.remove('hidden');
                return;
            }

            saveCityBtn.disabled = true;
            saveCityBtn.textContent = 'Enregistrement...';

            try {
                const response = await fetch('/city/new', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({name, postalCode})
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Erreur lors de l\'ajout');
                }

                const city = await response.json();
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                option.selected = true;
                citySelect.appendChild(option);

                closeCityModal();
                citySelect.dispatchEvent(new Event('change'));
            } catch (error) {
                cityErrorText.textContent = error.message || 'Erreur lors de l\'ajout de la ville';
                cityError.classList.remove('hidden');
            } finally {
                saveCityBtn.disabled = false;
                saveCityBtn.textContent = 'Enregistrer';
            }
        });
    }

    // Gestion du modal lieu
    if (addPlaceBtn && placeModal && placeModalOverlay) {
        addPlaceBtn.addEventListener('click', openPlaceModal);
        closePlaceModalBtn?.addEventListener('click', closePlaceModal);
        cancelPlace?.addEventListener('click', closePlaceModal);
        placeModalOverlay.addEventListener('click', closePlaceModal);

        // Validation en temps réel pour le lieu
        const placeNameInput = document.getElementById('new-place-name');
        const placeStreetInput = document.getElementById('new-place-street');
        const placeLatInput = document.getElementById('new-place-latitude');
        const placeLonInput = document.getElementById('new-place-longitude');

        [placeNameInput, placeStreetInput, placeLatInput, placeLonInput].forEach(input => {
            input?.addEventListener('input', () => {
                placeError.classList.add('hidden');
            });
        });

        placeLatInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^\d.-]/g, '');
        });

        placeLonInput?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^\d.-]/g, '');
        });

        savePlaceBtn?.addEventListener('click', async () => {
            const name = document.getElementById('new-place-name').value.trim();
            const street = document.getElementById('new-place-street').value.trim();
            const latitude = document.getElementById('new-place-latitude').value.trim();
            const longitude = document.getElementById('new-place-longitude').value.trim();
            const cityId = citySelect.value;

            const validationError = validatePlace(name, street, latitude, longitude);
            if (validationError) {
                placeErrorText.textContent = validationError;
                placeError.classList.remove('hidden');
                return;
            }

            savePlaceBtn.disabled = true;
            savePlaceBtn.textContent = 'Enregistrement...';

            try {
                const response = await fetch('/place/new', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name,
                        street,
                        latitude: latitude || null,
                        longitude: longitude || null,
                        cityId
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Erreur lors de l\'ajout');
                }

                const place = await response.json();

                const option = document.createElement('option');
                option.value = place.id;
                option.textContent = place.name;
                option.dataset.street = place.street;
                option.dataset.latitude = place.latitude || '';
                option.dataset.longitude = place.longitude || '';
                option.dataset.city = place.cityName || '';
                option.dataset.cp = place.postalCode || '';
                option.selected = true;
                placeSelect.appendChild(option);

                closePlaceModal();
                placeSelect.dispatchEvent(new Event('change'));
            } catch (error) {
                placeErrorText.textContent = error.message || 'Erreur lors de l\'ajout du lieu';
                placeError.classList.remove('hidden');
            } finally {
                savePlaceBtn.disabled = false;
                savePlaceBtn.textContent = 'Enregistrer';
            }
        });
    }

    // Filtrage des lieux par ville
    citySelect.addEventListener('change', function () {
        const cityId = this.value;

        placeSelect.innerHTML = '<option value="">-- Choisissez un lieu --</option>';
        placeSelect.disabled = !cityId;

        if (cityId) {
            fetch(`/places/by-city/${cityId}`)
                .then(response => response.json())
                .then(places => {
                    places.forEach(place => {
                        const option = document.createElement('option');
                        option.value = place.id;
                        option.textContent = place.name;
                        option.dataset.street = place.street || '';
                        option.dataset.latitude = place.latitude || '';
                        option.dataset.longitude = place.longitude || '';
                        option.dataset.city = place.cityName || '';
                        option.dataset.cp = place.postalCode || '';
                        placeSelect.appendChild(option);
                    });
                });

        }
    });

    // Affichage des détails du lieu
    if (placeSelect && placeDetails) {
        function loadPlaceInfos() {
            const selectedOption = this.options[this.selectedIndex];

            if (!this.value) {
                placeDetails.classList.add('hidden');
                return;
            }

            document.getElementById('place-street').textContent = selectedOption.dataset.street || 'N/A';
            document.getElementById('place-city').textContent = selectedOption.dataset.city || 'N/A';
            document.getElementById('place-cp').textContent = selectedOption.dataset.cp || 'N/A';
            document.getElementById('place-latitude').textContent = selectedOption.dataset.latitude || 'N/A';
            document.getElementById('place-longitude').textContent = selectedOption.dataset.longitude || 'N/A';

            placeDetails.classList.remove('hidden');
        }

        placeSelect.addEventListener('change', loadPlaceInfos);
        if (placeSelect.value) loadPlaceInfos.call(placeSelect);
    }

    // Fermer les modals avec la touche Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!cityModal.classList.contains('hidden')) closeCityModal();
            if (!placeModal.classList.contains('hidden')) closePlaceModal();
        }
    });
}

document.addEventListener('DOMContentLoaded', initializeSortieForm);
document.addEventListener('turbo:load', initializeSortieForm);
document.addEventListener('turbo:render', initializeSortieForm);
document.addEventListener('turbo:before-render', () => {
    sortieFormInitialized = false;
});
