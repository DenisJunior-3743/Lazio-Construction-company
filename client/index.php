<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'client') {
  header('Location: /lazio_site/auth/login.php'); exit;
}
$secondName = $_SESSION['user']['second_name'] ?? '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Portal - Lazio</title>
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
          <span class="section-eyebrow"><i class="bi bi-receipt"></i>Client Portal</span>
          <h1 class="h3 fw-bold mb-1">Your Invoices</h1>
          <p class="text-muted mb-0">View and print invoices sent by Lazio Construction Company</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-primary btn-sm" onclick="refreshInvoices()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
          </button>
        </div>
      </div>

      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th class="border-0">Invoice #</th>
                  <th class="border-0">Date</th>
                  <th class="border-0">Total</th>
                  <th class="border-0">Status</th>
                  <th class="border-0 text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="invoiceTableBody">
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-receipt icon-md mb-2 d-block"></i>
                    Loading invoices...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script>
      window.userSecondName = <?php echo json_encode($secondName); ?>;
    </script>
    <script src="/lazio_site/assets/js/client.js"></script>
  </body>
</html>
