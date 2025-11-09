<template>
  <div>
    <nav style="padding:10px;border-bottom:1px solid #ddd;display:flex;gap:12px">
      <router-link to="/login">Login</router-link>
      <router-link to="/dashboard">Dashboard</router-link>
      <router-link to="/public/DEMO-CODE">Public (demo)</router-link>
    </nav>
    <router-view />
  </div>
</template>

<script setup>
</script>

<style>
body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
a { text-decoration: none; }
nav a.router-link-active { font-weight: 700; }
</style>
