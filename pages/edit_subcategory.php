<?php
session_start();
require_once '../function.php';

$db = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // Controllo di validità
    if (empty($id) || empty($name) || empty($category_id)) {
        $_SESSION['error'] = 'Tutti i campi sono obbligatori.';
        header('Location: categories.php');
        exit;
    }

    // Verifica che la categoria principale esista
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['error'] = 'La categoria principale selezionata non esiste.';
        header('Location: categories.php');
        exit;
    }

    // Aggiornamento della sottocategoria
    $stmt = $db->prepare("UPDATE subcategories SET name = ?, category_id = ? WHERE id = ?");
    if ($stmt->execute([$name, $category_id, $id])) {
        $_SESSION['success'] = 'Sottocategoria aggiornata con successo!';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiornamento della sottocategoria.';
    }

    header('Location: categories.php');
    exit;
}
?>
