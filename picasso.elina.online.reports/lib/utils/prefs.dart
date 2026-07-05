import 'dart:convert';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';

extension Prefs on SharedPreferences {
  static GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
  static final regex = RegExp(r"([.]*0+)(?!.*\d)");
  static Map<String, dynamic> config = {};

  BuildContext context() {
    return navigatorKey.currentContext!;
  }

  String string(String key) {
    return getString(key) ?? '';
  }

  String df(String v) {
    return v.replaceAll(RegExp('r(?!\d[\.\,][1-9]+)0+\$'), '').replaceAll('[\.\,]\$', '');
  }

  String currentDateText() {
    DateTime dt = DateTime.now();
    return DateFormat('dd/MM/yyyy').format(dt);
  }

  DateTime strDate(String s) {
    return DateFormat('yyyy-MM-dd').tryParse(s) ?? DateTime.now();
  }

  String dateText(DateTime dt) {
    return DateFormat('dd/MM/yyyy').format(dt);
  }

  String dateMySqlText(DateTime dt) {
    return DateFormat('yyyy-MM-dd').format(dt);
  }

  DateTime workingDay() {
    return strDate(string('workingday'));
  }

  String number(num? v) {
    v ??= 0;
    var nf = NumberFormat.decimalPatternDigits(locale: 'en_us',
      decimalDigits:0);
    return nf.format(v);
  }

  void init() {
    config.clear();

    var configString = string('config');
    print(configString);
    if (configString.isEmpty) {
      configString = '{}';
    }
    try {
      config = jsonDecode(configString);
    } catch (e) {
      setString('config', '{}');
      config = {'dashboard': ''};
    }
  }
}

late final SharedPreferences prefs;
