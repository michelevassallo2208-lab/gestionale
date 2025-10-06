<?php
session_start();
require_once '../function.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','editor'])) {
    header('Location: ../index.php');
    exit;
}
$db = connectDB();

// Filtri
$filters = [
    'product_name'   => $_GET['product_name']   ?? '',
    'destroyed_by'   => $_GET['destroyed_by']   ?? '',
    'date_from'      => $_GET['date_from']      ?? '',
    'date_to'        => $_GET['date_to']        ?? '',
    'quantity_min'   => $_GET['quantity_min']   ?? '',
    'quantity_max'   => $_GET['quantity_max']   ?? '',
];

// Costruzione query con filtri e ordinamento
$sql = "
  SELECT pd.*, p.name AS product_name, u.username AS destroyed_by_username
    FROM product_destructions pd
    JOIN products p ON pd.product_id = p.id
    JOIN users u    ON pd.destroyed_by = u.id
   WHERE 1=1
";
$params = [];
if ($filters['product_name']!=='') {
  $sql .= " AND p.name LIKE ?";
  $params[] = "%{$filters['product_name']}%";
}
if ($filters['destroyed_by']!=='') {
  $sql .= " AND pd.destroyed_by = ?";
  $params[] = $filters['destroyed_by'];
}
if ($filters['date_from']!=='') {
  $sql .= " AND pd.destroyed_at >= ?";
  $params[] = $filters['date_from'].' 00:00:00';
}
if ($filters['date_to']!=='') {
  $sql .= " AND pd.destroyed_at <= ?";
  $params[] = $filters['date_to'].' 23:59:59';
}
if ($filters['quantity_min']!=='') {
  $sql .= " AND pd.quantity_destroyed >= ?";
  $params[] = $filters['quantity_min'];
}
if ($filters['quantity_max']!=='') {
  $sql .= " AND pd.quantity_destroyed <= ?";
  $params[] = $filters['quantity_max'];
}
// **ORDINA per data distruzione discendente**
$sql .= " ORDER BY pd.destroyed_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$destructions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Utenti per filtro
$users = $db->query("SELECT id, username FROM users ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Trash – Gestionale Magazzino</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Bootstrap & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    body { background: #f8f9fa; }
    main.container-fluid { max-width:1200px; margin:5rem auto 2rem; }
    h2 { color:#343a40; margin-bottom:1.5rem; }
    .card { border:none; border-radius:.75rem; box-shadow:0 4px 16px rgba(0,0,0,0.08); margin-bottom:2rem; }
    .card-header { background:#4e89ff; color:#fff; font-weight:600; border-radius:.75rem .75rem 0 0; }
    .form-label { font-weight:500; color:#495057; }
    .form-control, .form-select { border-radius:.5rem; }
    .form-control:focus, .form-select:focus { border-color:#4e89ff; box-shadow:0 0 0 .2rem rgba(78,137,255,.25); }
    .btn-primary { border-radius:.5rem; padding:.5rem 1rem; }
    .table-responsive { overflow:hidden; border-radius:.75rem; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
    table.table { margin-bottom:0; background:#fff; border-radius:.75rem; }
    table.table thead { background:#4e89ff; color:#fff; }
    table.table th, table.table td { border:none; vertical-align:middle; }
    table.table tbody tr:hover { background:#eef5ff; }
    th { cursor:pointer; }
    th.asc::after  { content:" ▲"; }
    th.desc::after { content:" ▼"; }
    td.notes { white-space: pre-wrap; } /* mostra note per intero */
  </style>
</head>
<body>
  <?php include '../templates/header.php'; ?>

  <main class="container-fluid">
    <h2><i class="fas fa-trash-alt me-2"></i>Prodotti Distrutti</h2>

    <div class="card">
      <div class="card-header">Filtri di Ricerca</div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Nome Prodotto</label>
            <input name="product_name" class="form-control"
              value="<?= htmlspecialchars($filters['product_name']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Distrutto da</label>
            <select name="destroyed_by" class="form-select">
              <option value="">Tutti</option>
              <?php foreach($users as $u): ?>
              <option value="<?= $u['id'] ?>"
                <?= $filters['destroyed_by']==$u['id']?'selected':''?>>
                <?= htmlspecialchars($u['username']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Data da</label>
            <input type="date" name="date_from" class="form-control"
              value="<?= htmlspecialchars($filters['date_from']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Data a</label>
            <input type="date" name="date_to" class="form-control"
              value="<?= htmlspecialchars($filters['date_to']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Quantità Minima</label>
            <input type="number" name="quantity_min" class="form-control" min="1"
              value="<?= htmlspecialchars($filters['quantity_min']) ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Quantità Massima</label>
            <input type="number" name="quantity_max" class="form-control" min="1"
              value="<?= htmlspecialchars($filters['quantity_max']) ?>">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2">
              <i class="fas fa-filter me-1"></i>Filtra
            </button>
            <a href="trash.php" class="btn btn-secondary">
              <i class="fas fa-times me-1"></i>Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped">
        <thead><tr>
          <th>Prodotto</th>
          <th>Descrizione</th>
          <th>Quantità</th>
          <th>Note</th>
          <th>Distrutto da</th>
          <th>Data</th>
        </tr></thead>
        <tbody>
        <?php if($destructions): foreach($destructions as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['product_name']) ?></td>
            <td><?= nl2br(htmlspecialchars($d['destruction_notes']?:'—')) ?></td>
            <td><?= htmlspecialchars($d['quantity_destroyed']) ?></td>
            <td class="notes"><?= nl2br(htmlspecialchars($d['destruction_notes']?:'—')) ?></td>
            <td><?= htmlspecialchars($d['destroyed_by_username']) ?></td>
            <td><?= htmlspecialchars($d['destroyed_at']) ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              Nessuna distruzione registrata.
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>

  <?php include '../templates/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelectorAll('th').forEach((th,i) => {
      th.addEventListener('click',()=>{
        const tbl=th.closest('table'), asc=!th.classList.toggle('asc');
        th.classList.toggle('desc',!asc);
        Array.from(tbl.tBodies[0].rows)
          .sort((a,b)=> asc
            ? a.cells[i].innerText.localeCompare(b.cells[i].innerText,{numeric:true})
            : b.cells[i].innerText.localeCompare(a.cells[i].innerText,{numeric:true})
          ).forEach(r=>tbl.tBodies[0].appendChild(r));
      });
    });
  </script>
</body>
</html>
