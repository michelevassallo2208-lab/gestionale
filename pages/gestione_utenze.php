<?php
// pages/gestione_utenze.php

session_start();
require_once '../function.php';

// Verifica se l'utente è loggato e ha il ruolo "admin"
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Non sei autorizzato ad accedere a questa pagina.";
    header('Location: ../index.php');
    exit;
}

// Connessione al database
$db = connectDB();
if (!$db) {
    die("Errore di connessione al database"); // Gestione errore robusta
}

// Recupera tutte le aziende
$companies = $db->query("SELECT * FROM companies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Funzione per mostrare i messaggi
function mostraMessaggi() {
    if (isset($_SESSION['success'])) {
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>" 
            . htmlspecialchars($_SESSION['success']) .
            "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>" 
            . htmlspecialchars($_SESSION['error']) .
            "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>";
        unset($_SESSION['error']);
    }
}

// Recupera tutti gli utenti con le loro aziende
$stmt = $db->query("
    SELECT u.id, u.username, u.role, GROUP_CONCAT(uc.company_id) as company_ids
    FROM users u
    LEFT JOIN user_companies uc ON u.id = uc.user_id
    GROUP BY u.id, u.username, u.role
    ORDER BY u.username
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gestione delle azioni POST (crea, modifica, elimina)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        // ... (resto del blocco create invariato)
    }

    elseif ($action === 'edit') {
        // ... (resto del blocco edit invariato)
    }

    elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $_SESSION['error'] = "ID utente mancante per l'eliminazione.";
        } else {
            try {
                // Inizio transazione
                $db->beginTransaction();

                // 1) Rimuovi prima tutte le associazioni in user_companies
                $stmt = $db->prepare("DELETE FROM user_companies WHERE user_id = ?");
                $stmt->execute([$userId]);

                // 2) Elimina l'utente
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);

                // Commit
                $db->commit();
                $_SESSION['success'] = "Utente eliminato con successo.";
            } catch (PDOException $e) {
                // Rollback in caso di errore
                $db->rollBack();
                $_SESSION['error'] = "Errore durante l'eliminazione dell'utente: " . $e->getMessage();
            }
        }

        // Ricarica la pagina per mostrare messaggi e lista aggiornata
        header('Location: gestione_utenze.php');
        exit;
    }
}

// Dopo eventuale POST, ricarica la lista aggiornata
// (In create/edit rimandiamo già via header() alla stessa pagina.)

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti – Gestionale Magazzino</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../templates/header.php'; ?>
    <div class="container-fluid" style="margin-top:80px;">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            <main class="col-12 col-lg-10 ms-auto px-4">
                <h1 class="mt-4 mb-4">Gestione Utenti</h1>

                <!-- Messaggi -->
                <?php mostraMessaggi(); ?>

                <!-- Form per creare un nuovo utente -->
                <div class="card mb-4">
                    <div class="card-header">Crea Nuovo Utente</div>
                    <div class="card-body">
                        <form method="POST" action="gestione_utenze.php">
                            <input type="hidden" name="action" value="create">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="username" class="form-label">Nome Utente</label>
                                    <input type="text" id="username" name="username" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="role" class="form-label">Gruppo</label>
                                    <select id="role" name="role" class="form-select" required>
                                        <option value="admin">Admin</option>
                                        <option value="editor">Editor</option>
                                        <option value="read">Lettore</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="companies" class="form-label">Aziende</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($companies as $company): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="companies[]" value="<?= $company['id'] ?>"
                                                    id="companyCheck<?= $company['id'] ?>">
                                                <label class="form-check-label" for="companyCheck<?= $company['id'] ?>">
                                                    <?= htmlspecialchars($company['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i> Crea Utente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabella utenti esistenti -->
                <div class="card">
                    <div class="card-header">Utenti Esistenti</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>Nome Utente</th>
                                        <th>Gruppo</th>
                                        <th>Aziende</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): 
                                        $userCompanyIds = !empty($user['company_ids'])
                                            ? explode(',', $user['company_ids'])
                                            : [];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['role']) ?></td>
                                        <td>
                                            <?php if ($userCompanyIds): ?>
                                                <?php foreach ($companies as $co): 
                                                    if (in_array($co['id'], $userCompanyIds)): ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($co['name']) ?></span>
                                                <?php endif; endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nessuna azienda</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Bottone Modifica -->
                                            <button class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal<?= $user['id'] ?>">
                                                <i class="fas fa-edit me-1"></i>Modifica
                                            </button>
                                            <!-- Form Eliminazione -->
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Eliminare utente?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash-alt me-1"></i>Elimina
                                                </button>
                                            </form>

                                            <!-- Modal Modifica -->
                                            <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="gestione_utenze.php">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Modifica Utente</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="edit">
                                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nuova Password</label>
                                                                    <input type="password" name="password" class="form-control" placeholder="Lascia vuoto per non cambiare">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Gruppo</label>
                                                                    <select name="role" class="form-select" required>
                                                                        <option value="admin"  <?= $user['role']==='admin'? 'selected':'' ?>>Admin</option>
                                                                        <option value="editor" <?= $user['role']==='editor'?'selected':'' ?>>Editor</option>
                                                                        <option value="read"   <?= $user['role']==='read'?  'selected':'' ?>>Lettore</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Aziende</label>
                                                                    <div class="d-flex flex-wrap gap-2">
                                                                        <?php foreach ($companies as $co): ?>
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                name="companies[]" value="<?= $co['id'] ?>"
                                                                                id="editCo<?= $user['id'] ?>_<?= $co['id'] ?>"
                                                                                <?= in_array($co['id'], $userCompanyIds)? 'checked':'' ?>>
                                                                            <label class="form-check-label" for="editCo<?= $user['id'] ?>_<?= $co['id'] ?>">
                                                                                <?= htmlspecialchars($co['name']) ?>
                                                                            </label>
                                                                        </div>
                                                                        <?php endforeach; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                                                                <button type="submit" class="btn btn-warning">Salva</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- fine modal -->
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
        </div>
    </div>

    <?php include '../templates/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
