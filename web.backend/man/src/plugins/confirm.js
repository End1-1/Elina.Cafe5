import { createApp, h } from 'vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { i18n } from '../i18n.js'  // <-- вот тут исправлено

let instance = null

function createConfirmInstance() {
  const container = document.createElement('div')
  document.body.appendChild(container)

  const app = createApp({
    render() {
      return h(ConfirmDialog, {
        ref: (el) => { instance = el }
      })
    }
  })

  app.use(i18n) // теперь корректно
  app.mount(container)
}

export function showConfirm(options) {
  if (!instance) createConfirmInstance()
  instance.openDialog(options)
}
