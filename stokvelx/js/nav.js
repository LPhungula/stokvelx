// Shared sidebar HTML injected into all app pages
// nav.js injects the correct links based on the logged-in user's role
function renderSidebar(activePage, userRole) {
  const links = [
    { href:'dashboard.html',    ico:'🏠', label:'Dashboard' },
    { href:'circles.html',      ico:'🔵', label:'My Circles' },
    { href:'join.html',         ico:'➕', label:'Join a Circle' },
    { href:'wallet.html',       ico:'💰', label:'Wallet' },
    { href:'transactions.html', ico:'📋', label:'Transactions' },
    { href:'referrals.html',    ico:'👥', label:'Referrals' },
    { href:'chat.html',         ico:'💬', label:'Circle Chat' },
    { href:'profile.html',      ico:'👤', label:'Profile' },
  ];

  const navHtml = links.map(l => `
    <a href="${l.href}" class="sb-link${activePage===l.href?' active':''}">
      <span class="ico">${l.ico}</span>${l.label}
    </a>`).join('');

  // Admin Panel link only shown to admins
  const adminHtml = userRole === 'admin' ? `
    <div class="sb-label">Admin</div>
    <a href="admin.html" class="sb-link${activePage==='admin.html'?' active':''}">
      <span class="ico">🛡️</span>Admin Panel
    </a>` : '';

  return `
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="logo-icon">X</div>StokvelX
    </div>
    <nav class="sb-nav">
      <div class="sb-label">Main</div>
      ${navHtml}
      ${adminHtml}
      <div class="sb-label">More</div>
      <a href="settings.html" class="sb-link${activePage==='settings.html'?' active':''}"><span class="ico">⚙️</span>Settings</a>
      <a href="help.html" class="sb-link${activePage==='help.html'?' active':''}"><span class="ico">❓</span>Help &amp; Support</a>
    </nav>
    <div class="sb-foot">
      <div class="sb-user">
        <div class="u-av">SD</div>
        <div><div class="u-name nm">Sipho Dlamini</div><div class="u-role rl">Student</div></div>
      </div>
      <div class="sb-logout" onclick="doLogout()">🚪 Logout</div>
    </div>
  </aside>`;
}

// Mobile nav HTML
function renderMobileNav(activePage) {
  const items = [
    { href:'dashboard.html', ico:'🏠', label:'Dashboard' },
    { href:'circles.html',   ico:'🔵', label:'Circles' },
    { href:'wallet.html',    ico:'💰', label:'Wallet' },
    { href:'referrals.html', ico:'👥', label:'Referrals' },
    { href:'help.html',      ico:'❓', label:'Help' },
  ];
  return `<nav class="mob-nav">${items.map(i=>`
    <a href="${i.href}" class="mn-item${activePage===i.href?' active':''}">
      <span class="mn-ico">${i.ico}</span>${i.label}
    </a>`).join('')}</nav>`;
}

// Inject on load – passes userRole so Admin Panel link shows/hides correctly
document.addEventListener('DOMContentLoaded', async () => {
  const page = location.pathname.split('/').pop();
  const sbEl = document.getElementById('sidebar-mount');
  const mnEl = document.getElementById('mobnav-mount');

  // Fetch current user role to decide which links to show
  let userRole = 'student';
  try {
    const res = await fetch(window.API_BASE + '/auth/me.php', { credentials: 'include' });
    const data = await res.json();
    if (data.success) {
      userRole = data.user.role;
      // Update user name / role in sidebar after nav renders
      document.querySelectorAll('.nm').forEach(el => el.textContent = data.user.first_name + ' ' + data.user.last_name);
      document.querySelectorAll('.rl').forEach(el => el.textContent = data.user.role === 'admin' ? 'Admin' : 'Student');
      document.querySelectorAll('.u-av').forEach(el => el.textContent = data.user.first_name[0] + data.user.last_name[0]);
    }
  } catch(e) {}

  if (sbEl) sbEl.innerHTML = renderSidebar(page, userRole);
  if (mnEl) mnEl.innerHTML = renderMobileNav(page);

  // Re-apply name/avatar after sidebar HTML is injected
  try {
    const res = await fetch(window.API_BASE + '/auth/me.php', { credentials: 'include' });
    const data = await res.json();
    if (data.success) {
      document.querySelectorAll('.nm').forEach(el => el.textContent = data.user.first_name + ' ' + data.user.last_name);
      document.querySelectorAll('.rl').forEach(el => el.textContent = data.user.role === 'admin' ? 'Admin' : 'Student');
      document.querySelectorAll('.u-av').forEach(el => el.textContent = data.user.first_name[0] + data.user.last_name[0]);
    }
  } catch(e) {}
});

async function doLogout() {
  if (!confirm('Logout of StokvelX?')) return;
  try {
    await fetch(window.API_BASE + '/auth/logout.php', { method: 'POST', credentials: 'include' });
  } catch(e) {}
  const depth = window.location.pathname.includes('/pages/') ? '' : 'pages/';
  window.location.href = depth + 'login.html';
}
