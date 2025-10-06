<?php
session_start();
require_once '../function.php';

// Autenticazione e Autorizzazione
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'editor'])) {
    header('Location: ../index.php'); // Reindirizza a una pagina di accesso/errore
    exit;
}

$db = connectDB(); //Potrebbe non essere necessario qui.
if (!$db) {
  echo "<div class='alert alert-danger'>Errore di connessione al database.</div>";
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carico/Scarico - Gestionale Magazzino</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Contenitore per il video (Stili Migliorati) */
        #interactiveStock {
            position: relative;
            width: 100%;
            max-width: 400px;
            height: auto;
            margin: 0 auto 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        #interactiveStock video {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Overlay per il mirino */
        #interactiveStock .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            box-sizing: border-box;
        }
        #interactiveStock .scanline {
          position: absolute;
          top: 50%;
          left: 0;
          width: 100%;
          height: 3px;
          background-color: rgba(255, 0, 0, 0.5);
          transform: translateY(-50%);
          box-shadow: 0 0 10px rgba(255, 0, 0, 0.8);
          animation: scanlineAnimation 2s linear infinite;
        }

        @keyframes scanlineAnimation {
          0% { top: 0; }
          50% { top: 100%; }
          100% { top: 0; }
        }

        /* Pulsante Scansiona */
        @media (max-width: 576px) {
            #btnScanBarcodeStock {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
            #interactiveStock {
                max-width: 300px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.js" defer></script>
    <script src="../js/script.js" defer></script>
</head>
<body>
    <?php include '../templates/header.php'; ?>

    <div class="container-fluid" style="margin-top: 80px;">
        <h2 class="mb-4">Carico/Scarico Prodotti</h2>

        <?php
        // Visualizzazione Messaggi (con stile Bootstrap)
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['success']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
            unset($_SESSION['success']);
        }

        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['error']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
            unset($_SESSION['error']);
        }
        ?>

        <form action="carico_scarico_process.php" method="POST">
            <div class="mb-3">
                <label for="barcodeStock" class="form-label">Codice a Barre</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="barcodeStock" name="barcodeStock" placeholder="Inserisci o scansiona" required>
                    <button type="button" class="btn btn-secondary" id="btnScanBarcodeStock" title="Scansiona">
                        <i class="fas fa-camera"></i> Scansiona
                    </button>
                </div>
            </div>

            <div id="interactiveStock" class="mb-3">
                <div class="overlay">
                    <div class="scanline"></div>
                </div>
            </div>

            <div class="mb-3">
                <label for="stockAction" class="form-label">Operazione</label>
                <select class="form-select" id="stockAction" name="stockAction" required>
                    <option value="carico">Carico</option>
                    <option value="scarico">Scarico</option>
                </select>
            </div>

            <div class="mb-3" id="notesContainer" style="display: none;">
                <label for="stockNotes" class="form-label">Note (Solo per Scarico)</label>
                <textarea class="form-control" id="stockNotes" name="stockNotes" rows="3" placeholder="Inserisci note sullo scarico (es. motivo, riferimento)"></textarea>
            </div>

            <div class="mb-3">
                <label for="stockQuantity" class="form-label">Quantità</label>
                <input type="number" class="form-control" id="stockQuantity" name="stockQuantity" value="1" min="1" required>
            </div>

            <button type="submit" class="btn btn-primary">Invia</button>
            <a href="../pages/dashboard.php" class="btn btn-secondary">Dashboard</a>
        </form>
    </div>

    <?php include '../templates/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestione visibilità campo note
        const stockActionSelect = document.getElementById('stockAction');
        const notesContainer = document.getElementById('notesContainer');

        stockActionSelect.addEventListener('change', function() {
            notesContainer.style.display = (this.value === 'scarico') ? 'block' : 'none';
        });


        // ---  Codice QuaggaJS (migliorato) ---
        const btnScanBarcodeStock = document.getElementById('btnScanBarcodeStock');
        const interactiveStock = document.getElementById('interactiveStock');
        const barcodeStockInput = document.getElementById('barcodeStock');
        let scannerRunning = false;

        btnScanBarcodeStock.addEventListener('click', function() {
            if (scannerRunning) {
                Quagga.stop();
                scannerRunning = false;
                interactiveStock.classList.remove('camera-on'); // Rimuovi classe per stile
                btnScanBarcodeStock.innerHTML = '<i class="fas fa-camera"></i> Scansiona'; // Ripristina testo pulsante
                btnScanBarcodeStock.classList.remove('btn-danger');
                btnScanBarcodeStock.classList.add('btn-secondary');
                return;
            }


            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: interactiveStock,
                    constraints: {
                        facingMode: "environment", // Usa la fotocamera posteriore (se disponibile)
                        width: { ideal: 640 },  // Imposta risoluzioni ideali
                        height: { ideal: 480 }
                    },
                      area: { // defines rectangle of the detection/localization area
                        top: "25%",    // top offset
                        right: "10%",  // right offset
                        left: "10%",   // left offset
                        bottom: "25%"  // bottom offset
                      },
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "i2of5_reader"
                    ],
                },

            }, function(err) {
                if (err) {
                    console.error(err);
                    alert("Errore nell'inizializzazione dello scanner: " + err); // Mostra un messaggio di errore all'utente
                    return;
                }
                Quagga.start();
                scannerRunning = true;
                interactiveStock.classList.add('camera-on'); // Aggiungi classe per stile
                btnScanBarcodeStock.innerHTML = '<i class="fas fa-stop"></i> Ferma'; // Cambia testo pulsante
                btnScanBarcodeStock.classList.add('btn-danger');
                btnScanBarcodeStock.classList.remove('btn-secondary');

            });

            Quagga.onDetected(function(result) {
                barcodeStockInput.value = result.codeResult.code;
                Quagga.stop();
                scannerRunning = false;
                 interactiveStock.classList.remove('camera-on');
                 btnScanBarcodeStock.innerHTML = '<i class="fas fa-camera"></i> Scansiona'; // Ripristina il testo
                btnScanBarcodeStock.classList.remove('btn-danger');
                btnScanBarcodeStock.classList.add('btn-secondary');

                // Opzionale: Invia automaticamente il form dopo la scansione (se lo desideri)
                // document.querySelector('form').submit();
            });

            // Gestione degli errori di Quagga (durante la scansione)
            Quagga.onProcessed(function(result) {
                var drawingCtx = Quagga.canvas.ctx.overlay;
                var drawingCanvas = Quagga.canvas.dom.overlay;

                if (result) {
                    if (result.boxes) {
                        drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.width), parseInt(drawingCanvas.height));
                        result.boxes.filter(function (box) {
                            return box !== result.box;
                        }).forEach(function (box) {
                            Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 2});
                        });
                    }

                    if (result.box) {
                        Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "#00F", lineWidth: 2});
                    }

                    if (result.codeResult && result.codeResult.code) {
                        // Disegna una linea di conferma (opzionale)
                        // Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 3});
                    }
                }
            });


        }); // Fine evento click btnScanBarcodeStock
    }); // Fine DOMContentLoaded
    </script>
</body>
</html>