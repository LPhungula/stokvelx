/**
 * StokvelX – API helper
 * Provides a single `api()` function used by every page to talk to the
 * PHP backend. The correct base URL is computed automatically based on
 * where this script lives in the folder tree.
 */

(function () {
  // Work out whether we're one folder deep (/pages/xxx.html) or at root
  const depth = window.location.pathname.includes('/pages/') ? '../' : '';
  window.API_BASE = depth + 'backend/api';

  /**
   * Make an authenticated fetch request to the backend.
   * @param {string} endpoint  e.g. 'auth/login'
   * @param {string} [method]  'GET' | 'POST' (default 'GET')
   * @param {object} [body]    data to send (JSON-encoded automatically)
   * @returns {Promise<object>} parsed JSON response
   */
  window.api = async function (endpoint, method = 'GET', body = null) {
    const opts = {
      method,
      credentials: 'include',   // send session cookie with every request
      headers: { 'Content-Type': 'application/json' },
    };
    if (body && method !== 'GET') {
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(`${window.API_BASE}/${endpoint}.php`, opts);
    const data = await res.json().catch(() => ({ success: false, message: 'Server returned an unexpected response.' }));
    return { ...data, _status: res.status };
  };

  /**
   * Guard – call on every protected page (not login/signup/index).
   * Redirects to login if the session has expired or the user is not logged in.
   * Returns the user object if valid.
   */
  window.requireAuth = async function () {
    const data = await api('auth/me');
    if (!data.success) {
      window.location.href = depth + 'pages/login.html';
      return null;
    }
    return data.user;
  };

  /**
   * Guard – call on the admin panel page only.
   * Redirects to dashboard if the user is not an admin.
   */
  window.requireAdmin = async function () {
    const user = await requireAuth();
    if (user && user.role !== 'admin') {
      window.location.href = 'dashboard.html';
      return null;
    }
    return user;
  };

  /** Format a number as South African Rand, e.g. R 1 250.00 */
  window.formatRand = function (amount) {
    return 'R' + Number(amount).toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  /** Format an ISO date string to a readable SA date, e.g. "24 May 2024" */
  window.formatDate = function (dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-ZA', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  /** Badge colour for a circle type */
  window.typeBadge = function (type) {
    const map = { standard: '#6b7280', premium: '#7c3aed', investment: '#059669' };
    return map[type] || '#6b7280';
  };
})();
