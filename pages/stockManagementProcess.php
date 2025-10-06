<?php
session_start();
require_once '../function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leggi i campi inviati dal form
    $barcode  = trim($_POST['barcodeStock'] ?? '');
    $action   = $_POST['stockAction'] ?? '';
    $quantity = (int)($_POST['stockQuantity'] ?? 0);

    // Controlli di base
    if (empty($barcode) || empty($action) || $quantity <= 0) {
        $_SESSION['error'] = 'Dati non validi. Inserire codice a barre, selezionare operazione e una quantità valida.';
        header('Location: ../pages/dashboard.php');
        exit;
    }

    // Connessione al DB
    $db = connectDB();

    // Trova il prodotto tramite barcode
    $stmt = $db->prepare("SELECT id, name, quantity FROM products WHERE barcode = ?");
    $stmt->execute([$barcode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = 'Prodotto non trovato con il codice a barre inserito.';
        header('Location: ../pages/dashboard.php');
        exit;
    }

    $currentQuantity = (int)$product['quantity'];
    $productId       = $product['id'];

    if ($action === 'carico') {
        // Aggiungi quantità
        $newQuantity = $currentQuantity + $quantity;
        $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        if ($stmt->execute([$newQuantity, $productId])) {
            $_SESSION['success'] = "Carico effettuato con successo. ({$quantity} unità aggiunte)";
        } else {
            $_SESSION['error'] = 'Errore durante il carico del prodotto.';
        }

    } elseif ($action === 'scarico') {
        // Verifica che la quantità disponibile sia sufficiente
        if ($currentQuantity < $quantity) {
            $_SESSION['error'] = 'Non è possibile scaricare più prodotti di quanti ne siano disponibili.';
            header('Location: ../pages/dashboard.php');
            exit;
        }
        $newQuantity = $currentQuantity - $quantity;
        $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        if ($stmt->execute([$newQuantity, $productId])) {
            $_SESSION['success'] = "Scarico effettuato con successo. ({$quantity} unità rimosse)";
        } else {
            $_SESSION['error'] = 'Errore durante lo scarico del prodotto.';
        }

    } else {
        $_SESSION['error'] = 'Operazione non riconosciuta.';
    }

    header('Location: ../pages/dashboard.php');
    exit;
} else {
    $_SESSION['error'] = 'Richiesta non valida.';
    header('Location: ../pages/dashboard.php');
    exit;
}
