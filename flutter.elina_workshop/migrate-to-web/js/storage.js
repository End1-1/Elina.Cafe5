window.Store = (function () {
  const p = () => AppConfig.storagePrefix;

  function get(key, def) {
    const v = localStorage.getItem(p() + key);
    if (v === null || v === undefined) return def;
    return v;
  }

  function set(key, value) {
    localStorage.setItem(p() + key, value == null ? '' : String(value));
  }

  function getJson(key, def) {
    try {
      const v = get(key, '');
      if (!v) return def;
      return JSON.parse(v);
    } catch (e) {
      return def;
    }
  }

  function setJson(key, value) {
    set(key, JSON.stringify(value));
  }

  function getSession() {
    return get('sessionkey', '');
  }

  function setSession(key) {
    set('sessionkey', key || '');
  }

  function clearSession() {
    localStorage.removeItem(p() + 'sessionkey');
    localStorage.removeItem(p() + 'user');
  }

  function getUser() {
    return getJson('user', null);
  }

  function setUser(user) {
    setJson('user', user);
  }

  return {
    get, set, getJson, setJson,
    getSession, setSession, clearSession,
    getUser, setUser,
  };
})();
