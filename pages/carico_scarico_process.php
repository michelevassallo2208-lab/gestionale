<?php
session_start();
require_once '../function.php'; // Assicurati che questo percorso sia corretto

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recupero e Sanificazione dei Dati
    $barcode  = trim($_POST['barcodeStock'] ?? '');
    $action   = $_POST['stockAction'] ?? '';
    $quantity = (int)($_POST['stockQuantity'] ?? 0);
    $notes    = trim($_POST['stockNotes'] ?? ''); // Recupera le note


    // 2. Validazione dei Dati (Rigorosa)
    if (empty($barcode)) {
        $error = 'Inserisci un codice a barre.';
    } elseif (!in_array($action, ['carico', 'scarico'])) {
        $error = 'Operazione non valida. Scegli "carico" o "scarico".';
    } elseif ($quantity <= 0) {
        $error = 'Inserisci una quantità valida (maggiore di 0).';
    }
    // Potresti aggiungere una validazione per la lunghezza massima delle note, se necessario

    if (isset($error)) {
        $_SESSION['error'] = $error;
        logActivity(
            $_SESSION['user_id'] ?? 'Unknown', // Gestisce il caso in cui user_id non sia impostato
            'errore_carico_scarico',
            "Tentativo con dati non validi: barcode='{$barcode}', action='{$action}', quantity='{$quantity}', notes='{$notes}'.",
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );
        header('Location: carico_scarico.php');
        exit;
    }


    // 3. Connessione al Database (con gestione errori)
    $db = connectDB();
    if (!$db) {
        $_SESSION['error'] = 'Errore di connessione al database.';
        logActivity(
            $_SESSION['user_id'] ?? 'Unknown',
            'errore_connessione_db',
            "Errore di connessione al database.",
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );
        header('Location: carico_scarico.php');
        exit;
    }

    // 4. Ricerca Prodotto (con Prepared Statement e gestione errori)
    $stmt = $db->prepare("SELECT id, name, quantity FROM products WHERE barcode = ?");
    if (!$stmt) {
        $_SESSION['error'] = 'Errore nella preparazione della query.';
        logActivity($_SESSION['user_id'] ?? 'Unknown', 'errore_db', "Errore preparazione query: " . $db->errorInfo()[2], $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        header('Location: carico_scarico.php');
        exit;
    }
    $stmt->execute([$barcode]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = 'Prodotto non trovato.';
        logActivity($_SESSION['user_id'] ?? 'Unknown', 'errore_carico_scarico', "Prodotto non trovato per barcode='{$barcode}'.", $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        header('Location: carico_scarico.php');
        exit;
    }

    // 5. Logica di Carico/Scarico (con Transazioni ed Eccezioni)
    $currentQuantity = (int)$product['quantity'];
    $productId       = $product['id'];

    $db->beginTransaction(); // Inizia una transazione

    try {
        if ($action === 'carico') {
            $newQuantity = $currentQuantity + $quantity;
            $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $productId]);
            logActivity($_SESSION['user_id'], 'carico', "Carico di {$quantity} unità per prodotto ID {$productId} ('{$product['name']}').  Pre: {$currentQuantity}, Post: {$newQuantity}.", $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
            $_SESSION['success'] = "Carico effettuato con successo. {$quantity} unità aggiunte a '{$product['name']}'.";

        } elseif ($action === 'scarico') {
            if ($currentQuantity < $quantity) {
              throw new Exception("Quantità non disponibile.  Disponibile: {$currentQuantity}, Richiesto: {$quantity}");
            }
            $newQuantity = $currentQuantity - $quantity;

            //--- Salvataggio delle note (con storico) ---
            $stmt = $db->prepare("UPDATE products SET quantity = ?, notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ?");
            $notesToAppend = empty($notes) ? "" : "\n" . date('Y-m-d H:i:s') . " - Scarico: " . $notes; // Formattazione nota con data/ora
            $stmt->execute([$newQuantity, $notesToAppend, $productId]);
            //------------------------------------------------

            $logMessage = "Scarico di {$quantity} unità per prodotto ID {$productId} ('{$product['name']}'). Pre: {$currentQuantity}, Post: {$newQuantity}.";
            if (!empty($notes)) {
                $logMessage .= " Note: " . $notes;
            }
            logActivity($_SESSION['user_id'], 'scarico', $logMessage, $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
            $_SESSION['success'] = "Scarico effettuato con successo. {$quantity} unità rimosse da '{$product['name']}'.";

        }

        $db->commit(); // Commit della transazione

    } catch (Exception $e) {
        $db->rollBack(); // Rollback in caso di errore
        $_SESSION['error'] = 'Errore durante l\'operazione: ' . $e->getMessage();
        logActivity($_SESSION['user_id'] ?? 'Unknown', 'errore_carico_scarico', "Errore durante {$action}: " . $e->getMessage(), $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    }

    header('Location: carico_scarico.php');
    exit;

} else {
    // Gestione richieste non POST (accesso diretto)
    $_SESSION['error'] = 'Richiesta non valida.';
    logActivity($_SESSION['user_id'] ?? 'Unknown', 'errore_richiesta_non_valida', "Tentativo di accesso diretto a carico_scarico_process.php.", $_SERVER['REMOTE_ADDR'] ?? 'Unknown', $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    header('Location: carico_scarico.php');
    exit;
}
?>