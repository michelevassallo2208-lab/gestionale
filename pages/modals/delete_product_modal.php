<!-- pages/modals/delete_product_modal.php -->
<?php if (isset($product)): ?>
    <div class="modal fade" id="deleteProductModal-<?= htmlspecialchars($product['id']) ?>" tabindex="-1" aria-labelledby="deleteProductModalLabel-<?= htmlspecialchars($product['id']) ?>" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../pages/delete_product.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteProductModalLabel-<?= htmlspecialchars($product['id']) ?>">Conferma Eliminazione</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        Sei sicuro di voler eliminare il prodotto "<strong><?= htmlspecialchars($product['name'] ?? 'N/A') ?></strong>"?
                        <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-danger">Elimina</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
