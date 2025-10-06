<!-- templates/modals/adjust_stock_modal.php -->
<?php if (isset($product)): ?>
    <div class="modal fade" id="adjustStockModal-<?= htmlspecialchars($product['id']) ?>" tabindex="-1" aria-labelledby="adjustStockModalLabel-<?= htmlspecialchars($product['id']) ?>" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../pages/adjust_stock.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adjustStockModalLabel-<?= htmlspecialchars($product['id']) ?>">Regola Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Campi per regolare lo stock -->
                        <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                        <div class="mb-3">
                            <label for="quantity-<?= htmlspecialchars($product['id']) ?>" class="form-label">Quantità</label>
                            <input type="number" class="form-control" id="quantity-<?= htmlspecialchars($product['id']) ?>" name="quantity" value="<?= htmlspecialchars($product['quantity']) ?>" required min="0">
                        </div>
                        <!-- Campo Note -->
                        <div class="mb-3">
                            <label for="note-<?= htmlspecialchars($product['id']) ?>" class="form-label">Note</label>
                            <textarea class="form-control" id="note-<?= htmlspecialchars($product['id']) ?>" name="note" rows="3" placeholder="Inserisci eventuali note..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="submit" class="btn btn-info">Aggiorna Stock</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
