import 'dart:convert';

import 'package:picasso_elina_online_worker/bloc/app_bloc.dart';
import 'package:picasso_elina_online_worker/bloc/question_bloc.dart';
import 'package:picasso_elina_online_worker/screen/dashboard.dart';
import 'package:picasso_elina_online_worker/screen/login.dart';
import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:picasso_elina_online_worker/utils/res.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:url_launcher/url_launcher.dart';

import 'navigation.dart';

part 'model.menu.dart';

class AppStateAppBar extends AppState {
  AppStateAppBar():super(0);
}

class AppEventAppBar extends AppEvent {}

class WMModel {
  final serverTextController = TextEditingController();
  final serverUserTextController = TextEditingController();
  final serverPasswordTextController = TextEditingController();
  final configPinTextController = TextEditingController();

  late final Navigation navigation;

  WMModel() {
    navigation = Navigation(this);
  }

  String tr(String s) {
    return Res.tr[s] ?? s;
  }

  void registerOnServer() {
    prefs.setString('serveraddress', serverTextController.text);
    BlocProvider.of<AppBloc>(Prefs.navigatorKey.currentContext!)
        .add(AppEventLoading(tr('Registering on server'), 'engine/login.php', {
      'method': 1,
      'username': serverUserTextController.text,
      'password': serverPasswordTextController.text
    }, (e, d) {
      if (!e) {
        prefs.setString('serveraddress', serverTextController.text);
        serverPasswordTextController.clear();
        serverUserTextController.clear();
        Navigator.pop(prefs.context(), true);
      }
    }, AppStateFinished(data: null)));
  }

  void loginUsernamePassword() {
    BlocProvider.of<AppBloc>(prefs.context())
        .add(AppEventLoading(tr('Sign in'), 'engine/login.php', {
      "username": serverUserTextController.text,
      "password": serverPasswordTextController.text,
      "method": WMLogin.username_password
    }, (e, d) {
      if (!e) {
        try {
          prefs.setString('sessionkey', d['sessionkey']);
          if (d['config']['f_config'] is Map) {
            prefs.setString('config', jsonEncode(d['config']['f_config']));
          } else {
            prefs.setString('config', d['config']['f_config']);
          }
          prefs.setInt('userid', d['user']['f_id']);
          prefs.init();
        } catch (e) {
          print(e.toString());
        }
        Navigator.pushAndRemoveUntil(
            prefs.context(),
            MaterialPageRoute(builder: (builder) => WMDashboard(model: this)),
            (route) => false);
      }
    }, AppStateFinished(data: null)));
  }

  void passwordSubmitted(String s) {
    loginUsernamePassword();
  }

  void loginPin() {}

  void loginPasswordHash() {
    BlocProvider.of<AppBloc>(prefs.context()).add(AppEventLoading(
        tr('Sign in'), 'engine/login.php', {
      'sessionkey': prefs.string('sessionkey'),
      'method': WMLogin.password_hash
    }, (e, d) {
      if (e) {
        prefs.setBool('stayloggedin', false);
        prefs.setString('sessionkey', '');
        prefs.setInt('userid', d['user']['f_id']);
      } else {
        if (d['config']['f_config'] is String) {
          prefs.setString('config', d['config']['f_config']);
        } else {
          prefs.setString('config', jsonEncode(d['config']['f_config']));
        }
        prefs.init();
        Navigator.pushAndRemoveUntil(
            prefs.context(),
            MaterialPageRoute(builder: (builder) => WMDashboard(model: this)),
            (route) => false);
      }
    }, AppStateFinished(data: null)));
  }

  void closeDialog() {
    BlocProvider.of<AppBloc>(Prefs.navigatorKey.currentContext!)
        .add(AppEvent());
  }

  void closeQuestionDialog() {
    BlocProvider.of<QuestionBloc>(Prefs.navigatorKey.currentContext!)
        .add(QuestionEvent());
  }

  void menuRaise() {
    BlocProvider.of<AppAnimateBloc>(prefs.context())
        .add(AppAnimateEventRaise());
  }

  void downloadLatestVersion() async {
    launchUrl(
        Uri.parse('https://download.picasso.am/'),
        mode: LaunchMode.inAppBrowserView);

  }
}
