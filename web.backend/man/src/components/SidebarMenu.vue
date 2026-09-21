<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import menu from '../MenuItems.js'
import { showConfirm } from '../plugins/confirm.js'

const STORAGE_KEY = 'man-sidebar-collapsed'
const collapsed = ref(false)
const openSubMenu = ref(null)

const router = useRouter()
const { t } = useI18n()

onMounted(() => {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved === '1') {
    collapsed.value = true
  } else if (saved === '0') {
    collapsed.value = false
  } else {
    collapsed.value = window.matchMedia('(max-width: 1024px)').matches
  }
})

function toggleCollapsed() {
  collapsed.value = !collapsed.value
  localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0')
}

function handleClick(item, index = null) {
  if (item.children && index !== null) {
    openSubMenu.value = openSubMenu.value === index ? null : index
    return
  }

  if (item.action === 'logout') {
    showConfirm({
      title: t('logout'),
      message: t('confirmToLogout'),
      onYes: () => {
        localStorage.removeItem('token')
        router.replace('/login')
      },
    })
    return
  }

  if (item.action === 'home' || (!item.action && !item.children)) {
    router.push('/dashboard')
  }
  if (item.action === 'actionsList') router.push('/mfactions')
  if (item.action === 'materialsList') router.push('/mfmaterials')
  if (item.action === 'production') {
    router.push({ path: '/reports', query: { report: 'm-goal-product', showincomplete: '1' } })
  }
}
</script>

<template>
  <aside class="sidebar" :class="{ 'sidebar--collapsed': collapsed }">
    <div class="sidebar-header">
      <button
        type="button"
        class="collapse-btn"
        :title="collapsed ? t('menu.expand') : t('menu.collapse')"
        @click="toggleCollapsed"
      >
        {{ collapsed ? '»' : '«' }}
      </button>
      <span v-if="!collapsed" class="sidebar-title">{{ t('manufacture') }}</span>
    </div>

    <nav class="sidebar-nav">
      <ul>
        <li v-for="(item, index) in menu" :key="item.key">
          <button
            type="button"
            class="menu-btn"
            :title="t(item.key)"
            @click="handleClick(item, index)"
          >
            <span class="menu-icon">{{ item.icon || '▸' }}</span>
            <span v-if="!collapsed" class="menu-label">{{ t(item.key) }}</span>
          </button>

          <ul
            v-if="item.children && openSubMenu === index"
            class="submenu"
          >
            <li
              v-for="child in item.children"
              :key="child.key"
              class="submenu-item"
              :title="t(child.key)"
              @click.stop="handleClick(child)"
            >
              <span class="menu-icon">{{ child.icon || '•' }}</span>
              <span v-if="!collapsed" class="menu-label">{{ t(child.key) }}</span>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  flex: 0 0 250px;
  width: 250px;
  height: 100vh;
  background: #1f2937;
  color: white;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: width 0.2s ease, flex-basis 0.2s ease;
  position: relative;
  z-index: 1;
}

.sidebar--collapsed {
  flex-basis: 64px;
  width: 64px;
}

.sidebar-header {
  min-height: 64px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 10px;
  border-bottom: 1px solid #374151;
  font-weight: bold;
}

.collapse-btn {
  flex: 0 0 auto;
  width: 40px;
  min-width: 40px;
  max-width: 40px;
  height: 40px;
  padding: 0;
  border: none;
  border-radius: 8px;
  background: #374151;
  color: #fff;
  cursor: pointer;
  font-size: 18px;
}

.collapse-btn:hover {
  background: #4b5563;
}

.sidebar-title {
  white-space: nowrap;
  overflow: hidden;
}

.sidebar-nav {
  padding-top: 8px;
  overflow-y: auto;
}

.sidebar-nav ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.menu-btn,
.submenu-item {
  width: 100%;
  max-width: none;
  display: flex;
  align-items: center;
  gap: 10px;
  text-align: left;
  padding: 12px 16px;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  box-sizing: border-box;
}

.sidebar--collapsed .menu-btn,
.sidebar--collapsed .submenu-item {
  justify-content: center;
  padding: 12px 0;
}

.menu-btn:hover,
.submenu-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.menu-icon {
  flex: 0 0 24px;
  width: 24px;
  text-align: center;
  font-size: 18px;
  line-height: 1;
}

.menu-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.submenu {
  padding: 0;
}

.submenu-item {
  color: #ccc;
  padding-left: 28px;
}

.sidebar--collapsed .submenu-item {
  padding-left: 0;
}
</style>
