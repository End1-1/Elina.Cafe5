window.Screens = window.Screens || {};

Screens.workshops = async function (root) {
  root.innerHTML =
    UI.header({ back: 'home', title: I18n.tr('Workshops') }) +
    '<main class="shell-body">' +
    '<div class="panel">' +
    '<div class="field"><label>' + UI.esc(I18n.tr('Workshop')) + '</label>' +
    '<select id="workshop"><option value="0">—</option></select></div>' +
    '<div class="btn-row"><button type="button" class="btn btn-primary" id="btn-load">' +
    UI.esc(I18n.tr('Refresh')) + '</button></div>' +
    '</div>' +
    '<div class="panel">' +
    '<h2 class="panel-title">' + UI.esc(I18n.tr('Current tasks')) + '</h2>' +
    '<div class="table-wrap"><table class="data" id="tbl"><thead><tr>' +
    '<th>NN</th><th>' + UI.esc(I18n.tr('Product')) + '</th><th>' + UI.esc(I18n.tr('Date')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Qty')) + '</th><th class="num">%</th>' +
    '</tr></thead><tbody></tbody></table></div>' +
    '<div class="empty hidden" id="empty">' + UI.esc(I18n.tr('No data')) + '</div>' +
    '</div></main>';

  UI.bindShell(root);

  UI.showLoading();
  const lists = await Api.request('rwlist', {});
  UI.hideLoading();
  if (lists.status !== 1) {
    await UI.alert(lists.data);
    return;
  }
  const sel = root.querySelector('#workshop');
  (lists.data.workshop || []).forEach((w) => {
    const o = document.createElement('option');
    o.value = w.f_id;
    o.textContent = w.f_name;
    sel.appendChild(o);
  });

  async function load() {
    const id = Number(sel.value);
    if (!id) return;
    UI.showLoading();
    const res = await Api.request('loadworkshop', { workshop: id });
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(res.data);
      return;
    }
    const rows = res.data.r1 || [];
    const tbody = root.querySelector('#tbl tbody');
    tbody.innerHTML = '';
    root.querySelector('#empty').classList.toggle('hidden', rows.length > 0);
    rows.forEach((r) => {
      const tr = document.createElement('tr');
      const tid = r.f_id;
      tr.innerHTML =
        '<td>' + UI.esc(tid) + '</td>' +
        '<td>' + UI.esc(r.afname || '') + '</td>' +
        '<td>' + UI.esc(r.f_date || '') + '</td>' +
        '<td class="num">' + UI.esc(r.f_qty || '') + '</td>' +
        '<td class="num">' + UI.esc(r.f_cmpt || '') + '</td>';
      tr.onclick = () => Router.go('task/' + tid);
      tbody.appendChild(tr);
    });
  }

  root.querySelector('#btn-load').onclick = load;
  sel.onchange = load;
};
