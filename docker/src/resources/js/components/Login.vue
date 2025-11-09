<template>
  <div class="login-container">
    <div class="login-box">
      <h1>Login</h1>
      <form @submit.prevent="login">
        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="Enter your email" />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" placeholder="Enter your password" />
        </div>

        <button type="submit">Sign In</button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import router from '../router'

const email = ref('')
const password = ref('')
const error = ref('')

const login = async () => {
  try {
    const res = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })
    localStorage.setItem('token', res.data.token)
    router.push('/')
  } catch (err) {
    error.value = 'Invalid login credentials.'
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: linear-gradient(135deg, #0077ff, #00bfff);
  font-family: Arial, sans-serif;
}

.login-box {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  width: 320px;
  text-align: center;
}

.login-box h1 {
  margin-bottom: 1rem;
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
  text-align: left;
}

.form-group label {
  font-size: 0.9rem;
  color: #555;
}

.form-group input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-top: 0.3rem;
}

button {
  width: 100%;
  padding: 0.7rem;
  background-color: #0077ff;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: bold;
}

button:hover {
  background-color: #005fcc;
}

.error {
  margin-top: 0.8rem;
  color: #d00;
  font-size: 0.9rem;
}
</style>
