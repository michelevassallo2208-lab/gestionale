<?php
session_start();
require_once '../function.php';

$db = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';

    // Controllo di validità
    if (empty($id) || empty($name)) {
        $_SESSION['error'] = 'Tutti i campi sono obbligatori.';
        header('Location: categories.php');
        exit;
    }

    // Aggiornamento della categoria
    $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
    if ($stmt->execute([$name, $id])) {
        $_SESSION['success'] = 'Categoria aggiornata con successo!';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiornamento della categoria.';
    }

    header('Location: categories.php');
    exit;
}
?>
