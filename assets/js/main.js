// --- Make these GLOBAL ---
let activityFilter = 'all';
function setFilter(type) {
  activityFilter = type;
  renderActivities();
}

let activitiesData = [];
async function fetchActivities() {
  console.log('Fetching activities...');
  const response = await fetch('/lazio_site/admin/api/uploads.php?action=list_public');
  const data = await response.json();
  activitiesData = data.items;
  console.log('Fetched activities:', activitiesData);
  renderActivities();
}

function renderActivities() {
  const activities = document.getElementById('activities-grid');
  if (!activities) {
    console.warn('No activities-grid element found!');
    return;
  }
  // Group by category
  const grouped = {};
  let visibleItems = [];
  activitiesData.forEach(item => {
    if (activityFilter !== 'all' && item.type !== activityFilter) return;
    if (!item.show_home) return; // Only show marked for home
    visibleItems.push(item);
    const cat = item.category || 'Other';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(item);
  });

  activities.innerHTML = Object.entries(grouped).map(([cat, items]) => {
    const images = items.filter(i => i.type === 'image');
    const videos = items.filter(i => i.type === 'video');
    return `
      <div class="col-12 mb-4">
        <h5 class="mb-3 fw-bold text-gradient">${cat}</h5>
        ${images.length ? `
          <div class="mb-2 fw-bold text-primary">Images</div>
          <div class="row g-3 mb-3">
            ${images.map(item => `
              <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 card-hover">
                  <img src="${item.path}" class="activity-img mb-2" alt="">
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
          <div class="mb-2 fw-bold text-danger">Videos</div>
          <div class="row g-3 mb-3">
            ${videos.map(item => `
              <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 card-hover">
                  <video class="w-100 mb-2 activity-video" controls src="${item.path}"></video>
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
  }).join('');

  if (visibleItems.length === 0) {
    activities.innerHTML = '<p class="text-muted small">No activities to show. Check admin "Show on Home" setting.</p>';
  }
}

// --- Keep leaders section inside DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', async () => {
  // Leaders Carousel
  const leadersCarousel = document.getElementById('leaders-carousel-inner');
  if (leadersCarousel) {
    try {
      const leaders = await (await fetch('/lazio_site/assets/leaders.json')).json();
      leadersCarousel.innerHTML = leaders.map((l, i) => `
        <div class="carousel-item${i === 0 ? ' active' : ''}">
          <div class="leader-card text-center position-relative" style="cursor:pointer;">
            <img src="${l.image}" alt="${l.name}">
            <div class="mt-3 fw-semibold">${l.name}</div>
            <div class="small small-muted">${l.title || ''}</div>
            <div class="leader-desc">
              <div>${l.description || ''}</div>
              <div class="leader-socials mt-3">
                <a href="${l.linkedin || '#'}" target="_blank" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                <a href="${l.twitter || '#'}" target="_blank" title="Twitter"><i class="bi bi-twitter"></i></a>
                <a href="${l.facebook || '#'}" target="_blank" title="Facebook"><i class="bi bi-facebook"></i></a>
              </div>
            </div>
          </div>
        </div>
      `).join('');
    } catch (e) {
      leadersCarousel.innerHTML = '<div class="carousel-item active"><p class="text-muted small">Leaders info coming soon…</p></div>';
    }
  }

  fetchActivities();
});
