/**
 * StokvelX – API helper
 * Points to the live Railway PHP backend.
 */

(function () {
  // Live Railway backend URL
  window.API_BASE = 'https://stokvelx-production.up.railway.app/backend/api';

  /**
   * Make an authenticated fetch request to the backend.
   */
  window.api = async function (endpoint, method = 'GET', body = null) {
    const opts = {
      method,
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
    };
    if (body && method !== 'GET') {
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(`${window.API_BASE}/${endpoint}.php`, opts);
    const data = await res.json().catch(() => ({ success: false, message: 'Server returned an unexpected response.' }));
    return { ...data, _status: res.status };
  };

  /** Guard – redirects to login if session expired */
  window.requireAuth = async function () {
    const data = await api('auth/me');
    if (!data.success) {
      window.location.href = 'login.html';
      return null;
    }
    return data.user;
  };

  /** Guard – admin only */
  window.requireAdmin = async function () {
    const user = await requireAuth();
    if (user && user.role !== 'admin') {
      window.location.href = 'dashboard.html';
      return null;
    }
    return user;
  };

  /** Format as South African Rand */
  window.formatRand = function (amount) {
    return 'R' + Number(amount).toLocaleString('en-ZA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  /** Format date string to readable SA date */
  window.formatDate = function (dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-ZA', { day: 'numeric', month: 'long', year: 'numeric' });
  };

  /** Badge colour for circle type */
  window.typeBadge = function (type) {
    const map = { standard: '#6b7280', premium: '#7c3aed', investment: '#059669' };
    return map[type] || '#6b7280';
  };
})();
