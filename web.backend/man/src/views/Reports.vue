<template>
  <div class="layout">
    <SidebarMenu />

    <main class="content">
      <section class="report-box">
        <header class="report-header" v-if="reportData">
          <h1>{{ reportData.widget?.title }}</h1>
          <button class="btn-new" @click="router.push('/goal/new')">
            {{ t('report.new') }}
          </button>
        </header>

        <div v-if="loading" class="loading">{{ t('report.loading') }}...</div>

        <div v-else-if="error" class="error-box">
          <p>{{ error }}</p>
          <button @click="loadReport" class="retry-btn">{{ t('report.retry') }}</button>
        </div>

        <div v-else-if="reportData" class="report-content">
          <table class="report-table" v-if="reportData.rows && reportData.cols">
            <thead>
              <tr>
                <th
                  v-for="(col, c) in reportData.cols"
                  :key="c"
                  v-show="!reportData.hiddencols?.includes(c)"
                >
                  {{ col }}
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-if="reportData.rows.length === 0">
                <td :colspan="reportData.cols.length" class="no-data">
                  {{ t('report.no_data') }}
                </td>
              </tr>

              <tr
                v-for="(row, r) in reportData.rows"
                :key="r"
              >
                <td
                  v-for="(cell, c) in row"
                  :key="c"
                  v-show="!reportData.hiddencols?.includes(c)"
                  :class="{ clickable: c === 0 }"
                  @click="c === 0 && editRecord(cell)"
                >
                  {{ cell }}
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="reportData.debug" class="debug">
            <strong>DEBUG SQL:</strong>
            <pre>{{ reportData.debug }}</pre>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import SidebarMenu from '../components/SidebarMenu.vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ref, onMounted, computed, watch } from 'vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const reportName = computed(() => route.query.report)
const bodyParams = computed(() => {
  const { report, ...rest } = route.query
  return normalizeParams(rest)
})

const loading = ref(false)
const error = ref('')
const reportData = ref(null)

function normalizeParams(params) {
  const out = {}

  for (const [key, value] of Object.entries(params)) {
    if (value === 'true') out[key] = true
    else if (value === 'false') out[key] = false
    else if (value === '1') out[key] = true
    else if (value === '0') out[key] = false
    else if (!isNaN(value)) out[key] = Number(value)
    else out[key] = value
  }

  return out
}


async function loadReport() {
  loading.value = true
  error.value = ''
  reportData.value = null

  try {
    const res = await fetch(`/engine/v2/reports/${reportName.value}/get`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token') || ''}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(bodyParams.value)
    })

    const text = await res.text()
    let data = null
    try {
      data = JSON.parse(text)
    } catch {}

    if (!res.ok || !data || data.status !== 1) {
      const msg =
        (data && (data.error || data.message)) ||
        text ||
        `Server responded with ${res.status}`
      throw new Error(msg)
    }

    reportData.value = data
  } catch (err) {
    console.error('Report load error:', err)
    error.value = err.message || t('report.failed')
  } finally {
    loading.value = false
  }
}

watch(
  () => route.query,
  loadReport,
  { immediate: true }
)

// 👉 переход в режим редактирования
function editRecord(id) {
  if (!id) return
  router.push(`/goal/edit?id=${id}`)
}

onMounted(loadReport)
</script>

<style scoped>
.layout {
  display: flex;
  height: 100vh;
  background-color: #f4f5f8;
}

.content {
  flex: 1;
  min-width: 0;
  overflow: auto;
  padding: 40px;
}

.report-box {
  max-width: 1000px;
  margin: 0 auto;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
  padding: 40px;
}

/* Заголовок и кнопка New */
.report-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.btn-new {
  background: #00a5c4;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-new:hover { background: #008ca5; }

.report-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
.report-table th,
.report-table td {
  border: 1px solid #e0e0e0;
  padding: 8px 12px;
  text-align: left;
  font-size: 14px;
}
.report-table th {
  background: #00a5c4;
  color: white;
  font-weight: 600;
}

/* первая колонка кликабельна */
.clickable {
  color: #007b9e;
  cursor: pointer;
  font-weight: 600;
  transition: color 0.2s;
}
.clickable:hover {
  color: #004e63;
  text-decoration: underline;
}

.no-data {
  text-align: center;
  color: #777;
  padding: 20px 0;
}

.debug {
  margin-top: 20px;
  padding: 12px;
  background: #f5f5f5;
  font-size: 12px;
  color: #444;
  border-radius: 6px;
  font-family: monospace;
}
</style>
