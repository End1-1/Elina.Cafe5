import 'package:picasso_elina_online_worker/bloc/app_bloc.dart';
import 'package:picasso_elina_online_worker/bloc/app_cubits.dart';
import 'package:picasso_elina_online_worker/model/model.dart';
import 'package:picasso_elina_online_worker/utils/prefs.dart';
import 'package:picasso_elina_online_worker/utils/styles.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class WMLogin extends StatelessWidget {
  static const int username_password = 1;
  static const int pin = 2;
  static const int password_hash = 3;
  final WMModel model;
  final int mode;

  WMLogin(this.model, this.mode);

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AppBloc, AppState>(builder: (context, state) {
      if (mode == username_password) {
        return Container(
            constraints: const BoxConstraints(maxWidth: 500),
            padding: const EdgeInsets.all(5),
            child: SingleChildScrollView(
                child: Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              mainAxisAlignment: MainAxisAlignment.start,
              children: [
                const Icon(Icons.login_outlined, size: 60, color: Colors.green),
                Styling.text(model.tr('Login')),
                Styling.columnSpacingWidget(),
                Row(children: [
                  Expanded(
                      child: Styling.textFormField(
                          model.serverUserTextController, model.tr('Username')))
                ]),
                Styling.columnSpacingWidget(),
                Row(children: [
                  Expanded(
                      child:  BlocBuilder<AppCubits, int>(builder: (builder, state) {return TextFormField(
                controller: model.serverPasswordTextController,
                obscureText: state &cubIsPasswordShow != 0,
                  onFieldSubmitted: model.passwordSubmitted,
                  style: const TextStyle(color: Colors.black),
                  decoration: InputDecoration(
                      suffix: IconButton(onPressed: (){builder.read<AppCubits>().toggleShowPassword();}, icon: Icon(Icons.remove_red_eye_outlined)),
                      contentPadding: const EdgeInsets.fromLTRB(5, 0, 5, 0),
                      border: const OutlineInputBorder(
                          borderSide: BorderSide(color: Colors.black26)),
                      labelText: model.tr('Password')),
                );}))

                ]),
                Styling.columnSpacingWidget(),
                Row(children: [
                  WMCheckbox(model.tr('Stay in'), (b) {
                    prefs.setBool('stayloggedin', b ?? false);
                  }, prefs.getBool('stayloggedin') ?? false),
                  Expanded(child: Container()),
                  Styling.textButton(
                      model.navigation.settings, model.tr('Configuration'))
                ]),
                Styling.columnSpacingWidget(),
                BlocBuilder<AppLoadingCubit, AppLoadingState>(
                    builder: (builder, state) {
                  if (state == AppLoadingState.loading) {
                    return Column(children: [
                      const SizedBox(
                          height: 30,
                          width: 30,
                          child: CircularProgressIndicator()),
                    ]);
                  } else {
                    return Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Styling.textButton(
                              model.loginUsernamePassword, model.tr('Next'))
                        ]);
                  }
                }),
                if (state is AppStateError) ...[Styling.textError(state.text)]
              ],
            )));
      } else {
        return Container(child: Text('Not implemented'));
      }
    });
  }
}
