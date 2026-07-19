window.Screens = window.Screens || {};

Screens.journal = async function (root) {
  let date = new Date();
  let task = { f_id: 0, f_name: I18n.tr('Task'), f_product: 0 };
  let taskFiltered = false;
  let employee = null;
  let teamleaderId = Number(Store.get('teamleaderid') || 0);
  let teamLeaderName = Store.get('teamleadername') || '';

  function renderShell() {
    root.innerHTML =
      UI.header({ back: 'home', title: I18n.tr('Journal') }) +
      '<main class="shell-body">' +
      '<div class="panel">' +
      '<div class="toolbar">' +
      '<button type="button" class="btn" id="btn-task">' + UI.esc(task.f_name) + '</button>' +
      '<label class="chip"><input type="checkbox" id="chk-task" ' + (taskFiltered ? 'checked' : '') + ' /> ' +
      UI.esc(I18n.tr('Filter by task')) + '</label>' +
      '<button type="button" class="btn btn-icon" id="btn-prev"><img src="assets/left.png" alt="" /></button>' +
      '<strong id="date-label">' + UI.esc(UI.fmtDate(date)) + '</strong>' +
      '<button type="button" class="btn btn-icon" id="btn-next"><img src="assets/right.png" alt="" /></button>' +
      '</div>' +
      '<div class="toolbar">' +
      '<button type="button" class="btn" id="btn-day-workers">' + UI.esc(I18n.tr('Workers of day')) + '</button>' +
      '<button type="button" class="btn" id="btn-all-workers">' + UI.esc(I18n.tr('All employees')) + '</button>' +
      '<span class="chip" id="worker-label">' + UI.esc(employee ? employee.f_name : I18n.tr('Select worker')) + '</span>' +
      '<div class="grow"></div>' +
      '<button type="button" class="btn btn-primary" id="btn-add-work">' + UI.esc(I18n.tr('Add work')) + '</button>' +
      '</div>' +
      '<div class="toolbar">' +
      '<button type="button" class="btn" id="btn-teamlead">' + UI.esc(I18n.tr('Teamlead')) + '</button>' +
      '<span class="chip" id="tl-label">' + UI.esc(teamLeaderName || '—') + '</span>' +
      '<button type="button" class="btn" id="btn-clear-tl">' + UI.esc(I18n.tr('Remove')) + '</button>' +
      '</div>' +
      '</div>' +
      '<div class="panel">' +
      '<div class="table-wrap"><table class="data" id="tbl"><thead><tr>' +
      '<th>' + UI.esc(I18n.tr('Product')) + '</th>' +
      '<th>' + UI.esc(I18n.tr('Process')) + '</th>' +
      '<th class="num">' + UI.esc(I18n.tr('Qty')) + '</th>' +
      '<th class="num">' + UI.esc(I18n.tr('Price')) + '</th>' +
      '<th>' + UI.esc(I18n.tr('Task')) + '</th>' +
      '<th></th>' +
      '</tr></thead><tbody></tbody></table></div>' +
      '<div class="empty" id="empty">' + UI.esc(I18n.tr('No data')) + '</div>' +
      '</div></main>';

    UI.bindShell(root);
    bind();
  }

  async function loadWorks() {
    const tbody = root.querySelector('#tbl tbody');
    const empty = root.querySelector('#empty');
    if (!employee) {
      tbody.innerHTML = '';
      empty.classList.remove('hidden');
      return;
    }
    UI.showLoading();
    const res = await Api.index(Api.Q.listOfWorks, {
      f_worker: employee.f_id,
      f_task: taskFiltered ? task.f_id : 0,
      f_date: UI.toMysqlDate(date),
      f_teamlead: teamleaderId,
    });
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      return;
    }
    const rows = res.data || [];
    tbody.innerHTML = '';
    empty.classList.toggle('hidden', rows.length > 0);
    rows.forEach((r) => {
      const tr = document.createElement('tr');
      tr.innerHTML =
        '<td>' + UI.esc(r.f_productname || '') + '</td>' +
        '<td>' + UI.esc(r.f_processname || '') + '</td>' +
        '<td class="num">' + UI.esc(r.f_qty) + '</td>' +
        '<td class="num">' + UI.esc(r.f_price) + '</td>' +
        '<td>' + UI.esc(r.f_taskdate || '') + '</td>' +
        '<td><button type="button" class="btn" data-done="' + UI.escAttr(r.f_id) + '" ' +
        'data-process="' + UI.escAttr(r.f_process) + '" data-task="' + UI.escAttr(r.f_taskid) + '" ' +
        'data-name="' + UI.escAttr(r.f_processname || '') + '">' + UI.esc(I18n.tr('Execute')) + '</button> ' +
        '<button type="button" class="btn" data-rm="' + UI.escAttr(r.f_id) + '">' + UI.esc(I18n.tr('Remove')) + '</button></td>';
      tbody.appendChild(tr);
    });
    tbody.querySelectorAll('[data-rm]').forEach((b) => {
      b.onclick = async (e) => {
        e.stopPropagation();
        if (!(await UI.confirm(I18n.tr('Remove') + '?'))) return;
        UI.showLoading();
        const r = await Api.index(Api.Q.removeWork, { f_id: Number(b.getAttribute('data-rm')) });
        UI.hideLoading();
        if (r.status !== 1) await UI.alert(r.data);
        else loadWorks();
      };
    });
    tbody.querySelectorAll('[data-done]').forEach((b) => {
      b.onclick = (e) => {
        e.stopPropagation();
        Router.go(
          'work-details-done/' +
          b.getAttribute('data-task') + '/' +
          b.getAttribute('data-process') + '/' +
          b.getAttribute('data-done') + '/' +
          encodeURIComponent(b.getAttribute('data-name') || '')
        );
      };
    });
  }

  function bind() {
    root.querySelector('#btn-prev').onclick = () => { date = UI.addDays(date, -1); renderShell(); loadWorks(); };
    root.querySelector('#btn-next').onclick = () => { date = UI.addDays(date, 1); renderShell(); loadWorks(); };
    root.querySelector('#chk-task').onchange = (e) => { taskFiltered = e.target.checked; loadWorks(); };
    root.querySelector('#btn-clear-tl').onclick = () => {
      teamleaderId = 0;
      teamLeaderName = '';
      Store.set('teamleaderid', '0');
      Store.set('teamleadername', '');
      renderShell();
      loadWorks();
    };
    root.querySelector('#btn-task').onclick = async () => {
      const picked = await Screens.pickTask();
      if (picked) {
        task = picked;
        renderShell();
        loadWorks();
      }
    };
    root.querySelector('#btn-day-workers').onclick = async () => {
      const picked = await Screens.pickEmployeeOfDay(date, teamleaderId);
      if (picked === false) {
        employee = null;
      } else if (picked) {
        employee = picked;
      }
      renderShell();
      loadWorks();
    };
    root.querySelector('#btn-all-workers').onclick = async () => {
      const picked = await Screens.pickEmployee(date);
      if (picked) {
        employee = picked;
        // also register for day
        await Api.index(Api.Q.addWorkerToWork, {
          f_date: UI.toMysqlDate(date),
          f_worker: employee.f_id,
        });
        renderShell();
        loadWorks();
      }
    };
    root.querySelector('#btn-teamlead').onclick = async () => {
      const picked = await Screens.pickTeamlead();
      if (picked) {
        teamleaderId = picked.f_id;
        teamLeaderName = picked.f_name;
        Store.set('teamleaderid', String(teamleaderId));
        Store.set('teamleadername', teamLeaderName);
        renderShell();
        loadWorks();
      }
    };
    root.querySelector('#btn-add-work').onclick = () => {
      if (!employee) return UI.alert(I18n.tr('Select employee'));
      if (!task.f_id) return UI.alert(I18n.tr('Select task'));
      Router.go('list-task-works/' + task.f_id + '/' + task.f_product + '/' + employee.f_id + '/' +
        encodeURIComponent(employee.f_name) + '/' + UI.toMysqlDate(date) + '/' + encodeURIComponent(task.f_name));
    };
  }

  renderShell();
  await loadWorks();
};

Screens.pickTask = async function () {
  return new Promise(async (resolve) => {
    UI.showLoading();
    const res = await Api.index(Api.Q.listOfTasks, {});
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      resolve(null);
      return;
    }
    const rows = res.data || [];
    const el = document.getElementById('overlay');
    el.classList.remove('hidden');
    el.innerHTML =
      '<div class="dialog" style="width:min(520px,100%);max-height:80vh;overflow:auto">' +
      '<h3>' + UI.esc(I18n.tr('List of task')) + '</h3>' +
      '<div id="pick-list"></div>' +
      '<div class="actions"><button type="button" class="btn" data-close>' + UI.esc(I18n.tr('Close')) + '</button></div></div>';
    const list = el.querySelector('#pick-list');
    rows.forEach((r) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn';
      b.style.cssText = 'width:100%;margin:4px 0;justify-content:flex-start';
      b.textContent = r.f_name || ('#' + r.f_id);
      b.onclick = () => {
        UI.hideLoading();
        resolve({ f_id: r.f_id, f_name: r.f_name, f_product: r.f_product });
      };
      list.appendChild(b);
    });
    el.querySelector('[data-close]').onclick = () => { UI.hideLoading(); resolve(null); };
  });
};
