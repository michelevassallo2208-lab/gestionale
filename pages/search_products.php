<?php
// search_products.php
session_start();
require_once '../function.php';


// Disabilita la visualizzazione degli errori per sicurezza
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

try {
    // Connessione al DB
    $db = connectDB();

    // Recupera il ruolo dell'utente
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'] ?? '';

    // Ottieni il termine di ricerca
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_query = $search;

    $products = [];

    if (strlen($search) >= 3) {
        // Filtra i prodotti per azienda se selezionata
        if (isset($_SESSION['selected_company_id']) && $_SESSION['selected_company_id'] !== 'all') {
            $company_id = $_SESSION['selected_company_id'];
            $stmt = $db->prepare("
                SELECT products.*, suppliers.name AS supplier_name
                FROM products
                LEFT JOIN suppliers ON products.supplier_id = suppliers.id
                WHERE company_id = ? AND (
                    products.name LIKE ? 
                    OR products.description LIKE ? 
                    OR products.barcode LIKE ?
                    OR products.serial_number LIKE ?
                    OR products.model LIKE ?
                )
            ");
            $like_search = '%' . $search . '%';
            $stmt->execute([$company_id, $like_search, $like_search, $like_search, $like_search, $like_search]);
        } else {
            // Filtra senza considerare l'azienda
            $stmt = $db->prepare("
                SELECT products.*, suppliers.name AS supplier_name
                FROM products
                LEFT JOIN suppliers ON products.supplier_id = suppliers.id
                WHERE 
                    products.name LIKE ? 
                    OR products.description LIKE ? 
                    OR products.barcode LIKE ?
                    OR products.serial_number LIKE ?
                    OR products.model LIKE ?
            ");
            $like_search = '%' . $search . '%';
            $stmt->execute([$like_search, $like_search, $like_search, $like_search, $like_search]);
        }
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Recupera i fornitori
    $stmt = $db->query("SELECT id, name FROM suppliers ORDER BY name");
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recupera le categorie
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recupera le aziende
    $stmt = $db->query("SELECT id, name FROM companies ORDER BY name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Includi la tabella principale con i prodotti filtrati
    include '../templates/maintable.php';
} catch (PDOException $e) {
    // Logga l'errore sul server
    error_log('Errore in search_products.php: ' . $e->getMessage());

    // Mostra un messaggio di errore all'utente
    echo '<div class="alert alert-danger">Si è verificato un errore durante la ricerca dei prodotti. Riprova più tardi.</div>';
}
?>
