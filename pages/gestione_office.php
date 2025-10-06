<?php
// pages/gestione_office.php

session_start();
require_once '../function.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$db = connectDB();
if (!$db) {
    die('Impossibile connettersi al database.');
}

$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$successMessage = $_SESSION['success'] ?? null;
$errorMessage   = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $licenseKey   = trim($_POST['license_key'] ?? '');
            $productName  = trim($_POST['product_name'] ?? '');
            $notes        = trim($_POST['notes'] ?? '');
            $assignHost   = trim($_POST['assign_hostname'] ?? '');

            if ($licenseKey === '' || $productName === '') {
                throw new RuntimeException('Compila almeno il prodotto e la licenza.');
            }

            $hostname     = $assignHost !== '' ? strtoupper($assignHost) : null;
            $assignedAt   = $hostname ? date('Y-m-d H:i:s') : null;

            $stmt = $db->prepare("INSERT INTO office_licenses (product_name, license_key, notes, assigned_hostname, assigned_at)
                                  VALUES (:product_name, :license_key, :notes, :assigned_hostname, :assigned_at)");
            $stmt->execute([
                ':product_name'      => $productName,
                ':license_key'       => $licenseKey,
                ':notes'             => $notes !== '' ? $notes : null,
                ':assigned_hostname' => $hostname,
                ':assigned_at'       => $assignedAt,
            ]);

            logActivity(
                $_SESSION['user_id'],
                'office_license_create',
                "Creata licenza {$licenseKey}" . ($hostname ? " assegnata a {$hostname}" : ''),
                $ipAddress,
                $userAgent
            );

            $_SESSION['success'] = 'Licenza aggiunta con successo.';
        }

        if ($action === 'assign') {
            $licenseId = isset($_POST['license_id']) ? (int) $_POST['license_id'] : 0;
            $hostname  = trim($_POST['hostname'] ?? '');

            if ($licenseId <= 0 || $hostname === '') {
                throw new RuntimeException('Dati per l\'assegnazione non validi.');
            }

            $stmt = $db->prepare('SELECT license_key, assigned_hostname FROM office_licenses WHERE id = :id');
            $stmt->execute([':id' => $licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                throw new RuntimeException('Licenza non trovata.');
            }

            if (!empty($license['assigned_hostname'])) {
                throw new RuntimeException('La licenza risulta già assegnata.');
            }

            $hostname = strtoupper($hostname);

            $stmt = $db->prepare('UPDATE office_licenses
                                   SET assigned_hostname = :hostname, assigned_at = :assigned_at
                                   WHERE id = :id');
            $stmt->execute([
                ':hostname'    => $hostname,
                ':assigned_at' => date('Y-m-d H:i:s'),
                ':id'          => $licenseId,
            ]);

            logActivity(
                $_SESSION['user_id'],
                'office_license_assign',
                "Assegnata licenza {$license['license_key']} a {$hostname}",
                $ipAddress,
                $userAgent
            );

            $_SESSION['success'] = 'Licenza assegnata correttamente.';
        }

        if ($action === 'unassign') {
            $licenseId = isset($_POST['license_id']) ? (int) $_POST['license_id'] : 0;

            if ($licenseId <= 0) {
                throw new RuntimeException('Licenza non valida.');
            }

            $stmt = $db->prepare('SELECT license_key, assigned_hostname FROM office_licenses WHERE id = :id');
            $stmt->execute([':id' => $licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                throw new RuntimeException('Licenza non trovata.');
            }

            $stmt = $db->prepare('UPDATE office_licenses
                                   SET assigned_hostname = NULL, assigned_at = NULL
                                   WHERE id = :id');
            $stmt->execute([':id' => $licenseId]);

            logActivity(
                $_SESSION['user_id'],
                'office_license_unassign',
                "Disassegnata licenza {$license['license_key']}",
                $ipAddress,
                $userAgent
            );

            $_SESSION['success'] = 'Licenza disassegnata. Torna disponibile.';
        }
    } catch (PDOException $e) {
        if ((int) $e->getCode() === 23000) {
            $_SESSION['error'] = 'Questa licenza esiste già. Utilizza un codice univoco.';
        } else {
            $_SESSION['error'] = 'Errore database: ' . $e->getMessage();
        }
    } catch (RuntimeException $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: gestione_office.php');
    exit;
}

$stmt = $db->query('SELECT id, product_name, license_key, notes, assigned_hostname, assigned_at, created_at
                    FROM office_licenses
                    ORDER BY assigned_hostname IS NOT NULL, product_name, license_key');
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLicenses    = count($licenses);
$assignedLicenses = 0;
foreach ($licenses as $license) {
    if (!empty($license['assigned_hostname'])) {
        $assignedLicenses++;
    }
}
$availableLicenses = $totalLicenses - $assignedLicenses;

$preFilledSearch = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Office – Gestionale Magazzino</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        main { padding-bottom: 4rem; }
        .page-title {
            margin-top: 1.5rem;
            animation: fadeInDown .6s ease;
        }
        .page-title h1 { font-weight: 700; }
        .page-title p { color: #6c757d; }
        .metric-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 12px 30px rgba(13, 110, 253, .1);
            background: linear-gradient(135deg, #001f3f, #0d6efd);
            color: #fff;
            overflow: hidden;
        }
        .metric-card .icon-wrap {
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, .15);
        }
        .card-office {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
        }
        .card-office .card-header {
            background: linear-gradient(120deg, #001f3f, #0d6efd);
            color: #fff;
            border-bottom: none;
        }
        .card-office .table thead {
            background: #0d6efd;
            color: #fff;
        }
        .card-office .table tbody tr {
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .card-office .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, .15);
        }
        tr[data-status="assigned"] {
            border-left: 4px solid #ffc107;
        }
        tr[data-status="available"] {
            border-left: 4px solid #20c997;
        }
        .badge-assigned {
            background: rgba(255, 193, 7, .2);
            color: #856404;
        }
        .badge-available {
            background: rgba(32, 201, 151, .2);
            color: #0f5132;
        }
        .search-bar .input-group-text {
            background: transparent;
            border-right: 0;
        }
        .search-bar .form-control {
            border-left: 0;
            box-shadow: none !important;
        }
        #emptyState {
            padding: 3rem 1rem;
        }
        .notes-text {
            max-width: 260px;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translate3d(0, -10px, 0); }
            to   { opacity: 1; transform: none; }
        }
    </style>
</head>
<body>
<?php include '../templates/header.php'; ?>
<div class="container-fluid" style="margin-top: 80px;">
    <div class="row">
        <?php include '../templates/sidebar.php'; ?>
        <main class="col-12 col-lg-10 ms-auto px-4">
            <div class="page-title">
                <h1 class="h2 text-dark mb-1"><i class="fas fa-microsoft me-2 text-primary"></i>Gestione Office</h1>
                <p class="mb-0">Tutte le licenze Microsoft Office in un unico pannello, con ricerca immediata per hostname o codice.</p>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show mt-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
                </div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 mt-1">
                <div class="col-md-4">
                    <div class="card metric-card p-4 d-flex flex-row align-items-center justify-content-between">
                        <div>
                            <p class="mb-1 text-uppercase small opacity-75">Totale licenze</p>
                            <h2 class="mb-0" id="totalLicenses" data-base="<?= $totalLicenses ?>"><?= $totalLicenses ?></h2>
                        </div>
                        <div class="icon-wrap d-flex align-items-center justify-content-center">
                            <i class="fas fa-layer-group"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card metric-card p-4 d-flex flex-row align-items-center justify-content-between" style="background: linear-gradient(135deg, #0d6efd, #6610f2);">
                        <div>
                            <p class="mb-1 text-uppercase small opacity-75">Licenze assegnate</p>
                            <h2 class="mb-0" id="assignedLicenses" data-base="<?= $assignedLicenses ?>"><?= $assignedLicenses ?></h2>
                        </div>
                        <div class="icon-wrap d-flex align-items-center justify-content-center">
                            <i class="fas fa-desktop"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card metric-card p-4 d-flex flex-row align-items-center justify-content-between" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <div>
                            <p class="mb-1 text-uppercase small opacity-75">Licenze libere</p>
                            <h2 class="mb-0" id="availableLicenses" data-base="<?= $availableLicenses ?>"><?= $availableLicenses ?></h2>
                        </div>
                        <div class="icon-wrap d-flex align-items-center justify-content-center">
                            <i class="fas fa-unlock"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-12 col-xl-4">
                    <div class="card card-office h-100">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Aggiungi nuova licenza</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="gestione_office.php">
                                <input type="hidden" name="action" value="create">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Prodotto</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Es. Microsoft 365 Business" required>
                                </div>
                                <div class="mb-3">
                                    <label for="license_key" class="form-label">Codice licenza</label>
                                    <input type="text" class="form-control" id="license_key" name="license_key" placeholder="XXXXX-XXXXX-XXXXX-XXXXX" required>
                                </div>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Note</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Informazioni aggiuntive facoltative"></textarea>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="assignToPcToggle">
                                    <label class="form-check-label" for="assignToPcToggle">Assegna subito ad un PC</label>
                                </div>
                                <div id="assignHostnameGroup" class="mb-3 d-none">
                                    <label for="assign_hostname" class="form-label">Hostname PC</label>
                                    <input type="text" class="form-control" id="assign_hostname" name="assign_hostname" placeholder="Es. PC-UFFICIO-01">
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Salva licenza
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="card card-office">
                        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i>Archivio licenze Office</h5>
                            <div class="search-bar w-100 w-lg-50">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="search" id="licenseSearch" class="form-control" placeholder="Cerca per licenza, hostname o prodotto" value="<?= htmlspecialchars($preFilledSearch) ?>">
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 180px;">Licenza</th>
                                            <th style="min-width: 160px;">Prodotto</th>
                                            <th style="min-width: 160px;">Hostname</th>
                                            <th style="min-width: 150px;">Stato</th>
                                            <th class="notes-text">Note</th>
                                            <th class="text-end" style="min-width: 180px;">Azioni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!$licenses): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-5">Non ci sono licenze salvate al momento.</td>
                                            </tr>
                                        <?php endif; ?>
                                        <?php foreach ($licenses as $license):
                                            $isAssigned = !empty($license['assigned_hostname']);
                                            $searchData = strtolower($license['license_key'] . ' ' . $license['product_name'] . ' ' . ($license['assigned_hostname'] ?? ''));
                                            $assignedAtFormatted = $license['assigned_at'] ? date('d/m/Y H:i', strtotime($license['assigned_at'])) : null;
                                            $modalId = 'assignModal' . $license['id'];
                                        ?>
                                        <tr data-license-row data-status="<?= $isAssigned ? 'assigned' : 'available' ?>" data-search="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold text-primary"><?= htmlspecialchars($license['license_key']) ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-copy="<?= htmlspecialchars($license['license_key'], ENT_QUOTES, 'UTF-8') ?>" data-label="Copia">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Creato il <?= date('d/m/Y', strtotime($license['created_at'])) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($license['product_name']) ?></td>
                                            <td>
                                                <?php if ($isAssigned): ?>
                                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($license['assigned_hostname']) ?></span><br>
                                                    <small class="text-muted">dal <?= htmlspecialchars($assignedAtFormatted ?? '') ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isAssigned): ?>
                                                    <span class="badge badge-assigned">Assegnata</span>
                                                <?php else: ?>
                                                    <span class="badge badge-available">Disponibile</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= $license['notes'] ? nl2br(htmlspecialchars($license['notes'])) : '<span class="text-muted">—</span>' ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($isAssigned): ?>
                                                    <form method="POST" action="gestione_office.php" class="d-inline" onsubmit="return confirm('Vuoi disassegnare questa licenza?');">
                                                        <input type="hidden" name="action" value="unassign">
                                                        <input type="hidden" name="license_id" value="<?= $license['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="fas fa-unlink me-1"></i>Disassegna
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= $modalId ?>">
                                                        <i class="fas fa-link me-1"></i>Assegna
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="gestione_office.php">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><i class="fas fa-link me-2 text-primary"></i>Assegna licenza</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="assign">
                                                            <input type="hidden" name="license_id" value="<?= $license['id'] ?>">
                                                            <div class="mb-3">
                                                                <label for="hostname<?= $license['id'] ?>" class="form-label">Hostname del PC</label>
                                                                <input type="text" class="form-control" id="hostname<?= $license['id'] ?>" name="hostname" placeholder="Es. PC-UFFICIO-01" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-check me-1"></i>Conferma
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        <tr id="emptyState" class="d-none">
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-search me-2"></i>Nessuna licenza corrisponde alla ricerca corrente.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const assignToggle = document.getElementById('assignToPcToggle');
    const assignGroup = document.getElementById('assignHostnameGroup');
    const assignInput = document.getElementById('assign_hostname');

    const updateAssignGroup = () => {
        if (!assignToggle) return;
        if (assignToggle.checked) {
            assignGroup.classList.remove('d-none');
            assignInput.removeAttribute('disabled');
            assignInput.focus();
        } else {
            assignGroup.classList.add('d-none');
            assignInput.value = '';
            assignInput.setAttribute('disabled', 'disabled');
        }
    };

    if (assignToggle) {
        assignToggle.addEventListener('change', updateAssignGroup);
        updateAssignGroup();
    }

    const searchInput = document.getElementById('licenseSearch');
    const rows = Array.from(document.querySelectorAll('[data-license-row]'));
    const totalSpan = document.getElementById('totalLicenses');
    const assignedSpan = document.getElementById('assignedLicenses');
    const availableSpan = document.getElementById('availableLicenses');
    const emptyState = document.getElementById('emptyState');

    const filterRows = () => {
        if (!searchInput) return;
        const query = searchInput.value.trim().toLowerCase();
        let visible = 0;
        let assigned = 0;
        let available = 0;

        rows.forEach(row => {
            const haystack = row.dataset.search ?? '';
            if (!query || haystack.includes(query)) {
                row.classList.remove('d-none');
                visible++;
                if (row.dataset.status === 'assigned') {
                    assigned++;
                } else {
                    available++;
                }
            } else {
                row.classList.add('d-none');
            }
        });

        if (query) {
            totalSpan.textContent = visible;
            assignedSpan.textContent = assigned;
            availableSpan.textContent = available;
        } else {
            totalSpan.textContent = totalSpan.dataset.base;
            assignedSpan.textContent = assignedSpan.dataset.base;
            availableSpan.textContent = availableSpan.dataset.base;
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', visible !== 0);
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', filterRows);
        if (searchInput.value) {
            filterRows();
        }
    }

    const copyButtons = document.querySelectorAll('[data-copy]');

    const copyToClipboard = async (text) => {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                return fallbackCopy(text);
            }
        }
        return fallbackCopy(text);
    };

    const fallbackCopy = (text) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'absolute';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        const selected = document.getSelection().rangeCount > 0 ? document.getSelection().getRangeAt(0) : false;
        textarea.select();
        const success = document.execCommand('copy');
        document.body.removeChild(textarea);
        if (selected) {
            document.getSelection().removeAllRanges();
            document.getSelection().addRange(selected);
        }
        return success;
    };

    copyButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const originalHTML = button.innerHTML;
            const originalClasses = button.className;
            const value = button.dataset.copy ?? '';
            const copied = await copyToClipboard(value);
            if (copied) {
                button.className = 'btn btn-sm btn-success';
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    button.className = originalClasses;
                    button.innerHTML = originalHTML;
                }, 1500);
            }
        });
    });
</script>

<?php include '../templates/footer.php'; ?>
