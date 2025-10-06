<?php
session_start();
require_once '../function.php';

$db = connectDB();

$product_id = $_POST['product_id'] ?? null;
$barcode = $_POST['barcode'] ?? null;

if (!$product_id || !$barcode) {
    $_SESSION['error'] = 'Prodotto e codice a barre sono obbligatori!';
    header('Location: dashboard.php'); // o dove preferisci
    exit;
}

// Controlla che il codice a barre sia univoco
$stmt = $db->prepare("SELECT id FROM products WHERE barcode = ?");
$stmt->execute([$barcode]);
if ($stmt->fetch()) {
    $_SESSION['error'] = 'Questo codice a barre è già assegnato a un altro prodotto.';
    header('Location: dashboard.php');
    exit;
}

// Assegna il codice a barre al prodotto
$stmt = $db->prepare("UPDATE products SET barcode = ? WHERE id = ?");
if ($stmt->execute([$barcode, $product_id])) {
    $_SESSION['success'] = 'Codice a barre assegnato con successo!';
} else {
    $_SESSION['error'] = 'Errore durante l\'assegnazione del codice a barre.';
}

header('Location: dashboard.php');
exit;
