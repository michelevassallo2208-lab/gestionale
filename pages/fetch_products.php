<?php
session_start();
require_once '../function.php';

// Disabilita la visualizzazione degli errori
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Inizia l'output buffering
ob_start();

try {
    // Connessione al DB
    $db = connectDB();

    // Recupera i parametri
    $searchTerm = isset($_GET['query']) ? trim($_GET['query']) : '';
    $subcategory_id = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;

    // Recupera le categorie e i fornitori
    $categories = getCategories($db);
    $suppliers = getSuppliers($db);

    // Costruisci la query per i prodotti
    $productQuery = "
        SELECT 
            p.id, p.name, p.description, p.model, p.manufacturer, p.serial_number, p.quantity, p.availability, 
            p.purchase_price, p.shelf, s.name AS supplier_name, p.barcode, p.category_id, p.subcategory_id, p.supplier_id
        FROM products p
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        WHERE 1=1
    ";

    $params = [];

    if ($subcategory_id) {
        $productQuery .= " AND p.subcategory_id = :subcategory_id";
        $params[':subcategory_id'] = $subcategory_id;
    }

    if ($searchTerm !== '') {
        $productQuery .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $searchTerm . '%';
    }

    $productQuery .= " ORDER BY p.name";

    $stmt = $db->prepare($productQuery);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcola il totale dei prodotti basato sui filtri
    $countQuery = "SELECT COUNT(*) AS total_products FROM products p WHERE 1=1";
    $countParams = [];

    if ($subcategory_id) {
        $countQuery .= " AND p.subcategory_id = :subcategory_id";
        $countParams[':subcategory_id'] = $subcategory_id;
    }

    if ($searchTerm !== '') {
        $countQuery .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $countParams[':search'] = '%' . $searchTerm . '%';
    }

    $stmt = $db->prepare($countQuery);
    $stmt->execute($countParams);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'] ?? 0;

    // Calcola il totale dei prodotti non disponibili
    $unavailableQuery = "SELECT COUNT(*) AS unavailable_products FROM products p WHERE 1=1";
    $unavailableParams = [];

    if ($subcategory_id) {
        $unavailableQuery .= " AND p.subcategory_id = :subcategory_id";
        $unavailableParams[':subcategory_id'] = $subcategory_id;
    }

    if ($searchTerm !== '') {
        $unavailableQuery .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $unavailableParams[':search'] = '%' . $searchTerm . '%';
    }

    $unavailableQuery .= " AND (p.quantity = 0 OR p.availability = 0)";

    $stmt = $db->prepare($unavailableQuery);
    $stmt->execute($unavailableParams);
    $unavailableProducts = $stmt->fetch(PDO::FETCH_ASSOC)['unavailable_products'] ?? 0;

    // Prepara i dati per i template
    $categoryName = null;
    $subcategoryName = null;
    if ($subcategory_id) {
        // Recupera il nome della categoria e della sottocategoria
        $stmt = $db->prepare("SELECT c.name AS category_name, sc.name AS subcategory_name FROM subcategories sc JOIN categories c ON sc.category_id = c.id WHERE sc.id = ?");
        $stmt->execute([$subcategory_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $categoryName = $result['category_name'];
            $subcategoryName = $result['subcategory_name'];
        }
    }

    // Cattura l'output di maincard.php
    ob_start();
    include '../templates/maincard.php';
    $maincardHTML = ob_get_clean();

    // Cattura l'output di maintable.php o un messaggio appropriato
    if (!empty($products)) {
        ob_start();
        include '../templates/maintable.php';
        $maintableHTML = ob_get_clean();
    } else {
        if ($searchTerm !== '') {
            $maintableHTML = '<div class="alert alert-warning">Nessun prodotto trovato per la ricerca: ' . htmlspecialchars($searchTerm) . '</div>';
        } elseif ($subcategory_id) {
            $maintableHTML = '<div class="alert alert-warning">Nessun prodotto trovato per questa categoria.</div>';
        } else {
            $maintableHTML = '<div class="alert alert-info">Inserisci un termine di ricerca o seleziona una categoria per visualizzare i prodotti.</div>';
        }
    }

    // Pulisci l'output buffer iniziale
    ob_end_clean();

    // Restituisci la risposta in formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'maincard' => $maincardHTML,
        'maintable' => $maintableHTML
    ]);
} catch (Exception $e) {
    // Pulisci l'output buffer
    ob_end_clean();

    // Logga l'errore sul server
    error_log('Errore in fetch_products.php: ' . $e->getMessage());

    // Restituisci una risposta JSON di errore
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Si è verificato un errore durante il recupero dei prodotti. Riprova più tardi.'
    ]);
}
?>
