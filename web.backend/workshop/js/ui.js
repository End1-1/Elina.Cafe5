window.UI = (function () {
  const overlay = () => document.getElementById('overlay');
  const toastEl = () => document.getElementById('toast');

  function showLoading(text) {
    const el = overlay();
    el.classList.remove('hidden');
    el.innerHTML = '<div class="loading-box"><div class="spinner"></div><div>' +
      esc(text || I18n.tr('Loading')) + '</div></div>';
  }

  function hideLoading() {
    const el = overlay();
    el.classList.add('hidden');
    el.innerHTML = '';
  }

  function toast(msg) {
    const el = toastEl();
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => el.classList.add('hidden'), 2800);
  }

  function alert(msg, title) {
    return new Promise((resolve) => {
      const el = overlay();
      el.classList.remove('hidden');
      el.innerHTML =
        '<div class="dialog">' +
        '<h3>' + esc(title || I18n.tr('Task')) + '</h3>' +
        '<p>' + esc(msg) + '</p>' +
        '<div class="actions"><button type="button" class="btn btn-primary" data-ok>' +
        esc(I18n.tr('Ok')) + '</button></div></div>';
      el.querySelector('[data-ok]').onclick = () => {
        hideLoading();
        resolve();
      };
    });
  }

  function confirm(msg, title) {
    return new Promise((resolve) => {
      const el = overlay();
      el.classList.remove('hidden');
      el.innerHTML =
        '<div class="dialog">' +
        '<h3>' + esc(title || I18n.tr('Task')) + '</h3>' +
        '<p>' + esc(msg) + '</p>' +
        '<div class="actions">' +
        '<button type="button" class="btn" data-no>' + esc(I18n.tr('No')) + '</button>' +
        '<button type="button" class="btn btn-primary" data-yes>' + esc(I18n.tr('Yes')) + '</button>' +
        '</div></div>';
      el.querySelector('[data-no]').onclick = () => { hideLoading(); resolve(false); };
      el.querySelector('[data-yes]').onclick = () => { hideLoading(); resolve(true); };
    });
  }

  function prompt(msg, def) {
    return new Promise((resolve) => {
      const el = overlay();
      el.classList.remove('hidden');
      el.innerHTML =
        '<div class="dialog">' +
        '<h3>' + esc(msg) + '</h3>' +
        '<div class="field"><input type="text" data-in value="' + escAttr(def || '') + '" /></div>' +
        '<div class="actions">' +
        '<button type="button" class="btn" data-no>' + esc(I18n.tr('No')) + '</button>' +
        '<button type="button" class="btn btn-primary" data-yes>' + esc(I18n.tr('Ok')) + '</button>' +
        '</div></div>';
      const input = el.querySelector('[data-in]');
      input.focus();
      el.querySelector('[data-no]').onclick = () => { hideLoading(); resolve(null); };
      el.querySelector('[data-yes]').onclick = () => { hideLoading(); resolve(input.value); };
    });
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function escAttr(s) {
    return esc(s).replace(/'/g, '&#39;');
  }

  function header(opts) {
    opts = opts || {};
    const user = Store.getUser();
    const name = user ? ((user.f_last || '') + ' ' + (user.f_first || '')).trim() : '';
    return (
      '<header class="shell-header">' +
      (opts.back
        ? '<button type="button" class="btn btn-ghost btn-icon" data-nav="' + escAttr(opts.back) + '" title="' + escAttr(I18n.tr('Back')) + '">' +
          '<img src="assets/back.png" alt="" /></button>'
        : '') +
      '<div class="brand"><img src="assets/logo.png" alt="" /><span>ELINA</span></div>' +
      (opts.title ? '<div>' + esc(opts.title) + '</div>' : '') +
      '<div class="spacer"></div>' +
      '<div class="meta">' + esc(name) + ' · v' + esc(AppConfig.version) + '</div>' +
      (opts.logout !== false
        ? '<button type="button" class="btn btn-ghost" data-logout style="color:#fff;border-color:rgba(255,255,255,.25)">' +
          esc(I18n.tr('Logout')) + '</button>'
        : '') +
      '</header>'
    );
  }

  function bindShell(root) {
    root.querySelectorAll('[data-nav]').forEach((btn) => {
      btn.addEventListener('click', () => Router.go(btn.getAttribute('data-nav')));
    });
    const logout = root.querySelector('[data-logout]');
    if (logout) {
      logout.addEventListener('click', () => {
        Store.clearSession();
        Router.go('login');
      });
    }
  }

  async function withLoading(fn) {
    showLoading();
    try {
      return await fn();
    } finally {
      hideLoading();
    }
  }

  function fmtDate(d) {
    const x = d instanceof Date ? d : new Date(d);
    const dd = String(x.getDate()).padStart(2, '0');
    const mm = String(x.getMonth() + 1).padStart(2, '0');
    const yyyy = x.getFullYear();
    return dd + '/' + mm + '/' + yyyy;
  }

  function toMysqlDate(d) {
    const x = d instanceof Date ? d : new Date(d);
    const mm = String(x.getMonth() + 1).padStart(2, '0');
    const dd = String(x.getDate()).padStart(2, '0');
    return x.getFullYear() + '-' + mm + '-' + dd;
  }

  function addDays(d, n) {
    const x = new Date(d.getTime());
    x.setDate(x.getDate() + n);
    return x;
  }

  return {
    showLoading, hideLoading, toast, alert, confirm, prompt,
    esc, escAttr, header, bindShell, withLoading,
    fmtDate, toMysqlDate, addDays,
  };
})();
