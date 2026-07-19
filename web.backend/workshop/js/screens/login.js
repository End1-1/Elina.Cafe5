window.Screens = window.Screens || {};

Screens.login = async function (root) {
  root.innerHTML =
    '<div class="login-page">' +
    '<div class="login-card">' +
    '<img src="assets/logo_big.png" alt="Elina" />' +
    '<h1>Elina Workshop</h1>' +
    '<div class="field" style="text-align:left">' +
    '<label>' + UI.esc(I18n.tr('PIN')) + '</label>' +
    '<input type="password" inputmode="numeric" id="pin" autocomplete="one-time-code" />' +
    '</div>' +
    '<button type="button" class="btn btn-primary" id="btn-login">' + UI.esc(I18n.tr('Login')) + '</button>' +
    '<div class="login-error" id="login-error"></div>' +
    '<div class="login-ver">v' + UI.esc(AppConfig.version) + '</div>' +
    '</div></div>';

  const pin = root.querySelector('#pin');
  const err = root.querySelector('#login-error');
  pin.focus();

  async function doLogin() {
    err.textContent = '';
    const value = pin.value.trim();
    if (!value) {
      err.textContent = I18n.tr('PIN');
      return;
    }
    UI.showLoading();
    const res = await Api.request('login', { pin: value, method: 2 });
    UI.hideLoading();
    if (res.status !== 1) {
      err.textContent = typeof res.data === 'string' ? res.data : I18n.tr('Unauthorized');
      return;
    }
    Store.setSession(res.data.sessionkey);
    Store.setUser(res.data.user || null);
    Router.go('home');
  }

  root.querySelector('#btn-login').onclick = doLogin;
  pin.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') doLogin();
  });
};
