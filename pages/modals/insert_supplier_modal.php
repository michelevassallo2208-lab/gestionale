<!-- insert_supplier_modal.php -->
<div class="modal fade" id="insertSupplierModal" tabindex="-1" aria-labelledby="insertSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="../pages/insert_supplier.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="insertSupplierModalLabel">Inserisci Fornitore</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="supplierName" class="form-label">Nome Fornitore</label>
            <input type="text" class="form-control" id="supplierName" name="name" required>
          </div>
          <div class="mb-3">
            <label for="supplierAddress" class="form-label">Indirizzo</label>
            <textarea class="form-control" id="supplierAddress" name="address"></textarea>
          </div>
          <div class="mb-3">
            <label for="supplierPhone" class="form-label">Telefono</label>
            <input type="text" class="form-control" id="supplierPhone" name="phone">
          </div>
          <div class="mb-3">
            <label for="supplierEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="supplierEmail" name="email">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary">Inserisci</button>
        </div>
      </div>
    </form>
  </div>
</div>
