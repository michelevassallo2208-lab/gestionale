<?php if (isset($product)): ?>
    <?php
    // Recupera le sottocategorie per la categoria corrente del prodotto
    if ($product['category_id']) {
        $stmt = $db->prepare("SELECT id, name FROM subcategories WHERE category_id = ? ORDER BY name");
        $stmt->execute([$product['category_id']]);
        $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $subcategories = [];
    }

    // Verifica il ruolo dell'utente (assumiamo che $_SESSION['role'] contenga il ruolo dell'utente)
    $isReader = (isset($_SESSION['role']) && $_SESSION['role'] === 'read');
    ?>
    <div class="modal fade" id="editProductModal-<?= htmlspecialchars($product['id']) ?>" tabindex="-1" aria-labelledby="editProductModalLabel-<?= htmlspecialchars($product['id']) ?>" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="../pages/edit_product.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProductModalLabel-<?= htmlspecialchars($product['id']) ?>">Modifica Prodotto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                    </div>
                    <div class="modal-body">
                        <!-- ID nascosto -->
                        <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                        
                        <!-- Campi nascosti per categoria corrente -->
                        <input type="hidden" name="current_category_id" value="<?= htmlspecialchars($category_id ?? $product['category_id']) ?>">
                        <input type="hidden" name="current_subcategory_id" value="<?= htmlspecialchars($subcategory_id ?? $product['subcategory_id']) ?>">
                        
                        <!-- **Campo Nascosto per il Termine di Ricerca** -->
                        <input type="hidden" name="current_search_query" value="<?= $current_search ?>">

                        <!-- Nome -->
                        <div class="mb-3">
                            <label for="name-<?= htmlspecialchars($product['id']) ?>" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name-<?= htmlspecialchars($product['id']) ?>" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?> required>
                        </div>

                        <!-- Descrizione -->
                        <div class="mb-3">
                            <label for="description-<?= htmlspecialchars($product['id']) ?>" class="form-label">Descrizione</label>
                            <textarea class="form-control" id="description-<?= htmlspecialchars($product['id']) ?>" name="description" rows="3" <?= $isReader ? 'disabled' : '' ?> required><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Modello -->
                        <div class="mb-3">
                            <label for="model-<?= htmlspecialchars($product['id']) ?>" class="form-label">Modello</label>
                            <input type="text" class="form-control" id="model-<?= htmlspecialchars($product['id']) ?>" name="model" value="<?= htmlspecialchars($product['model'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?> required>
                        </div>

                        <!-- Produttore -->
                        <div class="mb-3">
                            <label for="manufacturer-<?= htmlspecialchars($product['id']) ?>" class="form-label">Produttore</label>
                            <input type="text" class="form-control" id="manufacturer-<?= htmlspecialchars($product['id']) ?>" name="manufacturer" value="<?= htmlspecialchars($product['manufacturer'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?> required>
                        </div>

                        <!-- Barcode -->
                        <div class="mb-3">
                            <label for="barcode-<?= htmlspecialchars($product['id']) ?>" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="barcode-<?= htmlspecialchars($product['id']) ?>" name="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?> required minlength="15" maxlength="15">
                        </div>

                        <!-- Serial Number (Opzionale) -->
                        <div class="mb-3">
                            <label for="serial_number-<?= htmlspecialchars($product['id']) ?>" class="form-label">Serial Number (Opzionale)</label>
                            <input type="text" class="form-control" id="serial_number-<?= htmlspecialchars($product['id']) ?>" name="serial_number" value="<?= htmlspecialchars($product['serial_number'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?>>
                        </div>

                        <!-- Categoria -->
                        <div class="mb-3">
                            <label for="category-<?= htmlspecialchars($product['id']) ?>" class="form-label">Categoria</label>
                            <select class="form-select" id="category-<?= htmlspecialchars($product['id']) ?>" name="category_id" <?= $isReader ? 'disabled' : '' ?> required>
                                <option value="">Seleziona Categoria</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['id']) ?>" <?= ($product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sottocategoria -->
                        <div class="mb-3">
                            <label for="subcategory-<?= htmlspecialchars($product['id']) ?>" class="form-label">Sottocategoria</label>
                            <select class="form-select" id="subcategory-<?= htmlspecialchars($product['id']) ?>" name="subcategory_id" data-selected-subcategory="<?= htmlspecialchars($product['subcategory_id']) ?>" <?= $isReader ? 'disabled' : '' ?> required>
                                <option value="">Seleziona Sottocategoria</option>
                                <?php foreach ($subcategories as $subcategory): ?>
                                    <option value="<?= htmlspecialchars($subcategory['id']) ?>" <?= ($product['subcategory_id'] == $subcategory['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subcategory['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Fornitore -->
                        <div class="mb-3">
                            <label for="supplier-<?= htmlspecialchars($product['id']) ?>" class="form-label">Fornitore</label>
                            <select class="form-select" id="supplier-<?= htmlspecialchars($product['id']) ?>" name="supplier_id" <?= $isReader ? 'disabled' : '' ?> required>
                                <option value="">Seleziona Fornitore</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <option value="<?= htmlspecialchars($supplier['id']) ?>" <?= ($product['supplier_id'] == $supplier['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($supplier['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Scaffale -->
                        <div class="mb-3">
                            <label for="shelf-<?= htmlspecialchars($product['id']) ?>" class="form-label">Scaffale</label>
                            <input type="text" class="form-control" id="shelf-<?= htmlspecialchars($product['id']) ?>" name="shelf" value="<?= htmlspecialchars($product['shelf'] ?? '') ?>" <?= $isReader ? 'disabled' : '' ?>>
                        </div>

                        <!-- Quantità -->
                        <div class="mb-3">
                            <label for="quantity-<?= htmlspecialchars($product['id']) ?>" class="form-label">Quantità</label>
                            <input type="number" class="form-control" id="quantity-<?= htmlspecialchars($product['id']) ?>" name="quantity" value="<?= htmlspecialchars($product['quantity'] ?? '0') ?>" min="1" <?= $isReader ? 'disabled' : '' ?> required>
                        </div>

                        <!-- Prezzo d'acquisto -->
                        <div class="mb-3">
                            <label for="purchase_price-<?= htmlspecialchars($product['id']) ?>" class="form-label">Prezzo d'acquisto (€)</label>
                            <input type="number" step="0.01" class="form-control" id="purchase_price-<?= htmlspecialchars($product['id']) ?>" name="purchase_price" value="<?= htmlspecialchars($product['purchase_price'] ?? '0.00') ?>" <?= $isReader ? 'disabled' : '' ?> required>
                        </div>

                        <!-- Disponibilità -->
                        <div class="mb-3">
                            <label for="availability-<?= htmlspecialchars($product['id']) ?>" class="form-label">Disponibilità</label>
                            <select class="form-select" id="availability-<?= htmlspecialchars($product['id']) ?>" name="availability" <?= $isReader ? 'disabled' : '' ?> required>
                                <option value="1" <?= ($product['availability'] == 1) ? 'selected' : '' ?>>Disponibile</option>
                                <option value="0" <?= ($product['availability'] == 0) ? 'selected' : '' ?>>Non Disponibile</option>
                            </select>
                        </div>

                        <!-- Azienda -->
                        <div class="mb-3">
                            <label for="company-<?= htmlspecialchars($product['id']) ?>" class="form-label">Azienda</label>
                            <select class="form-select" id="company-<?= htmlspecialchars($product['id']) ?>" name="company_id" <?= $isReader ? 'disabled' : '' ?> required>
                                <option value="">Seleziona Azienda</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= htmlspecialchars($company['id']) ?>" <?= ($product['company_id'] == $company['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" <?= $isReader ? 'disabled' : '' ?>>Annulla</button>
                        <button type="submit" class="btn btn-primary" <?= $isReader ? 'disabled' : '' ?>>Salva Modifiche</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
