<?php
session_start();
require_once 'function.php'; // Assicurati di includere le funzioni necessarie

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    logActivity(
        $userId,
        'logout',
        "L'utente ID {$userId} ha effettuato il logout.",
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    );
}

session_unset();
session_destroy();

echo <<<HTML
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Logout – Gestionale Magazzino</title>
  
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      margin: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      /* Gradient background */
      background: linear-gradient(135deg, #4e89ff, #67d0ff);
    }
    .logout-card {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      padding: 2rem;
      max-width: 400px;
      text-align: center;
      animation: fadeIn 0.6s ease-out;
    }
    .logout-icon {
      font-size: 3rem;
      color: #4e89ff;
      margin-bottom: 1rem;
    }
    .btn-login {
      background-color: #4e89ff;
      border: none;
    }
    .btn-login:hover {
      background-color: #3b6ed8;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="logout-card">
    <i class="bi bi-box-arrow-right logout-icon"></i>
    <h2 class="fw-bold mb-3">Logout Effettuato</h2>
    <p class="mb-4">Hai utilizzato la BETA di Gestionale Magazzino.</p>
    <p class="text-muted mb-4">Verrai reindirizzato alla pagina di accesso in <strong>3 secondi</strong>.</p>
    <a href="index.php" class="btn btn-login btn-lg text-white">
      <i class="bi bi-person-circle me-2"></i> Ritorna al Login
    </a>
  </div>

  <script>
    setTimeout(() => {
      window.location.href = 'index.php';
    }, 3000);
  </script>
</body>
</html>
HTML;
exit;
?>
