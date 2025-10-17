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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
    <style>
      .stat { 
        background: var(--card); 
        border-radius: 16px; 
        padding: 1.5rem; 
        border: 1px solid var(--border);
        transition: all 0.2s ease;
      }
      .stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px -5px var(--shadow);
      }
      .admin-sidebar {
        background: var(--card);
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px 0 var(--shadow);
      }
      .admin-content {
        background: var(--card);
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: 0 1px 3px 0 var(--shadow);
        min-height: 500px;
      }
    </style>
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container-fluid my-3">
      <!-- Sticky Toggle Button -->
      <button class="btn btn-sm btn-outline-secondary position-fixed" id="sidebarToggler">
        <i class="bi bi-layout-sidebar me-1"></i> Toggle
      </button>
      <div class="row">
        <aside class="col-12 col-lg-2 mb-3" id="adminSidebar">
          <div class="admin-sidebar p-3">
            <!-- REMOVE the toggle button from here -->
            <div class="list-group list-group-flush">
              <button class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 py-2" data-fetch="/lazio_site/admin/partials/stats.html">
                <span><i class="bi bi-graph-up me-2"></i>Overview</span>
              </button>
              <button class="list-group-item list-group-item-action border-0 py-2" data-fetch="/lazio_site/admin/partials/clients.html">
                <i class="bi bi-people me-2"></i>Clients
              </button>
              <button class="list-group-item list-group-item-action border-0 py-2" data-fetch="/lazio_site/admin/partials/items.html">
                <i class="bi bi-box-seam me-2"></i>Items
              </button>
              <button class="list-group-item list-group-item-action border-0 py-2" data-fetch="/lazio_site/admin/partials/quotation.html">
                <i class="bi bi-file-text me-2"></i>Design Quotation
              </button>
              <button class="list-group-item list-group-item-action border-0 py-2" data-fetch="/lazio_site/admin/partials/invoices.html">
                <i class="bi bi-receipt me-2"></i>View Invoices
              </button>
              <button class="list-group-item list-group-item-action border-0 py-2" data-fetch="/lazio_site/admin/partials/uploads.html">
                <i class="bi bi-cloud-upload me-2"></i>Uploads
              </button>
            </div>
          </div>
        </aside>
        <section class="col-12 col-lg-10">
          <div id="adminContent" class="admin-content p-4">
            <div class="text-center">
              <i class="bi bi-speedometer2 icon-lg mb-3"></i>
              <h4 class="fw-bold mb-2">Welcome, Admin</h4>
              <p class="text-muted">Choose a section from the sidebar to get started managing your construction business.</p>
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
