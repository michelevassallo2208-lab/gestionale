<?php
// dashboard.php
session_start();
require_once '../function.php';

// Connessione al DB
$db = connectDB();

// Recupera subcategory_id e search_query se presenti
$subcategory_id = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

// Recupera la selezione dell'azienda dalla sessione
$selected_company_id = isset($_SESSION['selected_company_id']) ? $_SESSION['selected_company_id'] : 'all';

// Inizializza variabili per i nomi di categoria e sottocategoria
$categoryName = null;
$subcategoryName = null;

// Filtro prodotti per azienda e categoria o ricerca
$products = [];
$params = [];
$whereClauses = [];

if ($subcategory_id) {
    // Filtra per subcategoria
    $whereClauses[] = 'products.subcategory_id = :subcategory_id';
    $params[':subcategory_id'] = $subcategory_id;

    // Ottieni il nome della categoria e della sottocategoria
    $stmt = $db->prepare("
        SELECT c.name AS category_name, s.name AS subcategory_name
        FROM categories c
        JOIN subcategories s ON c.id = s.category_id
        WHERE s.id = :subcategory_id
    ");
    $stmt->execute([':subcategory_id' => $subcategory_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $categoryName = $result['category_name'];
        $subcategoryName = $result['subcategory_name'];
    }
} elseif ($search_query && strlen($search_query) >= 3) {
    // Filtra per ricerca
    $whereClauses[] = '(products.name LIKE :search OR products.description LIKE :search OR products.barcode LIKE :search)';
    $params[':search'] = '%' . $search_query . '%';
}

// Filtra per azienda se selezionata
if ($selected_company_id !== 'all') {
    $whereClauses[] = 'products.company_id = :company_id';
    $params[':company_id'] = $selected_company_id;
}

$whereSQL = '';
if (count($whereClauses) > 0) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Conta i prodotti totali e non disponibili solo se categoria selezionata
if ($subcategory_id) {
    // Conta i prodotti totali per i filtri (categoria e azienda)
    $countQuery = "SELECT COUNT(*) AS total_products FROM products $whereSQL";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'] ?? 0;

    // Conta i prodotti non disponibili
    $unavailableProductsQuery = "SELECT COUNT(*) AS unavailable_products FROM products $whereSQL AND (quantity = 0 OR availability = 0)";
    $stmt = $db->prepare($unavailableProductsQuery);
    $stmt->execute($params);
    $unavailableProducts = $stmt->fetch(PDO::FETCH_ASSOC)['unavailable_products'] ?? 0;
} else {
    $totalProducts = 0;
    $unavailableProducts = 0;
}

// Paginazione
$logsPerPage = 30;
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $logsPerPage;

// Calcola il totale delle pagine se categoria selezionata
if ($subcategory_id) {
    $totalPages = ceil($totalProducts / $logsPerPage);
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $logsPerPage;
    }
}

// Recupera i prodotti per la pagina corrente se categoria selezionata o ricerca effettuata
if ($subcategory_id || ($search_query && strlen($search_query) >=3 )) {
    $productQuery = "SELECT products.*, suppliers.name AS supplier_name
                    FROM products
                    LEFT JOIN suppliers ON products.supplier_id = suppliers.id
                    $whereSQL
                    ORDER BY products.name
                    LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($productQuery);
    
    // Aggiungi i parametri
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    
    // Aggiungi i parametri limit e offset
    $stmt->bindValue(':limit', (int)$logsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = [];
}

// Recupera i fornitori
$stmt = $db->query("SELECT id, name FROM suppliers ORDER BY name");
$suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recupera tutte le aziende per export se necessario
$stmt = $db->query("SELECT id, name FROM companies ORDER BY name");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recupera tutte le azioni uniche per il filtro
$actions = ['accesso', 'accesso_fallito', 'logout', 'cancellazione', 'modifica', 'creazione', 'regola_stock', 'carico', 'scarico', 'duplicazione'];

// Recupera tutti gli utenti per il filtro
$stmt = $db->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Funzione per generare URL con i parametri di filtro e pagina
function buildUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'dashboard.php?' . http_build_query($params);
}

// Funzione per esportare in CSV
if (isset($_GET['export'])) {
    // Esporta tutti i prodotti filtrati
    $exportQuery = "SELECT products.*, suppliers.name AS supplier_name
                    FROM products
                    LEFT JOIN suppliers ON products.supplier_id = suppliers.id
                    $whereSQL
                    ORDER BY products.name";
    $stmt = $db->prepare($exportQuery);
    
    // Aggiungi i parametri di filtro
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }

    $stmt->execute();
    $exportProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Headers per forzare il download del file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="products_export.csv"');

    // Creazione del file CSV
    $output = fopen('php://output', 'w');
    // Aggiungi l'intestazione
    fputcsv($output, ['ID', 'Nome', 'Descrizione', 'Subcategoria', 'Azienda', 'Fornitore', 'Quantità', 'Disponibilità', 'Prezzo d\'acquisto', 'Scaffale', 'Barcode', 'Data di Creazione']);
    
    // Prepara una mappa per company_id e subcategory_id
    $companyMap = [];
    foreach ($companies as $company) {
        $companyMap[$company['id']] = $company['name'];
    }
    
    // Aggiungi i dati
    foreach ($exportProducts as $product) {
        // Mappa subcategory_id a nome subcategoria
        $subcategoryNameExport = 'N/A';
        if ($product['subcategory_id']) {
            // Fetch subcategory name
            $stmtSub = $db->prepare("SELECT name FROM subcategories WHERE id = ?");
            $stmtSub->execute([$product['subcategory_id']]);
            $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);
            if ($sub) {
                $subcategoryNameExport = $sub['name'];
            }
        }
        
        // Mappa company_id a nome azienda
        $companyName = isset($companyMap[$product['company_id']]) ? $companyMap[$product['company_id']] : 'N/A';

        fputcsv($output, [
            $product['id'],
            $product['name'],
            $product['description'],
            $subcategoryNameExport,
            $companyName,
            $product['supplier_name'] ?? 'N/A',
            $product['quantity'],
            ($product['quantity'] > 0) ? 'Disponibile' : 'Non Disponibile',
            number_format($product['purchase_price'], 2, ',', '.'),
            $product['shelf'] ?? 'N/A',
            $product['barcode'] ?? 'N/A',
            $product['created_at'] ?? 'N/A'
        ]);
    }

    fclose($output);
    exit();
}

// Passa le variabili alla maincard

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestionale Magazzino</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    
    <!-- Custom JavaScript -->
    <script src="../js/script.js" defer></script>
    <script src="../js/search.js" defer></script>
    <script src="../js/modalsearch.js" defer></script> <!-- Script essenziale per la ricerca -->
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include '../templates/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../templates/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-12 col-lg-10 ms-auto content">
                <div class="container-fluid pt-4">
                    <!-- Mostra i messaggi di successo o errore -->
                    <?php
                    if (isset($_SESSION['success'])) {
                        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['success']) . "
                              <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                              </div>";
                        unset($_SESSION['success']);
                    }

                    if (isset($_SESSION['error'])) {
                        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['error']) . "
                              <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                              </div>";
                        unset($_SESSION['error']);
                    }
                    ?>

                    <!-- Logo e Barra di Ricerca Migliorata e Condizionata -->
                    <?php if (!$subcategory_id && !$search_query): ?>
                        <div class="mb-4 text-center">
                            <!-- Logo -->
                            <a href="#">
                                <img src="../img/logo.png" alt="Logo" class="img-fluid mb-3" style="max-width: 200px;">
                            </a>
                            
                            <!-- Barra di Ricerca -->
                            <form id="searchForm" method="GET" action="dashboard.php" class="position-relative">
                                <input type="hidden" name="subcategory_id" value="<?= htmlspecialchars($subcategory_id ?? '') ?>">
                                <input type="text" id="searchInput" class="form-control form-control-lg ps-5" placeholder="Cerca prodotti per nome, descrizione o barcode" name="search" value="<?= htmlspecialchars($search_query ?? '') ?>">
                                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                                <?php if ($search_query): ?>
                                    <button class="btn btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-3" type="button" id="clear-search">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Include le main cards solo se una sottocategoria è selezionata -->
                    <?php if ($subcategory_id): ?>
                        <?php include '../templates/maincard.php'; ?>
                    <?php endif; ?>

                    <!-- Tabella dei Prodotti -->
                    <div id="searchResults">
                        <?php if (empty($products) && !$subcategory_id && !$search_query): ?>
                            <div class="alert alert-info">
                                Inserisci almeno 3 caratteri nella barra di ricerca per visualizzare i prodotti.
                            </div>
                        <?php elseif (!empty($products)): ?>
                            <?php include '../templates/maintable.php'; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Seleziona un filtro dalla sidebar o effettua una ricerca per visualizzare i prodotti.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../templates/footer.php'; ?>

    <!-- Script per il Pulsante di Cancellazione della Ricerca e Gestione del Form -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchForm = document.getElementById('searchForm');
            const clearButton = document.getElementById('clear-search');
            const searchInput = document.getElementById('searchInput');

            if (searchForm) {
                // Previeni la sottomissione del form tramite invio
                searchForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    // Chiama la funzione di ricerca nel tuo search.js
                    performSearch(searchInput.value);
                });
            }

            // Pulsante di Cancellazione
            if (clearButton) {
                clearButton.addEventListener('click', function () {
                    searchInput.value = '';
                    performSearch('');
                });
            }
        });
    </script>
</body>
</html>
