import 'package:picasso_elina_online_worker/bloc/app_bloc.dart';
import 'package:picasso_elina_online_worker/bloc/question_bloc.dart';
import 'package:picasso_elina_online_worker/main.dart';
import 'package:picasso_elina_online_worker/model/model.dart';
import 'package:picasso_elina_online_worker/screen/config.dart';
import 'package:picasso_elina_online_worker/screen/dashboard.dart';
import 'package:picasso_elina_online_worker/screen/reports/elina/day_end.dart';
import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class Navigation {
  final WMModel model;

  Navigation(this.model);

  Future<void> config() {
    return Navigator.push(prefs.context(),
        MaterialPageRoute(builder: (builder) => WMConfig(model: model)));
  }

  Future<void> onlineRep() {
    hideMenu();
    return Navigator.pushAndRemoveUntil(
        prefs.context(),
        MaterialPageRoute(builder: (builder) => WMDashboard(model: model)),
            (route) => false);
  }

  Future<void> dayEnd() {
    hideMenu();
    return Navigator.push(prefs.context(), MaterialPageRoute(builder: (builder) => WMDayEnd(model: model)));
  }

  Future<void> settings() {
    hideMenu();
    model.serverTextController.text = prefs.string('serveraddress');
    model.serverUserTextController.clear();
    model.serverPasswordTextController.clear();
    return Navigator.push(prefs.context(), MaterialPageRoute(builder: (builder) => WMConfig(model: model)));
  }

  void logout() {
    hideMenu();
    BlocProvider.of<QuestionBloc>(prefs.context()).add(QuestionEventRaise(model.tr('Logout?'), (){
      BlocProvider.of<QuestionBloc>(Prefs.navigatorKey.currentContext!)
          .add(QuestionEvent());
      BlocProvider.of<AppBloc>(prefs.context()).add(AppEventLoading(model.tr('Logout'), 'engine/logout.php', {}, (e, d) {

          prefs.setBool('stayloggedin', false);
          prefs.setString('sessionkey', '');
          Navigator.pushAndRemoveUntil(prefs.context(), MaterialPageRoute(builder: (builder) =>  App()), (route) => false);

      }, AppStateFinished(data: null)));
    }, null));

  }

  void hideMenu() {
    BlocProvider.of<AppAnimateBloc>(prefs.context()).add(AppAnimateEvent());
  }


}
