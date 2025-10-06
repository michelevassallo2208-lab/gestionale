<?php
// pages/product_history.php

session_start();
require_once '../function.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Connessione al DB
$db = connectDB();

// Recupera e valida product_id
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    die("ID prodotto non valido.");
}

// Recupera nome del prodotto
$stmtProd = $db->prepare("SELECT name FROM products WHERE id = ?");
$stmtProd->execute([$productId]);
$product = $stmtProd->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("Prodotto non trovato.");
}
$productName = htmlspecialchars($product['name']);

?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Storico Prodotti – <?= $productName ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../templates/header.php'; ?>
  <div class="container mt-5">
    <h1 class="mb-4"><i class="fas fa-history"></i> Storico Carico/Scarico: <?= $productName ?></h1>
    <?php
    // Query sui log: carico/scarico e description contenente l'ID prodotto
    $stmt = $db->prepare("
      SELECT l.created_at, l.action, l.description, u.username
      FROM logs l
      LEFT JOIN users u ON l.user_id = u.id
      WHERE l.action IN ('carico','scarico')
        AND l.description LIKE :pattern
      ORDER BY l.created_at DESC
    ");
    $stmt->execute([':pattern' => '%'.$productId.'%']);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($logs)): ?>
      <div class="alert alert-info">Non ci sono operazioni di carico/scarico per questo prodotto.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead class="table-dark">
            <tr>
              <th>Data e Ora</th>
              <th>Azione</th>
              <th>Utente</th>
              <th>Descrizione</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= htmlspecialchars($log['created_at']) ?></td>
                <td><?= ucfirst(htmlspecialchars($log['action'])) ?></td>
                <td><?= htmlspecialchars($log['username'] ?? 'Anonimo') ?></td>
                <td><?= htmlspecialchars($log['description']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <a href="../pages/dashboard.php" class="btn btn-secondary mt-4">
      <i class="fas fa-arrow-left"></i> Torna all’elenco prodotti
    </a>
  </div>

  <?php include '../templates/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
