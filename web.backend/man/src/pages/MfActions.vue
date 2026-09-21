<template>
  <div class="layout">
    <SidebarMenu />


    <div class="page">
      <header class="page-header">
        <div class="page-header-row">
          <h1>{{ t('actions.groups_title') }}</h1>
        </div>
      </header>

      <section class="page-body">
        <div class="card">
          <div class="filter-group">
            <input
              v-model="filter"
              type="text"
              class="input"
              :placeholder="t('actions.search_placeholder')"
              @keyup.enter="loadData"
            />
            <button class="btn primary" @click="loadData">{{ t('actions.filter') }}</button>
            <button class="btn secondary" @click="resetFilter">{{ t('actions.reset') }}</button>
          </div>
        </div>

        <div class="card">
          <div v-if="loading" class="loading">{{ t('loading_report_data') }}</div>

          <table v-else class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>{{ t('actions.name') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in data" :key="row.f_id">
                <td class="clickable" @click="openView(row.f_id)">
                  {{ row.f_id }}
                </td>
                <td>{{ row.f_name }}</td>
              </tr>
              <tr v-if="!loading && data.length === 0">
                <td colspan="2">{{ t('actions.no_data') }}</td>
              </tr>
            </tbody>
          </table>

          <div v-if="error" class="error">{{ error }}</div>
        </div>
      </section>
    </div>

    <!-- === Модальное окно просмотра === -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal modal-large">
        <h2>{{ t('actions.view_details') }}</h2>

        <div class="form-group">
          <label>ID</label>
          <input v-model="viewId" type="text" class="input" readonly />
        </div>

        <div class="form-group">
          <label>{{ t('actions.name') }}</label>
          <input v-model="viewName" type="text" class="input" readonly />
        </div>

        <!-- === Таблица материалов === -->
        <div class="card materials-card" v-if="materials.length">
          <h3>{{ t('actions.materials_list') }}</h3>
          <table class="table small">
            <thead>
              <tr>
                <th>{{ t('actions.name') }}</th>
                <th>{{ t('code') }}</th>
                <th>{{ t('qty') }}</th>
                <th>{{ t('comment') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in materials" :key="m.f_material">
                <td>{{ m.f_materialname }}</td>
                <td>{{ m.f_code }}</td>
                <td>{{ m.f_qty }}</td>
                <td>{{ m.f_comment }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="no-materials">{{ t('actions.no_materials') }}</div>

        <!-- === Подтверждение запуска === -->
        <div v-if="confirmStart" class="confirm-box">
          <p>{{ t('actions.confirm_start') }}</p>
          <div class="modal-buttons">
            <button class="btn secondary" @click="cancelConfirm">{{ t('cancel') }}</button>
            <button class="btn primary" @click="beginProduction">{{ t('confirm') }}</button>
          </div>
        </div>

        <!-- === Обычные кнопки === -->
        <div class="modal-buttons" v-else>
          <button class="btn secondary" @click="closeModal">{{ t('close') }}</button>
          <button class="btn primary" @click="askConfirm">{{ t('actions.begin_production') }}</button>
        </div>

        <div v-if="modalError" class="error">{{ modalError }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'
import { useDataProvider } from '../composables/useDataProvider.js'
import { useI18n } from 'vue-i18n'

const { getData } = useDataProvider()
const { t } = useI18n({ useScope: 'global' })
const router = useRouter()

const data = ref([])
const filter = ref('')
const loading = ref(false)
const error = ref(null)
const confirmStart = ref(false)

const showModal = ref(false)
const viewId = ref('')
const viewName = ref('')
const materials = ref([])
const modalError = ref(null)

async function loadData() {
  try {
    loading.value = true
    error.value = null
    const res = await getData('/engine/v2/workshop/actions/list', { filter: filter.value })
    data.value = res.data || []
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

function resetFilter() {
  filter.value = ''
  loadData()
}

async function openView(id) {
  try {
    modalError.value = null
    showModal.value = true
    confirmStart.value = false
    viewId.value = ''
    viewName.value = ''
    materials.value = []

    const res = await getData('/engine/v2/workshop/actions/get', { f_id: id })

    if (res.d1) {
      viewId.value = res.d1.f_id || ''
      viewName.value = res.d1.f_name || ''
    }
    materials.value = res.materials || []
  } catch (err) {
    modalError.value = err.message
  }
}

async function beginProduction() {
  try {
    modalError.value = null
    confirmStart.value = false

    if (!viewId.value) {
      modalError.value = t('actions.no_selected_action')
      return
    }

    const res = await getData('/engine/v2/workshop/actions/start', { f_id: viewId.value })
    if (res.error) {
      modalError.value = res.error
      return
    }

    showModal.value = false
    router.push({ path: '/reports', query: { report: 'm-goal-product' } })
  } catch (err) {
    modalError.value = err.message
  }
}

function closeModal() {
  showModal.value = false
  confirmStart.value = false
}

function askConfirm() {
  confirmStart.value = true
}

function cancelConfirm() {
  confirmStart.value = false
}

onMounted(loadData)
</script>


<style src="./pages.css"></style>

<style scoped>
.page-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.clickable {
  cursor: pointer;
  color: #2d5be3;
  transition: color 0.2s, text-decoration 0.2s;
}
.clickable:hover {
  color: #1a46c3;
  text-decoration: underline;
}

/* --- Модалка --- */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.modal {
  background: #fff;
  border-radius: 10px;
  padding: 25px 30px;
  width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.modal-large {
  width: 700px;
}

.modal h2 {
  margin-top: 0;
  font-size: 18px;
  margin-bottom: 15px;
}

.materials-card {
  margin-top: 20px;
}

.table.small th,
.table.small td {
  padding: 6px 10px;
  font-size: 14px;
}

.no-materials {
  margin: 10px 0;
  font-style: italic;
  color: #777;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  margin-top: 15px;
}

.confirm-box {
  margin-top: 20px;
  text-align: center;
  background: #f8f8f8;
  padding: 15px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

.confirm-box p {
  margin-bottom: 15px;
  font-weight: 500;
}
</style>
