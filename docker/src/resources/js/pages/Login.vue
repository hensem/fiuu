<template>
  <div class="login-container">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <div class="login-box">
      <h2>Login</h2>

      <form @submit.prevent="submit">
        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" required placeholder="you@example.com" />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input v-model="password" type="password" required placeholder="••••••••" />
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? "Signing in..." : "Sign In" }}
        </button>

        <p v-if="error" class="error">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Auth } from '../lib/api';
import { useRouter } from 'vue-router';

const router = useRouter();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

// 🔹 Redirect to dashboard if already logged in
onMounted(() => {
  const token = localStorage.getItem('token');
  if (token) {
    router.push('/dashboard');
  }
});

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const result = await Auth.login(email.value, password.value);
    console.log('✅ Login success:', result);
    router.push('/dashboard');
  } catch (e) {
    console.error('❌ Login error:', e);
    error.value = 'Invalid email or password';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100vh;
  background: linear-gradient(135deg, #007bff, #00c6ff);
  font-family: "Segoe UI", Arial, sans-serif;
  position: relative;
}

/* 🔄 Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.75);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
}

.spinner {
  border: 4px solid #ddd;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.login-box {
  background: white;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  width: 350px;
  text-align: center;
  transition: all 0.3s ease;
}

.login-box:hover {
  transform: translateY(-4px);
}

h2 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
  text-align: left;
}

label {
  display: block;
  font-weight: 500;
  color: #555;
  margin-bottom: 0.3rem;
}

input {
  width: 100%;
  padding: 0.7rem;
  border: 1px solid #ccc;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

input:focus {
  border-color: #007bff;
  box-shadow: 0 0 6px rgba(0, 123, 255, 0.3);
  outline: none;
}

button {
  width: 100%;
  background: #007bff;
  color: white;
  padding: 0.8rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  transition: background 0.3s;
}

button:hover {
  background: #005fcc;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  color: red;
  margin-top: 1rem;
  font-size: 0.9rem;
}
</style>
