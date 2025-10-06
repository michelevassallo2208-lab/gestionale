<?php
session_start();
require_once '../function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';

    // Controllo di validità
    if (empty($name)) {
        $_SESSION['error'] = 'Il nome della categoria è obbligatorio.';
        header('Location: categories.php');
        exit;
    }

    $db = connectDB();

    // Inserimento della categoria
    $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    if ($stmt->execute([$name])) {
        $_SESSION['success'] = 'Categoria aggiunta con successo!';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiunta della categoria.';
    }

    header('Location: categories.php');
    exit;
}
?>
