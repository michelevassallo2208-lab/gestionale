<?php
// maintable.php

// Verifica che $products sia definito e sia un array
if (!isset($products) || !is_array($products)) {
    echo '<tr><td colspan="11" class="text-center text-danger">Errore nel caricamento dei prodotti.</td></tr>';
    return;
}

// Inizializza search_query se non è già definito
$search_query = $search_query ?? (isset($_GET['search']) ? trim($_GET['search']) : null);

// Assicurati che $suppliers e $companies siano definiti
$suppliers = $suppliers ?? [];
$companies = $companies ?? [];
if (empty($companies)) {
    try {
        $stmt = $db->query("SELECT id, name FROM companies ORDER BY name");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Errore nel recupero delle aziende: ' . $e->getMessage());
        echo '<tr><td colspan="11" class="text-center text-danger">Errore nel recupero delle aziende.</td></tr>';
        return;
    }
}

// Recupera il ruolo dell'utente
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'] ?? '';
?>

<!-- Stili per spaziatura perfetta dei pulsanti -->
<style>
  .action-group {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .action-group .btn {
    margin: 0;
    padding: 0.35rem;
    border-radius: 0.5rem;
  }
</style>

<div class="table-container">
    <!-- Messaggi di successo o errore -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Modifica effettuata con successo!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
        </div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == 2): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Prodotto duplicato con successo!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
        </div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Si è verificato un errore durante la modifica del prodotto.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
        </div>
    <?php endif; ?>

    <!-- Nome Categoria e Sottocategoria -->
    <?php if (!empty($categoryName) && !empty($subcategoryName)): ?>
        <div class="mb-3">
            <h2 class="fw-bold"><?= htmlspecialchars($categoryName) ?> – <?= htmlspecialchars($subcategoryName) ?></h2>
        </div>
    <?php elseif (!empty($categoryName)): ?>
        <div class="mb-3">
            <h2 class="fw-bold"><?= htmlspecialchars($categoryName) ?></h2>
        </div>
    <?php endif; ?>

    <!-- Header della Tabella con Export -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Elenco Prodotti</h5>
        <div>
            <form method="GET" action="../pages/export_products.php" class="d-inline me-2">
                <?php 
                if ($selected_company_id !== 'all') {
                    echo '<input type="hidden" name="company_id" value="' . htmlspecialchars($selected_company_id) . '">';
                }
                if (!empty($subcategory_id)) {
                    echo '<input type="hidden" name="subcategory_id" value="' . htmlspecialchars($subcategory_id) . '">';
                }
                if ($search_query && strlen($search_query) >= 3) {
                    echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">';
                }
                ?>
                <button type="submit" name="export_total" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export totale
                </button>
            </form>
            <form method="GET" action="../pages/export_products.php" class="d-inline">
                <?php 
                if ($selected_company_id !== 'all') {
                    echo '<input type="hidden" name="company_id" value="' . htmlspecialchars($selected_company_id) . '">';
                }
                if (!empty($subcategory_id)) {
                    echo '<input type="hidden" name="subcategory_id" value="' . htmlspecialchars($subcategory_id) . '">';
                }
                if ($search_query && strlen($search_query) >= 3) {
                    echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">';
                }
                ?>
                <button type="submit" name="export_table" class="btn btn-primary">
                    <i class="fas fa-file-export"></i> Export tabella
                </button>
            </form>
        </div>
    </div>

    <!-- Tabella Prodotti -->
    <div class="table-responsive table-responsive-sm">
        <table class="table table-striped table-hover mb-0 w-100" id="productTable" data-sort-asc="true">
            <thead class="table-dark">
                <tr>
                    <th onclick="sortTable(0)" class="text-wrap">Nome <i class="fas fa-sort"></i></th>
                    <th class="d-none d-sm-table-cell text-wrap" onclick="sortTable(1)">Descrizione <i class="fas fa-sort"></i></th>
                    <th class="d-none d-md-table-cell text-wrap" onclick="sortTable(2)">Modello <i class="fas fa-sort"></i></th>
                    <th class="d-none d-md-table-cell text-wrap" onclick="sortTable(3)">Produttore <i class="fas fa-sort"></i></th>
                    <th class="d-none d-lg-table-cell text-wrap" onclick="sortTable(4)">Serial Number <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(5)" class="text-wrap">Quantità <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(6)" class="text-wrap">Disponibilità <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(7)" class="text-wrap">Prezzo d'acquisto <i class="fas fa-sort"></i></th>
                    <th class="d-none d-lg-table-cell text-wrap" onclick="sortTable(8)">Scaffale <i class="fas fa-sort"></i></th>
                    <th class="d-none d-lg-table-cell text-wrap" onclick="sortTable(9)">Barcode <i class="fas fa-sort"></i></th>
                    <th class="text-wrap">Azioni</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name'] ?? 'N/A') ?></td>
                        <td class="d-none d-sm-table-cell"><?= htmlspecialchars($product['description'] ?? 'N/A') ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($product['model'] ?? 'N/A') ?></td>
                        <td class="d-none d-md-table-cell"><?= htmlspecialchars($product['manufacturer'] ?? 'N/A') ?></td>
                        <td class="d-none d-lg-table-cell"><?= htmlspecialchars($product['serial_number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars((int)($product['quantity'] ?? 0)) ?></td>
                        <td>
                            <?php
                            $q = (int)($product['quantity'] ?? 0);
                            $avail = !empty($product['availability']);
                            if ($q === 0 || !$avail) {
                                echo '<span class="badge bg-danger">Non Disponibile</span>';
                            } else {
                                echo '<span class="badge bg-success">Disponibile</span>';
                            }
                            ?>
                        </td>
                        <td>€<?= number_format($product['purchase_price'] ?? 0, 2, ',', '.') ?></td>
                        <td class="d-none d-lg-table-cell"><?= htmlspecialchars($product['shelf'] ?? 'N/A') ?></td>
                        <td class="d-none d-lg-table-cell <?= strpos($product['barcode'] ?? '', 'DUP') === 0 ? 'bg-warning' : '' ?>">
                            <?= htmlspecialchars($product['barcode'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <!-- Modifica -->
                                <button class="btn btn-sm btn-warning btn-action" data-bs-toggle="modal"
                                        data-bs-target="#editProductModal-<?= htmlspecialchars($product['id']) ?>" title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Cancella, Regola Stock, Distruggi, Duplica -->
                                <?php if (in_array($user_role, ['admin','editor'])): ?>
                                    <button class="btn btn-sm btn-danger btn-action" data-bs-toggle="modal"
                                            data-bs-target="#deleteProductModal-<?= htmlspecialchars($product['id']) ?>" title="Elimina">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info btn-action" data-bs-toggle="modal"
                                            data-bs-target="#adjustStockModal-<?= htmlspecialchars($product['id']) ?>" title="Regola Stock">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-secondary btn-action" data-bs-toggle="modal"
                                            data-bs-target="#destroyProductModal-<?= htmlspecialchars($product['id']) ?>"
                                            title="Distruggi">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <form method="POST" action="../pages/duplicate_product.php" class="d-inline">
                                        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success btn-action" title="Duplica">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Storico -->
                                <a href="../pages/product_history.php?product_id=<?= htmlspecialchars($product['id']) ?>"
                                   class="btn btn-sm btn-info btn-action" title="Storico">
                                    <i class="fas fa-history"></i>
                                </a>

                                <!-- Nuovo: Carico/Scarico -->
                                <?php if (in_array($user_role, ['admin','editor'])): ?>
                                <button type="button"
                                        class="btn btn-sm btn-primary btn-action"
                                        data-bs-toggle="modal"
                                        data-bs-target="#stockModal"
                                        data-barcode="<?= htmlspecialchars($product['barcode']) ?>"
                                        title="Carico/Scarico">
                                    <i class="fas fa-box-open"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="text-center">Nessun prodotto trovato per i criteri selezionati.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Includi i modali esistenti (Edit, Delete, Stock adjustment, Destruction) -->
<?php if (!empty($products)): ?>
    <?php foreach ($products as $product): ?>
        <?php 
        include '../pages/modals/edit_product_modal.php';
        include '../pages/modals/delete_product_modal.php';
        include '../pages/modals/adjust_stock_modal.php';
        include '../pages/modals/destructionModal.php';
        ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Nuovo Modal Carico/Scarico -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="../pages/carico_scarico_process.php" method="POST" id="stockModalForm">
        <div class="modal-header">
          <h5 class="modal-title" id="stockModalLabel"><i class="fas fa-box-open me-2"></i>Carico / Scarico</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="barcodeStockModal" class="form-label">Codice a Barre</label>
            <input type="text" id="barcodeStockModal" name="barcodeStock" class="form-control" readonly>
          </div>
          <div class="mb-3">
            <label for="stockActionModal" class="form-label">Operazione</label>
            <select id="stockActionModal" name="stockAction" class="form-select" required>
              <option value="carico">Carico</option>
              <option value="scarico">Scarico</option>
            </select>
          </div>
          <div class="mb-3" id="notesContainerModal" style="display:none;">
            <label for="stockNotesModal" class="form-label">Note (solo per scarico)</label>
            <textarea id="stockNotesModal" name="stockNotes" class="form-control" rows="2"></textarea>
          </div>
          <div class="mb-3">
            <label for="stockQuantityModal" class="form-label">Quantità</label>
            <input type="number" id="stockQuantityModal" name="stockQuantity"
                   class="form-control" min="1" value="1" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Conferma</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Script Ordinamento & Tooltips & Stock Modal -->
<script>
// sortTable e tooltip (come prima) …
function sortTable(columnIndex) {
    const table = document.getElementById('productTable');
    const rows = Array.from(table.rows).slice(1);
    const isAsc = table.getAttribute('data-sort-asc') === 'true';
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].innerText.trim().toLowerCase();
        const bText = b.cells[columnIndex].innerText.trim().toLowerCase();
        if (!isNaN(aText) && !isNaN(bText)) {
            return isAsc ? aText - bText : bText - aText;
        }
        return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });
    rows.forEach(r => table.tBodies[0].appendChild(r));
    table.setAttribute('data-sort-asc', !isAsc);
    table.querySelectorAll('th').forEach((th, i) => {
        const icon = th.querySelector('i.fas');
        if (i === columnIndex) {
            icon.classList.toggle('fa-sort-up', !isAsc);
            icon.classList.toggle('fa-sort-down', isAsc);
            icon.classList.remove('fa-sort');
        } else {
            icon.classList.remove('fa-sort-up','fa-sort-down');
            icon.classList.add('fa-sort');
        }
    });
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[title]').forEach(el => new bootstrap.Tooltip(el));
    // Popola e resetta il modal di carico/scarico
    var stockModal = document.getElementById('stockModal');
    stockModal.addEventListener('show.bs.modal', function (event) {
      var button = event.relatedTarget;
      var barcode = button.getAttribute('data-barcode');
      stockModal.querySelector('#barcodeStockModal').value = barcode;
      stockModal.querySelector('#stockQuantityModal').value = 1;
      stockModal.querySelector('#stockActionModal').value = 'carico';
      stockModal.querySelector('#stockNotesModal').value = '';
      stockModal.querySelector('#notesContainerModal').style.display = 'none';
    });
    // Mostra/nascondi note quando cambio azione
    document.getElementById('stockActionModal')
      .addEventListener('change', function(){
        var notes = document.getElementById('notesContainerModal');
        notes.style.display = (this.value==='scarico')?'block':'none';
      });
});
</script>
