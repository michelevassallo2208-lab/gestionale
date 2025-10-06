<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aggiorna il timestamp dell'ultima attività
$_SESSION['last_activity'] = time();

// Controlla se l'utente è autenticato
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Connessione al DB
require_once '../function.php';
$db = connectDB();

// Recupera il ruolo dell'utente
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'] ?? '';

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

// Recupera le aziende abilitate all'utente
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("
    SELECT c.id, c.name 
    FROM companies c 
    INNER JOIN user_companies uc ON c.id = uc.company_id 
    WHERE uc.user_id = ? 
    ORDER BY c.name
");
$stmt->execute([$userId]);
$userCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determina la selezione corrente dell'azienda
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['company_id'])) {
    $selected_company_id = $_GET['company_id'];
    $_SESSION['selected_company_id'] = $selected_company_id;
} elseif (isset($_SESSION['selected_company_id'])) {
    $selected_company_id = $_SESSION['selected_company_id'];
} else {
    $selected_company_id = 'all';
}

// Mappa l'ID dell'azienda alle classi di colore di Bootstrap
$navbar_class = 'bg-primary';
switch ($selected_company_id) {
    case '1': $navbar_class = 'bg-primary'; break;
    case '2': $navbar_class = 'bg-danger'; break;
    case '3': $navbar_class = 'bg-success'; break;
    case '4': $navbar_class = 'bg-yellow-orange'; break;
    default:  $navbar_class = 'bg-primary'; break;
}
?>

<!-- Magic Styling for Navbar -->
<style>
  .navbar-custom {
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    backdrop-filter: saturate(180%) blur(10px);
  }
  .navbar-custom .navbar-brand {
    font-family: 'Segoe UI', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    letter-spacing: .05rem;
    transition: transform .3s;
  }
  .navbar-custom .navbar-brand:hover {
    transform: scale(1.05);
  }
  .navbar-custom .nav-link {
    position: relative;
    padding: .5rem 1rem;
    transition: color .3s;
    overflow: hidden;
  }
  .navbar-custom .nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: rgba(255,255,255,0.9);
    transition: width .3s, left .3s;
  }
  .navbar-custom .nav-link:hover::after {
    width: 80%;
    left: 10%;
  }
  .navbar-custom .form-select {
    border-radius: .5rem;
    background-color: rgba(255,255,255,0.2) !important;
    color: #fff;
    border: none;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    transition: background-color .3s;
  }
  .navbar-custom .form-select:focus {
    background-color: rgba(255,255,255,0.3) !important;
    outline: none;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
  }
  .navbar-custom .btn-outline-light {
    transition: background-color .3s, color .3s;
  }
  .navbar-custom .btn-outline-light:hover {
    background-color: rgba(255,255,255,0.2);
    color: #000;
  }
</style>

<nav class="navbar navbar-expand-lg navbar-dark <?= $navbar_class ?> navbar-custom fixed-top">
  <div class="container-fluid">
    <!-- Toggler (mobile) -->
    <button class="btn btn-outline-light d-lg-none me-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar"
            aria-controls="offcanvasSidebar" aria-label="Apri Navigazione Laterale">
      <i class="fas fa-bars"></i>
    </button>

    <!-- Brand -->
    <a class="navbar-brand text-white" href="../pages/dashboard.php">
      Gestionale Magazzino <small class="fw-bold" style="font-size: 0.5em;">BETA</small>
    </a>

    <!-- Toggler link -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= $currentPage === 'gestione_office.php' ? 'active' : '' ?>" href="../pages/gestione_office.php">Gestione Office</a>
        </li>
        <?php if (in_array($user_role, ['admin','editor'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="../pages/products.php">Inserisci Prodotto</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../pages/categories.php">Categorie</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal"
               data-bs-target="#assignBarcodeModal">Assegna Codici</a>
          </li>
        <?php endif; ?>
        <?php if ($user_role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="../pages/gestione_utenze.php">Utenze</a></li>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin','editor'])): ?>
          <li class="nav-item"><a class="nav-link" href="../pages/carico_scarico.php">Carico/Scarico</a></li>
        <?php endif; ?>
        <?php if ($user_role === 'admin'): ?>
          <li class="nav-item"><a class="nav-link" href="../pages/logs.php">Log</a></li>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin','editor'])): ?>
          <li class="nav-item"><a class="nav-link" href="#"
              data-bs-toggle="modal" data-bs-target="#insertSupplierModal">Fornitori</a></li>
          <li class="nav-item"><a class="nav-link" href="../pages/trash.php">Distruzione</a></li>
        <?php endif; ?>
      </ul>

      <!-- Selezione Azienda -->
      <?php if ($user_role === 'admin' || $user_role === 'editor' ||
               ($user_role === 'read' && count($userCompanies) > 1)): ?>
      <form method="GET" action="../pages/dashboard.php"
            class="d-flex align-items-center ms-lg-3">
        <label for="company_select" class="me-2 text-white">Azienda:</label>
        <select name="company_id" id="company_select"
                class="form-select select-company"
                onchange="this.form.submit()">
          <?php if ($user_role === 'admin'): ?>
            <option value="all" <?= $selected_company_id==='all'?'selected':''; ?>>Generale</option>
          <?php endif; ?>
          <?php foreach ($userCompanies as $c): ?>
            <option value="<?= $c['id'] ?>"
              <?= $selected_company_id==$c['id']?'selected':''; ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <noscript>
          <button type="submit" class="btn btn-outline-light ms-2">Vai</button>
        </noscript>
      </form>
      <?php endif; ?>

      <!-- Logout -->
      <a class="btn btn-outline-light ms-lg-3 d-flex align-items-center"
         href="../logout.php" data-bs-toggle="tooltip"
         title="Esci dalla tua sessione">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
      </a>
    </div>
  </div>
</nav>

<?php
include '../pages/modals/insert_supplier_modal.php';
?>
<style>
  .bg-yellow-orange { background-color: #FFA500; }
</style>
