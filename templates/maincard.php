<div class="row g-4 maincard-container">
    <div class="col-12 col-lg-6">
        <div class="card card-total bg-primary text-white shadow-sm">
            <div class="card-body d-flex flex-column flex-lg-row align-items-center justify-content-between">
                <i class="fas fa-boxes fa-3x mb-3 mb-lg-0"></i>
                <div class="text-center text-lg-start">
                    <h5 class="card-title mb-0">Prodotti Totali</h5>
                    <p class="card-text fs-4 mt-2" id="totalProducts"><?= htmlspecialchars($totalProducts) ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card card-total bg-danger text-white shadow-sm">
            <div class="card-body d-flex flex-column flex-lg-row align-items-center justify-content-between">
                <i class="fas fa-times-circle fa-3x mb-3 mb-lg-0"></i>
                <div class="text-center text-lg-start">
                    <h5 class="card-title mb-0">Prodotti Non Disponibili</h5>
                    <p class="card-text fs-4 mt-2" id="unavailableProducts"><?= htmlspecialchars($unavailableProducts) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>