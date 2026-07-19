window.Screens = window.Screens || {};

Screens.home = async function (root) {
  root.innerHTML =
    UI.header({ title: I18n.tr('Current tasks') }) +
    '<main class="shell-body">' +
    '<div class="btn-row">' +
    '<button type="button" class="btn btn-primary" data-go="journal">' + UI.esc(I18n.tr('Journal')) + '</button>' +
    '<button type="button" class="btn" data-go="task/0">' + UI.esc(I18n.tr('New task')) + '</button>' +
    '<button type="button" class="btn" data-go="workshops">' + UI.esc(I18n.tr('Workshops')) + '</button>' +
    '<button type="button" class="btn" id="btn-open">' + UI.esc(I18n.tr('Open')) + '</button>' +
    '<button type="button" class="btn" id="btn-refresh">' + UI.esc(I18n.tr('Refresh')) + '</button>' +
    '</div>' +
    '<div class="panel show-sm-list">' +
    '<h2 class="panel-title">' + UI.esc(I18n.tr('List of task')) + '</h2>' +
    '<div class="table-wrap"><table class="data" id="tbl"><thead><tr>' +
    '<th>NN</th><th>' + UI.esc(I18n.tr('Date')) + '</th><th>' + UI.esc(I18n.tr('Product')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Qty')) + '</th><th class="num">' + UI.esc(I18n.tr('Ready')) + '</th>' +
    '<th class="num">' + UI.esc(I18n.tr('Out')) + '</th><th>' + UI.esc(I18n.tr('Workshop')) + '</th>' +
    '</tr></thead><tbody></tbody></table></div>' +
    '<div class="card-list" id="cards"></div>' +
    '<div class="empty hidden" id="empty">' + UI.esc(I18n.tr('No data')) + '</div>' +
    '</div></main>';

  UI.bindShell(root);
  root.querySelectorAll('[data-go]').forEach((b) => {
    b.onclick = () => Router.go(b.getAttribute('data-go'));
  });

  let selectedId = 0;
  const tbody = root.querySelector('#tbl tbody');
  const cards = root.querySelector('#cards');
  const empty = root.querySelector('#empty');

  function select(id) {
    selectedId = id;
    tbody.querySelectorAll('tr').forEach((tr) => {
      tr.classList.toggle('selected', Number(tr.dataset.id) === id);
    });
    cards.querySelectorAll('.item-card').forEach((c) => {
      c.classList.toggle('selected', Number(c.dataset.id) === id);
    });
  }

  async function load() {
    UI.showLoading();
    const res = await Api.request('rwtasklist', {});
    UI.hideLoading();
    if (res.status !== 1) {
      await UI.alert(typeof res.data === 'string' ? res.data : JSON.stringify(res.data));
      return;
    }
    const rows = Array.isArray(res.data) ? res.data : [];
    tbody.innerHTML = '';
    cards.innerHTML = '';
    empty.classList.toggle('hidden', rows.length > 0);
    rows.forEach((r) => {
      const id = r.NN || r.f_id || r.nn;
      const tr = document.createElement('tr');
      tr.dataset.id = id;
      tr.innerHTML =
        '<td>' + UI.esc(id) + '</td>' +
        '<td>' + UI.esc(r['Օր'] || r.f_date || '') + '</td>' +
        '<td>' + UI.esc(r['Արտադրանք'] || r.f_name || '') + '</td>' +
        '<td class="num">' + UI.esc(r['Քանակ'] || r.f_qty || '') + '</td>' +
        '<td class="num">' + UI.esc(r['Վիճակ'] || r.f_ready || '') + '</td>' +
        '<td class="num">' + UI.esc(r['Ելք'] || r.f_out || '') + '</td>' +
        '<td>' + UI.esc(r['Արտադրամաս'] || '') + '</td>';
      tr.onclick = () => select(Number(id));
      tr.ondblclick = () => Router.go('task/' + id);
      tbody.appendChild(tr);

      const card = document.createElement('div');
      card.className = 'item-card';
      card.dataset.id = id;
      card.innerHTML =
        '<div class="title">#' + UI.esc(id) + ' · ' + UI.esc(r['Արտադրանք'] || '') + '</div>' +
        '<div class="meta">' +
        '<span>' + UI.esc(r['Օր'] || '') + '</span>' +
        '<span>' + UI.esc(I18n.tr('Qty')) + ': ' + UI.esc(r['Քանակ'] || '') + '</span>' +
        '<span>' + UI.esc(r['Արտադրամաս'] || '') + '</span>' +
        '</div>';
      card.onclick = () => select(Number(id));
      card.ondblclick = () => Router.go('task/' + id);
      cards.appendChild(card);
    });
  }

  root.querySelector('#btn-refresh').onclick = load;
  root.querySelector('#btn-open').onclick = () => {
    if (!selectedId) {
      UI.alert(I18n.tr('Select task'));
      return;
    }
    Router.go('task/' + selectedId);
  };

  await load();
};
