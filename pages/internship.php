<?php
session_start();
require_once __DIR__ . '/../includes/intern_helpers.php'; // no database needed here
$role = $_SESSION['user']['role'] ?? null;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Internships & Industrial Training - Lazio</title>
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
      <section class="hero p-4 p-md-5 text-center fade-in">
        <span class="section-eyebrow"><i class="bi bi-mortarboard"></i>Industrial training</span>
        <h1 class="display-5 fw-bold text-gradient">Learn the trade on real sites</h1>
        <p class="lead text-muted mt-3 mb-4">Lazio Construction Company takes interns from vocational institutes, polytechnics and universities for hands-on training alongside our experienced teams.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <?php if ($role === 'intern'): ?>
            <a href="/lazio_site/intern/index.php" class="btn btn-primary btn-lg px-4"><i class="bi bi-person-badge me-2"></i>Open my intern portal</a>
          <?php else: ?>
            <a href="/lazio_site/auth/signup.php?role=intern" class="btn btn-primary btn-lg px-4"><i class="bi bi-person-plus me-2"></i>Register as an intern</a>
            <a href="/lazio_site/auth/login.php" class="btn btn-outline-primary btn-lg px-4"><i class="bi bi-box-arrow-in-right me-2"></i>Intern login</a>
          <?php endif; ?>
        </div>
      </section>

      <div class="stripe-divider my-4" style="max-width:260px;"></div>

      <section class="mt-4">
        <span class="section-eyebrow"><i class="bi bi-1-circle"></i>Step 1</span>
        <h3 class="fw-bold mb-1">View all the industrial trainings before signing in</h3>
        <p class="text-muted">Interns available:</p>
        <div class="row g-3" id="trainingGrid">
          <?php /* Shown straight away (and kept if the database list cannot be loaded). */ ?>
          <?php foreach (internship_default_services() as $name): ?>
            <div class="col-6 col-md-4 col-lg-3"><div class="card card-hover h-100 p-3 text-center">
              <span class="stat-icon mx-auto mb-2"><i class="bi bi-hammer"></i></span>
              <div class="fw-semibold"><?php echo htmlspecialchars($name); ?></div>
            </div></div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="mt-5">
        <span class="section-eyebrow"><i class="bi bi-signpost-split"></i>How it works</span>
        <h3 class="fw-bold mb-3">From registration to your acceptance letter</h3>
        <div class="row g-3">
          <div class="col-12 col-md-6 col-lg-3"><div class="card p-3 h-100"><span class="stat-icon mb-2"><i class="bi bi-person-plus"></i></span><h6 class="fw-bold">1. Register</h6><p class="small text-muted mb-0">Create your account with your institution registration number as your username, and a password of your choice. The office can also register you.</p></div></div>
          <div class="col-12 col-md-6 col-lg-3"><div class="card p-3 h-100"><span class="stat-icon mb-2"><i class="bi bi-send"></i></span><h6 class="fw-bold">2. Apply</h6><p class="small text-muted mb-0">Choose your training areas and period, and upload the application letter from your institution and your other documents.</p></div></div>
          <div class="col-12 col-md-6 col-lg-3"><div class="card p-3 h-100"><span class="stat-icon mb-2"><i class="bi bi-cash-coin"></i></span><h6 class="fw-bold">3. Pay the organisation fee</h6><p class="small text-muted mb-0">Interns pay UGX <span id="feeText">20,000</span> before approval, which helps us organise your internship better.</p></div></div>
          <div class="col-12 col-md-6 col-lg-3"><div class="card p-3 h-100"><span class="stat-icon mb-2"><i class="bi bi-envelope-paper"></i></span><h6 class="fw-bold">4. Get your letter</h6><p class="small text-muted mb-0">Once approved, your acceptance letter appears in your portal &mdash; print it as many times as you need.</p></div></div>
        </div>
      </section>

      <section class="mt-5">
        <div class="card p-4">
          <h5 class="fw-bold"><i class="bi bi-info-circle me-2 text-gradient"></i>Notes</h5>
          <ul class="mb-0">
            <li>Sign-in is free for clients only.</li>
            <li>Interns pay <strong>UGX <span class="fee-inline">20,000</span></strong> before approval, which helps in a better organisation of your internship.</li>
            <li>Pay through the company <strong>bank account</strong> or a <strong>verified mobile money number</strong> &mdash; ask the office for the details, then add your payment reference to your application.</li>
            <li>The internship fee will be discussed in person, according to the Directors/Instructors.</li>
            <li>Bring your original academic documents on your reporting day.</li>
          </ul>
        </div>
      </section>
    </main>
    <div id="footer-include"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script>
      (async function () {
        var grid = document.getElementById('trainingGrid');
        var icons = [
          [/brick/i, 'bi-bricks'], [/drawing/i, 'bi-vector-pen'], [/plan|setting/i, 'bi-rulers'], [/plaster|screed/i, 'bi-layers'],
          [/tiling|tile/i, 'bi-grid-3x3'], [/paint/i, 'bi-palette'], [/plumb/i, 'bi-droplet'], [/electric|wiring/i, 'bi-lightning-charge'],
          [/generator/i, 'bi-plug'], [/solar/i, 'bi-brightness-high'], [/motor/i, 'bi-gear'], [/roof|form/i, 'bi-house'], [/steel/i, 'bi-tools']
        ];
        function iconFor(name) {
          for (var i = 0; i < icons.length; i++) if (icons[i][0].test(name)) return icons[i][1];
          return 'bi-hammer';
        }
        function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }
        try {
          var res = await fetch('/lazio_site/admin/api/internship_services.php?action=list_public');
          var data = await res.json();
          var items = data.items || [];
          if (data.fee) {
            var fee = Number(data.fee).toLocaleString();
            document.getElementById('feeText').textContent = fee;
            document.querySelectorAll('.fee-inline').forEach(function (el) { el.textContent = fee; });
          }
          grid.innerHTML = items.length ? items.map(function (s) {
            return '<div class="col-6 col-md-4 col-lg-3"><div class="card card-hover h-100 p-3 text-center">' +
              '<span class="stat-icon mx-auto mb-2"><i class="bi ' + iconFor(s.name) + '"></i></span>' +
              '<div class="fw-semibold">' + esc(s.name) + '</div></div></div>';
          }).join('') : '<div class="col-12 text-muted small">Training areas will be listed here soon.</div>';
        } catch (e) {
          // Keep the list the page was rendered with (the poster's trainings).
        }
      })();
    </script>
  </body>
</html>
