<?php
/******************************************************************
 *  add_shelf.php – salva (o aggiorna) uno scaffale aggiuntivo
 *  Percorso: gestionale/pages/add_shelf.php
 ******************************************************************/

require_once __DIR__ . '/../function.php'; // funzione connectDB()
session_start();

$db      = connectDB();                    // connessione PDO
$user_id = $_SESSION['user_id'] ?? 0;      // per i log (se serve)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_id = (int)($_POST['product_id'] ?? 0);
    $shelf      = trim($_POST['shelf_name'] ?? '');   // colonna "shelf" nella tabella product_shelves
    $qty        = max(1, (int)($_POST['quantity'] ?? 0));

    if ($product_id && $shelf !== '') {
        try {
            /* Inserisce un nuovo scaffale
               — oppure — se esiste già (product_id + shelf) aggiorna la quantità sommando */
            $stmt = $db->prepare(
                'INSERT INTO product_shelves (product_id, shelf, quantity)
                 VALUES (:pid, :shelf, :qty)
                 ON DUPLICATE KEY UPDATE quantity = quantity + :qty'
            );
            $stmt->execute([
                ':pid'   => $product_id,
                ':shelf' => $shelf,
                ':qty'   => $qty,
            ]);

            /* Log facoltativo (se hai la funzione logActivity) */
            if (function_exists('logActivity')) {
                $ip  = $_SERVER['REMOTE_ADDR']     ?? 'unknown';
                $ua  = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                $msg = "Scaffale '$shelf' (+$qty) aggiunto a prodotto #$product_id";
                logActivity($user_id, 'add_shelf', $msg, $ip, $ua);
            }

            header('Location: maintable.php?success=3'); // success alert
            exit;

        } catch (PDOException $e) {
            error_log('add_shelf.php – DB ERROR: ' . $e->getMessage());
        }
    }
}

/* In tutti gli altri casi: errore generico */
header('Location: maintable.php?error=2');
exit;
