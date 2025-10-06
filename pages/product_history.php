<?php
// pages/product_history.php

session_start();
require_once '../function.php';

// Verifica autenticazione
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

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

// URL di ritorno (fallback dashboard)
$returnUrl = $_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Storico Movimentazioni – <?= $productName ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Font & CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* sfondo animato a gradient */
    body {
      margin:0; padding:0; min-height:100vh;
      font-family:'Poppins',sans-serif;
      background: linear-gradient(-45deg, #003366, #0055aa, #3388ff, #66bbff);
      background-size:400% 400%;
      animation: gradientBG 15s ease infinite;
    }
    @keyframes gradientBG {
      0%{background-position:0% 50%}
      50%{background-position:100% 50%}
      100%{background-position:0% 50%}
    }
    /* contenuto offset per navbar */
    .content-offset {
      margin-top: 4rem;
    }
    @media (min-width: 992px) {
      .content-offset { margin-top: 6rem; }
    }
    /* glass-card */
    .glass-card {
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
      margin: 2rem auto;
      padding: 2rem;
      max-width: 1000px;
    }
    .glass-card h1 {
      color: #003366;
      font-weight: 600;
      margin-bottom: 0.5rem;
      display: flex; align-items: center;
    }
    .glass-card h1 .fas { margin-right: 0.5rem; }
    .glass-card h4 {
      color: #555;
      margin-bottom: 1.5rem;
    }
    /* table styling */
    .table thead {
      background: linear-gradient(90deg, #4e89ff, #67d0ff);
      color: #fff;
      border: none;
    }
    .table-hover tbody tr:hover {
      background: rgba(78,137,255,0.1);
    }
    /* badge for actions */
    .badge { font-size: 0.9rem; }
    /* pulsante ritorno */
    .btn-back {
      border-radius: .75rem;
      transition: background .3s;
    }
    .btn-back:hover {
      background: #0055aa; color:#fff;
    }
  </style>
</head>
<body>
  <?php include '../templates/header.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php include '../templates/sidebar.php'; ?>

      <main class="col-12 col-lg-10 ms-auto content-offset">
        <div class="glass-card">
          <h1><i class="fas fa-history"></i> Storico Movimentazioni</h1>
          <h4><?= $productName ?></h4>

          <?php
          // Estrai i log di carico/scarico/regolazione
          $stmt = $db->prepare("
            SELECT l.created_at, l.action, l.description, u.username
            FROM logs l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE l.action IN ('carico','scarico','regola_stock')
              AND l.description LIKE :pattern
            ORDER BY l.created_at DESC
          ");
          $stmt->execute([':pattern' => '%'.$productId.'%']);
          $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
          ?>

          <?php if (empty($logs)): ?>
            <div class="alert alert-info">
              Non ci sono operazioni di carico, scarico o regolazione stock per questo prodotto.
            </div>
          <?php else: ?>
            <div class="table-responsive mb-4">
              <table class="table table-striped table-hover align-middle">
                <thead>
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
                      <td>
                        <?php 
                          switch($log['action']) {
                            case 'carico':       echo '<span class="badge bg-success">Carico</span>';       break;
                            case 'scarico':      echo '<span class="badge bg-danger">Scarico</span>';     break;
                            case 'regola_stock': echo '<span class="badge bg-info">Regolazione</span>';   break;
                          }
                        ?>
                      </td>
                      <td><?= htmlspecialchars($log['username'] ?? 'Anonimo') ?></td>
                      <td><?= htmlspecialchars($log['description']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <a href="<?= htmlspecialchars($returnUrl) ?>"
             class="btn btn-secondary btn-back">
            <i class="fas fa-arrow-left me-1"></i> Torna indietro
          </a>
        </div>
      </main>
    </div>
  </div>

  <?php include '../templates/footer.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
