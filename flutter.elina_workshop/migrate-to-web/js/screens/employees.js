window.Screens = window.Screens || {};

Screens.pickTeamlead = async function () {
  UI.showLoading();
  const res = await Api.index(Api.Q.listOfTeamlead, {});
  UI.hideLoading();
  if (res.status !== 1) {
    await UI.alert(res.data);
    return null;
  }
  return Screens._pickList(I18n.tr('Teamlead'), res.data || [], (r) => ({
    f_id: r.f_id,
    f_name: r.f_name,
  }));
};

Screens.pickEmployee = async function (date) {
  UI.showLoading();
  const [t, e] = await Promise.all([
    Api.index(Api.Q.listOfTeamlead, {}),
    Api.index(Api.Q.listOfEmployee, {}),
  ]);
  UI.hideLoading();
  if (e.status !== 1) {
    await UI.alert(e.data);
    return null;
  }
  return Screens._pickList(I18n.tr('All employees'), e.data || [], (r) => ({
    f_id: r.f_id,
    f_name: r.f_name,
    f_teamlead: r.f_teamlead,
  }));
};

Screens.pickEmployeeOfDay = async function (date, teamleadId) {
  UI.showLoading();
  const res = await Api.index(Api.Q.employesOfDay, {
    f_date: UI.toMysqlDate(date),
    f_teamlead: teamleadId || 0,
  });
  UI.hideLoading();
  if (res.status !== 1) {
    await UI.alert(res.data);
    return null;
  }
  return Screens._pickList(I18n.tr('Workers of day'), res.data || [], (r) => ({
    f_id: r.f_id,
    f_name: r.f_name,
    f_teamlead: r.f_teamlead,
  }), true);
};

Screens._pickList = function (title, rows, mapFn, allowClear) {
  return new Promise((resolve) => {
    const el = document.getElementById('overlay');
    el.classList.remove('hidden');
    el.innerHTML =
      '<div class="dialog" style="width:min(520px,100%);max-height:80vh;overflow:auto">' +
      '<h3>' + UI.esc(title) + '</h3>' +
      '<div id="pick-list"></div>' +
      '<div class="actions">' +
      (allowClear ? '<button type="button" class="btn" data-clear>' + UI.esc(I18n.tr('Remove')) + '</button>' : '') +
      '<button type="button" class="btn" data-close>' + UI.esc(I18n.tr('Close')) + '</button></div></div>';
    const list = el.querySelector('#pick-list');
    if (!rows.length) {
      list.innerHTML = '<div class="empty">' + UI.esc(I18n.tr('No data')) + '</div>';
    }
    rows.forEach((r) => {
      const item = mapFn(r);
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn';
      b.style.cssText = 'width:100%;margin:4px 0;justify-content:flex-start';
      b.textContent = item.f_name;
      b.onclick = () => { UI.hideLoading(); resolve(item); };
      list.appendChild(b);
    });
    el.querySelector('[data-close]').onclick = () => { UI.hideLoading(); resolve(null); };
    const clear = el.querySelector('[data-clear]');
    if (clear) clear.onclick = () => { UI.hideLoading(); resolve(false); };
  });
};

// Route stubs for deep links if needed later
Screens.employees = async function (root) {
  root.innerHTML = UI.header({ back: 'journal', title: I18n.tr('Employee') }) +
    '<main class="shell-body"><div class="panel"><p>' + UI.esc(I18n.tr('Select employee')) + '</p>' +
    '<button class="btn btn-primary" id="go">' + UI.esc(I18n.tr('Ok')) + '</button></div></main>';
  UI.bindShell(root);
  root.querySelector('#go').onclick = async () => {
    const p = await Screens.pickEmployee(new Date());
    if (p) UI.toast(p.f_name);
  };
};

Screens.employees_of_day = Screens.employees;
