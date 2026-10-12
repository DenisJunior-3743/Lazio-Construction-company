<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  header('Location: /lazio_site/auth/login.php'); exit;
}
$secondName = $_SESSION['user']['second_name'] ?? '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Lazio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container-fluid my-3 px-3 px-lg-4">
      <div class="admin-shell-header mb-3">
        <div>
          <span class="section-eyebrow"><i class="bi bi-speedometer2"></i>Admin Dashboard</span>
          <h4 class="fw-bold mb-0">Manage your construction business</h4>
        </div>
        <button class="btn btn-outline-secondary btn-sm" id="sidebarToggler">
          <i class="bi bi-layout-sidebar me-1"></i>Toggle Menu
        </button>
      </div>
      <div class="row g-3">
        <aside class="col-12 col-lg-2" id="adminSidebar">
          <div class="admin-sidebar p-2">
            <div class="list-group list-group-flush">
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2 active" data-fetch="/lazio_site/admin/partials/stats.html">
                <i class="bi bi-graph-up me-2"></i>Overview
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/clients.html">
                <i class="bi bi-people me-2"></i>Clients
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/items.html">
                <i class="bi bi-box-seam me-2"></i>Items
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/quotation.html">
                <i class="bi bi-file-text me-2"></i>Design Quotation
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/agreements.html">
                <i class="bi bi-file-earmark-text me-2"></i>Design Agreement
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/invoices.html">
                <i class="bi bi-receipt me-2"></i>View Invoices
              </button>
              <button class="list-group-item list-group-item-action d-flex align-items-center py-2" data-fetch="/lazio_site/admin/partials/uploads.html">
                <i class="bi bi-cloud-upload me-2"></i>Uploads
              </button>
            </div>
          </div>
        </aside>
        <section class="col-12 col-lg-10">
          <div id="adminContent" class="admin-content p-4">
            <div class="empty-state">
              <i class="bi bi-speedometer2"></i>
              <h4 class="fw-bold mb-2" style="color:var(--fg);">Welcome, Admin</h4>
              <p class="text-muted mb-0">Choose a section from the sidebar to get started managing your construction business.</p>
            </div>
          </div>
        </section>
      </div>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script>
      window.userSecondName = <?php echo json_encode($secondName); ?>;
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        function getGreeting() {
          const hour = new Date().getHours();
          if (hour < 12) return "Good morning";
          if (hour < 17) return "Good afternoon";
          return "Good evening";
        }
        if (window.userSecondName) {
          const greetMsg = `${getGreeting()}, ${window.userSecondName}! Welcome to Lazio Construction.`;
          // Display in a greeting div or alert
          const greetingDiv = document.createElement('div');
          greetingDiv.className = 'alert alert-success my-3';
          greetingDiv.textContent = greetMsg;
          document.body.prepend(greetingDiv);
        }
      });
    </script>
    <script src="/lazio_site/assets/js/admin.js"></script>
  </body>
</html>
