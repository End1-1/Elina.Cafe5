<template>
  <div class="layout">
   <SidebarMenu />


    <div class="page">
      <header class="page-header">
        <div class="page-header-row">
          <h1>{{ t('actions.materials') }}</h1>
          <button class="btn primary" @click="openEdit(0)">{{ t('new') }}</button>
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
                <td class="clickable" @click="openEdit(row.f_id)">{{ row.f_id }}</td>
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

    <!-- === Модальное окно редактирования / создания === -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <h2>{{ editId === 0 ? t('actions.new') : t('actions.edit') }}</h2>

        <!-- Поле кода (только чтение) -->
        <div class="form-group" v-if="editId !== 0">
          <label>{{ t('code') }}</label>
          <input v-model="editCode" type="text" class="input" readonly />
        </div>

        <!-- Поле имени -->
        <div class="form-group">
          <label>{{ t('name') }}</label>
          <input v-model="editName" type="text" class="input" />
        </div>

        <div class="modal-buttons">
          
          <button class="btn secondary" @click="closeModal">{{ t('cancel') }}</button>
        </div>

        <div v-if="modalError" class="error">{{ modalError }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { useDataProvider } from '../composables/useDataProvider.js'
import { useI18n } from 'vue-i18n'

const { getData } = useDataProvider()
const { t } = useI18n({ useScope: 'global' })

const data = ref([])
const filter = ref('')
const loading = ref(false)
const error = ref(null)

const showModal = ref(false)
const editId = ref(0)
const editName = ref('')
const editCode = ref('')
const modalError = ref(null)

async function loadData() {
  try {
    loading.value = true
    error.value = null
    const res = await getData('/engine/v2/workshop/materials/list', { filter: filter.value })
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

function openEdit(id) {
  editId.value = id
  editName.value = ''
  editCode.value = ''
  modalError.value = null
  showModal.value = true

  if (id !== 0) {
    getData('/engine/v2/workshop/materials/get', { f_id: id })
      .then(res => {
        editName.value = res.data?.f_name || ''
        editCode.value = res.data?.f_id || ''
      })
      .catch(err => (modalError.value = err.message))
  }
}

function closeModal() {
  showModal.value = false
}

async function saveMaterial() {
  try {
    if (!editName.value.trim()) {
      modalError.value = t('actions.name_required')
      return
    }

    modalError.value = null
    const params = { f_id: editId.value, f_name: editName.value }
    const res = await getData('/engine/v2/workshop/materials/edit', params)

    if (res.error) modalError.value = res.error
    else {
      showModal.value = false
      loadData()
    }
  } catch (err) {
    modalError.value = err.message
  }
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

/* --- Кликабельная ячейка --- */
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
  width: 400px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.modal h2 {
  margin-top: 0;
  font-size: 18px;
  margin-bottom: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-bottom: 15px;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
