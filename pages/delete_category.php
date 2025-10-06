<?php
session_start();
require_once '../function.php';

$db = connectDB();

$id = $_POST['id'] ?? ''; // Cambiato da $_GET a $_POST

if (!empty($id)) {
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'Categoria eliminata con successo!';
    } else {
        $_SESSION['error'] = 'Errore durante l\'eliminazione della categoria.';
    }
} else {
    $_SESSION['error'] = 'ID categoria mancante.';
}

header('Location: categories.php');
exit;
?>
