<?php
session_start();
require_once 'function.php'; // Assicurati che il percorso sia corretto

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizzazione e validazione degli input
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $user = verifyUser($username, $password);
    if ($user) {
        // Imposta le variabili di sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role'] = $user['role']; // Assicurati di impostare anche 'role' se usato in logs.php

        // Log dell'accesso riuscito
        logActivity(
            $user['id'], // user_id
            'accesso', // action
            "L'utente '{$username}' ha effettuato il login.", // description
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown', // ip_address
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown' // user_agent
        );

        // Reindirizza alla dashboard
        header('Location: pages/dashboard.php');
        exit();
    } else {
        // Log del tentativo di accesso fallito
        logActivity(
            0, // user_id = 0 per tentativi falliti
            'accesso_fallito', // action
            "Tentativo di accesso fallito per l'username: '{$username}'.", // description
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown', // ip_address
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown' // user_agent
        );

        // Imposta un messaggio di errore nella sessione
        $_SESSION['error'] = "Credenziali non valide. Riprova.";

        // Reindirizza alla pagina di login
        header('Location: index.php');
        exit();
    }
}
?>
