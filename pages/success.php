<?php
// Include il file di configurazione e database
session_start();

// Controlla il parametro success nell'URL
$success_code = isset($_GET['success']) ? $_GET['success'] : null;
$success_message = '';

switch ($success_code) {
    case 1:
        $success_message = "Prodotto duplicato con successo!";
        break;
    default:
        $success_message = "Operazione completata con successo.";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Successo</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="success-container">
        <h1>Operazione completata</h1>
        <p><?php echo $success_message; ?></p>
        <a href="maintable.php">Torna alla pagina principale</a>
    </div>
</body>
</html>
