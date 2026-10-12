<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Services & Deliveries - Lazio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container my-4">
      <div class="admin-shell-header mb-4">
        <div>
          <span class="section-eyebrow"><i class="bi bi-tools"></i>What we offer</span>
          <h1 class="h4 m-0 fw-bold">Services & Deliveries</h1>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="small text-muted d-none d-sm-inline">Filter by category</span>
          <select id="categoryFilter" class="form-select form-select-sm w-auto">
            <option value="All">All</option>
            <option value="Construction">Construction</option>
            <option value="Plan Drawing">Plan Drawing</option>
            <option value="Training">Training</option>
          </select>
        </div>
      </div>
      <div class="stripe-divider mb-4" style="max-width:220px;"></div>
      <div class="row g-3" id="servicesGrid"></div>
    </main>
    <div id="footer-include"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script>
    const grid = document.getElementById('servicesGrid');
    const select = document.getElementById('categoryFilter');
    const categoryIcons = { 'Construction': 'bi-building', 'Plan Drawing': 'bi-vector-pen', 'Training': 'bi-mortarboard' };

    // Always load "All" on page load
    async function load(category = 'All') {
      const r = await fetch('/lazio_site/admin/api/uploads.php?action=list_public');
      const data = await r.json();
      const items = data.items;

      // Group items by category
      const grouped = {};
      items.forEach(item => {
        if (category !== 'All' && item.category !== category) return;
        const cat = item.category || 'Other';
        if (!grouped[cat]) grouped[cat] = [];
        grouped[cat].push(item);
      });

      // Render grouped sections, separating images and videos
      grid.innerHTML = Object.entries(grouped).map(([cat, catItems]) => {
        const images = catItems.filter(i => i.type === 'image');
        const videos = catItems.filter(i => i.type === 'video');
        return `
          <div class="col-12">
            <h4 class="mt-4 mb-3 text-gradient"><i class="bi ${categoryIcons[cat] || 'bi-folder2'} me-2"></i>${cat}</h4>
            ${images.length ? `
              <h5 class="mb-2 fw-bold text-primary">Images</h5>
              <div class="row g-3 mb-3">
                ${images.map(item => `
                  <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card h-100 card-hover">
                      <img src="${item.path}" class="activity-img" alt="">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="fw-semibold">${item.category || 'Activity'}</span>
                          <span class="small small-muted">${new Date(item.created_at).toLocaleDateString()}</span>
                        </div>
                        <p class="small mb-0">${item.description || ''}</p>
                      </div>
                    </div>
                  </div>
                `).join('')}
              </div>
            ` : ''}
            ${videos.length ? `
              <h5 class="mb-2 fw-bold text-danger">Videos</h5>
              <div class="row g-3 mb-3">
                ${videos.map(item => `
                  <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card h-100 card-hover">
                      <video class="w-100 activity-video" controls src="${item.path}"></video>
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="fw-semibold">${item.category || 'Activity'}</span>
                          <span class="small small-muted">${new Date(item.created_at).toLocaleDateString()}</span>
                        </div>
                        <p class="small mb-0">${item.description || ''}</p>
                      </div>
                    </div>
                  </div>
                `).join('')}
              </div>
            ` : ''}
          </div>
        `;
      }).join('') || '<p class="text-muted small">Nothing here yet.</p>';
    }

    // Always set filter to "All" on page load
    select.value = "All";
    load("All");

    select.addEventListener('change', () => {
      load(select.value);
    });
    </script>
  </body>
</html>
