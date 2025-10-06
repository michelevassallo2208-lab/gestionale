<?php
// pages/adjust_stock.php
session_start();
require_once '../function.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Accesso negato. Effettua il login.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['quantity'])) {
        $product_id = (int)$_POST['id'];
        $quantity = (int)$_POST['quantity'];
        $note = trim($_POST['note'] ?? '');

        if ($quantity < 0) {
            $_SESSION['error'] = 'La quantità non può essere negativa.';
            header('Location: ../pages/dashboard.php');
            exit();
        }

        try {
            $db = connectDB();

            $stmt = $db->prepare("SELECT name, quantity, subcategory_id FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $old_quantity = $product['quantity'];
                $subcategory_id = $product['subcategory_id'];

                $updateStmt = $db->prepare("UPDATE products SET quantity = ? WHERE id = ?");
                $updateStmt->execute([$quantity, $product_id]);

                $availability = $quantity > 0 ? 1 : 0;
                $updateAvailabilityStmt = $db->prepare("UPDATE products SET availability = ? WHERE id = ?");
                $updateAvailabilityStmt->execute([$availability, $product_id]);

                $message = "Stock del prodotto ID {$product_id} ('{$product['name']}') regolato. Quantità precedente: {$old_quantity}, nuova quantità: {$quantity}.";
                if (!empty($note)) {
                    $message .= " Note: " . $note;
                }

                logActivity(
                    $_SESSION['user_id'],
                    'regola_stock',
                    $message,
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                );

                $_SESSION['success'] = 'Quantità di stock aggiornata con successo.';
            } else {
                $_SESSION['error'] = 'Prodotto non trovato.';
                $subcategory_id = null;
            }
        } catch (PDOException $e) {
            logActivity(
                $_SESSION['user_id'],
                'errore_regola_stock',
                "Errore durante l'aggiornamento dello stock del prodotto ID {$product_id}: " . $e->getMessage(),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
            $_SESSION['error'] = 'Errore durante l\'aggiornamento dello stock: ' . $e->getMessage();
            $subcategory_id = null;
        }
    } else {
        $_SESSION['error'] = 'Dati inviati non validi.';
        $subcategory_id = null;
    }
} else {
    $_SESSION['error'] = 'Richiesta non valida.';
    $subcategory_id = null;
}

if (isset($subcategory_id) && $subcategory_id) {
    header('Location: ../pages/dashboard.php?subcategory_id=' . urlencode($subcategory_id));
} else {
    header('Location: ../pages/dashboard.php');
}
exit();
?>
