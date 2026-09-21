<template>
  <div class="layout">
    <SidebarMenu />

    <main class="page">
      <section class="page-body card">
        <header class="page-header">
          <h1>
            {{ t('goal.product') }}
            <span class="title-name">{{ form.f_name }}</span>
          </h1>
          <div class="header-right">
            <input type="date" v-model="form.f_date" class="date-input" />
            <button type="button" class="btn secondary" @click="printPage">
              🖨️ {{ t('actions.print') }}
            </button>
          </div>


        </header>

        <form @submit.prevent="saveProduct" class="form-grid">
          <input type="hidden" v-model="form.f_id" />
          <div class="row-3">
            <label>
              {{ t('goal.status') }}
              <select v-model="form.f_status" required>
                <option v-for="s in statuses" :key="s.f_id" :value="s.f_id">
                  {{ s.f_name }}
                </option>
              </select>
            </label>
          </div>

          <!-- IMAGE UPLOAD -->
          <div class="full-row image-block">

            <!-- скрытый input -->
            <input ref="imageInput" type="file" accept="image/*" class="hidden-file-input" @change="onImageSelected" />

            <!-- Кликабельная рамка -->
            <div class="image-frame clickable" @click="triggerImageSelect">
              <img v-if="previewImage || form.f_image_url" :src="previewImage || form.f_image_url"
                alt="product image" />
              <div v-else class="no-image">
                {{ t('goal.no_image') }}
              </div>
            </div>
          </div>


          <!-- sizes -->
          <div class="full-row">
            <h3>{{ t('goal.sizes') }}</h3>
            <div class="sizes-row">
              <label v-for="size in sizes" :key="size" class="size-checkbox">
                <input type="checkbox" v-model="form['f_' + size]" />
                <span>{{ size }}</span>
              </label>
            </div>
          </div>

          <div class="editor-tabs">
            <button
              v-for="tab in editorTabs"
              :key="tab.id"
              type="button"
              :class="{ active: activeTab === tab.id }"
              @click="activeTab = tab.id"
            >
              {{ tab.title }}
              <span v-if="tab.count" class="tab-count">{{ tab.count }}</span>
            </button>
          </div>

          <div
            v-for="sec in materialSections"
            v-show="activeTab === sec.id"
            :key="sec.id"
            class="related-table card tab-panel"
          >
            <h3 class="flex-between">
              {{ sec.title }}
              <button type="button" class="btn small" @click="openSelectorFor(sec.id, listOf(sec.id).length - 1)">
                ➕ {{ t('add') }}
              </button>
            </h3>

            <div class="section-dims">
              <label>
                {{ t('goal.width') }}
                <input type="number" v-model="form[sec.widthKey]" step="0.01" />
              </label>
              <label>
                {{ t('goal.height') }}
                <input type="number" v-model="form[sec.heightKey]" step="0.01" />
              </label>
            </div>

            <table class="table small">
              <colgroup>
                <col class="hide-col">
                <col class="hide-col">
                <col class="hide-col">
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
              </colgroup>
              <thead>
                <tr>
                  <th>#</th>
                  <th>row</th>
                  <th>ID</th>
                  <th>{{ t('goal.material_code') }}</th>
                  <th :style="{ '--w': '200px' }">{{ t('goal.material_name') }}</th>
                  <th>{{ t('code') }}</th>
                  <th>{{ t('color') }}</th>
                  <th>{{ t('goal.qty1') }}</th>
                  <th>{{ t('goal.qty2') }}</th>
                  <th>{{ t('goal.totalqty') }}</th>
                  <th>{{ t('goal.qty_per_one') }}</th>
                  <th>{{ t('goal.color_qty') }}</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(m, idx) in listOf(sec.id)" :key="idx">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ m.f_row }}</td>
                  <td><input type="text" v-model="m.f_id" readonly /></td>
                  <td><input type="text" v-model="m.f_material" readonly /></td>
                  <td><input type="text" v-model="m.f_materialname" readonly /></td>
                  <td><input type="text" v-model="m.f_code" /></td>
                  <td><input type="text" v-model="m.f_color" /></td>

                  <td><input type="number" v-model.number="m.f_qty1" step="0.01" /></td>
                  <td><input type="number" v-model.number="m.f_qty2" step="0.01" /></td>
                  <td><input type="number" v-model.number="m.f_totalqty" step="0.01" /></td>
                  <td><input type="number" v-model.number="m.f_qtyperone" step="0.01" /></td>
                  <td><input type="number" v-model.number="m.f_colorqty" step="0.01" /></td>

                  <td class="action-buttons">
                    <button type="button" @click="removeFrom(sec.id, idx)">➖</button>
                    <button type="button" @click="openSelectorFor(sec.id, idx)">➕</button>
                  </td>
                </tr>

                <tr v-if="!listOf(sec.id).length">
                  <td colspan="13">{{ t('actions.no_data') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-show="activeTab === 'other'" class="related-table card tab-panel">
            <h3 class="flex-between">
              {{ t('goal.other_table') }}
              <button type="button" class="btn small" @click="openSelectorFor('other', other.length - 1)">
                ➕ {{ t('add') }}
              </button>
            </h3>

            <table class="table small">
              <thead>
                <tr>
                  <th>#</th>
                  <th>row</th>
                  <th>ID</th>
                  <th>{{ t('goal.reason') }}</th>
                  <th>{{ t('goal.material_code') }}</th>
                  <th>{{ t('goal.material_name') }}</th>
                  <th>{{ t('code') }}</th>
                  <th>{{ t('color') }}</th>
                  <th>{{ t('goal.qty1') }}</th>
                  <th>{{ t('goal.qty2') }}</th>
                  <th>{{ t('goal.totalqty') }}</th>
                  <th>{{ t('goal.qty_per_one') }}</th>
                  <th>{{ t('goal.color_qty') }}</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(o, idx) in other" :key="idx">
                  <td>{{ idx + 1 }}</td>
                  <td>{{ o.f_row }}</td>

                  <td><input type="text" v-model="o.f_id" readonly /></td>

                  <td>
                    <select v-model="o.f_reason">
                      <option v-for="r in reasons" :value="r.f_id" :key="r.f_id">
                        {{ r.f_name }}
                      </option>
                    </select>
                  </td>

                  <td><input type="text" v-model="o.f_material" readonly /></td>
                  <td><input type="text" v-model="o.f_materialname" readonly /></td>

                  <td><input type="text" v-model="o.f_code" /></td>
                  <td><input type="text" v-model="o.f_color" /></td>

                  <td><input type="number" v-model.number="o.f_qty1" step="0.01" /></td>
                  <td><input type="number" v-model.number="o.f_qty2" step="0.01" /></td>
                  <td><input type="number" v-model.number="o.f_totalqty" step="0.01" /></td>
                  <td><input type="number" v-model.number="o.f_qtyperone" step="0.01" /></td>
                  <td><input type="number" v-model.number="o.f_colorqty" step="0.01" /></td>

                  <td class="action-buttons">
                    <button type="button" @click="removeFrom('other', idx)">➖</button>
                  </td>
                </tr>

                <tr v-if="!other.length">
                  <td colspan="14">{{ t('actions.no_data') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-show="activeTab === 'totals'" class="related-table card tab-panel">
            <h3>{{ t('goal.tab_totals') }}</h3>
            <table class="table small totals-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ t('goal.material_name') }}</th>
                  <th>{{ t('code') }}</th>
                  <th>{{ t('color') }}</th>
                  <th>{{ t('goal.qty1') }}</th>
                  <th>{{ t('goal.qty2') }}</th>
                  <th>{{ t('goal.totalqty') }}</th>
                  <th>{{ t('goal.qty_per_one') }}</th>
                  <th>{{ t('goal.color_qty') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in totalsRows" :key="idx">
                  <td>{{ idx + 1 }}</td>
                  <td class="name-cell">{{ row.f_materialname }}</td>
                  <td>{{ row.f_code }}</td>
                  <td>{{ row.f_color }}</td>
                  <td>{{ fmtQty(row.f_qty1) }}</td>
                  <td>{{ fmtQty(row.f_qty2) }}</td>
                  <td>{{ fmtQty(row.f_totalqty) }}</td>
                  <td>{{ fmtQty(row.f_qtyperone) }}</td>
                  <td>{{ fmtQty(row.f_colorqty) }}</td>
                </tr>
                <tr v-if="!totalsRows.length">
                  <td colspan="9">{{ t('actions.no_data') }}</td>
                </tr>
                <tr v-else class="totals-sum">
                  <td></td>
                  <td class="name-cell">{{ t('goal.tab_totals') }}</td>
                  <td></td>
                  <td></td>
                  <td>{{ fmtQty(totalsSum.f_qty1) }}</td>
                  <td>{{ fmtQty(totalsSum.f_qty2) }}</td>
                  <td>{{ fmtQty(totalsSum.f_totalqty) }}</td>
                  <td>{{ fmtQty(totalsSum.f_qtyperone) }}</td>
                  <td>{{ fmtQty(totalsSum.f_colorqty) }}</td>
                </tr>
              </tbody>
            </table>
          </div>




          <!-- buttons -->
          <div class="form-actions">
            <button type="submit" class="btn primary">{{ t('save') }}</button>
            <button type="button" class="btn secondary" @click="router.back()">{{ t('cancel') }}</button>
          </div>

          <p v-if="error" class="error">{{ error }}</p>
          <p v-if="success" class="success">{{ t('goal.saved_successfully') }}</p>
        </form>
      </section>
    </main>

    <!-- MATERIAL SELECTOR modal -->
    <div v-if="showSelector" class="modal-backdrop" @click.self="closeSelector">
      <div class="modal modal-large">
        <h2>{{ t('actions.select_materials') }}</h2>

        <div class="filter-group">
          <input type="text" v-model="filter" class="input" :placeholder="t('actions.search_placeholder')"
            @keyup.enter="loadMaterialsList" />
          <button class="btn primary" @click="loadMaterialsList">{{ t('actions.filter') }}</button>
        </div>

        <div class="modal-body">
        <table class="table small">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>{{ t('goal.material_name') }}</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="mat in materialList" :key="mat.f_id">
              <td><input type="checkbox" :value="mat" v-model="selected" /></td>
              <td>{{ mat.f_id }}</td>
              <td>{{ mat.f_materialname }}</td>
            </tr>

            <tr v-if="!materialList.length">
              <td colspan="3">{{ t('actions.no_data') }}</td>
            </tr>
          </tbody>
        </table>
        </div>

        <div class="modal-footer sticky">
    <button class="btn secondary" @click="closeSelector">
      {{ t('cancel') }}
    </button>
    <button class="btn primary" @click="applySelected">
      {{ t('add') }}
    </button>
  </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import SidebarMenu from '../components/SidebarMenu.vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, onMounted, watch } from 'vue'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const sizes = [34, 36, 38, 40, 42, 44, 46]

const form = ref({
  f_id: null,
  f_date: new Date().toISOString().slice(0, 10),
  f_status: 1,
  f_name: '',
  f_width: '',
  f_height: '',
  f_width_doublerin: '',
  f_height_doublerin: '',
  f_width_doublerin_fabric: '',
  f_height_doublerin_fabric: '',
  f_width_lining: '',
  f_height_lining: '',
  f_34: 0, f_36: 0, f_38: 0, f_40: 0, f_42: 0, f_44: 0, f_46: 0,
  f_image_url: null,
})

const materials = ref([])
const doublerin = ref([])
const doublerinFabric = ref([])
const lining = ref([])
const other = ref([])
const statuses = ref([])
const reasons = ref([])
const error = ref('')
const success = ref(false)
const isEdit = ref(false)
const activeTab = ref('materials')

const materialSections = computed(() => [
  { id: 'materials', title: t('goal.materials_table'), widthKey: 'f_width', heightKey: 'f_height' },
  { id: 'doublerin', title: t('goal.tab_doublerin'), widthKey: 'f_width_doublerin', heightKey: 'f_height_doublerin' },
  { id: 'doublerin_fabric', title: t('goal.tab_doublerin_fabric'), widthKey: 'f_width_doublerin_fabric', heightKey: 'f_height_doublerin_fabric' },
  { id: 'lining', title: t('goal.tab_lining'), widthKey: 'f_width_lining', heightKey: 'f_height_lining' },
])

function listRef(id) {
  return {
    materials,
    doublerin,
    doublerin_fabric: doublerinFabric,
    lining,
    other,
  }[id]
}

function listOf(id) {
  return listRef(id)?.value || []
}

const editorTabs = computed(() => [
  ...materialSections.value.map(sec => ({
    id: sec.id,
    title: sec.title,
    count: listOf(sec.id).length,
  })),
  { id: 'other', title: t('goal.other_table'), count: other.value.length },
  { id: 'totals', title: t('goal.tab_totals'), count: 0 },
])

const QTY_FIELDS = ['f_qty1', 'f_qty2', 'f_totalqty', 'f_qtyperone', 'f_colorqty']

const totalsRows = computed(() => {
  const map = new Map()
  const lists = [materials.value, doublerin.value, doublerinFabric.value, lining.value, other.value]
  for (const list of lists) {
    for (const m of list) {
      const key = `${m.f_material}|${m.f_code}|${m.f_color}`
      if (!map.has(key)) {
        map.set(key, {
          f_material: m.f_material,
          f_materialname: m.f_materialname,
          f_code: m.f_code,
          f_color: m.f_color,
          f_qty1: 0,
          f_qty2: 0,
          f_totalqty: 0,
          f_qtyperone: 0,
          f_colorqty: 0,
        })
      }
      const row = map.get(key)
      QTY_FIELDS.forEach(f => {
        row[f] += Number(m[f]) || 0
      })
    }
  }
  return [...map.values()]
})

const totalsSum = computed(() => {
  const sum = { f_qty1: 0, f_qty2: 0, f_totalqty: 0, f_qtyperone: 0, f_colorqty: 0 }
  totalsRows.value.forEach(row => {
    QTY_FIELDS.forEach(f => {
      sum[f] += Number(row[f]) || 0
    })
  })
  return sum
})

function fmtQty(n) {
  return (Number(n) || 0).toFixed(2)
}

const showSelector = ref(false)
const materialList = ref([])
const selected = ref([])
const filter = ref('')
const previewImage = ref(null)
const imageInput = ref(null)




let selectorMode = 'materials'
let insertIndex = 0

function onImageSelected(e) {
  const file = e.target.files[0]
  if (!file) return

  form.value._imageFile = file      // ✔ отправится на сервер
  previewImage.value = URL.createObjectURL(file) // ✔ только preview, не в JSON
}

function triggerImageSelect() {
  imageInput.value?.click()
}

function renumber() {
  ['materials', 'doublerin', 'doublerin_fabric', 'lining', 'other'].forEach(id => {
    listOf(id).forEach((row, i) => { row.f_row = i + 1 })
  })
}

watch(() => form.value.f_height, recalcMaterials)
watch(() => form.value.f_height_doublerin, recalcMaterials)
watch(() => form.value.f_height_doublerin_fabric, recalcMaterials)
watch(() => form.value.f_height_lining, recalcMaterials)
sizes.forEach(size => watch(() => form.value[`f_${size}`], recalcMaterials))
watch(materials, recalcMaterials, { deep: true })
watch(doublerin, recalcMaterials, { deep: true })
watch(doublerinFabric, recalcMaterials, { deep: true })
watch(lining, recalcMaterials, { deep: true })

function parseMaterialName(row) {
  if (!row?.f_materialname) return

  // ищем "123.45"
  const match = row.f_materialname.match(/^(\d{3})\.(\d{2})/)

  if (!match) return

  const [, code, color] = match

  // заполняем ТОЛЬКО если пусто (чтобы не затирать руками введённое)
  if (!row.f_code) row.f_code = code
  if (!row.f_color) row.f_color = color
}


function recalcList(list, height) {
  const checkedCount = sizes.filter(s => form.value[`f_${s}`]).length
  list.forEach(m => {
    const qty2 = parseFloat(m.f_qty2) || 0
    m.f_qtyperone = checkedCount && height ? +(height / checkedCount).toFixed(2) : 0
    m.f_totalqty = qty2 * height
    m.f_colorqty = checkedCount && qty2 ? +(qty2 * checkedCount).toFixed(2) : 0
  })
}

function recalcMaterials() {
  recalcList(materials.value, parseFloat(form.value.f_height) || 0)
  recalcList(doublerin.value, parseFloat(form.value.f_height_doublerin) || 0)
  recalcList(doublerinFabric.value, parseFloat(form.value.f_height_doublerin_fabric) || 0)
  recalcList(lining.value, parseFloat(form.value.f_height_lining) || 0)
}

async function loadStatuses() {
  const res = await fetch('/engine/v2/reports/m-goal-product/status-list', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${localStorage.getItem('token') || ''}`, 'Content-Type': 'application/json' },
    body: '{}',
  })
  const data = await res.json()
  statuses.value = data.data || []
}

async function loadReasons() {
  const res = await fetch('/engine/v2/reports/m-goal-product/ReasonList', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}`, 'Content-Type': 'application/json' },
    body: '{}',
  })
  const data = await res.json()
  reasons.value = data.data || []
}

async function loadProduct(id) {
  const res = await fetch('/engine/v2/reports/m-goal-product/edit', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${localStorage.getItem('token') || ''}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ id }),
  })

  const data = await res.json()

  Object.assign(form.value, data.data)
  form.value.f_image_url = data.data.f_image_url || null

  sizes.forEach(s => (form.value[`f_${s}`] = form.value[`f_${s}`] == 1))

  materials.value = (data.materials || []).map((m, i) => ({ ...m, f_row: m.f_row || i + 1 }))
  doublerin.value = (data.doublerin || []).map((m, i) => ({ ...m, f_row: m.f_row || i + 1 }))
  doublerinFabric.value = (data.doublerin_fabric || []).map((m, i) => ({ ...m, f_row: m.f_row || i + 1 }))
  lining.value = (data.lining || []).map((m, i) => ({ ...m, f_row: m.f_row || i + 1 }))
  other.value = (data.other || []).map((o, i) => ({ ...o, f_row: o.f_row || i + 1 }))
}

function printPage() {
  window.print();
}


function removeFrom(id, i) {
  listOf(id).splice(i, 1)
  renumber()
}


function openSelectorFor(mode, idx) {
  selectorMode = mode
  insertIndex = idx
  selected.value = []
  showSelector.value = true
  loadMaterialsList()
}

function closeSelector() {
  showSelector.value = false
  selected.value = []
}

async function loadMaterialsList() {
  const res = await fetch('/engine/v2/workshop/materials/list', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${localStorage.getItem('token') || ''}`, 'Content-Type': 'application/json' },
    body: JSON.stringify({ filter: filter.value }),
  })

  const data = await res.json()
  materialList.value = data.data || []
}

function applySelected() {
  if (!selected.value.length) return

  const rows = selected.value.map(m => ({
    f_row: 0,
    f_id: 0,
    f_material: m.f_id,
    f_materialname: m.f_materialname,
    f_code: '',
    f_color: '',
    f_qty1: 0,
    f_qty2: 0,
    f_totalqty: 0,
    f_qtyperone: 0,
    f_colorqty: 0,
    f_reason: 1,
    f_reasonname: '',
  }))

  rows.forEach(parseMaterialName)


  listOf(selectorMode).splice(insertIndex + 1, 0, ...rows)

  renumber()
  closeSelector()
}

function normalizeNumbers(obj, fields) {
  fields.forEach(f => {
    if (obj[f] === '' || obj[f] === null || isNaN(obj[f])) {
      obj[f] = 0
    } else {
      obj[f] = Number(obj[f])
    }
  })
}


async function saveProduct() {
  error.value = ''
  success.value = false

  sizes.forEach(s => (form.value[`f_${s}`] = form.value[`f_${s}`] ? 1 : 0))
  renumber()

  ;[materials.value, doublerin.value, doublerinFabric.value, lining.value, other.value].forEach(list => {
    list.forEach(m => {
      normalizeNumbers(m, QTY_FIELDS)
    })
  })

  // Создаём FormData
  const fd = new FormData()

  const { _imageFile, ...formData } = form.value

  // Добавляем JSON (без File-объекта)
  fd.append(
    "data",
    JSON.stringify({
      ...formData,
      f_id: Number(formData.f_id) || 0,
      materials: materials.value,
      doublerin: doublerin.value,
      doublerin_fabric: doublerinFabric.value,
      lining: lining.value,
      other: other.value,
    })
  )

  // Добавляем изображение
  if (_imageFile) {
    fd.append("image", _imageFile)
  }

  // Отправляем FormData
  const res = await fetch('/engine/v2/reports/m-goal-product/put', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token') || ''}`

    },
    body: fd
  })

  const raw = await res.text()
  let data
  try {
    data = JSON.parse(raw)
  } catch (e) {
    error.value = raw.slice(0, 400) || ('HTTP ' + res.status)
    return
  }

  if (!res.ok || data.status !== 1) {
    error.value = data.message || ('HTTP ' + res.status)
    return
  }

  success.value = true
  setTimeout(() => router.back(), 800)
}


onMounted(async () => {
  await loadStatuses()
  await loadReasons()

  const id = route.query.id
  if (id) {
    isEdit.value = true
    await loadProduct(id)
  }
})
</script>

<style src="./pages.css"></style>

<style scoped>
.related-table .table.small:not(.totals-table) td:nth-child(1),
.related-table .table.small:not(.totals-table) th:nth-child(1),
.related-table .table.small:not(.totals-table) td:nth-child(2),
.related-table .table.small:not(.totals-table) th:nth-child(2),
.related-table .table.small:not(.totals-table) td:nth-child(3),
.related-table .table.small:not(.totals-table) th:nth-child(3) {
  display: none;
}

.editor-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 24px;
  border-bottom: 1px solid #dcdfe6;
}

.editor-tabs button {
  border: 1px solid #dcdfe6;
  border-bottom: none;
  background: #f6f7fb;
  padding: 8px 14px;
  border-radius: 8px 8px 0 0;
  cursor: pointer;
  font-size: 14px;
  color: #444;
}

.editor-tabs button.active {
  background: #00a5c4;
  color: #fff;
  border-color: #00a5c4;
}

.tab-count {
  margin-left: 6px;
  font-size: 12px;
  opacity: 0.85;
}

.tab-panel.related-table {
  margin-top: 12px;
}

.totals-table .name-cell {
  text-align: left;
  padding: 0 8px;
}

.totals-sum td {
  font-weight: 700;
  background: #f3f6f8;
}



.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
}

/* слева название */
.title-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* справа — дата + кнопка */
.header-right {
  display: flex;
  align-items: center;
  gap: 15px;
}

/* дата как нормальный инпут */
.date-input {
  padding: 6px 10px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  font-size: 14px;
  height: 34px;
}


.title-name {
  font-weight: 700;
  color: #333;
}

.hidden-file-input {
  display: none;
}

.image-frame.clickable {
  cursor: pointer;
}

.image-frame.clickable:hover {
  border-color: #999;
}



/* Рамка под изображение (17см × 10см) */
.image-frame {
  width: 17cm;
  /* фиксированный размер для печати */
  height: 10cm;
  border: 2px solid #ccc;
  border-radius: 6px;
  margin-top: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  /* обрезаем всё, что выходит за рамку */
  background: #f8f8f8;
}

/* Изображение внутри рамки */
.image-frame img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  /* картинка сохраняет пропорции */
}

/* Если картинки нет */
.no-image {
  color: #888;
  font-size: 14px;
}


.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.related-table {
  margin-top: 30px;
}

.related-table table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #ddd;
}

.related-table th {
  background: #00a5c4;
  color: white;
  font-weight: 600;
}


/* плотная таблица */
.related-table td,
.related-table th {
  height: 32px;
  padding: 0;
  text-align: center;
  vertical-align: middle;
  width: var(--w);
  /* ВАЖНО: выравниваем контент */
}

/* красивые компактные инпуты */
.related-table input {
  height: 24px;
  /* компактная высота */
  line-height: 24px;
  width: 95%;
  /* ограничение, чтобы не расползалось */
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 0 6px;
  margin: auto;
  /* центровка внутри ячейки */
  display: block;
  /* обязательно */
  box-sizing: border-box;
  font-size: 13px;
}


.action-buttons button {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 2px 4px;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 3000;
}

.modal {
  background: white;
  border-radius: 10px;
  padding: 25px;
  width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  margin: 10px 0;
}

/* кнопки всегда снизу */
.modal-footer.sticky {
  position: sticky;
  bottom: 0;
  background: #fff;
  padding: 12px 0 0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  border-top: 1px solid #e0e0e0;
  z-index: 2;
}

.size-checkbox {
  display: flex;
  gap: 5px;
  align-items: center;
  padding: 6px 12px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  background: #f6f7fb;
}

.sizes-row {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap !important;
  gap: 10px !important;
  overflow-x: auto;
  white-space: nowrap;
  align-items: center !important;
}

.row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 15px;
  margin: 10px 0 20px 0;
}

.row-3 label {
  display: flex;
  flex-direction: column;
  font-weight: 500;
  color: #333;
}

.row-3 input,
.row-3 select {
  margin-top: 5px;
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #dcdfe6;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
}

.section-dims {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
  margin: 0 0 16px 0;
  max-width: 480px;
}

.section-dims label {
  display: flex;
  flex-direction: column;
  font-weight: 500;
  color: #333;
}

.related-table .section-dims input {
  height: auto;
  line-height: normal;
  width: 100%;
  padding: 8px 10px;
  margin: 5px 0 0;
  font-size: 14px;
}
</style>

<style>
@media print {

  /* прячем меню */
  .layout>SidebarMenu,
  .layout .sidebar,
  .page-header button,
  .form-actions,
  .modal-backdrop {
    display: none !important;
  }

  .card {
    padding: 0;
  }

  .page-body {
    gap: 0;
  }

  /* разворачиваем страницу на всю ширину */
  .page {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
  }


  /*
  .image-frame {
    width: 17cm !important;
    height: 10cm !important;
    border: 1px solid #000 !important;
    margin-bottom: 10px;
  }

  .image-frame img {
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: contain !important;
  } 
  */


  .image-frame,
  .image-frame img {
    display: none !important;
    visibility: hidden !important;
  }

  /* делаем таблицы читабельными */
  table {
    border-collapse: collapse !important;
    width: 100% !important;
    font-size: 12px !important;
  }

  th {
    border: 1px solid #000 !important;
    padding: 4px !important;
    color: #000 !important;
  }

  td {
    border: 1px solid #000 !important;
    padding: 0px !important;
    color: #000 !important;
  }

  /* убираем цветные фоны — на принтерах они выглядят плохо */
  th {
    background: #e0e0e0 !important;
    color: #000 !important;
  }

  /* фиксируем checkbox-строку */
  .sizes-row {
    flex-wrap: wrap !important;
    overflow: visible !important;
    white-space: normal !important;
  }

  /* убираем кнопки действий в таблицах */
  .action-buttons {
    display: none !important;
  }

  .editor-tabs {
    display: none !important;
  }

  .tab-panel {
    display: block !important;
  }

  /* Убрать кнопки "no data" */
  .btn,
  button {
    display: none !important;
  }

  /* Убрать футер */
  .page-footer {
    display: none !important;
  }
}
</style>
