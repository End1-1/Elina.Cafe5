window.Screens = window.Screens || {};

const SIZES = ['34', '36', '38', '40', '42', '44', '46', '48', '50', '52'];

Screens.work_details = async function (root, params) {
  const taskId = Number(params[0] || 0);
  const process = Number(params[1] || 0);

  root.innerHTML =
    UI.header({ back: 'task/' + taskId, title: I18n.tr('Details') + ' #' + taskId }) +
    '<main class="shell-body">' +
    '<div class="btn-row">' +
    '<button type="button" class="btn btn-primary" id="btn-add-color">' + UI.esc(I18n.tr('Color')) + ' +</button>' +
    '<button type="button" class="btn" id="btn-refresh">' + UI.esc(I18n.tr('Refresh')) + '</button>' +
    '</div>' +
    '<div class="panel">' +
    '<div class="table-wrap"><table class="data sizes-table" id="tbl"><thead><tr>' +
    '<th>' + UI.esc(I18n.tr('Color')) + '</th>' +
    SIZES.map((s) => '<th class="num">' + s + '</th>').join('') +
    '</tr></thead><tbody></tbody></table></div>' +
    '<div class="empty hidden" id="empty">' + UI.esc(I18n.tr('No data')) + '</div>' +
    '</div></main>';

  UI.bindShell(root);

  async function load() {
    UI.showLoading();
    const res = await Api.index(Api.Q.workDetailsList, { f_taskid: taskId });
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      return;
    }
    const rows = res.data || [];
    const tbody = root.querySelector('#tbl tbody');
    tbody.innerHTML = '';
    root.querySelector('#empty').classList.toggle('hidden', rows.length > 0);
    rows.forEach((r) => {
      const tr = document.createElement('tr');
      let html = '<td>' + UI.esc(r.f_color || '') + '</td>';
      SIZES.forEach((s) => {
        const key = 'f_' + s + 'p';
        html += '<td class="num"><input type="number" min="0" data-id="' + UI.escAttr(r.f_id) +
          '" data-field="f_' + s + '" value="' + UI.escAttr(r[key] || 0) + '" /></td>';
      });
      tr.innerHTML = html;
      tbody.appendChild(tr);
    });
    tbody.querySelectorAll('input').forEach((inp) => {
      inp.onchange = async () => {
        const qty = Number(inp.value) || 0;
        UI.showLoading();
        const res2 = await Api.index(Api.Q.workDetailsUpdate, {
          f_id: Number(inp.dataset.id),
          f_field: inp.dataset.field,
          f_qty: qty,
        });
        UI.hideLoading();
        if (res2.status !== 1) await UI.alert(res2.data);
      };
    });
  }

  root.querySelector('#btn-add-color').onclick = async () => {
    const color = await UI.prompt(I18n.tr('Color'), '');
    if (color == null || !String(color).trim()) return;
    UI.showLoading();
    const res = await Api.index(Api.Q.workDetails, {
      f_id: 0,
      f_task: taskId,
      f_process: process,
      f_color: String(color).trim(),
      f_34: 0, f_36: 0, f_38: 0, f_40: 0, f_42: 0,
      f_44: 0, f_46: 0, f_48: 0, f_50: 0, f_52: 0,
    });
    UI.hideLoading();
    if (res.status !== 1) await UI.alert(res.data);
    else load();
  };
  root.querySelector('#btn-refresh').onclick = load;
  await load();
};

Screens.work_details_done = async function (root, params) {
  const taskId = Number(params[0] || 0);
  const process = Number(params[1] || 0);
  const dailyId = Number(params[2] || 0);
  const name = decodeURIComponent(params[3] || I18n.tr('Execute'));

  root.innerHTML =
    UI.header({ back: 'journal', title: name }) +
    '<main class="shell-body">' +
    '<div class="btn-row">' +
    '<button type="button" class="btn" id="btn-refresh">' + UI.esc(I18n.tr('Refresh')) + '</button>' +
    '<button type="button" class="btn btn-primary" id="btn-commit">' + UI.esc(I18n.tr('Save')) + '</button>' +
    '</div>' +
    '<div class="panel">' +
    '<div class="table-wrap"><table class="data sizes-table" id="tbl"><thead><tr>' +
    '<th>' + UI.esc(I18n.tr('Color')) + '</th>' +
    SIZES.map((s) => '<th class="num">' + s + '</th>').join('') +
    '</tr></thead><tbody></tbody></table></div>' +
    '</div></main>';

  UI.bindShell(root);
  const pending = [];

  async function load() {
    UI.showLoading();
    const res = await Api.index(Api.Q.workDetailsDone, {
      f_task: taskId,
      f_process: process,
      f_dailyid: dailyId,
    });
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      return;
    }
    const rows = res.data || [];
    const tbody = root.querySelector('#tbl tbody');
    tbody.innerHTML = '';
    rows.forEach((r) => {
      const tr = document.createElement('tr');
      let html = '<td>' + UI.esc(r.f_color || '') + '</td>';
      SIZES.forEach((s) => {
        const plan = Number(r['f_' + s + 'p'] || 0);
        const done = Number(r['f_' + s + 'd'] || 0);
        const cur = Number(r['f_' + s + 'c'] || 0);
        const left = plan - done;
        html += '<td class="num' + (cur > 0 ? ' cell-pending' : (done > 0 ? ' cell-done' : '')) + '">' +
          '<button type="button" class="btn" style="min-height:36px;padding:0 6px" ' +
          'data-id="' + UI.escAttr(r.f_id) + '" data-color="' + UI.escAttr(r.f_color) + '" ' +
          'data-size="' + s + '" data-left="' + left + '">' +
          UI.esc(done + '/' + plan) + (cur ? ' +' + cur : '') +
          '</button></td>';
      });
      tr.innerHTML = html;
      tbody.appendChild(tr);
    });

    tbody.querySelectorAll('button[data-size]').forEach((b) => {
      b.onclick = async () => {
        const left = Number(b.dataset.left);
        if (left <= 0) return;
        const qty = 1;
        pending.push({
          f_id: Number(b.dataset.id),
          f_taskid: taskId,
          f_dailyid: dailyId,
          f_color: b.dataset.color,
          f_field: 'f_' + b.dataset.size,
          f_qty: qty,
        });
        b.classList.add('cell-pending');
        UI.toast('+' + qty + ' ' + b.dataset.size);
      };
    });
  }

  root.querySelector('#btn-refresh').onclick = load;
  root.querySelector('#btn-commit').onclick = async () => {
    if (!pending.length) {
      UI.toast(I18n.tr('No data'));
      return;
    }
    UI.showLoading();
    const res = await Api.index(Api.Q.workDetailsUpdateDoneArray2, { arr: pending.splice(0) });
    UI.hideLoading();
    if (res.status !== 1) await UI.alert(res.data);
    else {
      UI.toast(I18n.tr('Ok'));
      load();
    }
  };

  await load();
};
