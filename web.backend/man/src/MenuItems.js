export default [
  { key: 'menu.home', icon: '🏠', action: 'home' },
  {
    key: 'menu.products', icon: '📦', children: [
      { key: 'menu.materials', action: 'materialsList', icon: '🧱' },
      { key: 'menu.list', action: 'actionsList', icon: '📋' },
      { key: 'menu.production', action: 'production', icon: '🏭' }
    ]
  },
  { key: 'logout', action: 'logout', icon: '🚪' }
]
