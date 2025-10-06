<?php
// pages/delete_product.php
session_start();
require_once '../function.php';

// Controllo dell'autenticazione (opzionale ma consigliato)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Accesso negato. Effettua il login.';
    header('Location: ../index.php');
    exit();
}

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se l'ID del prodotto è stato inviato
    if (isset($_POST['id']) && is_numeric($_POST['id'])) {
        $product_id = (int)$_POST['id'];
        
        try {
            $db = connectDB();
            
            // Verifica se il prodotto esiste e recupera subcategory_id
            $stmt = $db->prepare("SELECT name, subcategory_id FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $product_name = $product['name'];
                $subcategory_id = $product['subcategory_id'];
                
                // Elimina il prodotto
                $deleteStmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $deleteStmt->execute([$product_id]);
                
                // Log dell'eliminazione
                logActivity(
                    $_SESSION['user_id'],
                    'cancellazione',
                    "Il prodotto ID {$product_id} ('{$product_name}') è stato cancellato.",
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                );
                
                $_SESSION['success'] = 'Prodotto eliminato con successo.';
            } else {
                $_SESSION['error'] = 'Prodotto non trovato.';
                $subcategory_id = null; // Nessuna sottocategoria da mantenere
            }
        } catch (PDOException $e) {
            // Log dell'errore
            logActivity(
                $_SESSION['user_id'],
                'errore_cancellazione',
                "Errore durante l'eliminazione del prodotto ID {$product_id}: " . $e->getMessage(),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
            $_SESSION['error'] = 'Errore durante l\'eliminazione del prodotto: ' . $e->getMessage();
            $subcategory_id = null; // Nessuna sottocategoria da mantenere
        }
    } else {
        $_SESSION['error'] = 'ID prodotto non valido.';
        $subcategory_id = null; // Nessuna sottocategoria da mantenere
    }
} else {
    $_SESSION['error'] = 'Richiesta non valida.';
    $subcategory_id = null; // Nessuna sottocategoria da mantenere
}

// Reindirizza alla dashboard con l'`subcategory_id` se disponibile
if (isset($subcategory_id) && $subcategory_id) {
    header('Location: ../pages/dashboard.php?subcategory_id=' . urlencode($subcategory_id));
} else {
    header('Location: ../pages/dashboard.php');
}
exit();
?>
