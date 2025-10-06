<?php
// sidebar.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../function.php';
$db = connectDB();

// 1) Prendi tutte le categorie
$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 2) Prendi tutte le sottocategorie
$subs = $db->query("SELECT id, name, category_id FROM subcategories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// 3) Costruisci albero iniziale
$rawTree = [];
foreach ($subs as $s) {
    $rawTree[$s['category_id']][] = $s;
}

// 4) Filtra le sottocategorie vuote (quelle senza prodotti)
$checkStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE subcategory_id = ?");
$subcategoryTree = [];
foreach ($rawTree as $catId => $list) {
    foreach ($list as $sub) {
        $checkStmt->execute([ $sub['id'] ]);
        if ($checkStmt->fetchColumn() > 0) {
            $subcategoryTree[$catId][] = $sub;
        }
    }
}

// 5) Filtra le categorie senza sottocategorie rimaste
$filteredCategories = array_filter($categories, function($cat) use ($subcategoryTree) {
    return !empty($subcategoryTree[$cat['id']]);
});

// 6) Determina sottocategoria e categoria attive
$activeSub = isset($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;
$activeCat = null;
if ($activeSub) {
    foreach ($subcategoryTree as $catId => $list) {
        if (in_array($activeSub, array_column($list,'id'))) {
            $activeCat = $catId;
            break;
        }
    }
}
?>

<!-- Stili sidebar con gradient animato blu → nero -->
<style>
  .sidebar-clean {
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    width: 14%;
    /* Gradient animato da blu scuro (#001f3f) a nero */
    background: linear-gradient(45deg, #001f3f, #000000);
    background-size: 200% 200%;
    animation: gradientBG 15s ease infinite;
    color: #ecf0f1;
    padding-top: 4rem; /* spazio per header */
    overflow-y: auto;
  }
  @keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }
  .sidebar-clean ul { margin: 0; padding: 0; list-style: none; }
  .sidebar-clean .nav-item { width: 100%; }
  .sidebar-clean .nav-link {
    display: flex;
    align-items: center;
    padding: .75rem 1.25rem;
    color: #ecf0f1;
    text-decoration: none;
    transition: background .2s, color .2s;
  }
  .sidebar-clean .nav-link:hover,
  .sidebar-clean .nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    color: #67d0ff;
  }
  .sidebar-clean .category-link {
    font-size: 0.95rem;
    font-weight: 550;
    text-transform: uppercase;
  }
  .sidebar-clean .subcategory-list .subcategory-link {
    padding: .65rem 1.5rem;
    font-size: .9rem;
    font-weight: 400;
    color: #d1dbeb;
  }
  .sidebar-clean .subcategory-list .subcategory-link:hover,
  .sidebar-clean .subcategory-list .subcategory-link.active {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
  }
  .sidebar-clean i {
    margin-right: .75rem;
    width: 1rem;
    text-align: center;
  }
  /* Scrollbar custom */
  .sidebar-clean {
    scrollbar-width: thin;
    scrollbar-color: #001f3f #000000;
  }
  .sidebar-clean::-webkit-scrollbar { width: 6px; }
  .sidebar-clean::-webkit-scrollbar-track { background: #000000; }
  .sidebar-clean::-webkit-scrollbar-thumb {
    background-color: #001f3f;
    border-radius: 3px;
  }
</style>

<!-- Sidebar desktop -->
<nav id="sidebarMenu" class="sidebar-clean d-none d-lg-block">
  <ul>
    <?php foreach ($filteredCategories as $cat):
      $catId       = (int)$cat['id'];
      $hasSubs     = !empty($subcategoryTree[$catId]);
      $isOpen      = ($activeCat === $catId);
      $openClass   = $isOpen ? 'show' : '';
      $activeClass = $isOpen ? 'active' : '';
    ?>
      <li class="nav-item">
        <?php if ($hasSubs): ?>
          <a 
            class="nav-link category-link <?= $activeClass ?>"
            data-bs-toggle="collapse"
            href="#cat-<?= $catId ?>"
            aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
          >
            <i class="fas fa-folder me-2"></i>
            <?= htmlspecialchars($cat['name']) ?>
          </a>
          <div class="collapse <?= $openClass ?>" id="cat-<?= $catId ?>">
            <ul class="subcategory-list">
              <?php foreach ($subcategoryTree[$catId] as $sub):
                $subActive = ($sub['id'] === $activeSub) ? 'active' : '';
              ?>
                <li class="nav-item">
                  <a 
                    class="nav-link subcategory-link <?= $subActive ?>"
                    href="../pages/dashboard.php?subcategory_id=<?= htmlspecialchars($sub['id']) ?>"
                  >
                    <i class="fas fa-folder-open me-2"></i>
                    <?= htmlspecialchars($sub['name']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; // le categorie senza sottocategorie non appaiono ?>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>

<!-- Sidebar offcanvas (mobile) -->
<nav id="offcanvasSidebar" class="offcanvas offcanvas-start sidebar-clean" tabindex="-1">
  <div class="offcanvas-header">
    <h5 class="text-white">Menu</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul>
      <?php foreach ($filteredCategories as $cat):
        $catId       = (int)$cat['id'];
        $hasSubs     = !empty($subcategoryTree[$catId]);
        $isOpen      = ($activeCat === $catId);
        $openClass   = $isOpen ? 'show' : '';
        $activeClass = $isOpen ? 'active' : '';
      ?>
        <li class="nav-item">
          <?php if ($hasSubs): ?>
            <a 
              class="nav-link category-link <?= $activeClass ?>"
              data-bs-toggle="collapse"
              href="#mobile-cat-<?= $catId ?>"
            >
              <i class="fas fa-folder me-2"></i>
              <?= htmlspecialchars($cat['name']) ?>
            </a>
            <div class="collapse <?= $openClass ?>" id="mobile-cat-<?= $catId ?>">
              <ul class="subcategory-list">
                <?php foreach ($subcategoryTree[$catId] as $sub):
                  $subActive = ($sub['id'] === $activeSub) ? 'active' : '';
                ?>
                  <li class="nav-item">
                    <a 
                      class="nav-link subcategory-link <?= $subActive ?>"
                      href="../pages/dashboard.php?subcategory_id=<?= htmlspecialchars($sub['id']) ?>"
                    >
                      <i class="fas fa-folder-open me-2"></i>
                      <?= htmlspecialchars($sub['name']) ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</nav>

<!-- Chiudi offcanvas al click (mobile) -->
<script>
  document.querySelectorAll('#offcanvasSidebar .nav-link').forEach(el => {
    el.addEventListener('click', () => {
      const oc = bootstrap.Offcanvas.getInstance(
        document.getElementById('offcanvasSidebar')
      );
      if (oc) oc.hide();
    });
  });
</script>
