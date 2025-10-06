<?php
// pages/modals/destructionModal.php

// Assicurati che le variabili $product siano disponibili
if (!isset($product)) {
    die('Errore: Variabile $product non definita in destructionModal.php');
}
?>

<!-- Modale Distruggi Prodotto -->
<div class="modal fade" id="destroyProductModal-<?= htmlspecialchars($product['id']) ?>" tabindex="-1" aria-labelledby="destroyProductModalLabel-<?= htmlspecialchars($product['id']) ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form action="../pages/process_destroy.php" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="destroyProductModalLabel-<?= htmlspecialchars($product['id']) ?>">Distruggi Prodotto: <?= htmlspecialchars($product['name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>
                <div class="modal-body">
                    <!-- Campo Nascosto per l'ID del Prodotto -->
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                    
                    <!-- Campo per la Quantità da Distruggere -->
                    <div class="mb-3">
                        <label for="quantity_destroyed-<?= htmlspecialchars($product['id']) ?>" class="form-label">Quantità da Distruggere</label>
                        <input type="number" class="form-control" id="quantity_destroyed-<?= htmlspecialchars($product['id']) ?>" name="quantity_destroyed" min="1" max="<?= htmlspecialchars($product['quantity']) ?>" required>
                        <div class="form-text">Massimo: <?= htmlspecialchars($product['quantity']) ?></div>
                    </div>
                    
                    <!-- Campo per le Note/Motivazioni della Distruzione -->
                    <div class="mb-3">
                        <label for="destruction_notes-<?= htmlspecialchars($product['id']) ?>" class="form-label">Note/Motivazioni</label>
                        <textarea class="form-control" id="destruction_notes-<?= htmlspecialchars($product['id']) ?>" name="destruction_notes" rows="3" maxlength="500" placeholder="Inserisci le motivazioni della distruzione (max 500 caratteri)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">Conferma Distruzione</button>
                </div>
            </div>
        </form>
    </div>
</div>
