async function api(path, payload={}) {
  const r = await fetch(path, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload),
    credentials: 'include'
  });
  return r.json();
}

document.addEventListener('DOMContentLoaded', () => {
  // Sidebar toggle
  const sidebarToggler = document.getElementById('sidebarToggler');
  const sidebar = document.getElementById('adminSidebar');
  if (sidebarToggler && sidebar) {
    sidebarToggler.addEventListener('click', () => {
      sidebar.classList.toggle('d-none');
    });
  }

  // Helper to load a partial and execute its scripts
  const loadPartial = async (url) => {
    const target = document.getElementById('adminContent');
    const html = await (await fetch(url, {cache: 'no-cache'})).text();
    target.innerHTML = html;
    // Execute inline scripts inside the loaded partial
    const scripts = Array.from(target.querySelectorAll('script'));
    for (const oldScript of scripts) {
      const s = document.createElement('script');
      if (oldScript.type) s.type = oldScript.type;
      if (oldScript.src) {
        s.src = oldScript.src;
        s.async = false;
      } else {
        s.textContent = oldScript.textContent;
      }
      try {
        oldScript.parentNode.replaceChild(s, oldScript);
      } catch (err) {
        console.error('Script injection error:', err);
      }
    }
  };

  // Simple router for fetching inner pages
  const navButtons = document.querySelectorAll('[data-fetch]');
  navButtons.forEach(btn => {
    btn.addEventListener('click', async () => {
      navButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const url = btn.getAttribute('data-fetch');
      await loadPartial(url);
    });
  });

  // Auto-load Overview on first paint
  const defaultBtn = document.querySelector('[data-fetch="/lazio_site/admin/partials/stats.html"]');
  if (defaultBtn) {
    loadPartial(defaultBtn.getAttribute('data-fetch'));
  }
});
