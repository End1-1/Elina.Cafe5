import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:picasso_elina_online_worker/utils/styles.dart';
import 'package:flutter/material.dart';

import 'app.dart';

class WMConfig extends WMApp {
  WMConfig({super.key, required super.model});

  @override
  Widget body() {
    return SingleChildScrollView(child:  Column(children: [
      Styling.columnSpacingWidget(),
      Row(
        children: [
          Expanded(
              child: Styling.textFormField(
                  model.serverTextController, model.tr('Server address'))),
        ],
      ),
      Styling.columnSpacingWidget(),
      Row(children: [
        Expanded(
            child: Styling.textFormField(
                model.serverUserTextController, model.tr('Server user')))
      ]),
      Styling.columnSpacingWidget(),
      Row(children: [
        Expanded(
            child: Styling.textFormField(model.serverPasswordTextController,
                model.tr('Server password')))
      ]),
      Styling.columnSpacingWidget(),
      Row(
        crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
        Styling.textButton(model.registerOnServer, model.tr('Register on server'))
      ]),
      Row(children: [
        Expanded(child: Styling.textCenter(prefs.string('appversion')))
      ],),
      Row(
          mainAxisAlignment:
          MainAxisAlignment.center,
          children: [
            Styling.textButton(model.downloadLatestVersion, model.tr('Download latest version'))
          ]
      ),
    ]));
  }
}
