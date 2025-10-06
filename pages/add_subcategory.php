<?php
session_start();
require_once '../function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // Controllo di validità
    if (empty($name) || empty($category_id)) {
        $_SESSION['error'] = 'Il nome della sottocategoria e la categoria principale sono obbligatori.';
        header('Location: categories.php');
        exit;
    }

    $db = connectDB();

    // Controlla se la categoria principale esiste
    $stmt = $db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if ($stmt->fetchColumn() == 0) {
        $_SESSION['error'] = 'La categoria principale selezionata non esiste.';
        header('Location: categories.php');
        exit;
    }

    // Inserimento della sottocategoria
    $stmt = $db->prepare("INSERT INTO subcategories (name, category_id) VALUES (?, ?)");
    if ($stmt->execute([$name, $category_id])) {
        $_SESSION['success'] = 'Sottocategoria aggiunta con successo!';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiunta della sottocategoria.';
    }

    header('Location: categories.php');
    exit;
}
?>
