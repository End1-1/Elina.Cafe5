import 'package:picasso_elina_online_worker/bloc/app_bloc.dart';
import 'package:picasso_elina_online_worker/screen/app.dart';
import 'package:picasso_elina_online_worker/utils/calendar.dart';
import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:picasso_elina_online_worker/utils/styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class WMDayEnd extends WMApp {
  final _dateController = TextEditingController();
  var _date = DateTime.now();

  WMDayEnd({super.key, required super.model}) {
    _dateController.text = prefs.dateMySqlText(_date);
    _refresh();
  }

  @override
  Widget body() {
    const ts = const TextStyle(fontSize: 20);
    return Column(children: [
      Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [Text(locale().dayEnd)]),
      Styling.rowSpacingWidget(),
      Row(children: [
        Expanded(
            child: Styling.textFormField(_dateController, locale().date,
                readOnly: true, onTap: _changeDate)),
      ]),
      Expanded(child: SingleChildScrollView(
          child: BlocBuilder<AppBloc, AppState>(builder: (builder, state) {
        if (state is AppStateDayEnd) {
          return  SizedBox(
            width: MediaQuery.sizeOf(prefs.context()).width - 10,
              child:  SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child:  Column(children: [
              Row(mainAxisAlignment: MainAxisAlignment.start, children: [
            SizedBox(width: 140, child: Text(model.tr('Shop'), style: ts)),

            SizedBox(width: 140, child:Text( model.tr('Previouse'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Income'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Other income'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Sale'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Output'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Discount'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Final'), style: ts)),
            SizedBox(width: 140, child:Text( model.tr('Check'), style: ts)),
            ]),
            for (final e in state.data['report'] ?? []) ...[
              Row(mainAxisAlignment: MainAxisAlignment.start, children: [
                SizedBox(width: 140, child: Text(e['f_name'], style: ts)),

            SizedBox(width: 140, child:Text( prefs.number(e['f_prevday']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_income']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_inputother']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_sale']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_output']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_discount']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_final']), style: ts)),
            SizedBox(width: 140, child:Text( prefs.number(e['f_check']), style: ts)),
              ])
            ]
          ])));
        }
        return Container();
      })))
    ]);
  }

  @override
  List<Widget> actions() {
    return [IconButton(onPressed: _refresh, icon: const Icon(Icons.refresh)),
      IconButton(onPressed: model.menuRaise, icon: const Icon(Icons.menu))];
  }

  @override
  List<Widget> menuWidgets() {
    final defaultButtons = [
      Styling.menuButton2(model.navigation.onlineRep, 'finishflag', model.tr('Report')),
      Styling.menuButton(
          model.navigation.settings, 'config', model.tr('Configuration')),
      Styling.menuButton(model.navigation.logout, 'logout', model.tr('Logout')),
    ];

    return defaultButtons;
  }

  void _changeDate() {
    Calendar.show(
            firstDate: _date.add(const Duration(days: -365)),
            currentDate: _date)
        .then((value) {
      if (value != null) {
        _date = value;
        _dateController.text = prefs.dateMySqlText(_date);
        _refresh();
      }
    });
  }

  void _refresh() {
    BlocProvider.of<AppBloc>(prefs.context()).add(AppEventLoading2(
        '/engine/shop/reports/day-end.php',
        {'date': prefs.dateMySqlText(_date), 'action': 'get'},
        AppStateDayEnd(data: null)));
  }
}
