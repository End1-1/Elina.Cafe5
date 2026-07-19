window.Api = (function () {
  const Q = {
    listOfTasks: 1,
    listOfTeamlead: 2,
    listOfEmployee: 3,
    listOfWorks: 4,
    listOfTaskWorks: 5,
    addWorkToTask: 6,
    employesOfDay: 7,
    addWorkerToWork: 8,
    changeQty: 9,
    removeWork: 10,
    removeWorker: 11,
    workDetails: 12,
    workDetailsList: 13,
    workDetailsUpdate: 14,
    workDetailsDone: 15,
    workDetailsUpdateDone: 16,
    removeWorkDetails: 17,
    workDetailsUpdateDoneArray: 18,
    workDetailsUpdateUnDone: 19,
    workDetailsUpdateDoneArray2: 20,
  };

  async function request(route, params) {
    const body = Object.assign({}, params || {}, {
      app: AppConfig.appName,
      appversion: AppConfig.version,
      sessionkey: Store.getSession() || '',
    });

    const url = AppConfig.apiBase.replace(/\/$/, '') + '/' + route.replace(/^\//, '') +
      (route.indexOf('.php') === -1 ? '.php' : '');

    let response;
    try {
      response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
    } catch (e) {
      return { status: 2, data: I18n.tr('Network error') + ': ' + e.message };
    }

    const text = await response.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      return { status: 0, data: text || ('HTTP ' + response.status) };
    }

    if (typeof json.status === 'undefined') {
      return { status: 0, data: json };
    }
    return json;
  }

  function index(query, params) {
    return request('index', Object.assign({ query: query }, params || {}));
  }

  return { Q, request, index };
})();
