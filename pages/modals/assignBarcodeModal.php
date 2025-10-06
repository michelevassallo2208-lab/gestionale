<!-- Custom styles for Barcode-Assign Modal -->
<style>
  /* Centered, slightly larger dialog */
  #assignBarcodeModal .modal-dialog {
    max-width: 500px;
    margin: 1.75rem auto;
  }

  /* Glass-style background */
  #assignBarcodeModal .modal-content {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border-radius: 1rem;
    border: none;
    overflow: hidden;
  }

  /* Gradient header */
  #assignBarcodeModal .modal-header {
    background: linear-gradient(135deg, #4e89ff, #67d0ff);
    color: #fff;
    border-bottom: none;
  }
  #assignBarcodeModal .modal-header .modal-title {
    font-weight: 600;
  }
  #assignBarcodeModal .modal-header .btn-close {
    filter: invert(1);
  }

  /* Body padding and form controls */
  #assignBarcodeModal .modal-body {
    padding: 1.5rem;
  }
  #assignBarcodeModal .form-label {
    font-weight: 500;
    color: #333;
  }
  #assignBarcodeModal .form-select,
  #assignBarcodeModal .form-control {
    border-radius: 0.75rem;
    border: 1px solid #ddd;
    padding: 0.75rem 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
  }
  #assignBarcodeModal .form-select:focus,
  #assignBarcodeModal .form-control:focus {
    border-color: #4e89ff;
    box-shadow: 0 0 0 0.2rem rgba(78, 137, 255, 0.25);
    outline: none;
  }

  /* Scan button */
  #assignBarcodeModal .btn-scan {
    background: #4e89ff;
    color: #fff;
    border-radius: 0.75rem;
    transition: background 0.3s;
  }
  #assignBarcodeModal .btn-scan:hover {
    background: #3b6ed8;
  }

  /* Assign button */
  #assignBarcodeModal .btn-assign {
    background: #28a745;
    color: #fff;
    border-radius: 0.75rem;
    transition: background 0.3s;
  }
  #assignBarcodeModal .btn-assign:hover {
    background: #218838;
  }

  /* Video feed container */
  #assignBarcodeModal #interactive {
    position: relative;
    width: 100%;
    height: 250px;
    border-radius: 0.75rem;
    overflow: hidden;
    background: #000;
  }

  /* Scanning red line animation */
  #assignBarcodeModal .scanline {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 3px;
    background: rgba(255, 0, 0, 0.7);
    box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
    animation: scanAnim 2s linear infinite;
  }
  @keyframes scanAnim {
    0%   { top: 0; }
    50%  { top: calc(100% - 3px); }
    100% { top: 0; }
  }
</style>

<!-- Modale per Assegnare Codice a Barre -->
<div class="modal fade" id="assignBarcodeModal" tabindex="-1" aria-labelledby="assignBarcodeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="assignBarcodeModalLabel">
          <i class="fas fa-barcode me-2"></i>Assegna Codice a Barre
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="../pages/assign_barcode_process.php" method="POST">
        <div class="modal-body">

          <div class="mb-3">
            <label for="product_id" class="form-label">Seleziona Prodotto</label>
            <select class="form-select" id="product_id" name="product_id" required>
              <?php
              require_once '../function.php';
              $db = connectDB();
              $stmt = $db->query("SELECT id,name FROM products ORDER BY name");
              $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
              foreach ($products as $p) {
                echo "<option value=\"".htmlspecialchars($p['id'])."\">".htmlspecialchars($p['name'])."</option>";
              }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="barcode" class="form-label">Codice a Barre</label>
            <input type="text" class="form-control" id="barcode" name="barcode"
                   placeholder="Inserisci o scansiona" required>
          </div>

          <button type="button" class="btn btn-scan mb-3 w-100" id="btnScanBarcode">
            <i class="fas fa-camera me-1"></i>Scansiona
          </button>

          <div id="interactive" class="d-none">
            <div class="scanline"></div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annulla
          </button>
          <button type="submit" class="btn btn-assign">
            <i class="fas fa-check me-1"></i>Assegna
          </button>
        </div>
      </form>

    </div>
  </div>
</div>
