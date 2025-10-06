<?php
session_start();
require_once '../function.php';
$db = connectDB();

// Controllo autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Recupera e valida product_id
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    die("Prodotto non valido.");
}

// Prendi informazioni sul prodotto
$stmtP = $db->prepare("SELECT name FROM products WHERE id = ?");
$stmtP->execute([$productId]);
$product = $stmtP->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    die("Prodotto non trovato.");
}

// Prendi i log di carico/scarico
$stmt = $db->prepare("
    SELECT l.created_at, l.action, l.description, u.username
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    WHERE l.action IN ('carico','scarico')
      AND l.product_id = :pid
    ORDER BY l.created_at DESC
");
$stmt->execute([':pid' => $productId]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Storico – <?= htmlspecialchars($product['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../templates/header.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <?php include '../templates/sidebar.php'; ?>
      <main class="col-12 col-lg-10 ms-auto content pt-4">
        <div class="px-4">
          <a href="dashboard.php" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Torna indietro
          </a>
          <h1 class="mb-4">Storico Carico/Scarico – <?= htmlspecialchars($product['name']) ?></h1>

          <?php if (empty($logs)): ?>
            <div class="alert alert-info">Nessuno storico disponibile per questo prodotto.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>Data</th>
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
        </div>
      </main>
    </div>
  </div>
  <?php include '../templates/footer.php'; ?>
</body>
</html>
