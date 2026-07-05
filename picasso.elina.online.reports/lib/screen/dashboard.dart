import 'package:picasso_elina_online_worker/bloc/app_bloc.dart';
import 'package:picasso_elina_online_worker/bloc/question_bloc.dart';
import 'package:picasso_elina_online_worker/model/model.dart';
import 'package:picasso_elina_online_worker/utils/calendar.dart';
import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:picasso_elina_online_worker/utils/styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';

import 'app.dart';

part 'dashboard.model.dart';


part 'dashboard.reports.dart';


part 'reports/elina/dashboard.elinarep.dart';

class WMDashboard extends WMApp {
  final _model = DashboardModel();

  WMDashboard({super.key, required super.model}) {
    getDashboard();
  }

  @override
  Widget? leadingButton(BuildContext context) {
    return null;
  }

  @override
  String titleText() {
    return Prefs.config['first_page_title'] ?? 'Picasso';
  }

  @override
  List<Widget> actions() {
    return [
      IconButton(onPressed: getDashboard, icon: const Icon(Icons.refresh)),

      IconButton(onPressed: model.menuRaise, icon: const Icon(Icons.menu))
    ];
  }

  @override
  List<Widget> menuWidgets() {
    final defaultButtons = [
      Styling.menuButton(
          model.navigation.settings, 'config', model.tr('Configuration')),
      Styling.menuButton(model.navigation.logout, 'logout', model.tr('Logout')),
    ];

    return menuWidgetsElinarep()..addAll(defaultButtons);
  }

  @override
  Widget body() {
    return bodyElinaRep();
  }
}
