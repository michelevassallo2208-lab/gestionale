<?php
// pages/categories.php

session_start();
require_once '../function.php';
$db = connectDB();

// Accesso solo per admin ed editor
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'editor')) {
    header('Location: ../index.php');
    exit();
}

// Recupera categorie e sottocategorie
$categories    = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$subcategories = $db->query("SELECT * FROM subcategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Organizza l’albero
$categoryTree = [];
foreach ($categories as $c) {
    $categoryTree[$c['id']] = ['details'=>$c,'subcategories'=>[]];
}
foreach ($subcategories as $s) {
    if (isset($categoryTree[$s['category_id']])) {
        $categoryTree[$s['category_id']]['subcategories'][] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestione Categorie – Gestionale Magazzino</title>

  <!-- Bootstrap & Animate.css & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <style>
    body { background: #f0f2f5; }
    main { padding-top: 3.5rem; }

    .page-header {
      margin: 1.5rem 0 1rem;
      display: flex; justify-content: space-between; align-items: center;
      animation: fadeInDown 0.6s;
    }
    .page-header h1 { font-size: 1.75rem; }

    .card-category { border: none; border-radius: 1rem; box-shadow: 0 8px 24px rgba(0,0,0,0.1); animation: fadeInUp 0.6s; }
    .card-category .card-header {
      background: linear-gradient(90deg,#4e89ff,#67d0ff);
      color: #fff; border-bottom: none; font-weight: 500;
    }

    .table-category thead {
      background: linear-gradient(90deg,#4e89ff,#67d0ff);
      color: #fff;
    }
    .table-category tbody tr:hover { background: rgba(78,137,255,0.1); }

    /* Nuovo: differenziazione righe */
    .category-row {
      background-color: #e8f0fe;
      font-weight: 600;
    }
    .subcategory-row {
      background-color: #f9fbfd;
      font-weight: 400;
    }
    .subcategory-row td:first-child {
      padding-left: 2rem !important;
    }

    .btn-sm { padding: .25rem .5rem; font-size: .85rem; border-radius: .5rem; }
  </style>
</head>
<body>
  <?php include '../templates/header.php'; ?>

  <div class="container-fluid">
    <div class="row">
      <?php include '../templates/sidebar.php'; ?>
      <main class="col-12 col-lg-10 ms-auto px-4">

        <div class="page-header">
          <h1><i class="fas fa-tags me-2 text-primary"></i>Gestione Categorie</h1>
          <div>
            <button class="btn btn-primary rounded-pill me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
              <i class="fas fa-plus me-1"></i> Aggiungi Categoria
            </button>
            <button class="btn btn-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#addSubcategoryModal">
              <i class="fas fa-plus me-1"></i> Aggiungi Sotto
            </button>
          </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card card-category mb-5">
          <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Categorie e Sottocategorie</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-category align-middle mb-0">
                <thead>
                  <tr>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Categoria Prin.</th>
                    <th class="text-end">Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categoryTree as $cat): ?>
                    <tr class="category-row">
                      <td><i class="fas fa-folder text-primary me-2"></i><?= htmlspecialchars($cat['details']['name']) ?></td>
                      <td>Categoria</td>
                      <td>–</td>
                      <td class="text-end">
                        <button class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal-<?= $cat['details']['id'] ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal-<?= $cat['details']['id'] ?>">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </td>
                    </tr>
                    <?php foreach ($cat['subcategories'] as $sub): ?>
                      <tr class="subcategory-row">
                        <td><i class="fas fa-angle-right text-secondary me-2"></i><?= htmlspecialchars($sub['name']) ?></td>
                        <td>Sottocategoria</td>
                        <td><?= htmlspecialchars($cat['details']['name']) ?></td>
                        <td class="text-end">
                          <button class="btn btn-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal-<?= $sub['id'] ?>">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSubcategoryModal-<?= $sub['id'] ?>">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <?php include '../templates/footer.php'; ?>

  <!-- Modali -->
  <?php include '../pages/modals/addCategoryModal.php'; ?>
  <?php include '../pages/modals/addSubcategoryModal.php'; ?>
  <?php include '../pages/modals/editCategoryModal.php'; ?>
  <?php include '../pages/modals/deleteCategoryModal.php'; ?>
  <?php include '../pages/modals/editSubcategoryModal.php'; ?>
  <?php include '../pages/modals/deleteSubcategoryModal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
