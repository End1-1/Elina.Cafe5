part of '../../dashboard.dart';

extension DashboardElineRep on WMDashboard {
  void getDashboardElinaRep() {
    BlocProvider.of<AppBloc>(prefs.context()).add(AppEventLoading(
        model.tr('Wait, please'), 'engine/shop/reports/online-main.php', {
      'version': prefs.getInt('dataversion') ?? 0,
      'report': 'report',
      'hall': _model.hallId,
      'date1': prefs.dateMySqlText(_model.date1),
      'date2': prefs.dateMySqlText(_model.date2),
    }, (e, d) {
      if (e) {
        return;
      }
    }, AppStateDashboard(data: _model)));
  }

  List<Widget> actionsElinaRep() {
    return [];
  }

  List<Widget> menuWidgetsElinarep() {
    return [
      Styling.menuButton2(
          model.navigation.dayEnd, 'finishflag', locale().dayEnd),
    ];
  }

  Widget bodyElinaRep() {
    return BlocBuilder<AppBloc, AppState>(
        buildWhen: (p, c) => c is AppStateDashboard,
        builder: (builder, state) {
          if (state is! AppStateDashboard) {
            return Container();
          }
          _model.halls.clear();
          _model.halls.addAll(state.data['halllist'] ?? []);
          int row = 1;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Styling.columnSpacingWidget(),
              Row(children: [
                Expanded(
                    child: Styling.textFormField(
                        _model.date1Controller, model.tr('Start'),
                        readOnly: true, onTap: _model.changeDate1)),
                Styling.rowSpacingWidget(),
                Expanded(
                    child: Styling.textFormField(
                        _model.date2Controller, model.tr('End'),
                        readOnly: true, onTap: _model.changeDate2)),
              ]),
              const Divider(),
              Row(children: [
                Expanded(
                    child: Text(
                        '${locale().branch}: ${_model.hallName.isEmpty ? locale().all : _model.hallName} ',
                        style: const TextStyle(
                            fontSize: 20, fontWeight: FontWeight.bold))),
                IconButton(
                    onPressed: _selectBranch,
                    icon: Icon(Icons.arrow_right_alt, color: Colors.lightGreen))
              ]),
              Row(children: [
                Text(locale().goods,
                    style: const TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold))
              ]),
              Expanded(
                  child: SingleChildScrollView(
                      child: SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                for (final e in state.data['items'] ?? []) ...[
                                  Row(children: [
                                    SizedBox(
                                        width: 50,
                                        child: Text('${row++}')),
                                    SizedBox(
                                        width: 200,
                                        child: Text(e['f_groupname'])),
                                    Styling.columnSpacingWidget(),
                                    SizedBox(
                                        width: 100,
                                        child: Text('${e['f_qty']}')),
                                    Styling.columnSpacingWidget(),
                                    SizedBox(
                                        width: 150,
                                        child:
                                            Text(prefs.number(e['f_total']))),
                                  ]),
                                ],
                                Row(children: [
                                  SizedBox(
                                      width: 200,
                                      child: Text(locale().total,
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold))),
                                  Styling.columnSpacingWidget(),
                                  SizedBox(
                                      width: 100,
                                      child: Text(
                                          _totalQty(state.data['items'] ?? []),
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold))),
                                  Styling.columnSpacingWidget(),
                                  SizedBox(
                                      width: 150,
                                      child: Text(
                                          _totalAmount(
                                              state.data['items'] ?? []),
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold))),
                                ]),
                                //TOTALS HEADER
                                const SizedBox(height: 30),
                                Row(children: [
                                  Text(locale().amountTotal,
                                      style: const TextStyle(
                                          fontSize: 20,
                                          fontWeight: FontWeight.bold))
                                ]),
                                Row(children: [
                                  SizedBox(
                                      width: 200, child: Text(locale().branch)),
                                  Styling.columnSpacingWidget(),
                                  SizedBox(
                                      width: 100, child: Text(locale().total)),
                                  SizedBox(
                                      width: 100, child: Text(locale().cash)),
                                  SizedBox(
                                      width: 150, child: Text(locale().card)),
                                  SizedBox(
                                      width: 150, child: Text(locale().bank)),
                                  SizedBox(
                                      width: 150, child: Text(locale().fiscal)),
                                  SizedBox(width: 150, child: Text("%")),
                                  SizedBox(
                                      width: 150,
                                      child: Text(locale().prepaid)),
                                  SizedBox(
                                      width: 100,
                                      child: Text(locale().discount)),

                                ]),
                                for (final e in state.data['totals'] ?? []) ...[
                                  Row(children: [
                                    SizedBox(
                                        width: 200, child: Text(e['f_name'])),
                                    Styling.columnSpacingWidget(),
                                    SizedBox(
                                        width: 100,
                                        child: Text(
                                            prefs.number(e['f_amounttotal']))),
                                    SizedBox(
                                        width: 100,
                                        child: Text(
                                            prefs.number(e['f_amountcash']))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(
                                            prefs.number(e['f_amountcard']))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(
                                            prefs.number(e['f_amountbank']))),
                                    SizedBox(
                                        width: 150,
                                        child:
                                            Text(prefs.number(e['f_fiscal']))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(prefs.number(
                                            (e['f_fiscal'] /
                                                (e['f_amounttotal'] < 0.1
                                                    ? 0.1
                                                    : e['f_amounttotal']) *
                                                100)))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(prefs
                                            .number(e['f_amountprepaid']))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(
                                            prefs.number(e['f_disc'] ?? 0))),

                                  ])
                                ],
                                _totalsRow(state.data['totals']),
                                //CASHBOX
                                const SizedBox(height: 30),
                                Row(children: [
                                  Text(locale().cashbox,
                                      style: const TextStyle(
                                          fontSize: 20,
                                          fontWeight: FontWeight.bold))
                                ]),
                                const SizedBox(height: 30),
                                Row(children: [
                                  SizedBox(
                                      width: 200, child: Text(locale().branch)),
                                  Styling.columnSpacingWidget(),
                                  SizedBox(
                                      width: 100,
                                      child: Text(locale().collection)),
                                  SizedBox(
                                      width: 100, child: Text(locale().coin)),
                                  SizedBox(
                                      width: 150,
                                      child: Text(locale().spent)),
                                  SizedBox(
                                      width: 150,
                                      child: Text(locale().coinRemain)),
                                ]),
                                //CASHBOX
                                for (final e
                                    in state.data['cashbox'] ?? []) ...[
                                  Row(children: [
                                    SizedBox(
                                        width: 200,
                                        child: Text(e['f_hallname'])),
                                    Styling.columnSpacingWidget(),
                                    SizedBox(
                                        width: 100,
                                        child: Text(prefs
                                            .number(e['f_handznum'] ?? 0))),
                                    SizedBox(
                                        width: 100,
                                        child: Text(
                                            prefs.number(e['f_kopek'] ?? 0))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(prefs
                                            .number(e['f_spent'] ?? 0))),
                                    SizedBox(
                                        width: 150,
                                        child: Text(prefs
                                            .number(e['f_remainkopek'] ?? 0)))
                                  ]),
                                ],
                                _totalsCashbox(state.data['cashbox'] ?? [])
                              ]))))
            ],
          );
        });
  }

  void _selectBranch() async {
    final result = await showDialog<Map<String, dynamic>>(
      context: prefs.context(),
      builder: (BuildContext context) {
        return AlertDialog(
          title: Text('Select an option'),
          content: SingleChildScrollView(
            child: ListBody(
              children: _model.halls.map((option) {
                return ListTile(
                  title: Text(option['f_name']),
                  onTap: () {
                    Navigator.of(context).pop(option);
                  },
                );
              }).toList(),
            ),
          ),
        );
      },
    );
    if (result != null) {
      _model.hallId = result['f_id'];
      _model.hallName = result['f_name'];
      getDashboard();
    }
  }

  String _totalQty(dynamic data) {
    var t = 0.0;
    for (final e in data ?? []) {
      t += e['f_qty'];
    }
    return prefs.number(t);
  }

  String _totalAmount(dynamic data) {
    var t = 0.0;
    for (final e in data ?? []) {
      t += e['f_total'];
    }
    return prefs.number(t);
  }

  Widget _totalsRow(dynamic data) {
    var total = 0.0,
        cash = 0.0,
        card = 0.0,
    bank = 0.0,
        prepaid = 0.0,
        fiscal = 0.0,
        disc = 0.0;
    for (final e in data) {
      total += e['f_amounttotal'];
      cash += e['f_amountcash'];
      card += e['f_amountcard'];
      bank += e['f_amountbank'];
      fiscal += e['f_fiscal'];
      prepaid += e['f_amountprepaid'];
      disc += e['f_disc'] ?? 0;
    }
    return Row(children: [
      SizedBox(width: 200, child: Text(locale().total)),
      Styling.columnSpacingWidget(),
      SizedBox(
          width: 100,
          child: Text(prefs.number(total),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 100,
          child: Text(prefs.number(cash),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(card),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(bank),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(fiscal),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(fiscal / (total < 0.1 ? 0.1 : total) * 100),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(prepaid),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(disc),
              style: const TextStyle(fontWeight: FontWeight.bold))),
    ]);
  }

  Widget _totalsCashbox(dynamic data) {
    var coll = 0.0,
        coin = 0.0,
        spent = 0.0,
        remainCoin = 0.0;
    for (final e in data) {
      coll += e['f_handznum'] ?? 0;
      coin += e['f_kopek'] ?? 0 ;
      spent += e['f_spent'] ?? 0;
      remainCoin += e['f_remainkopek'] ?? 0;
    }
    return Row(children: [
      SizedBox(width: 200, child: Text(locale().total)),
      Styling.columnSpacingWidget(),
      SizedBox(
          width: 100,
          child: Text(prefs.number(coll),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 100,
          child: Text(prefs.number(coin),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(spent),
              style: const TextStyle(fontWeight: FontWeight.bold))),
      SizedBox(
          width: 150,
          child: Text(prefs.number(remainCoin),
              style: const TextStyle(fontWeight: FontWeight.bold))),

    ]);
  }
}
