<?php
session_start();
require_once '../function.php';

// Verifica autenticazione e permessi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$db = connectDB();
if (!$db) {
    die("Errore di connessione al database");
}

// Filtri
$actionFilter = $_GET['action'] ?? '';
$userFilter   = isset($_GET['user']) ? (int)$_GET['user'] : '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';

// Paginazione
$logsPerPage  = 30;
$currentPage  = max(1, (int)($_GET['page'] ?? 1));

// Costruisci baseQuery
$baseQuery = "FROM logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1";
$params = [];
if ($actionFilter) {
    $baseQuery .= " AND l.action = :action";
    $params[':action'] = $actionFilter;
}
if ($userFilter) {
    $baseQuery .= " AND l.user_id = :user_id";
    $params[':user_id'] = $userFilter;
}
if ($dateFrom) {
    $baseQuery .= " AND l.created_at >= :date_from";
    $params[':date_from'] = $dateFrom . ' 00:00:00';
}
if ($dateTo) {
    $baseQuery .= " AND l.created_at <= :date_to";
    $params[':date_to'] = $dateTo . ' 23:59:59';
}

// Conta
$stmt = $db->prepare("SELECT COUNT(*) " . $baseQuery);
$stmt->execute($params);
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $logsPerPage);
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $logsPerPage;

// Query logs
$logQuery = "SELECT l.*, u.username " . $baseQuery . " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($logQuery);
foreach ($params as $k => &$v) $stmt->bindParam($k, $v);
$stmt->bindValue(':limit',  $logsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,      PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filtri options
$actions = ['accesso','accesso_fallito','logout','cancellazione','modifica','creazione','regola_stock','carico','scarico','duplicazione'];
$users   = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);

function buildUrl($page) {
    $p = $_GET; $p['page']=$page;
    return 'logs.php?'.http_build_query($p);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Logs – Gestionale Magazzino</title>
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Font & CSS -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    /* Sfondo animato */
    body {
      margin:0; padding:0; min-height:100vh;
      font-family:'Poppins',sans-serif;
      background: linear-gradient(-45deg,rgb(253, 253, 253),rgb(255, 255, 255),rgb(211, 229, 255), #66bbff);
      background-size:400% 400%;
      animation: gradientBG 15s ease infinite;
    }
    @keyframes gradientBG {
      0%{background-position:0% 50%}
      50%{background-position:100% 50%}
      100%{background-position:0% 50%}
    }
    /* Glass card principale */
    .glass-card {
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(10px);
      border-radius: 1rem;
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
      margin: 6rem auto 3rem;
      padding: 2rem;
      max-width: 1200px;
    }
    .glass-card h1 {
      color: #003366;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    /* Filtri */
    .form-select, .form-control {
      border-radius: .75rem;
      border: none;
      background: rgba(255,255,255,0.7);
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);
      transition: background .3s, box-shadow .3s;
    }
    .form-select:focus, .form-control:focus {
      background: rgba(255,255,255,0.9);
      outline: none;
      box-shadow: inset 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Tabella */
    .table thead {
      background: linear-gradient(90deg, #4e89ff, #67d0ff);
      color: #fff;
      border: none;
    }
    .table-hover tbody tr:hover {
      background: rgba(78,137,255,0.1);
    }
    /* Paginazione */
    .pagination .page-link {
      border-radius: .5rem;
      margin: 0 .25rem;
    }
    /* Pulsanti */
    .btn-primary, .btn-secondary, .btn-success {
      border-radius: .75rem;
      transition: background .3s;
    }
    .btn-primary:hover { background: #003366; }
    .btn-secondary:hover { background: #0055aa; color:#fff; }
    .btn-success:hover { background: #3388ff; }
  </style>
</head>
<body>
  <?php include '../templates/header.php'; ?>
  <div class="container-fluid">
    <div class="row">
      <?php include '../templates/sidebar.php'; ?>
      <main class="col-12 col-lg-10 ms-auto px-3">
        <div class="glass-card">
          <h1><i class="fas fa-list-ul me-2"></i>Logs del Sistema</h1>

          <!-- Form Filtri -->
          <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label">Azione</label>
              <select name="action" class="form-select">
                <option value="">Tutte</option>
                <?php foreach($actions as $a): ?>
                  <option value="<?=$a?>" <?=$a==$actionFilter?'selected':''?>>
                    <?=ucfirst(str_replace('_',' ',$a))?>
                  </option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Utente</label>
              <select name="user" class="form-select">
                <option value="">Tutti</option>
                <?php foreach($users as $u): ?>
                  <option value="<?=$u['id']?>" <?=$u['id']==$userFilter?'selected':''?>>
                    <?=htmlspecialchars($u['username'])?>
                  </option>
                <?php endforeach;?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Data da</label>
              <input type="date" name="date_from" class="form-control" value="<?=htmlspecialchars($dateFrom)?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Data a</label>
              <input type="date" name="date_to" class="form-control" value="<?=htmlspecialchars($dateTo)?>">
            </div>
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter me-1"></i>Filtra</button>
              <a href="logs.php" class="btn btn-secondary me-2"><i class="fas fa-sync me-1"></i>Reset</a>
              <a href="logs.php?<?=http_build_query(array_merge($_GET,['export'=>'true']))?>" class="btn btn-success">
                <i class="fas fa-download me-1"></i>Export CSV
              </a>
            </div>
          </form>

          <!-- Tabella Logs -->
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>ID</th><th>Utente</th><th>Azione</th>
                  <th>Descrizione</th><th>IP</th><th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php if($logs): foreach($logs as $log): ?>
                  <tr>
                    <td><?=$log['id']?></td>
                    <td><?= $log['user_id']==0?'Anonimo':htmlspecialchars($log['username']) ?></td>
                    <td><?=htmlspecialchars(ucfirst(str_replace('_',' ',$log['action'])))?></td>
                    <td><?=htmlspecialchars($log['description'])?></td>
                    <td><?=htmlspecialchars($log['ip_address'])?></td>
                    <td><?=htmlspecialchars($log['created_at'])?></td>
                  </tr>
                <?php endforeach; else: ?>
                  <tr><td colspan="6" class="text-center">Nessun log trovato.</td></tr>
                <?php endif;?>
              </tbody>
            </table>
          </div>

          <!-- Paginazione -->
          <?php if($totalPages>1): ?>
          <nav class="mt-4">
            <ul class="pagination justify-content-center">
              <li class="page-item <?=$currentPage<=1?'disabled':''?>">
                <a class="page-link" href="<?=$currentPage>1?buildUrl($currentPage-1):'#'?>">&laquo;</a>
              </li>
              <?php for($i=1;$i<=$totalPages;$i++): ?>
                <li class="page-item <?=$i==$currentPage?'active':''?>">
                  <a class="page-link" href="<?=buildUrl($i)?>"><?=$i?></a>
                </li>
              <?php endfor;?>
              <li class="page-item <?=$currentPage>=$totalPages?'disabled':''?>">
                <a class="page-link" href="<?=$currentPage<$totalPages?buildUrl($currentPage+1):'#'?>">&raquo;</a>
              </li>
            </ul>
          </nav>
          <?php endif; ?>

        </div>
      </main>
    </div>
  </div>
  <?php include '../templates/footer.php'; ?>
</body>
</html>
