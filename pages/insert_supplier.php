<?php
// insert_supplier.php
session_start();
require_once '../function.php';
$db = connectDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name === '') {
        $_SESSION['error'] = "Il nome del fornitore è obbligatorio.";
        header("Location: ../pages/dashboard.php");
        exit;
    }
    $stmt = $db->prepare("INSERT INTO suppliers (name, address, phone, email) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $address, $phone, $email])) {
        $_SESSION['success'] = "Fornitore inserito con successo.";
    } else {
        $_SESSION['error'] = "Errore durante l'inserimento del fornitore.";
    }
}
header("Location: ../pages/dashboard.php");
exit;
?>
