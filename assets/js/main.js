/**
 * Campus2Career - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

  // ── Dark Mode Toggle ──
  const html        = document.documentElement;
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon   = document.getElementById('themeIcon');
  const toggleLabel = document.getElementById('toggleLabel');

  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    localStorage.setItem('c2c-theme', theme);
    if (themeIcon) {
      themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    if (toggleLabel) {
      toggleLabel.textContent = theme === 'dark' ? 'Light' : 'Dark';
    }
  }

  // Init from stored preference
  const savedTheme = localStorage.getItem('c2c-theme') || 'light';
  applyTheme(savedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = html.getAttribute('data-theme') || 'light';
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }


  // ── Mobile Nav Toggle ──
  const navToggle = document.getElementById('navToggle');
  const navLinks  = document.getElementById('navLinks');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      navToggle.classList.toggle('open');
    });
    // Close on outside click
    document.addEventListener('click', e => {
      if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
        navLinks.classList.remove('open');
        navToggle.classList.remove('open');
      }
    });
  }

  // ── User Dropdown ──
  const userMenuBtn  = document.getElementById('userMenuBtn');
  const userDropdown = document.getElementById('userDropdown');
  if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', e => {
      e.stopPropagation();
      userDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown.classList.remove('open'));
  }

  // ── Auto-dismiss flash message ──
  const flash = document.getElementById('flashMsg');
  if (flash) setTimeout(() => flash.remove(), 5000);

  // ── Role Tab Switching (Register Page) ──
  const roleTabs = document.querySelectorAll('.role-tab');
  roleTabs.forEach(tab => {
    tab.addEventListener('click', () => {
      roleTabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const role = tab.dataset.role;
      document.querySelectorAll('.role-section').forEach(s => s.style.display = 'none');
      const section = document.getElementById('section-' + role);
      if (section) section.style.display = 'block';
      const roleInput = document.getElementById('roleInput');
      if (roleInput) roleInput.value = role;
    });
  });

  // ── Form Validation ──
  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', function (e) {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        clearError(field);
        if (!field.value.trim()) {
          showError(field, 'This field is required.');
          valid = false;
        } else if (field.type === 'email' && !isValidEmail(field.value)) {
          showError(field, 'Enter a valid email address.');
          valid = false;
        } else if (field.dataset.minlength && field.value.length < parseInt(field.dataset.minlength)) {
          showError(field, `Minimum ${field.dataset.minlength} characters required.`);
          valid = false;
        }
      });
      // Password confirmation
      const pw  = form.querySelector('[name="password"]');
      const pw2 = form.querySelector('[name="confirm_password"]');
      if (pw && pw2 && pw.value !== pw2.value) {
        showError(pw2, 'Passwords do not match.');
        valid = false;
      }
      if (!valid) e.preventDefault();
    });
  });

  function showError(field, msg) {
    field.style.borderColor = 'var(--c-danger)';
    const err = document.createElement('span');
    err.className = 'form-error';
    err.textContent = msg;
    field.parentNode.appendChild(err);
  }
  function clearError(field) {
    field.style.borderColor = '';
    const prev = field.parentNode.querySelector('.form-error');
    if (prev) prev.remove();
  }
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  // ── Modal helpers ──
  window.openModal = function (id) {
    const m = document.getElementById(id);
    if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
  };
  window.closeModal = function (id) {
    const m = document.getElementById(id);
    if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
  };
  // Close modal on overlay click
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function (e) {
      if (e.target === this) closeModal(this.id);
    });
  });

  // ── Confirm Delete ──
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ── Search filter (client-side table search) ──
  const tableSearch = document.getElementById('tableSearch');
  if (tableSearch) {
    tableSearch.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.filterable-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ── Animate cards on scroll ──
  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.internship-card, .feature-card, .stat-card').forEach(card => {
      card.style.opacity    = '0';
      card.style.transform  = 'translateY(20px)';
      card.style.transition = 'opacity 0.5s ease, transform 0.5s ease, box-shadow 0.25s, border-color 0.25s';
      observer.observe(card);
    });
  }

  // ── Password toggle visibility ──
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
      const input = document.querySelector(this.dataset.target);
      if (!input) return;
      const isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      this.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
    });
  });

  // ── Character counter for textareas ──
  document.querySelectorAll('textarea[data-maxlength]').forEach(ta => {
    const max     = parseInt(ta.dataset.maxlength);
    const counter = document.createElement('div');
    counter.className   = 'form-hint text-right';
    counter.textContent = `0 / ${max}`;
    ta.parentNode.appendChild(counter);
    ta.addEventListener('input', () => {
      const len = ta.value.length;
      counter.textContent = `${len} / ${max}`;
      counter.style.color = len > max * 0.9 ? 'var(--c-danger)' : '';
      if (len > max) ta.value = ta.value.slice(0, max);
    });
  });

});

  // ── Notification Bell ──────────────────────────
  const notifBtn      = document.getElementById('notifBellBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifDot      = document.getElementById('notifDot');
  const notifList     = document.getElementById('notifList');

  function loadNotifications() {
    fetch('/campus2career/controllers/notifications_ajax.php?action=list')
      .then(r => r.json())
      .then(data => {
        if (data.error) return;
        if (data.unread > 0) {
          notifDot.style.display = 'flex';
          notifDot.textContent   = data.unread > 9 ? '9+' : data.unread;
        } else {
          notifDot.style.display = 'none';
        }
        if (!data.notifications || data.notifications.length === 0) {
          notifList.innerHTML = '<div class="notif-empty"><i class="fas fa-bell-slash"></i><p>No notifications yet</p></div>';
          return;
        }
        const typeIcons = { approval:'fa-check-circle', rejection:'fa-times-circle', interview:'fa-calendar-check', info:'fa-info-circle' };
        const typeColors= { approval:'notif-green', rejection:'notif-red', interview:'notif-blue', info:'notif-yellow' };
        notifList.innerHTML = data.notifications.map(n => `
          <div class="notif-item ${n.is_read=='0'?'notif-unread':''}">
            <div class="notif-icon ${typeColors[n.type]||'notif-blue'}">
              <i class="fas ${typeIcons[n.type]||'fa-bell'}"></i>
            </div>
            <div>
              <div class="notif-text">${escHtml(n.message)}</div>
              <div class="notif-time">${timeAgo(n.created_at)}</div>
            </div>
          </div>`).join('');
      })
      .catch(() => {});
  }

  function markNotifRead() {
    fetch('/campus2career/controllers/notifications_ajax.php?action=mark_read')
      .then(() => {
        notifDot.style.display = 'none';
        document.querySelectorAll('.notif-unread').forEach(el => el.classList.remove('notif-unread'));
      });
  }

  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', e => {
      e.stopPropagation();
      const open = notifDropdown.classList.toggle('open');
      if (open) loadNotifications();
    });
    document.addEventListener('click', () => notifDropdown.classList.remove('open'));
    // Load unread count on page load
    loadNotifications();
  }

  function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return 'Just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400)return Math.floor(diff/3600) + 'h ago';
    return Math.floor(diff/86400) + 'd ago';
  }

  // ── AJAX Live Search (student internships page) ──
  const liveSearch = document.getElementById('liveSearchInput');
  if (liveSearch) {
    let debounceTimer;
    liveSearch.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        const q    = liveSearch.value.toLowerCase().trim();
        const cards= document.querySelectorAll('.internship-card[data-search]');
        let   shown= 0;
        cards.forEach(card => {
          const text  = card.dataset.search.toLowerCase();
          const match = !q || text.includes(q);
          card.style.display = match ? '' : 'none';
          if (match) shown++;
        });
        const counter = document.getElementById('searchResultCount');
        if (counter) counter.textContent = shown + ' internship' + (shown !== 1 ? 's' : '') + ' found';
      }, 250);
    });
  }

  // ── Animated stat counters ──
  function animateCounters() {
    document.querySelectorAll('[data-counter]').forEach(el => {
      const target = parseInt(el.dataset.counter, 10);
      const dur    = 1400;
      const step   = 16;
      const inc    = target / (dur / step);
      let   current= 0;
      const timer  = setInterval(() => {
        current += inc;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = Math.floor(current).toLocaleString();
      }, step);
    });
  }

  // Trigger counters when elements enter viewport
  if ('IntersectionObserver' in window) {
    const counterObs = new IntersectionObserver(entries => {
      entries.forEach(e => { if (e.isIntersecting) { animateCounters(); counterObs.disconnect(); } });
    }, { threshold: 0.3 });
    document.querySelectorAll('[data-counter]').forEach(el => counterObs.observe(el));
  } else {
    animateCounters();
  }

  // ── Skill filter chips (internships page) ──
  document.querySelectorAll('.skill-filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const skill = chip.dataset.skill.toLowerCase();
      chip.classList.toggle('skill-filter-active');
      const activeSkills = [...document.querySelectorAll('.skill-filter-chip.skill-filter-active')]
        .map(c => c.dataset.skill.toLowerCase());
      document.querySelectorAll('.internship-card[data-search]').forEach(card => {
        if (activeSkills.length === 0) { card.style.display = ''; return; }
        const cardSkills = card.dataset.search.toLowerCase();
        card.style.display = activeSkills.some(s => cardSkills.includes(s)) ? '' : 'none';
      });
    });
  });
