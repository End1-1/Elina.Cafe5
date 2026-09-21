<template>
  <div class="login">
    <h1>{{ $t('enter_code_from_sms') }}</h1>

    <form @submit.prevent="handle" class="login-form">
      <input v-model="code" :placeholder="$t('confirmation_code')" />
      <button type="submit">{{ $t('confirm') }}</button>
    </form>

    <p v-if="error" class="error">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '../api'
import { useRouter } from 'vue-router'

const code = ref('')
const error = ref('')
const router = useRouter()

async function handle() {
  if (code.value.trim() === '') {
    error.value = t('code_cannot_be_empty')
  } else {
    try {
      const res = await api.post('/worker/user-login/check-otp',
        {
          code: code.value
        },
        {
          headers: {
            'Content-Type': 'application/json'
          },
          withCredentials: true
        }
      )
      router.push('/dashboard')
    } catch (err) {
      console.log(err);
      error.value = t('invalid_confirmation_code')
    }
  }
}
</script>

<style>
.login {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 250px;
}

.error {
  color: red;
  margin-top: 10px;
}
</style>
