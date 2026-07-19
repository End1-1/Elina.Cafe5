window.Screens = window.Screens || {};

Screens.list_task_works = async function (root, params) {
  const taskId = Number(params[0] || 0);
  const productId = Number(params[1] || 0);
  const workerId = Number(params[2] || 0);
  const workerName = decodeURIComponent(params[3] || '');
  const dateStr = params[4] || UI.toMysqlDate(new Date());
  const taskName = decodeURIComponent(params[5] || '');

  root.innerHTML =
    UI.header({ back: 'journal', title: I18n.tr('Add work') }) +
    '<main class="shell-body">' +
    '<div class="panel"><div class="meta">' +
    UI.esc(taskName) + ' · ' + UI.esc(workerName) + ' · ' + UI.esc(dateStr) +
    '</div></div>' +
    '<div class="panel">' +
    '<div class="table-wrap"><table class="data" id="tbl"><thead><tr>' +
    '<th>#</th><th>' + UI.esc(I18n.tr('Process')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Price')) + '</th><th></th>' +
    '</tr></thead><tbody></tbody></table></div>' +
    '<div class="empty hidden" id="empty">' + UI.esc(I18n.tr('No data')) + '</div>' +
    '</div></main>';

  UI.bindShell(root);

  UI.showLoading();
  const res = await Api.index(Api.Q.listOfTaskWorks, { f_product: productId });
  UI.hideLoading();
  if (res.status !== 1) {
    await UI.alert(res.data);
    return;
  }
  const rows = res.data || [];
  const tbody = root.querySelector('#tbl tbody');
  root.querySelector('#empty').classList.toggle('hidden', rows.length > 0);
  rows.forEach((r) => {
    const tr = document.createElement('tr');
    tr.innerHTML =
      '<td>' + UI.esc(r.f_rowid) + '</td>' +
      '<td>' + UI.esc(r.f_acname || '') + '</td>' +
      '<td class="num">' + UI.esc(r.f_price) + '</td>' +
      '<td><button type="button" class="btn btn-primary" data-add>' + UI.esc(I18n.tr('Create')) + '</button></td>';
    tr.querySelector('[data-add]').onclick = async () => {
      UI.showLoading();
      const add = await Api.index(Api.Q.addWorkToTask, {
        f_date: dateStr,
        f_worker: workerId,
        f_taskid: taskId,
        f_product: productId,
        f_process: r.f_process,
        f_price: r.f_price,
        f_laststep: 0,
      });
      UI.hideLoading();
      if (add.status !== 1) {
        await UI.alert(add.data);
        return;
      }
      UI.toast(I18n.tr('Ok'));
      Router.go('journal');
    };
    tbody.appendChild(tr);
  });
};
