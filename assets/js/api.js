/* ============================================================
   DUKASOFT HARDWARE ERP — API / Data Access Layer (api.js)
   All AJAX communication with the PHP backend lives here.
   Pages import this AFTER app.js.

   Base path: ../api/  (all pages live inside pages/)
   ============================================================ */

'use strict';

/* ── Base fetch wrapper ──────────────────────────────────── */
const API_BASE = '../api';   // relative to pages/

/**
 * Low-level fetch helper.
 * Returns parsed JSON or throws an Error with the server message.
 */
async function apiFetch(endpoint, options = {}) {
  const defaults = {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  };
  const mergedOptions = { ...defaults, ...options };
  if (mergedOptions.body && typeof mergedOptions.body === 'object') {
    mergedOptions.body = JSON.stringify(mergedOptions.body);
  }

  const res  = await fetch(API_BASE + endpoint, mergedOptions);
  const json = await res.json().catch(() => ({ success: false, message: 'Invalid server response.' }));

  if (res.status === 401) {
    // Session expired — redirect to login
    window.location.href = '../index.html';
    return;
  }

  return json;
}

const get  = (ep, params = {}) => {
  const qs = new URLSearchParams(params).toString();
  return apiFetch(ep + (qs ? '?' + qs : ''));
};
const post = (ep, body = {}) => apiFetch(ep, { method: 'POST', body });

/* ── Auth ─────────────────────────────────────────────────── */
const Auth = {
  login:    (username, password) => post('/auth/login.php',   { username, password }),
  register: (username, password, full_name, phone) => post('/auth/register.php', { username, password, full_name, phone }),
  logout:   ()                   => post('/auth/logout.php'),
  check:    ()                   => get('/auth/check.php'),
};

/* ── Categories ──────────────────────────────────────────── */
const Categories = {
  list:   ()               => get('/categories/list.php'),
  save:   (data)           => post('/categories/save.php', data),
  delete: (category_id)    => post('/categories/delete.php', { category_id }),
};

/* ── Items ───────────────────────────────────────────────── */
const Items = {
  list:        (params = {}) => get('/items/list.php', params),
  save:        (data)        => post('/items/save.php', data),
  delete:      (item_id)     => post('/items/delete.php', { item_id }),
  updatePrice: (item_id, price) => post('/items/update_price.php', { item_id, price }),
};

/* ── Sales ───────────────────────────────────────────────── */
const Sales = {
  create: (data)        => post('/sales/create.php', data),
  list:   (params = {}) => get('/sales/list.php', params),
  delete: (sale_id)     => post('/sales/delete.php', { sale_id }),
};

/* ── Restock ─────────────────────────────────────────────── */
const Restock = {
  create: (data)        => post('/restock/create.php', data),
  list:   (params = {}) => get('/restock/list.php', params),
  delete: (restockId)   => post('/restock/delete.php', { restock_id: restockId }),
};

/* ── Dashboard ───────────────────────────────────────────── */
const Dashboard = {
  stats: () => get('/dashboard/stats.php'),
};

/* ── Reports ─────────────────────────────────────────────── */
const Reports = {
  summary: (params = {}) => get('/reports/summary.php', params),
};

/* ── Auth guard for pages ─────────────────────────────────── */
/**
 * Call once at the top of each page's DOMContentLoaded handler.
 * Checks the session server-side and populates the sidebar user info.
 */
async function pageAuthGuard() {
  const res = await Auth.check();
  if (!res || !res.success) {
    window.location.href = '../index.html';
    return;
  }
  const user  = res.data;
  const uname = user?.username || 'Admin';
  const avatar     = document.getElementById('sidebar-avatar-initials');
  const usernameEl = document.getElementById('sidebar-username');
  if (usernameEl) usernameEl.textContent = uname;
  if (avatar)   avatar.textContent = uname.substring(0, 2).toUpperCase();
  return user;
}

/**
 * Refreshes the low-stock notification badge count.
 * Accepts pre-fetched stats or fetches from the dashboard endpoint.
 */
async function refreshLowStockBadge(preloadedCount = null) {
  const badge = document.getElementById('notif-badge');
  if (!badge) return;

  let count = preloadedCount;
  if (count === null) {
    try {
      const res = await Dashboard.stats();
      const stats = res?.data;
      count = parseInt(stats?.inv_stats?.low_stock_count ?? 0);
    } catch (_) { count = 0; }
  }

  badge.style.display = count > 0 ? 'inline-block' : 'none';
  if (count > 0) badge.textContent = count > 99 ? '99+' : count;
}
