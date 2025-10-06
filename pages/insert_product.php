<?php
session_start();
require_once '../function.php';

$db = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Raccolta e sanitizzazione dei dati
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $model = trim($_POST['model']);
        $manufacturer = trim($_POST['manufacturer']);
        $supplier_id = (int)$_POST['supplier_id'];
        $category_id = (int)$_POST['category_id'];
        $subcategory_id = (int)$_POST['subcategory_id'];
        $quantity = (int)$_POST['quantity'];
        $shelf = trim($_POST['shelf']);
        $purchase_price = floatval($_POST['purchase_price']);
        $barcode = trim($_POST['barcode']);
        $serial_number = trim($_POST['serial_number']); // Nuovo campo
        $availability = (int)$_POST['availability'];
        $company_id = (int)$_POST['company_id']; // Campo aggiunto

        // Se il barcode è uguale al valore predefinito, segnala l'errore
        if ($barcode === 'CCSUD000000') {
            $_SESSION['error'] = 'Errore: Inserisci un barcode univoco; il valore predefinito non è consentito.';
            $_SESSION['old_data'] = $_POST;
            header('Location: products.php');
            exit();
        }

        // Validazione del serial_number (se fornito)
        if (!empty($serial_number)) {
            $checkStmt = $db->prepare("SELECT id FROM products WHERE serial_number = ?");
            $checkStmt->execute([$serial_number]);
            if ($checkStmt->fetch()) {
                $_SESSION['error'] = 'Errore: Il Serial Number è già in uso. Inserisci un codice univoco.';
                $_SESSION['old_data'] = $_POST;
                header('Location: products.php');
                exit();
            }
        } else {
            $serial_number = NULL;
        }

        // Verifica se il barcode è già in uso
        $checkBarcodeStmt = $db->prepare("SELECT id FROM products WHERE barcode = ?");
        $checkBarcodeStmt->execute([$barcode]);
        if ($checkBarcodeStmt->fetch()) {
            $_SESSION['error'] = 'Errore: Il barcode esiste già. Inserisci un codice univoco.';
            logActivity(
                $_SESSION['user_id'],
                'errore_creazione',
                "Tentativo di inserimento prodotto con barcode duplicato '{$barcode}'.",
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
            $_SESSION['old_data'] = $_POST;
            header('Location: products.php');
            exit();
        }

        // Query di inserimento aggiornata per includere serial_number
        $stmt = $db->prepare("
            INSERT INTO products (name, description, model, manufacturer, supplier_id, category_id, subcategory_id, quantity, shelf, purchase_price, barcode, availability, company_id, serial_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name, $description, $model, $manufacturer, $supplier_id,
            $category_id, $subcategory_id, $quantity, $shelf, $purchase_price,
            $barcode, $availability, $company_id, $serial_number
        ]);

        $newProductId = $db->lastInsertId();

        logActivity(
            $_SESSION['user_id'],
            'creazione',
            "Nuovo prodotto inserito: ID {$newProductId} ('{$name}'). Quantità: {$quantity}, Barcode: '{$barcode}', Serial Number: '" . ($serial_number ?? 'NULL') . "'.",
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        );

        $_SESSION['success'] = 'Prodotto inserito con successo!';
        unset($_SESSION['old_data']);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $_SESSION['error'] = 'Errore: Il barcode o il Serial Number esistono già. Inserisci codici univoci.';
            logActivity(
                $_SESSION['user_id'],
                'errore_creazione',
                "Tentativo di inserimento prodotto con barcode duplicato '{$barcode}' o serial_number duplicato '{$serial_number}'.",
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
        } else {
            $_SESSION['error'] = 'Errore durante l\'inserimento del prodotto: ' . $e->getMessage();
            logActivity(
                $_SESSION['user_id'],
                'errore_creazione',
                "Errore durante l'inserimento del prodotto '{$name}': " . $e->getMessage(),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            );
        }
        $_SESSION['old_data'] = $_POST;
    }
    header('Location: products.php');
    exit;
}
?>
