<?php
// products.php

session_start();
require_once '../function.php';
$db = connectDB();

// Controllo permessi
if (!isset($_SESSION['user_id']) ||
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'editor')) {
    header('Location: ../index.php');
    exit;
}

// Sticky form data
$old = $_SESSION['old_data'] ?? [];
unset($_SESSION['old_data']);

// Fetch dati per dropdown
$categories = $db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$suppliers  = $db->query("SELECT id,name FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$companies  = $db->query("SELECT id,name FROM companies ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Messaggi
$error   = $_SESSION['error']   ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Nuovo Prodotto – Wizard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap, Animate.css & FontAwesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f0f2f5; }
    .card { border: none; border-radius: 1rem; overflow: hidden; margin-bottom: 2rem; }
    .card-body { padding: 2rem; }
    .wizard-nav { display: flex; justify-content: space-between; margin-bottom: 1.5rem; }
    .wizard-step {
      flex: 1; text-align: center; position: relative;
      color: #aaa; font-weight: 500; cursor: pointer;
      transition: color .3s;
    }
    .wizard-step:not(:last-child)::after {
      content: ''; position: absolute;
      right: 0; top: 50%;
      width: 100%; height: 2px;
      background: #ddd;
      transform: translateY(-50%) translateX(50%);
      z-index: -1;
    }
    .wizard-step.completed,
    .wizard-step.active { color: #4e89ff; }
    .wizard-step.completed:not(:last-child)::after { background: #4e89ff; }
    .form-section { display: none; }
    .form-section.active { display: block; animation: fadeInUp .5s; }
    .is-invalid { border-color: #dc3545; }
  </style>
</head>
<body>
  <?php include '../templates/header.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php include '../templates/sidebar.php'; ?>

      <main class="col-12 col-lg-10 ms-auto p-4">
        <h2 class="mb-4 text-primary animate__animated animate__fadeInDown">
          <i class="fas fa-plus-circle me-2"></i>Nuovo Prodotto (Wizard)
        </h2>

        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="card animate__animated animate__fadeInUp">
          <div class="card-body">

            <!-- Nav a step -->
            <div class="wizard-nav">
              <div class="wizard-step active" data-step="1">1. Dati</div>
              <div class="wizard-step"       data-step="2">2. Categoria</div>
              <div class="wizard-step"       data-step="3">3. Fornitore</div>
              <div class="wizard-step"       data-step="4">4. Stock</div>
              <div class="wizard-step"       data-step="5">5. Azienda</div>
            </div>

            <form action="insert_product.php" method="POST" id="wizardForm" novalidate>
              <!-- STEP 1: DATI PRODOTTO -->
              <div class="form-section active" data-step="1">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="name" class="form-label">Nome Prodotto</label>
                    <input type="text" id="name" name="name" class="form-control" required
                           value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                    <div class="invalid-feedback">Inserisci il nome.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="model" class="form-label">Modello</label>
                    <input type="text" id="model" name="model" class="form-control" required
                           value="<?= htmlspecialchars($old['model'] ?? '') ?>">
                    <div class="invalid-feedback">Inserisci il modello.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="manufacturer" class="form-label">Produttore</label>
                    <input type="text" id="manufacturer" name="manufacturer" class="form-control" required
                           value="<?= htmlspecialchars($old['manufacturer'] ?? '') ?>">
                    <div class="invalid-feedback">Inserisci il produttore.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="barcode" class="form-label">Barcode (15 caratteri)</label>
                    <input type="text" id="barcode" name="barcode" class="form-control" required minlength="15" maxlength="15"
                           value="<?= htmlspecialchars($old['barcode'] ?? 'CCSUD000000') ?>">
                    <div class="invalid-feedback">Inserisci un barcode di 15 caratteri.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="serial_number" class="form-label">Serial Number (Opzionale)</label>
                    <input type="text" id="serial_number" name="serial_number" class="form-control"
                           value="<?= htmlspecialchars($old['serial_number'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label for="description" class="form-label">Descrizione</label>
                    <textarea id="description" name="description" class="form-control" rows="3" required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    <div class="invalid-feedback">Inserisci una descrizione.</div>
                  </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                  <button type="button" class="btn btn-primary" data-next>Avanti</button>
                </div>
              </div>

              <!-- STEP 2: CATEGORIA / SOTTOCATEGORIA -->
              <div class="form-section" data-step="2">
                <div class="mb-3">
                  <label for="category" class="form-label">Categoria</label>
                  <select id="category" name="category_id" class="form-select" required>
                    <option value="">Seleziona categoria</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>"
                        <?= (isset($old['category_id']) && $old['category_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Seleziona una categoria.</div>
                </div>
                <div class="mb-3">
                  <label for="subcategory" class="form-label">Sottocategoria</label>
                  <select id="subcategory" name="subcategory_id" class="form-select" required>
                    <option value="">Seleziona prima categoria</option>
                  </select>
                  <div class="invalid-feedback">Seleziona una sottocategoria.</div>
                </div>
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-secondary" data-prev>Indietro</button>
                  <button type="button" class="btn btn-primary" data-next>Avanti</button>
                </div>
              </div>

              <!-- STEP 3: FORNITORE & SCAFFALE -->
              <div class="form-section" data-step="3">
                <div class="mb-3">
                  <label for="supplier" class="form-label">Fornitore</label>
                  <select id="supplier" name="supplier_id" class="form-select" required>
                    <option value="">Seleziona fornitore</option>
                    <?php foreach ($suppliers as $s): ?>
                      <option value="<?= $s['id'] ?>"
                        <?= isset($old['supplier_id']) && $old['supplier_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Seleziona un fornitore.</div>
                </div>
                <div class="mb-3">
                  <label for="shelf" class="form-label">Scaffale</label>
                  <input type="text" id="shelf" name="shelf" class="form-control" required
                         value="<?= htmlspecialchars($old['shelf'] ?? '') ?>">
                  <div class="invalid-feedback">Inserisci lo scaffale.</div>
                </div>
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-secondary" data-prev>Indietro</button>
                  <button type="button" class="btn btn-primary" data-next>Avanti</button>
                </div>
              </div>

              <!-- STEP 4: QUANTITÀ, PREZZO, DISPONIBILITÀ -->
              <div class="form-section" data-step="4">
                <div class="mb-3">
                  <label for="quantity" class="form-label">Quantità</label>
                  <input type="number" id="quantity" name="quantity" class="form-control" min="0" required
                         value="<?= htmlspecialchars($old['quantity'] ?? '') ?>">
                  <div class="invalid-feedback">Inserisci la quantità.</div>
                </div>
                <div class="mb-3">
                  <label for="purchase_price" class="form-label">Prezzo (€)</label>
                  <input type="number" step="0.01" id="purchase_price" name="purchase_price" class="form-control" required
                         value="<?= htmlspecialchars($old['purchase_price'] ?? '') ?>">
                  <div class="invalid-feedback">Inserisci il prezzo.</div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Disponibilità automatica</label>
                  <input type="hidden" name="availability" id="availability_input" value="<?= htmlspecialchars($old['availability'] ?? '0') ?>">
                  <select id="availability_display" class="form-select" disabled>
                    <option value="1">Disponibile</option>
                    <option value="0">Non disponibile</option>
                  </select>
                </div>
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-secondary" data-prev>Indietro</button>
                  <button type="button" class="btn btn-primary" data-next>Avanti</button>
                </div>
              </div>

              <!-- STEP 5: AZIENDA -->
              <div class="form-section" data-step="5">
                <div class="mb-3">
                  <label for="company" class="form-label">Azienda</label>
                  <select id="company" name="company_id" class="form-select" required>
                    <option value="">Seleziona azienda</option>
                    <?php foreach ($companies as $co): ?>
                      <option value="<?= $co['id'] ?>"
                        <?= isset($old['company_id']) && $old['company_id'] == $co['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($co['name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Seleziona un'azienda.</div>
                </div>
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-secondary" data-prev>Indietro</button>
                  <button type="submit" class="btn btn-primary">Salva Prodotto</button>
                </div>
              </div>
            </form>

          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include '../templates/footer.php'; ?>

  <!-- JS Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sections = document.querySelectorAll('.form-section');
    const steps    = document.querySelectorAll('.wizard-step');
    let current    = 1;

    function showStep(n) {
      current = n;
      sections.forEach(sec => sec.classList.toggle('active', +sec.dataset.step === n));
      steps.forEach(st => {
        const idx = +st.dataset.step;
        st.classList.toggle('completed', idx < n);
        st.classList.toggle('active',    idx === n);
      });
    }

    function validateStep() {
      const sec = sections[current-1];
      const fields = sec.querySelectorAll('input[required],select[required],textarea[required]');
      for (let f of fields) {
        if (!f.checkValidity()) {
          f.classList.add('is-invalid');
          f.reportValidity();
          return false;
        }
        f.classList.remove('is-invalid');
      }
      return true;
    }

    document.querySelectorAll('[data-next]').forEach(btn =>
      btn.addEventListener('click', () => {
        if (validateStep() && current < sections.length) showStep(current+1);
      })
    );
    document.querySelectorAll('[data-prev]').forEach(btn =>
      btn.addEventListener('click', () => showStep(current-1))
    );
    steps.forEach(st =>
      st.addEventListener('click', () => {
        const idx = +st.dataset.step;
        if (idx < current || validateStep()) showStep(idx);
      })
    );

    // Funzione per caricare le sottocategorie
    async function loadSubcats(cat, pre) {
      const sel = document.getElementById('subcategory');
      sel.innerHTML = '<option>Caricamento…</option>';
      try {
        const resp = await fetch(`../pages/get_subcategories.php?category_id=${cat}`);
        const data = await resp.json();
        sel.innerHTML = '<option value="">Seleziona sottocategoria</option>';
        data.forEach(s => sel.add(new Option(s.name, s.id)));
        if (pre) sel.value = pre;
      } catch {
        sel.innerHTML = '<option>Errore</option>';
      }
    }

    // **Listener aggiunto** sul change della categoria
    document.getElementById('category')
      .addEventListener('change', e => loadSubcats(e.target.value));

    // Aggiorna disponibilità da quantità
    function updateAvailability() {
      const q = parseInt(document.getElementById('quantity').value,10) || 0;
      const availVal = q > 0 ? '1' : '0';
      document.getElementById('availability_input').value = availVal;
      document.getElementById('availability_display').value = availVal;
    }
    document.getElementById('quantity')
      .addEventListener('input', updateAvailability);

    document.addEventListener('DOMContentLoaded', () => {
      // Sticky category/sub
      const oldCat = <?= json_encode($old['category_id'] ?? '') ?>;
      const oldSub = <?= json_encode($old['subcategory_id'] ?? '') ?>;
      if (oldCat) loadSubcats(oldCat, oldSub);
      // inizializza disponibilità e step
      updateAvailability();
      showStep(1);
    });
  </script>
</body>
</html>
