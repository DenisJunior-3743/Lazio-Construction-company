
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>
    <main class="container my-5">
      <div class="text-center">
        <h1 class="h3"><?php echo $greet; ?>, welcome <?php echo htmlspecialchars($name); ?>.</h1>
        <p class="text-muted">Choose where to go next.</p>
        <div class="d-flex gap-2 justify-content-center">
          <a class="btn btn-primary" href="/lazio_site/client/index.php">Client Portal</a>
          <a class="btn btn-outline-primary" href="/lazio_site/admin/dashboard.php">Admin Dashboard</a>
        </div>
      </div>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
  </body>
</html>
