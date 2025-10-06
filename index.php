<?php
session_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Login – Gestionale Magazzino</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <style>
    /* ------------- Parallax Vars & Base ------------- */
    body, input, button { font-family: 'Poppins', sans-serif; }
    body {
      --bg-x: 50%;
      --bg-y: 50%;
      margin: 0; padding: 0;
      height: 100vh; overflow: hidden;
      display: flex; align-items: center; justify-content: center;
      background: linear-gradient(-45deg, #003366, #0055aa, #3388ff, #66bbff);
      background-size: 400% 400%;
      background-position: var(--bg-x) var(--bg-y);
      animation: gradientBG 15s ease infinite;
      position: relative;
      transition: background-position 0.2s ease-out;
    }
    @keyframes gradientBG {
      0%   { background-position: var(--bg-x) var(--bg-y); }
      33%  { background-position: calc(var(--bg-x) + 10%) calc(var(--bg-y) + 10%); }
      66%  { background-position: calc(var(--bg-x) - 10%) calc(var(--bg-y) - 10%); }
      100% { background-position: var(--bg-x) var(--bg-y); }
    }

    /* Floating background blobs */
    body::before, body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: 0.15;
      background: radial-gradient(circle at center, #66bbff, transparent);
      animation: float 8s ease-in-out infinite;
    }
    body::before {
      width: 300px; height: 300px;
      top: 5%; left: 10%;
    }
    body::after {
      width: 250px; height: 250px;
      bottom: 5%; right: 15%;
      animation-duration: 10s;
    }
    @keyframes float {
      0%,100% { transform: translateY(0) scale(1); }
      50%    { transform: translateY(20px) scale(1.1); }
    }

    /* ------------- Glass Card ------------- */
    .glass-card {
      position: relative;
      background: rgba(255,255,255,0.85);
      border-radius: 16px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.5);
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
      padding: 2.5rem 2rem;
      width: 100%; max-width: 400px;
      transition: transform .3s ease, box-shadow .3s ease;
      transform-style: preserve-3d;
    }
    .glass-card:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 0 12px 48px rgba(0,0,0,0.15);
    }

    /* 3D tilt effect */
    .glass-card.tilt {
      transition: none;
    }

    /* ------------- Logo & Title ------------- */
    .logo {
      display: block; margin: 0 auto 1.5rem;
      width: 120px; height: 120px; object-fit: contain;
      animation: logoPop .6s ease;
    }
    @keyframes logoPop {
      0%   { transform: scale(0); }
      60%  { transform: scale(1.1); }
      100% { transform: scale(1); }
    }
    .glass-card h2 {
      color: #003366;
      font-weight: 600;
      text-align: center;
      margin-bottom: 1.5rem;
      position: relative;
    }
    .glass-card h2::after {
      content: '';
      width: 60px; height: 3px;
      background: #3388ff;
      display: block; margin: 8px auto 0;
      border-radius: 2px;
      animation: underlineGrow .5s ease both;
    }
    @keyframes underlineGrow {
      from { width: 0; }
      to   { width: 60px; }
    }

    /* ------------- Inputs ------------- */
    .input-group-custom {
      position: relative;
      margin-bottom: 1.5rem;
    }
    .input-icon {
      position: absolute; left: 1rem; top: 50%;
      transform: translateY(-50%);
      color: rgba(0,0,0,0.4); font-size: 1.1rem;
      pointer-events: none;
      transition: color .3s;
    }
    .form-control {
      width: 100%; height: 3rem;
      padding: 1rem .75rem .25rem 2.5rem;
      border-radius: .75rem;
      border: none;
      background: rgba(255,255,255,0.7);
      box-shadow: inset 0 2px 6px rgba(0,0,0,0.05);
      font-size: 1rem;
      transition: background .3s, box-shadow .3s;
    }
    .form-control:focus {
      background: rgba(255,255,255,0.9);
      box-shadow: inset 0 2px 8px rgba(0,0,0,0.1);
      outline: none;
    }
    .form-control:focus + .input-icon {
      color: #0055aa;
    }

    /* ------------- Ripple Button ------------- */
    .btn-login {
      position: relative; overflow: hidden;
      width: 100%; padding: .75rem;
      font-size: 1rem; font-weight: 600;
      border: none; border-radius: .75rem;
      background: rgba(0,51,102,0.85);
      color: #fff; backdrop-filter: blur(6px);
      transition: background .3s, box-shadow .3s;
    }
    .btn-login:hover {
      background: #003366;
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    @keyframes ripple {
      to { transform: scale(4); opacity: 0; }
    }
    .btn-login .ripple {
      position: absolute; border-radius: 50%;
      background-color: rgba(255,255,255,0.7);
      transform: scale(0);
      animation: ripple .6s linear;
      pointer-events: none;
    }

    /* ------------- Alerts ------------- */
    .alert {
      border-radius: .75rem;
      backdrop-filter: blur(6px);
      background: rgba(255,255,255,0.7) !important;
      color: #333 !important;
      margin-bottom: 1rem;
      animation: fadeIn .4s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="glass-card" id="loginCard">
    <img src="img/logo.png" alt="Logo" class="logo">
    <h2>Accedi al Gestionale</h2>

    <!-- Messaggi -->
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($_SESSION['error']) ?></div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success text-center"><?= htmlspecialchars($_SESSION['success']) ?></div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Form -->
    <form action="auth.php" method="POST" id="loginForm">
      <div class="input-group-custom">
        <i class="fas fa-user input-icon"></i>
        <input type="text" name="username" class="form-control" placeholder="Username" required>
      </div>
      <div class="input-group-custom">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="btn-login">Entra ora</button>
    </form>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
   
    // Parallax background on mouse
    document.addEventListener('mousemove', e => {
      const x = (e.clientX / window.innerWidth  - 0.5) * 20;
      const y = (e.clientY / window.innerHeight - 0.5) * 20;
      document.body.style.setProperty('--bg-x', `${50 + x}%`);
      document.body.style.setProperty('--bg-y', `${50 + y}%`);
    });
  </script>
</body>
</html>
