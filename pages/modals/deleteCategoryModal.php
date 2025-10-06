<?php foreach ($categories as $category): ?>
<div class="modal fade" id="deleteCategoryModal-<?= $category['id'] ?>" tabindex="-1" aria-labelledby="deleteCategoryModalLabel-<?= $category['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../pages/delete_category.php" method="POST"> <!-- Metodo POST -->
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel-<?= $category['id'] ?>">Conferma Eliminazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Sei sicuro di voler eliminare la categoria <strong><?= htmlspecialchars($category['name']) ?></strong>?
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" value="<?= $category['id'] ?>"> <!-- Passaggio dell'ID -->
                    <button type="submit" class="btn btn-danger">Elimina</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
