<?php
// Include il file di configurazione e database
session_start();

// Controlla il parametro error nell'URL
$error_code = isset($_GET['error']) ? $_GET['error'] : null;
$error_message = '';

switch ($error_code) {
    case 1:
        $error_message = "Errore nel duplicare il prodotto. Riprova più tardi.";
        break;
    case 2:
        $error_message = "Prodotto non trovato. Verifica l'ID del prodotto.";
        break;
    default:
        $error_message = "Si è verificato un errore sconosciuto. Riprova più tardi.";
}
?>
<?php if (isset($_GET['message'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_GET['message']) ?></div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="error-container">
        <h1>Errore</h1>
        <p><?php echo $error_message; ?></p>
        <a href="maintable.php">Torna alla pagina principale</a>
    </div>
</body>
</html>
