<template>
  <div class="report-table">
    <!-- Toolbar -->
    <div class="toolbar">
      <button @click="openFilter">⚙️</button>
      <button @click="loadData">🔄</button>
      <button @click="clearFilter">🧹</button>
      <button @click="exportExcel">📊</button>
    </div>

    <!-- Filter modal -->
    <div v-if="filterVisible" class="filter-overlay" @click.self="filterVisible = false">
      <div class="filter-box">
        <h3>Фильтр</h3>
        <div v-for="f in filterConfig" :key="f.field" class="filter-field">
          <label>{{ f.title }}</label>
          <input
            v-if="f.type === 'date'"
            type="date"
            v-model="filters[f.field]"
          />
          <input
            v-else
            type="text"
            v-model="filters[f.field]"
          />
        </div>
        <div class="filter-buttons">
          <button @click="applyFilter">Применить</button>
          <button @click="filterVisible = false">Закрыть</button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-if="rows.length" class="table-wrap">
      <table>
        <thead>
          <tr>
            <th
              v-for="(col, index) in columns"
              :key="index"
              v-show="!hiddenCols.includes(index)"
            >
              {{ col }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, rowIndex) in rows"
            :key="rowIndex"
            :style="rowColor(rowIndex)"
          >
            <td
              v-for="(value, colIndex) in row"
              :key="colIndex"
              v-show="!hiddenCols.includes(colIndex)"
            >
              {{ value }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Totals -->
    <div v-if="totals.length" class="totals">
      <strong>Итог:</strong>
      <span v-for="(v, i) in totals" :key="i">{{ v }}</span>
    </div>

    <div v-if="loading" class="loading">Загрузка...</div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useDataProvider } from '@/composables/useDataProvider.js'

const props = defineProps({
  route: { type: String, required: true },
  initParams: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const columns = ref([])
const rows = ref([])
const totals = ref([])
const filterConfig = ref([])
const hiddenCols = ref([])
const rowColors = ref({})
const widgetInfo = ref({})
const filters = reactive({})
const filterVisible = ref(false)

const { getData } = useDataProvider()

async function loadData(extra = {}) {
  loading.value = true
  try {
    const params = { ...props.initParams, ...filters, ...extra }
    const data = await getData(props.route, params)

    widgetInfo.value = data.widget
    columns.value = data.cols || []
    rows.value = data.rows || []
    totals.value = data.sum?.map(Object.values).flat() || []
    filterConfig.value = data.filter || []
    hiddenCols.value = data.hiddencols || []
    rowColors.value = Object.fromEntries(
      (data.rowcolors || []).map(r => [r.row, r.color])
    )
  } catch (e) {
    alert('Ошибка загрузки данных: ' + e)
  } finally {
    loading.value = false
  }
}

function rowColor(rowIndex) {
  if (!rowColors.value[rowIndex]) return {}
  return { background: rowColors.value[rowIndex] }
}

function openFilter() {
  filterVisible.value = true
}

function applyFilter() {
  filterVisible.value = false
  loadData()
}

function clearFilter() {
  Object.keys(filters).forEach(k => (filters[k] = ''))
  loadData()
}

function exportExcel() {
  import('xlsx').then(XLSX => {
    const ws = XLSX.utils.aoa_to_sheet([
      columns.value,
      ...rows.value,
      ['Итого', ...totals.value],
    ])
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Report')
    XLSX.writeFile(wb, `report_${Date.now()}.xlsx`)
  })
}

loadData()
</script>

<style scoped>
.report-table {
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  position: relative;
}

.toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  border: 1px solid #ccc;
  padding: 6px 10px;
  text-align: left;
}

.totals {
  margin-top: 10px;
  font-weight: bold;
}

.loading {
  position: absolute;
  top: 0; left: 0;
  right: 0; bottom: 0;
  background: rgba(255,255,255,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 600;
}

/* Filter modal */
.filter-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.filter-box {
  background: white;
  border-radius: 12px;
  padding: 24px;
  width: 400px;
}
.filter-field {
  margin-bottom: 10px;
}
.filter-buttons {
  display: flex;
  justify-content: space-between;
}
</style>
