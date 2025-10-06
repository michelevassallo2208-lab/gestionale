<?php foreach ($subcategories as $subcategory): ?>
<div class="modal fade" id="editSubcategoryModal-<?= $subcategory['id'] ?>" tabindex="-1" aria-labelledby="editSubcategoryModalLabel-<?= $subcategory['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../pages/edit_subcategory.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSubcategoryModalLabel-<?= $subcategory['id'] ?>">Modifica Sottocategoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $subcategory['id'] ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome Sottocategoria</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($subcategory['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria Principale</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $subcategory['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
