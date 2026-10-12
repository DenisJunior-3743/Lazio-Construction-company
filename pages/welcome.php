
<?php
session_start();
$name = $_SESSION['user']['username'] ?? 'Guest';
$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container my-5">
      <div class="auth-shell" style="min-height:auto;padding:2rem 1rem;">
        <div class="auth-card text-center fade-in">
          <div class="auth-icon"><i class="bi bi-emoji-smile"></i></div>
          <h1 class="h3 fw-bold mb-1"><?php echo $greet; ?>, <?php echo htmlspecialchars($name); ?> <span class="text-gradient">👋</span></h1>
          <p class="text-muted mb-4">Choose where you'd like to go next.</p>
          <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a class="btn btn-primary" href="/lazio_site/client/index.php"><i class="bi bi-receipt me-2"></i>Client Portal</a>
            <a class="btn btn-outline-primary" href="/lazio_site/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a>
          </div>
        </div>
      </div>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
  </body>
</html>
