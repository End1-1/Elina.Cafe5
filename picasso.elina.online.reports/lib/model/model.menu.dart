part of 'model.dart';

class Menu {
  static final Menu _menu = Menu._internal();

  factory Menu() {
    return _menu;
  }

  Menu._internal();

  final menu = {};

  void build(dynamic d) {
    menu.clear();
    for (final k in d['d_menu'].keys) {
      final o = d['d_menu'][k];
      if (!menu.containsKey(o['f_menuname'])) {
        menu[o['f_menuname']] = {};
      }
      final m = menu[o['f_menuname']];
      if (!m.containsKey(o['f_part1name'])) {
        m[o['f_part1name']] = {};
      }
      final p1 = m[o['f_part1name']];
      if (!p1.containsKey(o['f_part2name'])) {
        p1[o['f_part2name']] = []..add(o);
      }
      final p2 = p1[o['f_part2name']];
      p2.add(o);
    }
    print(menu);
  }

  List<dynamic> part1() {
    final l = [];
    for (final k in menu.keys) {
      l.addAll(menu[k].keys);
    }
    return l.toSet().toList();
  }

  List<dynamic> part2(String menufilter, String part1filter) {
    final l = [];
    if (part1filter.isEmpty) {
      for (final k in menu[menufilter].keys) {
        l.addAll(menu[menufilter][k].keys);
      }
    } else {
      l.addAll(menu[menufilter][part1filter].keys);
    }
    return l;
  }

  List<dynamic> dishes(String menufilter, String part2filter) {
    final m = menu[menufilter];
    for (final k in m.keys) {
      if (m[k].containsKey(part2filter)) {
        return m[k][part2filter];
      }
    }
    return [];
  }
}
