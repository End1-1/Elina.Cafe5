window.Router = (function () {
  const routes = {};
  let current = null;

  function register(name, handler) {
    routes[name] = handler;
  }

  function parse() {
    const hash = (location.hash || '#/login').replace(/^#\/?/, '');
    const parts = hash.split('/').filter(Boolean);
    return { name: parts[0] || 'login', params: parts.slice(1) };
  }

  async function render() {
    const app = document.getElementById('app');
    const { name, params } = parse();
    const needAuth = name !== 'login';
    if (needAuth && !Store.getSession()) {
      location.hash = '#/login';
      return;
    }
    if (name === 'login' && Store.getSession()) {
      location.hash = '#/home';
      return;
    }
    const handler = routes[name] || routes.home;
    current = name;
    app.innerHTML = '';
    await handler(app, params);
  }

  function go(path) {
    const clean = String(path || '').replace(/^#\/?/, '');
    location.hash = '#/' + clean;
  }

  function start() {
    window.addEventListener('hashchange', render);
    if (!location.hash) {
      location.hash = Store.getSession() ? '#/home' : '#/login';
    } else {
      render();
    }
  }

  return { register, go, start, render, get current() { return current; } };
})();
