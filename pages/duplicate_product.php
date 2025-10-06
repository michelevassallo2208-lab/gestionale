<?php
session_start();
require_once '../function.php';
$db = connectDB();

// Abilitare la visualizzazione degli errori in PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica se l'utente ha i permessi necessari
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'editor')) {
    error_log('Utente non autenticato o senza permessi sufficienti per duplicare il prodotto.');
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['product_id']) && is_numeric($_POST['product_id'])) {
    $productId = (int)$_POST['product_id'];

    try {
        // Recupera i dettagli del prodotto
        error_log('Recupero del prodotto con ID: ' . $productId);
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            error_log('Prodotto non trovato per l\'ID: ' . $productId);
            throw new Exception('Prodotto non trovato nel database.');
        }
        error_log('Prodotto trovato: ' . print_r($product, true));

        // Recupera l'ultimo barcode DUP presente nella tabella
        error_log('Recupero dell\'ultimo barcode duplicato dal database.');
        $stmt = $db->query("SELECT barcode FROM products WHERE barcode LIKE 'DUP%' ORDER BY barcode DESC LIMIT 1");
        $lastBarcode = $stmt->fetchColumn();

        if ($lastBarcode === false) {
            // Nessun duplicato precedente, inizia da 0
            $lastNumber = 0;
            error_log('Nessun barcode DUP trovato, inizializzo a 0.');
        } else {
            // Estrai la parte numerica dal barcode (formato DUPXXXXXXXX)
            error_log('Ultimo barcode DUP trovato: ' . $lastBarcode);
            if (preg_match('/DUP(\d{8})/', $lastBarcode, $matches)) {
                $lastNumber = (int)$matches[1];
            } else {
                // Se il formato non è valido, iniziamo comunque da 0
                error_log('Il formato del barcode DUP esistente non è valido. Inizializzo a 0.');
                $lastNumber = 0;
            }
        }

        // Incrementa il numero per il nuovo prodotto duplicato e genera il nuovo barcode
        $newBarcode = 'DUP' . str_pad($lastNumber + 1, 8, '0', STR_PAD_LEFT);
        error_log('Nuovo barcode generato: ' . $newBarcode);

        // Duplicazione del prodotto, senza l'ID originale
        $sql = "
            INSERT INTO products 
            (name, description, model, manufacturer, serial_number, quantity, availability, purchase_price, shelf, barcode, category_id, subcategory_id, company_id)
            VALUES 
            (:name, :description, :model, :manufacturer, :serial_number, :quantity, :availability, :purchase_price, :shelf, :barcode, :category_id, :subcategory_id, :company_id)
        ";

        error_log('Query SQL di duplicazione prodotto: ' . $sql);

        // Parametri da inserire
        $params = [
            ':name' => $product['name'],
            ':description' => $product['description'],
            ':model' => $product['model'],
            ':manufacturer' => $product['manufacturer'],
            ':serial_number' => $product['serial_number'],
            ':quantity' => $product['quantity'],
            ':availability' => $product['availability'],
            ':purchase_price' => $product['purchase_price'],
            ':shelf' => $product['shelf'],
            ':barcode' => $newBarcode, // Imposta il nuovo barcode generato
            ':category_id' => $product['category_id'],
            ':subcategory_id' => $product['subcategory_id'],
            ':company_id' => $product['company_id']
        ];

        error_log('Parametri passati alla query: ' . print_r($params, true));

        // Esegui l'inserimento
        $stmt = $db->prepare($sql);
        $executeResult = $stmt->execute($params);

        if (!$executeResult) {
            $errorInfo = $stmt->errorInfo();
            error_log('Errore durante l\'inserimento del prodotto duplicato: ' . print_r($errorInfo, true));
            throw new Exception('Errore nell\'inserire il nuovo prodotto duplicato nel database. Dettagli: ' . print_r($errorInfo, true));
        }

        $newProductId = $db->lastInsertId();
        error_log('Prodotto duplicato inserito correttamente con ID: ' . $newProductId);

        // Registrazione dell'attività di duplicazione
        $userId = $_SESSION['user_id'];
        $action = 'duplica_prodotto';
        $description = "Duplicato il prodotto: ID originale: $productId, Nome: {$product['name']}, Barcode originale: {$product['barcode']}, Nuovo ID: $newProductId, Nuovo Barcode: $newBarcode";
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        logActivity($userId, $action, $description, $ipAddress, $userAgent);

        $_SESSION['success'] = "Prodotto duplicato con successo: {$product['name']} (Barcode originale: {$product['barcode']}, Nuovo Barcode: $newBarcode)";

    } catch (PDOException $e) {
        error_log('Errore PDO nel duplicare il prodotto: ' . $e->getMessage());
        $_SESSION['error'] = 'Errore PDO: ' . $e->getMessage();
    } catch (Exception $e) {
        error_log('Errore generico nel duplicare il prodotto: ' . $e->getMessage());
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
} else {
    error_log('ID prodotto non valido o assente.');
    $_SESSION['error'] = 'ID prodotto non valido.';
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
