<?php foreach ($categories as $category): ?>
<div class="modal fade" id="editCategoryModal-<?= $category['id'] ?>" tabindex="-1" aria-labelledby="editCategoryModalLabel-<?= $category['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../pages/edit_category.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel-<?= $category['id'] ?>">Modifica Categoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Categoria</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
