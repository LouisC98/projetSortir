function initializeCommentHandlers() {
    // --- Gestion du formulaire principal d'ajout de commentaire ---
    const addCommentBtn = document.getElementById('addCommentBtn');
    const commentForm = document.getElementById('commentForm');

    if (addCommentBtn && commentForm) {
        const cancelBtn = commentForm.querySelector('button[type="button"]');

        const toggleCommentForm = () => {
            commentForm.classList.toggle('hidden');
            addCommentBtn.classList.toggle('hidden');
        };

        addCommentBtn.addEventListener('click', toggleCommentForm);
        if (cancelBtn) {
            cancelBtn.addEventListener('click', toggleCommentForm);
        }
    }

    // --- Gestion de l'édition des commentaires existants (via délégation d'événements) ---
    const commentsContainer = document.getElementById('commentsContainer');
    if (!commentsContainer) return;

    commentsContainer.addEventListener('click', async (event) => {
        const editBtn = event.target.closest('.edit-comment-btn');
        const cancelEditBtn = event.target.closest('.cancel-edit-btn');

        // Clic sur "Modifier"
        if (editBtn) {
            const commentId = editBtn.dataset.commentId;
            const container = editBtn.closest('.comment-container');
            const displayView = container.querySelector('.comment-display');
            const editView = container.querySelector('.comment-edit');

            try {
                const response = await fetch(`/comment/${commentId}/edit-form`);
                if (response.ok) {
                    editView.innerHTML = await response.text();
                    displayView.classList.add('hidden');
                    editView.classList.remove('hidden');
                } else {
                    console.error('Failed to fetch edit form');
                }
            } catch (error) {
                console.error('Error fetching edit form:', error);
            }
        }

        // Clic sur "Annuler"
        if (cancelEditBtn) {
            const container = cancelEditBtn.closest('.comment-container');
            const displayView = container.querySelector('.comment-display');
            const editView = container.querySelector('.comment-edit');
            
            displayView.classList.remove('hidden');
            editView.classList.add('hidden');
            editView.innerHTML = '';
        }
    });

    // Soumission du formulaire d'édition
    commentsContainer.addEventListener('submit', async (event) => {
        if (!event.target.matches('.comment-edit form')) return;

        event.preventDefault();

        const form = event.target;
        const container = form.closest('.comment-container');
        const editView = container.querySelector('.comment-edit');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const json = await response.json();
                const displayView = container.querySelector('.comment-display');

                const contentElement = displayView.querySelector('p.whitespace-pre-wrap');
                if (contentElement) {
                    contentElement.textContent = json.content;
                }

                displayView.classList.remove('hidden');
                editView.classList.add('hidden');
                editView.innerHTML = '';
            } else {
                editView.innerHTML = await response.text();
            }
        } catch (error) {
            console.error('Error submitting edit form:', error);
        }
    });
}

// Correctif : On n'utilise que l'événement turbo:load pour éviter le double attachement des écouteurs
document.addEventListener('turbo:load', initializeCommentHandlers);
