
/* includes loader + header/footer logic */
document.addEventListener('DOMContentLoaded', async () => {
  const headerHolder = document.getElementById('header-include');
  const footerHolder = document.getElementById('footer-include');

  // Theme toggle setup function (defined early)
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('theme') || 'light';
  
  // Apply saved theme immediately
  if (savedTheme === 'dark') {
    root.classList.add('dark');
  }
  
  // Update theme toggle icon
  const updateThemeIcon = () => {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      const icon = themeToggle.querySelector('i');
      if (icon) {
        icon.className = root.classList.contains('dark') ? 'bi bi-moon' : 'bi bi-brightness-high';
      }
    }
  };
  
  // Setup theme toggle
  const setupThemeToggle = () => {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle && !themeToggle.hasAttribute('data-theme-initialized')) {
      themeToggle.setAttribute('data-theme-initialized', 'true');
      
      themeToggle.addEventListener('click', () => {
        root.classList.toggle('dark');
        const isDark = root.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeIcon();
      });
      
      // Initialize icon
      updateThemeIcon();
      console.log('Theme toggle initialized successfully');
      return true;
    }
    return false;
  };

  // Inject header and footer
  const fetchHtml = async (url) => (await fetch(url, {cache: 'no-cache'})).text();
  if (headerHolder) {
    headerHolder.innerHTML = await fetchHtml('/lazio_site/includes/header.html');
    // Ensure theme toggle is set up after header is loaded
    setTimeout(() => {
      setupThemeToggle();
    }, 50);
  }
  if (footerHolder) footerHolder.innerHTML = await fetchHtml('/lazio_site/includes/footer.html');

  // Bootstrap JS requires re-initialization when dynamically injecting components
  if (window.bootstrap && bootstrap.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  // Try to setup theme toggle immediately (in case it already exists)
  setupThemeToggle();
  
  // Watch for theme toggle to be added to the DOM
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) { // Element node
            if (node.id === 'themeToggle' || node.querySelector && node.querySelector('#themeToggle')) {
              setupThemeToggle();
            }
          }
        });
      }
    });
  });
  
  // Start observing
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });

  // Nav visibility based on session state (simple check via endpoint)
  try {
    const r = await fetch('/lazio_site/auth/auth_check.php', {credentials: 'include'});
    const data = await r.json();
    const isAuth = !!data.auth;
    const isAdmin = data.role === 'admin';
    const isIntern = data.role === 'intern';

    const show = (id, show) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.toggle('d-none', !show);
    };
    show('nav-login', !isAuth);
    show('nav-signup', !isAuth);
    show('nav-logout', isAuth);
    show('nav-admin', isAdmin);
    show('nav-intern', isIntern);
  } catch (e) {}

  // Year
  const y = document.getElementById('yearNow');
  if (y) y.textContent = String(new Date().getFullYear());
  
  // Final fallback to ensure theme toggle is set up
  setTimeout(() => {
    setupThemeToggle();
  }, 200);
});
