<!-- Modale per la Mappa degli Scaffali -->
<div class="modal fade" id="shelfMapModal" tabindex="-1" aria-labelledby="shelfMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shelfMapModalLabel">Mappa degli Scaffali</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="shelf-map">
                    <?php
                    $columns = ['A', 'B', 'C', 'D', 'E', 'F','G', 'H', 'I', 'J']; // Lettere delle colonne
                    $rows = 4; // Numero di righe

                    for ($row = 1; $row <= $rows; $row++): ?>
                        <div class="shelf-row">
                            <?php foreach ($columns as $column): 
                                $shelfId = $column . $row; ?>
                                <div class="shelf" id="shelf-<?= $shelfId ?>" data-shelf-id="<?= $shelfId ?>">
                                    <?= $shelfId ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS per la Mappa degli Scaffali -->
<style>
    .shelf-map {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 20px 0;
    }

    .shelf-row {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .shelf {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border: 1px solid #ccc;
        background-color: #f8f9fa;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .shelf.active {
        background-color: #007bff;
        color: white;
    }
</style>

<!-- JavaScript per Evidenziare lo Scaffale -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shelfModal = document.getElementById('shelfMapModal');
        const shelves = document.querySelectorAll('.shelf');

        // Quando la modale viene mostrata
        shelfModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Bottone che ha attivato la modale
            const shelfId = button.getAttribute('data-shelf-id');

            // Rimuovi evidenziazione precedente
            shelves.forEach(shelf => shelf.classList.remove('active'));

            // Evidenzia lo scaffale selezionato
            const targetShelf = document.getElementById(`shelf-${shelfId}`);
            if (targetShelf) {
                targetShelf.classList.add('active');
            }
        });

        // Quando la modale viene chiusa
        shelfModal.addEventListener('hide.bs.modal', function () {
            shelves.forEach(shelf => shelf.classList.remove('active'));
        });
    });
</script>
