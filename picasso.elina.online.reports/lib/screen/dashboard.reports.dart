part of 'dashboard.dart';

extension WMDashboardReports on WMDashboard {
  List<Widget> menuWidgetsReports() {
    return [];
  }

  Widget bodyReports() {
    return BlocBuilder<AppBloc, AppState>(
        buildWhen: (p, c) => c is AppStateDashboard,
        builder: (builder, state) {
          if (state is! AppStateDashboard) {
            return Container();
          }
          return Column(
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
              Expanded(
                  child: SingleChildScrollView(
                      child: Column(children: [
                for (final t in state.data['total']) ...[
                  Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Styling.text(
                            '${model.tr('Closed orders')} ${t['f_hallname']}'),
                      ]),
                  Row(children: [
                    Styling.text(model.tr('Number of orders')),
                    Expanded(child: Container()),
                    Styling.textBold(prefs.number(t['f_count']))
                  ]),
                  Row(children: [
                    Styling.text(model.tr('Average check')),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(t['f_amounttotal'] / (t['f_count'] == 0 ? 1 : t['f_count'])))
                  ]),
                  if (t['f_amountcash'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Cash")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountcash']))
                    ]),
                  if (t['f_amountcard'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Card")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountcard']))
                    ]),
                  if (t['f_amountidram'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Idram")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountidram']))
                    ]),
                  if (t['f_amountbank'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Bank")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountbank']))
                    ]),
                  if (t['f_amountother'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Other")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountother']))
                    ]),
                  Row(children: [
                    Styling.textBold(model.tr('Total')),
                    Expanded(child: Container()),
                    Styling.textBold(prefs.number(t['f_amounttotal']))
                  ])
                ],
                Divider(),
                for (final t in state.data['open']) ...[
                  Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Styling.text(
                            '${model.tr('Opened orders')} ${t['f_hallname']}'),
                      ]),
                  Row(children: [
                    Styling.text(model.tr('Number of orders')),
                    Expanded(child: Container()),
                    Styling.textBold(prefs.number(t['f_count']))
                  ]),
                  Row(children: [
                    Styling.text(model.tr('Average check')),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(t['f_amounttotal'] / (t['f_count'] == 0 ? 1 : t['f_count'])))
                  ]),
                  if (t['f_amountcash'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Cash")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountcash']))
                    ]),
                  if (t['f_amountcard'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Card")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountcard']))
                    ]),
                  if (t['f_amountidram'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Idram")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountidram']))
                    ]),
                  if (t['f_amountbank'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Bank")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountbank']))
                    ]),
                  if (t['f_amountother'] > 0)
                    Row(children: [
                      Styling.text(model.tr("Other")),
                      Expanded(child: Container()),
                      Styling.textBold(prefs.number(t['f_amountother']))
                    ]),
                  Row(children: [
                    Styling.textBold(model.tr('Total')),
                    Expanded(child: Container()),
                    Styling.textBold(prefs.number(t['f_amounttotal']))
                  ])
                ],
                Divider(),
                Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Styling.text(
                          model.tr('Grand total of closed orders')),
                    ]),
                Row(children: [
                  Styling.text(model.tr('Number of orders')),
                  Expanded(child: Container()),
                  Styling.textBold(prefs.number(state.data['grandtotal']['f_count']))
                ]),
                Row(children: [
                  Styling.text(model.tr('Average check')),
                  Expanded(child: Container()),
                  Styling.textBold(
                      prefs.number(state.data['grandtotal']['f_amounttotal'] / (state.data['grandtotal']['f_count'] == 0 ? 1 : state.data['grandtotal']['f_count'])))
                ]),
                if (state.data['grandtotal']['f_amountcash'] > 0)
                  Row(children: [
                    Styling.text(model.tr("Cash")),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(state.data['grandtotal']['f_amountcash']))
                  ]),
                if (state.data['grandtotal']['f_amountcard'] > 0)
                  Row(children: [
                    Styling.text(model.tr("Card")),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(state.data['grandtotal']['f_amountcard']))
                  ]),
                if (state.data['grandtotal']['f_amountidram'] > 0)
                  Row(children: [
                    Styling.text(model.tr("Idram")),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(state.data['grandtotal']['f_amountidram']))
                  ]),
                if (state.data['grandtotal']['f_amountbank'] > 0)
                  Row(children: [
                    Styling.text(model.tr("Bank")),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(state.data['grandtotal']['f_amountbank']))
                  ]),
                if (state.data['grandtotal']['f_amountother'] > 0)
                  Row(children: [
                    Styling.text(model.tr("Other")),
                    Expanded(child: Container()),
                    Styling.textBold(
                        prefs.number(state.data['grandtotal']['f_amountother']))
                  ]),
                Row(children: [
                  Styling.textBold(model.tr('Total')),
                  Expanded(child: Container()),
                  Styling.textBold(
                      prefs.number(state.data['grandtotal']['f_amounttotal']))
                ]),
                Divider(),
                        Row(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Styling.text(
                                  model.tr('Grand total of opened orders')),
                            ]),
                        Row(children: [
                          Styling.text(model.tr('Number of orders')),
                          Expanded(child: Container()),
                          Styling.textBold(prefs.number(state.data['grandtotalopen']['f_count']))
                        ]),
                        Row(children: [
                          Styling.text(model.tr('Average check')),
                          Expanded(child: Container()),
                          Styling.textBold(
                              prefs.number(state.data['grandtotalopen']['f_amounttotal'] / (state.data['grandtotalopen']['f_count'] == 0 ? 1 : state.data['grandtotalopen']['f_count'])))
                        ]),
                        if (state.data['grandtotalopen']['f_amountcash'] > 0)
                          Row(children: [
                            Styling.text(model.tr("Cash")),
                            Expanded(child: Container()),
                            Styling.textBold(
                                prefs.number(state.data['grandtotalopen']['f_amountcash']))
                          ]),
                        if (state.data['grandtotalopen']['f_amountcard'] > 0)
                          Row(children: [
                            Styling.text(model.tr("Card")),
                            Expanded(child: Container()),
                            Styling.textBold(
                                prefs.number(state.data['grandtotalopen']['f_amountcard']))
                          ]),
                        if (state.data['grandtotalopen']['f_amountidram'] > 0)
                          Row(children: [
                            Styling.text(model.tr("Idram")),
                            Expanded(child: Container()),
                            Styling.textBold(
                                prefs.number(state.data['grandtotalopen']['f_amountidram']))
                          ]),
                        if (state.data['grandtotalopen']['f_amountbank'] > 0)
                          Row(children: [
                            Styling.text(model.tr("Bank")),
                            Expanded(child: Container()),
                            Styling.textBold(
                                prefs.number(state.data['grandtotalopen']['f_amountbank']))
                          ]),
                        if (state.data['grandtotalopen']['f_amountother'] > 0)
                          Row(children: [
                            Styling.text(model.tr("Other")),
                            Expanded(child: Container()),
                            Styling.textBold(
                                prefs.number(state.data['grandtotalopen']['f_amountother']))
                          ]),
                        Row(children: [
                          Styling.textBold(model.tr('Total')),
                          Expanded(child: Container()),
                          Styling.textBold(
                              prefs.number(state.data['grandtotalopen']['f_amounttotal']))
                        ]),
              ])))
            ],
          );
        });
  }

  void getDashboardReports() {
    BlocProvider.of<AppBloc>(prefs.context())
        .add(AppEventLoading(model.tr('Wait, please'), 'engine/dashboard.php', {
      'mode': 3,
      'date1': prefs.dateMySqlText(_model.date1),
      'date2': prefs.dateMySqlText(_model.date2),
    }, (e, d) {
      if (e) {
        return;
      }
    }, AppStateDashboard(data: _model)));
  }
}
