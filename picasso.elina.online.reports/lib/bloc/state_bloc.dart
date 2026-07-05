part of 'app_bloc.dart';

class AppState extends Equatable {
  int id = 0;
  AppState(this.id);
  @override
  List<Object?> get props => [id];
}

class AppStateError extends AppState {
  final String text;

  AppStateError(this.text) : super(0);
}

class AppStateFinished extends AppState {
  dynamic data;
  AppStateFinished({required this.data}) : super(0);
}

class InitAppState extends Equatable {
  @override
  List<Object?> get props => [];
}

class InitAppStateLoading extends InitAppState {}

class InitAppStateFinished extends InitAppState {
  final bool error;
  final String errorText;
  final dynamic data;
  InitAppStateFinished(this.error, this.errorText, this.data);
}

class AppAnimateStateIdle extends Equatable {
  @override
  List<Object?> get props => [];
}

class AppAnimateStateRaise extends AppAnimateStateIdle{}

class AppAnimateShowMenu extends AppAnimateStateIdle {}

class AppStateDayEnd extends AppStateFinished {
  AppStateDayEnd({required super.data});
  @override
  List<Object?> get props => [id, data];
}
