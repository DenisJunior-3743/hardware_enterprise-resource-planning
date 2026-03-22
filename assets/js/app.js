/* ============================================================
   DUKASOFT HARDWARE ERP — Shared JavaScript
   ============================================================ */

'use strict';

/* ── Sidebar toggle ──────────────────────────────────────── */
function initSidebar() {
  const sidebar    = document.getElementById('sidebar');
  const mainContent = document.getElementById('main-content');
  const topbar     = document.getElementById('topbar');
  const toggleBtns = document.querySelectorAll('.sidebar-toggle');
  const backdrop   = document.getElementById('sidebar-backdrop');
  const isMobile   = () => window.innerWidth <= 768;

  // Exit if sidebar doesn't exist
  if (!sidebar || toggleBtns.length === 0) return;

  function openSidebar() {
    if (isMobile()) {
      sidebar.classList.add('mobile-open');
      backdrop && backdrop.classList.add('open');
    } else {
      sidebar.classList.remove('collapsed');
      mainContent && mainContent.classList.remove('sidebar-collapsed');
      topbar && topbar.classList.remove('sidebar-collapsed');
    }
    localStorage.setItem('sidebarOpen', '1');
  }

  function closeSidebar() {
    if (isMobile()) {
      sidebar.classList.remove('mobile-open');
      backdrop && backdrop.classList.remove('open');
    } else {
      sidebar.classList.add('collapsed');
      mainContent && mainContent.classList.add('sidebar-collapsed');
      topbar && topbar.classList.add('sidebar-collapsed');
    }
    localStorage.setItem('sidebarOpen', '0');
  }

  function toggleSidebar() {
    if (isMobile()) {
      sidebar.classList.contains('mobile-open') ? closeSidebar() : openSidebar();
    } else {
      sidebar.classList.contains('collapsed') ? openSidebar() : closeSidebar();
    }
  }

  // Attach click handlers to all toggle buttons
  toggleBtns.forEach(btn => btn.addEventListener('click', toggleSidebar));
  
  // Close sidebar when backdrop is clicked (mobile only)
  if (backdrop) {
    backdrop.addEventListener('click', closeSidebar);
  }

  // Restore saved sidebar state on desktop
  if (!isMobile()) {
    const saved = localStorage.getItem('sidebarOpen');
    if (saved === '0') {
      sidebar.classList.add('collapsed');
      mainContent && mainContent.classList.add('sidebar-collapsed');
      topbar && topbar.classList.add('sidebar-collapsed');
    }
  }
}

/* ── Nav collapsible groups ──────────────────────────────── */
function initNavGroups() {
  const parents = document.querySelectorAll('.nav-parent');
  parents.forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('.nav-group');
      const children = group.querySelector('.nav-children');
      if (!children) return;

      const isOpen = children.classList.contains('open');

      // Close all
      document.querySelectorAll('.nav-children').forEach(c => c.classList.remove('open'));
      document.querySelectorAll('.nav-parent').forEach(p => p.classList.remove('open'));

      // Open this one if it was closed
      if (!isOpen) {
        children.classList.add('open');
        btn.classList.add('open');
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  });

  // Auto-open group that has active child
  document.querySelectorAll('.nav-child.active').forEach(child => {
    const children = child.closest('.nav-children');
    const parent   = child.closest('.nav-group').querySelector('.nav-parent');
    if (children) children.classList.add('open');
    if (parent)   { parent.classList.add('open'); parent.classList.add('active'); }
  });
}

/* ── Toast notifications ─────────────────────────────────── */
const Toast = {
  container: null,

  init() {
    this.container = document.getElementById('toast-container');
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'info', duration = 4000, undoFn = null) {
    if (!this.container) this.init();
    const icons = { success: '✓', danger: '✕', info: 'ℹ' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <span class="toast-msg">${message}</span>
      ${undoFn ? `<button class="toast-undo" onclick="this.closest('.toast')._undo()">UNDO</button>` : ''}
    `;
    if (undoFn) toast._undo = () => { undoFn(); toast.remove(); };
    this.container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'toastIn 0.2s ease reverse';
      setTimeout(() => toast.remove(), 200);
    }, duration);
  },

  success(msg, undoFn) { this.show(msg, 'success', 4000, undoFn); },
  danger(msg)          { this.show(msg, 'danger',  4000); },
  info(msg)            { this.show(msg, 'info',    3500); }
};

/* ── Modal helpers ───────────────────────────────────────── */
const Modal = {
  open(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
  }
};

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// Close modal with Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') Modal.closeAll();
});

/* ── Confirm Dialog ──────────────────────────────────────── */
const Confirm = {
  _callback: null,

  show(opts) {
    // opts: { title, message, confirmText, type('danger'|'warning'), onConfirm }
    const overlay = document.getElementById('confirm-overlay');
    if (!overlay) return;
    document.getElementById('confirm-title').textContent   = opts.title || 'Are you sure?';
    document.getElementById('confirm-message').textContent = opts.message || '';
    const btn = document.getElementById('confirm-btn');
    btn.textContent = opts.confirmText || 'Confirm';
    btn.className = `btn btn-lg ${opts.type === 'danger' ? 'btn-danger' : 'btn-primary'}`;
    const icon = overlay.querySelector('.confirm-icon');
    icon.className = `confirm-icon ${opts.type || 'warning'}`;
    icon.textContent = opts.type === 'danger' ? '🗑' : '⚠';
    this._callback = opts.onConfirm;
    overlay.classList.add('open');
  },

  confirm() {
    Modal.close('confirm-overlay');
    if (typeof this._callback === 'function') this._callback();
  }
};

/* ── Format UGX currency ─────────────────────────────────── */
function formatUGX(amount) {
  const num = parseFloat(amount) || 0;
  return 'UGX ' + num.toLocaleString('en-UG', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

/* ── Inactivity logout (30 min) ──────────────────────────── */
function initInactivityTimer() {
  const TIMEOUT_MS   = 30 * 60 * 1000; // 30 minutes
  const WARNING_MS   = 29 * 60 * 1000; // warn at 29 min
  const banner       = document.getElementById('inactivity-warning');
  let timer, warnTimer;

  function resetTimer() {
    clearTimeout(timer);
    clearTimeout(warnTimer);
    if (banner) banner.style.display = 'none';

    warnTimer = setTimeout(() => {
      if (banner) banner.style.display = 'block';
    }, WARNING_MS);

    timer = setTimeout(() => {
      Toast.danger('Session expired. Logging you out...');
      setTimeout(() => { window.location.href = 'index.html'; }, 1500);
    }, TIMEOUT_MS);
  }

  ['click','keydown','mousemove','touchstart','scroll'].forEach(e =>
    document.addEventListener(e, resetTimer, { passive: true })
  );
  resetTimer();
}

/* ── Number input formatting ─────────────────────────────── */
function formatNumberInput(input) {
  input.addEventListener('blur', function() {
    const val = parseFloat(this.value.replace(/,/g, ''));
    if (!isNaN(val)) {
      this.value = val.toLocaleString('en-UG');
    }
  });
}

/* ── Form validation ─────────────────────────────────────── */
function validateForm(form) {
  let valid = true;
  form.querySelectorAll('[required]').forEach(field => {
    const err = field.parentElement.querySelector('.form-error');
    if (!field.value.trim()) {
      field.classList.add('error');
      if (err) err.classList.add('show');
      valid = false;
    } else {
      field.classList.remove('error');
      if (err) err.classList.remove('show');
    }
  });
  return valid;
}

/* ── Animate number counter (for stat cards) ─────────────── */
function animateCount(el, target, prefix = '', suffix = '') {
  const duration = 800;
  const start    = 0;
  const step     = (timestamp) => {
    if (!start) start = timestamp;
    const progress = Math.min((timestamp - start) / duration, 1);
    const value = Math.floor(progress * target);
    el.textContent = prefix + value.toLocaleString('en-UG') + suffix;
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}

/* ── Date helpers ────────────────────────────────────────── */
function todayStr() {
  return new Date().toLocaleDateString('en-UG', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  });
}
function shortDate(date) {
  return new Date(date).toLocaleDateString('en-UG', {
    day: '2-digit', month: 'short', year: 'numeric'
  });
}

/* ── XSS-safe HTML escaping ──────────────────────────────── */
function esc(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ── Sign out (called from sidebar sign-out link) ────────── */
async function signOut(e) {
  if (e) e.preventDefault();
  if (!confirm('Sign out of DukaSoft?')) return false;
  try {
    await fetch('../api/auth/logout.php', { method: 'POST' });
  } catch (_) { /* ignore network errors */ }
  window.location.href = '../index.html';
  return false;
}

/* ── Init all shared behaviour on DOM ready ──────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initNavGroups();
  Toast.init();
  initInactivityTimer();
});
