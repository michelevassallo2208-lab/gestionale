// ../js/modalsearch.js

document.addEventListener('DOMContentLoaded', function () {
    // Delegation per gestire la sottomissione delle form nelle modali
    document.addEventListener('submit', function (event) {
        const form = event.target.closest('.modal-form');
        if (form) {
            console.log('Form trovata:', form); // Debug
            event.preventDefault();

            const formData = new FormData(form);
            const action = form.getAttribute('action');
            const method = form.getAttribute('method') || 'POST';

            // Trova il bottone di submit
            const submitButton = form.querySelector('button[type="submit"]');
            console.log('Bottone di submit:', submitButton); // Debug
            if (!submitButton) {
                console.error('Bottone di submit non trovato nella form.');
                showAlert('danger', 'Errore: Bottone di submit non trovato nella form.');
                return;
            }
            const originalButtonHTML = submitButton.innerHTML;

            // Disabilita il bottone e mostra uno spinner
            submitButton.disabled = true;
            submitButton.innerHTML = 'Elaborazione... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            fetch(action, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest' // Indica una richiesta AJAX
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Chiudi la modale
                        const modalElement = form.closest('.modal');
                        if (modalElement) {
                            const modalInstance = bootstrap.Modal.getInstance(modalElement);
                            if (modalInstance) {
                                modalInstance.hide();
                            } else {
                                console.warn('Modal instance not found.');
                            }
                        } else {
                            console.warn('Modal element not found.');
                        }

                        // Mostra un alert di successo
                        showAlert('success', data.message || 'Operazione completata con successo.');

                        // Aggiorna la tabella dei prodotti
                        refreshTable();
                    } else {
                        // Mostra un alert di errore
                        showAlert('danger', data.message || 'Si è verificato un errore durante l\'operazione.');
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    showAlert('danger', 'Si è verificato un errore durante l\'operazione.');
                })
                .finally(() => {
                    // Ripristina il bottone di submit
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHTML;
                });
        }
    });

    // Funzione per mostrare gli alert
    function showAlert(type, message) {
        // Crea l'elemento alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
        `;

        // Inserisci l'alert all'inizio del contenuto principale
        const mainContent = document.querySelector('main .container-fluid');
        if (mainContent) {
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
        } else {
            // Fallback: aggiungi all'inizio del body
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }

        // Auto-dismiss dopo 5 secondi
        setTimeout(() => {
            const alert = bootstrap.Alert.getInstance(alertDiv);
            if (alert) {
                alert.close();
            }
        }, 5000);
    }

    // Funzione per ricaricare la tabella dei prodotti
    function refreshTable() {
        const searchResults = document.getElementById('searchResults');
        const searchInput = document.getElementById('searchInput');

        if (searchInput && searchInput.value.trim().length >= 3) {
            // Trigger della ricerca per aggiornare i risultati
            const event = new Event('input');
            searchInput.dispatchEvent(event);
        } else {
            // Se non c'è una ricerca attiva, ricarica la pagina
            window.location.reload();
        }
    }
});
