// js/search.js

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');

    if (searchInput) {
        let timeout = null;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            clearTimeout(timeout);

            if (query.length >= 3) {
                timeout = setTimeout(function() {
                    fetch(`search_products.php?search=${encodeURIComponent(query)}`)
                        .then(response => response.text())
                        .then(html => {
                            searchResults.innerHTML = html;

                            // Inizializza le modali appena caricate
                            initializeEditModals();
                        })
                        .catch(error => {
                            console.error('Errore durante la ricerca:', error);
                        });
                }, 300); // Ritardo di 300ms per evitare troppe richieste
            } else if (query.length === 0) {
                // Mostra un messaggio quando l'input è vuoto
                searchResults.innerHTML = `<div class='alert alert-info'>Inserisci almeno 3 caratteri per cercare.</div>`;
            }
        });
    }

    function initializeEditModals() {
        // Seleziona tutte le dropdown delle categorie nelle modali
        const categoryDropdowns = document.querySelectorAll('[id^="category-"]');

        categoryDropdowns.forEach(function (dropdown) {
            const productId = dropdown.id.split('-')[1];
            const subcategorySelect = document.getElementById(`subcategory-${productId}`);
            const selectedSubcategoryId = subcategorySelect.getAttribute('data-selected-subcategory');

            dropdown.addEventListener('change', function () {
                const categoryId = this.value;

                subcategorySelect.innerHTML = '<option value="" selected>Caricamento...</option>';

                if (categoryId) {
                    fetch(`../pages/get_subcategories.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            subcategorySelect.innerHTML = '<option value="" selected>Seleziona Sottocategoria</option>';
                            data.forEach(subcategory => {
                                const isSelected = (subcategory.id == selectedSubcategoryId) ? 'selected' : '';
                                subcategorySelect.innerHTML += `<option value="${subcategory.id}" ${isSelected}>${subcategory.name}</option>`;
                            });
                        })
                        .catch(error => {
                            console.error('Errore durante il caricamento delle sottocategorie:', error);
                            subcategorySelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                        });
                } else {
                    subcategorySelect.innerHTML = '<option value="" selected>Seleziona prima una categoria</option>';
                }
            });

            // Rimuovi o commenta la seguente riga per evitare di sovrascrivere le sottocategorie pre-caricate
            // dropdown.dispatchEvent(new Event('change'));
        });
    }

    // Inizializza le modali nella pagina principale
    initializeEditModals();
});
