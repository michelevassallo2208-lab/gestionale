<?php
// pages/process_destroy.php
session_start();

// Verifica se l'utente è autenticato e ha i permessi necessari
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../function.php';
$db = connectDB();

// Recupera i dati dal form
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity_destroyed = isset($_POST['quantity_destroyed']) ? (int)$_POST['quantity_destroyed'] : 0;
$destruction_notes = isset($_POST['destruction_notes']) ? trim($_POST['destruction_notes']) : '';

// Validazioni
if ($product_id <= 0 || $quantity_destroyed <= 0) {
    $_SESSION['error'] = "Dati invalidi per la distruzione del prodotto.";
    header('Location: ../pages/dashboard.php');
    exit();
}

try {
    // Inizia una transazione
    $db->beginTransaction();
    
    // Recupera la quantità attuale del prodotto
    $stmt = $db->prepare("SELECT quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("Prodotto non trovato.");
    }
    
    $current_quantity = (int)$product['quantity'];
    
    if ($quantity_destroyed > $current_quantity) {
        throw new Exception("La quantità da distruggere supera la quantità disponibile.");
    }
    
    // Aggiorna la quantità del prodotto
    $new_quantity = $current_quantity - $quantity_destroyed;
    $stmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    $stmt->execute([$new_quantity, $product_id]);
    
    // Inserisci la registrazione della distruzione
    $stmt = $db->prepare("INSERT INTO product_destructions (product_id, quantity_destroyed, destruction_notes, destroyed_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$product_id, $quantity_destroyed, $destruction_notes, $_SESSION['user_id']]);
    
    // Conferma la transazione
    $db->commit();
    
    $_SESSION['success'] = "Distruzione del prodotto completata con successo.";
} catch (Exception $e) {
    // Annulla la transazione in caso di errore
    $db->rollBack();
    $_SESSION['error'] = "Errore durante la distruzione del prodotto: " . $e->getMessage();
}

// Reindirizza alla dashboard
header('Location: ../pages/dashboard.php');
exit();
?>
