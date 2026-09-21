<template>
    <div class="login">
        <h1>{{ $t('login') }}</h1>
        <form @submit.prevent="handleLogin" class="login-form">
            <input v-model="username" :placeholder="$t('username')" />
            <input v-model="password" type="password" :placeholder="$t('password')" />
            <button type="submit">{{ $t('signin') }}</button>
        </form>
        <p v-if="error" class="error">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import { useI18n } from 'vue-i18n'

const username = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()
const { t } = useI18n()


const handleLogin = async () => {
    try {
    const res = await api.post('/worker/user-login/login', 
        {
            username: username.value,
            password: password.value,
            nootp: true
        },
        {
            headers: {
                'Content-Type': 'application/json'
            },
            withCredentials: true  
        }
    )
    localStorage.setItem('token', res.data.token)
    if (res.data.nootp == true) {
        router.push('/dashboard')
    } else {
    router.push('/confirmSMS')
    }
} catch (err) {
    console.log(err)
    error.value = t('invalid_login')
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
</style>