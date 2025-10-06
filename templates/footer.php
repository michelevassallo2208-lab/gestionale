<?php
// footer.php

// Disabilita la visualizzazione degli errori (solo in produzione)
error_reporting(0);
ini_set('display_errors', 0);

// Connessione al DB per ottenere l'azienda selezionata
require_once '../function.php';
$db = connectDB();

// Assicurati che la sessione sia avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determina la selezione corrente dell'azienda dalla sessione
$selected_company_id = $_SESSION['selected_company_id'] ?? 'all';

// Mappa l'ID dell'azienda a un colore di partenza per il gradient
switch ($selected_company_id) {
    case '2':
        $startColor = '#dc3545'; // Rosso
        break;
    case '3':
        $startColor = '#198754'; // Verde
        break;
    case '4':
        $startColor = '#ffae00'; // Giallo-arancione
        break;
    default:
        $startColor = '#0d6efd'; // Blu
        break;
}

// Colore finale del gradient (sempre nero)
$endColor = '#000000';
?>
<!-- Footer con gradient animato -->
<style>
  .footer {
    background: linear-gradient(120deg, <?= $startColor ?>, <?= $endColor ?>);
    background-size: 200% 200%;
    animation: footerGradient 10s ease infinite;
    padding: 0.3rem 0;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.4);
    color: #ffffff;
  }
  @keyframes footerGradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
  .footer .footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    margin: 0 .5rem;
    transition: color .3s;
  }
  .footer .footer-links a:hover {
    color: #ffffff;
  }
  .footer p {
    margin: 0;
  }
  .footer .small {
    opacity: 0.8;
  }
</style>

<footer class="footer text-center">
  <div class="container">
    <p class="mb-1">&copy; 2025 Gestionale Magazzino</p>
    <div class="footer-links small">
    </div>
  </div>
</footer>

<?php
// Registra eventi di login, logout e modifiche
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    if (basename($_SERVER['PHP_SELF']) === 'logout.php') {
        logActivity($userId, 'Logout', "L'utente ha effettuato il logout.", $ipAddress, $userAgent);
    }
    if (basename($_SERVER['PHP_SELF']) === 'login.php') {
        logActivity($userId, 'Login', "L'utente ha effettuato l'accesso.", $ipAddress, $userAgent);
    }
}
?>

<!-- Includi i modali e script -->
<?php include '../pages/modals/assignBarcodeModal.php'; ?>
<?php include '../pages/modals/stockManagementModal.php'; ?>

<!-- Script per chiudere automaticamente la sidebar Offcanvas quando si clicca su un link -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const offcanvasSidebar = document.getElementById('offcanvasSidebar');
    const links = offcanvasSidebar.querySelectorAll('.subcategory-link');
    links.forEach(link => {
      link.addEventListener('click', () => {
        const oc = bootstrap.Offcanvas.getInstance(offcanvasSidebar);
        if (oc) oc.hide();
      });
    });
  });
</script>

</body>
</html>
