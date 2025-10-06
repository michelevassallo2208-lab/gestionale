<?php foreach ($subcategories as $subcategory): ?>
<div class="modal fade" id="deleteSubcategoryModal-<?= $subcategory['id'] ?>" tabindex="-1" aria-labelledby="deleteSubcategoryModalLabel-<?= $subcategory['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../pages/delete_subcategory.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubcategoryModalLabel-<?= $subcategory['id'] ?>">Conferma Eliminazione</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Sei sicuro di voler eliminare la sottocategoria <strong><?= htmlspecialchars($subcategory['name']) ?></strong>?
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id" value="<?= $subcategory['id'] ?>">
                    <button type="submit" class="btn btn-danger">Elimina</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
