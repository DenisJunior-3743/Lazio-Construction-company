<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lazio Construction Company</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/root.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/header.css" rel="stylesheet">
    <link href="/lazio_site/assets/css/footer.css" rel="stylesheet">
  </head>
  <body>
    <div id="header-include"></div>

    <main class="container my-4">
      <section class="hero p-4 p-md-5 text-center fade-in">
        <span class="section-eyebrow"><i class="bi bi-patch-check-fill"></i>Trusted since day one</span>
        <div class="d-flex align-items-center justify-content-center gap-3 mb-3 flex-wrap">
          <img src="/lazio_site/assets/images/logo.png" width="80" height="80" class="rounded-circle shadow-sm" alt="Logo">
          <h1 class="display-4 fw-bold m-0 text-gradient">We design, we build, we empower.</h1>
        </div>
        <p class="mt-3 lead fs-4 text-muted">Reliable construction • Expert training • Community impact</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="/lazio_site/pages/services.php" class="btn btn-primary btn-lg px-4 py-3">
            <i class="bi bi-tools me-2"></i>Explore Services
          </a>
          <a href="/lazio_site/auth/signup.php" class="btn btn-outline-primary btn-lg px-4 py-3">
            <i class="bi bi-person-plus me-2"></i>Become a Client
          </a>
        </div>
      </section>

      <div class="stripe-divider my-4" style="max-width:260px;"></div>

      <section class="mt-5">
        <div class="row g-4">
          <div class="col-12 col-lg-4">
            <div class="card p-4 h-100 card-hover">
              <div class="text-center mb-3">
                <span class="stat-icon"><i class="bi bi-bullseye"></i></span>
              </div>
              <h5 class="text-center mb-3">Our Goal</h5>
              <p class="text-center text-muted">Deliver innovative, sustainable, high-quality construction solutions that exceed expectations.</p>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card p-4 h-100 card-hover">
              <div class="text-center mb-3">
                <span class="stat-icon"><i class="bi bi-binoculars"></i></span>
              </div>
              <h5 class="text-center mb-3">Our Vision</h5>
              <p class="text-center text-muted">Be the region's leading construction company renowned for excellence and innovation.</p>
            </div>
          </div>
          <div class="col-12 col-lg-4">
            <div class="card p-4 h-100 card-hover">
              <div class="text-center mb-3">
                <span class="stat-icon"><i class="bi bi-cone-striped"></i></span>
              </div>
              <h5 class="text-center mb-3">Our Mission</h5>
              <p class="text-center text-muted">Provide reliable services and practical training built on integrity and innovation.</p>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <span class="section-eyebrow"><i class="bi bi-people-fill"></i>The team</span>
            <h3 class="m-0 fw-bold">Meet Our Leaders</h3>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
            <i class="bi bi-info-circle me-1"></i>About the team
          </a>
        </div>
        <div id="leaders-carousel" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner" id="leaders-carousel-inner"></div>
          <button class="carousel-control-prev" type="button" data-bs-target="#leaders-carousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#leaders-carousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
      </section>

      <section class="mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
          <div>
            <span class="section-eyebrow"><i class="bi bi-images"></i>Recent work</span>
            <h3 class="m-0 fw-bold">Our Activities</h3>
          </div>
          <a class="btn btn-outline-primary btn-sm" href="/lazio_site/pages/services.php">
            <i class="bi bi-arrow-right me-1"></i>See all
          </a>
        </div>
        <div class="mb-4 text-center btn-group" role="group" aria-label="Filter activities">
          <button class="btn btn-outline-primary btn-sm" onclick="setFilter('all')">All</button>
          <button class="btn btn-outline-primary btn-sm" onclick="setFilter('image')"><i class="bi bi-image me-1"></i>Images</button>
          <button class="btn btn-outline-primary btn-sm" onclick="setFilter('video')"><i class="bi bi-camera-video me-1"></i>Videos</button>
        </div>
        <div class="row g-4" id="activities-grid"></div>
      </section>
    </main>

    <div id="footer-include"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/lazio_site/assets/js/include.js"></script>
    <script src="/lazio_site/assets/js/main.js"></script>
  </body>
</html>
