window.Screens = window.Screens || {};

Screens.task = async function (root, params) {
  const taskId = Number(params[0] || 0);
  const isNew = taskId === 0;

  root.innerHTML =
    UI.header({ back: 'home', title: isNew ? I18n.tr('New task') : (I18n.tr('Edit task') + ' #' + taskId) }) +
    '<main class="shell-body">' +
    '<div class="panel">' +
    '<div class="grid-2">' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Product')) + '</label>' +
    '<input list="dl-products" id="product" ' + (isNew ? '' : 'disabled') + ' /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Workshop')) + '</label>' +
    '<input list="dl-workshops" id="workshop" /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Stage')) + '</label>' +
    '<input list="dl-stages" id="stage" /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Qty')) + '</label>' +
    '<input type="number" id="qty" step="1" min="0" /></div>' +
    '</div>' +
    '<div class="grid-2">' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Date created')) + '</label><input id="datec" disabled /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Time created')) + '</label><input id="timec" disabled /></div>' +
    '</div>' +
    '<div class="grid-2">' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Color')) + '</label><input id="note-color" /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Width')) + '</label><input id="note-width" /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Height')) + '</label><input id="note-height" /></div>' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Length')) + '</label><input id="note-length" /></div>' +
    '</div>' +
    '<div class="btn-row">' +
    (isNew
      ? '<button type="button" class="btn btn-primary" id="btn-create">' + UI.esc(I18n.tr('Create')) + '</button>'
      : '<button type="button" class="btn" id="btn-details">' + UI.esc(I18n.tr('Details')) + '</button>') +
    '</div>' +
    '<datalist id="dl-products"></datalist>' +
    '<datalist id="dl-workshops"></datalist>' +
    '<datalist id="dl-stages"></datalist>' +
    '</div>' +
    '<div class="panel' + (isNew ? ' hidden' : '') + '" id="proc-panel">' +
    '<h2 class="panel-title">' + UI.esc(I18n.tr('Process')) + '</h2>' +
    '<div class="table-wrap"><table class="data" id="proc"><thead><tr>' +
    '<th>' + UI.esc(I18n.tr('Stage')) + '</th><th>' + UI.esc(I18n.tr('Process')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Price')) + '</th><th class="num">' + UI.esc(I18n.tr('Goal')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Done')) + '</th><th class="num">%</th>' +
    '</tr></thead><tbody></tbody></table></div>' +
    '</div></main>';

  UI.bindShell(root);

  let products = [];
  let workshops = [];
  let stages = [];
  let productId = 0;
  let workshopId = 0;
  let stageId = 0;

  function fillList(id, arr) {
    const dl = root.querySelector(id);
    dl.innerHTML = arr.map((x) => '<option value="' + UI.escAttr(x.f_name) + '"></option>').join('');
  }

  function findByName(arr, name) {
    name = String(name || '').trim().toLowerCase();
    return arr.find((x) => String(x.f_name).toLowerCase() === name) || null;
  }

  UI.showLoading();
  const lists = await Api.request('rwlist', {});
  UI.hideLoading();
  if (lists.status !== 1) {
    await UI.alert(lists.data);
    return;
  }
  products = lists.data.product || [];
  workshops = lists.data.workshop || [];
  stages = lists.data.stages || [];
  fillList('#dl-products', products);
  fillList('#dl-workshops', workshops);
  fillList('#dl-stages', stages);

  async function loadTask(id) {
    UI.showLoading();
    const res = await Api.request('rwloadtask', { id: id });
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      return;
    }
    const t = res.data.task || {};
    root.querySelector('#product').value = t.f_productname || '';
    root.querySelector('#qty').value = t.f_qty || '';
    root.querySelector('#datec').value = t.f_datecreate || '';
    root.querySelector('#timec').value = t.f_timecreate || '';
    workshopId = Number(t.f_workshop || 0);
    stageId = Number(t.f_stage || 0);
    const w = workshops.find((x) => Number(x.f_id) === workshopId);
    const s = stages.find((x) => Number(x.f_id) === stageId);
    root.querySelector('#workshop').value = w ? w.f_name : '';
    root.querySelector('#stage').value = s ? s.f_name : '';
    try {
      const notes = JSON.parse(t.f_notes || t.notes || '{}');
      root.querySelector('#note-color').value = notes[I18n.tr('Color')] || notes.Color || '';
      root.querySelector('#note-width').value = notes[I18n.tr('Width')] || notes.Width || '';
      root.querySelector('#note-height').value = notes[I18n.tr('Height')] || notes.Height || '';
      root.querySelector('#note-length').value = notes[I18n.tr('Length')] || notes.Length || '';
    } catch (e) { /* ignore */ }

    const tbody = root.querySelector('#proc tbody');
    tbody.innerHTML = '';
    (res.data.description || []).forEach((row) => {
      const tr = document.createElement('tr');
      const keys = Object.keys(row);
      // columns from SQL: f_process, stage name, action name, duration, price, goal, done, %
      const vals = Object.values(row);
      tr.innerHTML =
        '<td>' + UI.esc(vals[1]) + '</td>' +
        '<td>' + UI.esc(vals[2]) + '</td>' +
        '<td class="num">' + UI.esc(vals[4]) + '</td>' +
        '<td class="num">' + UI.esc(vals[5]) + '</td>' +
        '<td class="num">' + UI.esc(vals[6]) + '</td>' +
        '<td class="num">' + UI.esc(typeof vals[7] === 'number' ? vals[7].toFixed(0) : vals[7]) + '</td>';
      tbody.appendChild(tr);
    });
  }

  if (!isNew) {
    await loadTask(taskId);
  }

  const createBtn = root.querySelector('#btn-create');
  if (createBtn) {
    createBtn.onclick = async () => {
      const p = findByName(products, root.querySelector('#product').value);
      const w = findByName(workshops, root.querySelector('#workshop').value);
      const s = findByName(stages, root.querySelector('#stage').value);
      const qty = Number(root.querySelector('#qty').value);
      if (!p) return UI.alert(I18n.tr('Product is not selected'));
      if (!w) return UI.alert(I18n.tr('Workshop is not selected'));
      if (!qty || qty < 0.1) return UI.alert(I18n.tr('Input right quantity'));
      UI.showLoading();
      const res = await Api.request('rwcreatetask', {
        productid: p.f_id,
        qty: qty,
        workshopid: w.f_id,
        stage: s ? s.f_id : 0,
      });
      UI.hideLoading();
      if (res.status !== 1) {
        await UI.alert(res.data);
        return;
      }
      const id = res.data.id || res.data.f_id;
      UI.toast('#' + id);
      Router.go('task/' + id);
    };
  }

  const detailsBtn = root.querySelector('#btn-details');
  if (detailsBtn) {
    detailsBtn.onclick = () => Router.go('work-details/' + taskId + '/0/0');
  }
};
