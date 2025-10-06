<!-- Modal per Carico/Scarico prodotti -->
<div class="modal fade" id="stockManagementModal" tabindex="-1" aria-labelledby="stockManagementModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      
      <div class="modal-header">
        <h5 class="modal-title" id="stockManagementModalLabel">Carico/Scarico Prodotti</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form action="../pages/stockManagementProcess.php" method="POST">
        <div class="modal-body">
          
          <!-- Campo per il codice a barre -->
          <div class="mb-3">
            <label for="barcodeStock" class="form-label">Codice a Barre</label>
            <input 
              type="text" 
              class="form-control" 
              id="barcodeStock" 
              name="barcodeStock" 
              placeholder="Inserisci o scansiona il codice a barre"
              required
            >
          </div>
          
          <!-- Pulsante per la scansione con la fotocamera -->
          <button type="button" class="btn btn-secondary mb-3" id="btnScanBarcodeStock">
            Scansiona Codice a Barre
          </button>
          
          <!-- Div nascosto per il video in tempo reale di QuaggaJS -->
          <div 
            id="interactiveStock" 
            class="d-none" 
            style="position: relative; width: 100%; border: 1px solid #ddd; margin-bottom: 10px;"
          ></div>
          
          <!-- Selezione Carico/Scarico -->
          <div class="mb-3">
            <label for="stockAction" class="form-label">Operazione</label>
            <select class="form-select" id="stockAction" name="stockAction" required>
              <option value="carico">Carico</option>
              <option value="scarico">Scarico</option>
            </select>
          </div>
          
          <!-- Quantità -->
          <div class="mb-3">
            <label for="stockQuantity" class="form-label">Quantità</label>
            <input 
              type="number" 
              class="form-control" 
              id="stockQuantity" 
              name="stockQuantity" 
              value="1" 
              min="1"
              required
            >
          </div>
          
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
          <button type="submit" class="btn btn-primary">Invia</button>
        </div>
      </form>
      
    </div>
  </div>
</div>
