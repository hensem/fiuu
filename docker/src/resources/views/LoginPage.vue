<template>
  <div class="flex flex-col items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
      <h1 class="text-2xl font-bold mb-4 text-center">Login</h1>
      <form @submit.prevent="login">
        <div class="mb-4">
          <label class="block mb-1 font-semibold">Email</label>
          <input v-model="email" type="email" class="w-full border rounded px-3 py-2" required />
        </div>
        <div class="mb-4">
          <label class="block mb-1 font-semibold">Password</label>
          <input v-model="password" type="password" class="w-full border rounded px-3 py-2" required />
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
          Login
        </button>
      </form>
      <p v-if="error" class="text-red-600 text-sm mt-3 text-center">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const email = ref('');
const password = ref('');
const error = ref('');

async function login() {
  try {
    const response = await axios.post('/login', { email: email.value, password: password.value });
    localStorage.setItem('token', response.data.token);
    router.push('/');
  } catch (err) {
    error.value = 'Invalid credentials';
  }
}
</script>
