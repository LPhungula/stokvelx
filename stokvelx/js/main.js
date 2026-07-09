// StokvelX - Main JS

// Toast
function showToast(msg, icon = '✅') {
  let t = document.getElementById('toast');
  if (!t) { t = document.createElement('div'); t.id = 'toast'; t.className = 'toast'; document.body.appendChild(t); }
  t.innerHTML = `<span class="toast-icon">${icon}</span> ${msg}`;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// FAQ
document.querySelectorAll('.faq-q').forEach(q => {
  q.addEventListener('click', () => {
    const item = q.closest('.faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
  });
});

// Tabs
document.querySelectorAll('.ctab').forEach(tab => {
  tab.addEventListener('click', () => {
    const group = tab.closest('.ctabs');
    group.querySelectorAll('.ctab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
  });
});

// Copy referral code
document.querySelectorAll('.copy-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const code = btn.closest('[data-code]')?.dataset.code || 'SIPHO10';
    navigator.clipboard?.writeText(code).catch(() => {});
    showToast('Referral code copied!');
  });
});

// Modal
function openModal(id) {
  document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
  document.getElementById(id)?.classList.remove('open');
}
document.querySelectorAll('[data-modal]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.dataset.modal));
});
document.querySelectorAll('.modal-ov').forEach(ov => {
  ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
});
document.querySelectorAll('.modal-cls').forEach(btn => {
  btn.addEventListener('click', () => btn.closest('.modal-ov')?.classList.remove('open'));
});

// Chat
const chatSend = document.getElementById('chatSend');
const chatInput = document.getElementById('chatInput');
const chatMsgs = document.getElementById('chatMsgs');
if (chatSend && chatInput && chatMsgs) {
  function sendMsg() {
    const val = chatInput.value.trim();
    if (!val) return;
    const div = document.createElement('div');
    div.className = 'msg sent';
    div.innerHTML = `<div class="msg-bub">${val}</div><div class="msg-mt">Just now · You</div>`;
    chatMsgs.appendChild(div);
    chatInput.value = '';
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
    setTimeout(() => {
      const r = document.createElement('div');
      r.className = 'msg recv';
      r.innerHTML = `<div class="msg-bub">Thanks for the message! 🙌</div><div class="msg-mt">Just now · Admin</div>`;
      chatMsgs.appendChild(r);
      chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }, 800);
  }
  chatSend.addEventListener('click', sendMsg);
  chatInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMsg(); });
}

// Chat room switch
document.querySelectorAll('.cr-item').forEach(item => {
  item.addEventListener('click', () => {
    document.querySelectorAll('.cr-item').forEach(i => i.classList.remove('active'));
    item.classList.add('active');
  });
});

// Animated bars on load
function animateBars() {
  document.querySelectorAll('.bar').forEach(bar => {
    const h = bar.dataset.h || '60';
    bar.style.height = '0';
    setTimeout(() => { bar.style.transition = 'height 0.8s ease'; bar.style.height = h + '%'; }, 100);
  });
}
animateBars();

// Progress bars animate
document.querySelectorAll('.prog-fill').forEach(fill => {
  const w = fill.dataset.w || fill.style.width || '50%';
  fill.style.width = '0';
  setTimeout(() => { fill.style.transition = 'width 1s ease'; fill.style.width = w; }, 200);
});

// Active nav link
const page = location.pathname.split('/').pop();
document.querySelectorAll('.sb-link').forEach(link => {
  if (link.getAttribute('href') === page) link.classList.add('active');
});

// Logout
document.querySelectorAll('.sb-logout').forEach(btn => {
  btn.addEventListener('click', () => {
    if (confirm('Logout?')) location.href = '../pages/login.html';
  });
});

// Pay Contribution button
document.querySelectorAll('[data-pay]').forEach(btn => {
  btn.addEventListener('click', () => openModal('payModal'));
});

// Join circle buttons
document.querySelectorAll('.join-now-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    showToast('Joining circle... redirecting to payment.', '🎉');
    setTimeout(() => openModal('payModal'), 1000);
  });
});

// Withdraw
document.querySelectorAll('[data-withdraw]').forEach(btn => {
  btn.addEventListener('click', () => openModal('withdrawModal'));
});

// Mobile menu toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');
if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => {
    sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? '' : 'translateX(0)';
  });
}

// Smooth scroll for landing page anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});
