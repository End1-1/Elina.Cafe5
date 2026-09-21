<template>
  <div v-if="visible" class="dialog-overlay">
    <div class="dialog-box">
      <h3 class="dialog-title">{{ title }}</h3>
      <p class="dialog-message">{{ message }}</p>

      <div class="dialog-buttons">
        <button class="btn btn-no" @click="cancel">{{ t('no') }}</button>
        <button class="btn btn-yes" @click="confirm">{{ t('yes') }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, getCurrentInstance } from 'vue'

const { proxy } = getCurrentInstance()
const t = proxy?.$t || ((s) => s) // fallback — если нет i18n

const title = ref('')
const message = ref('')
const visible = ref(false)
let onConfirm = null
let onCancel = null

function openDialog({ title: ttl, message: msg, onYes, onNo } = {}) {
  title.value = ttl
  message.value = msg
  onConfirm = onYes || null
  onCancel = onNo || null
  visible.value = true
}

function confirm() {
  visible.value = false
  if (onConfirm) onConfirm()
}

function cancel() {
  visible.value = false
  if (onCancel) onCancel()
}

defineExpose({ openDialog })
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.dialog-box {
  background: white;
  border-radius: 12px;
  padding: 24px;
  max-width: 400px;
  width: 90%;
  text-align: center;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
.dialog-title { font-weight: 700; margin-bottom: 12px; }
.dialog-buttons { display: flex; justify-content: space-between; margin-top: 20px; }
.btn {
  flex: 1;
  margin: 0 8px;
  padding: 10px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.btn-yes { background: #00a5c4; color: white; }
.btn-no { background: #ddd; }
.btn-yes:hover { background: #008fa9; }
.btn-no:hover { background: #ccc; }
</style>
