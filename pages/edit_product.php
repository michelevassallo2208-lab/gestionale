<?php
// edit_product.php
session_start();
require_once '../function.php';

// Verifica che l'utente sia autenticato
if (!isset($_SESSION['user_id'])) {
    die('Accesso negato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recupera i dati dal modulo
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $model = isset($_POST['model']) ? trim($_POST['model']) : '';
    $manufacturer = isset($_POST['manufacturer']) ? trim($_POST['manufacturer']) : '';
    $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : '';
    $serial_number = isset($_POST['serial_number']) ? trim($_POST['serial_number']) : null;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $subcategory_id = isset($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : 0;
    $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    $shelf = isset($_POST['shelf']) ? trim($_POST['shelf']) : '';
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $purchase_price = isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0.00;
    $availability = isset($_POST['availability']) ? (int)$_POST['availability'] : 0;
    $company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;

    // Connessione al DB
    $db = connectDB();

    // Validazioni
    $errors = [];

    if ($id <= 0) {
        $errors[] = 'ID prodotto non valido.';
    }

    if (empty($name)) {
        $errors[] = 'Il campo Nome è obbligatorio.';
    }

    if (empty($description)) {
        $errors[] = 'Il campo Descrizione è obbligatorio.';
    }

    if (empty($model)) {
        $errors[] = 'Il campo Modello è obbligatorio.';
    }

    if (empty($manufacturer)) {
        $errors[] = 'Il campo Produttore è obbligatorio.';
    }

    if (empty($barcode)) {
        $errors[] = 'Il campo Barcode è obbligatorio.';
    }

    // Verifica unicità del barcode
    $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE barcode = ? AND id != ?");
    $stmt->execute([$barcode, $id]);
    $barcodeCount = $stmt->fetchColumn();

    if ($barcodeCount > 0) {
        $errors[] = 'Il Barcode inserito è già in uso da un altro prodotto.';
    }

    // Verifica unicità del serial_number (se fornito)
    if (!empty($serial_number)) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE serial_number = ? AND id != ?");
        $stmt->execute([$serial_number, $id]);
        $serialNumberCount = $stmt->fetchColumn();

        if ($serialNumberCount > 0) {
            $errors[] = 'Il Serial Number inserito è già in uso da un altro prodotto.';
        }
    }

    // Verifica che la categoria e la sottocategoria siano valide
    if ($category_id > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Categoria selezionata non valida.';
        }
    } else {
        $errors[] = 'Categoria è obbligatoria.';
    }

    if ($subcategory_id > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM subcategories WHERE id = ? AND category_id = ?");
        $stmt->execute([$subcategory_id, $category_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Sottocategoria selezionata non valida per la categoria scelta.';
        }
    } else {
        $errors[] = 'Sottocategoria è obbligatoria.';
    }

    // Verifica che il fornitore sia valido
    if ($supplier_id > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Fornitore selezionato non valido.';
        }
    } else {
        $errors[] = 'Fornitore è obbligatorio.';
    }

    // Verifica che l'azienda sia valida
    if ($company_id > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = 'Azienda selezionata non valida.';
        }
    } else {
        $errors[] = 'Azienda è obbligatoria.';
    }

    // Se ci sono errori, mostrare all'utente
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
        }
        exit;
    }

    // Aggiornamento del prodotto
    $stmt = $db->prepare("
        UPDATE products 
        SET name = ?, description = ?, model = ?, manufacturer = ?, barcode = ?, serial_number = ?, category_id = ?, subcategory_id = ?, 
            supplier_id = ?, shelf = ?, quantity = ?, purchase_price = ?, availability = ?, company_id = ?
        WHERE id = ?
    ");

    try {
        $stmt->execute([
            $name,
            $description,
            $model,
            $manufacturer,
            $barcode,
            $serial_number,
            $category_id,
            $subcategory_id,
            $supplier_id,
            $shelf,
            $quantity,
            $purchase_price,
            $availability,
            $company_id,
            $id
        ]);

        // Recupera il prodotto aggiornato per il log
        $stmt = $db->prepare("SELECT name, barcode FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Registrare l'attività di modifica con ID, Nome e Barcode
        $userId = $_SESSION['user_id'];
        $action = 'modifica_prodotto';
        $description = "Modificato il prodotto: ID: $id, Nome: {$product['name']}, Barcode: {$product['barcode']}";
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        logActivity($userId, $action, $description, $ipAddress, $userAgent);

        // **Reindirizzare alla Dashboard con la Sottocategoria del Prodotto Modificato**
        $_SESSION['success'] = "Prodotto modificato con successo: {$product['name']} (Barcode: {$product['barcode']})";
        header('Location: ../pages/dashboard.php?subcategory_id=' . $subcategory_id);
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Errore durante l'aggiornamento del prodotto: " . $e->getMessage();
        header('Location: ../pages/dashboard.php?subcategory_id=' . $subcategory_id);
        exit;
    }
} else {
    $_SESSION['error'] = "Richiesta non valida.";
    header('Location: ../pages/dashboard.php');
    exit;
}
?>
