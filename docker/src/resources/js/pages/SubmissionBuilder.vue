<template>
  <div class="submission-container">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Fixed Logout Button -->
    <button class="logout-btn" @click="logout">Logout</button>

    <h2>Submission #{{ id }}</h2>

    <div v-if="sub" class="submission-details">
      <p><b>Status:</b> {{ statusText }}</p>

      <p><b>Partner:</b>
        <template v-if="sub.status == 1">
          <select v-model="selectedPartner" @change="updatePartner" class="partner-select">
            <option disabled value="">-- Select Partner --</option>
            <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </template>
        <template v-else>
          {{ sub.partnerRel?.name ?? '-' }}
        </template>
      </p>

      <p><b>Submitted at:</b> {{ sub.submitted_at ?? '-' }}</p>

      <h3>Applications</h3>
      <ul class="application-list">
        <li v-for="a in sub.applications" :key="a.id">
          #{{ a.id }} - {{ a.name }} (status {{ a.status === 1 ? 'Draft' : 'Submitted' }})
        </li>
      </ul>

      <div v-if="sub.status == 1" class="draft-section">
        <h3>Add/Remove Applications (Draft Only)</h3>
        <form @submit.prevent="applyChanges" class="app-form">
          <input v-model="adds" placeholder="add ids (comma)">
          <input v-model="removes" placeholder="remove ids (comma)">
          <button type="submit">Apply</button>
        </form>

        <button @click="doSubmit" class="submit-btn">Submit</button>
      </div>

      <p v-if="msg" class="success-msg">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { Submissions, Partners, Auth } from '../lib/api';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const id = Number(route.params.id);
const sub = ref(null);
const partners = ref([]);
const selectedPartner = ref('');
const msg = ref('');
const adds = ref('');
const removes = ref('');
const loading = ref(false); // 🔄 Spinner control

// 🔹 Logout Function
async function logout() {
  await Auth.logout();
  router.push('/login');
}

const statusText = computed(() => {
  return sub.value?.status === 1 ? 'Draft'
       : sub.value?.status === 2 ? 'Submitted'
       : sub.value?.status;
});

function parseIds(s) {
  return s
    .split(',')
    .map(x => x.trim())
    .filter(Boolean)
    .map(Number);
}

// 🔹 Load Submission
async function load() {
  loading.value = true;
  try {
    sub.value = await Submissions.show(id);
    selectedPartner.value = sub.value?.partnerRel?.id || '';
  } catch (err) {
    console.error('Error loading submission:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Load Partners
async function loadPartners() {
  loading.value = true;
  try {
    partners.value = await Partners.list();
  } catch (err) {
    console.error('Error loading partners:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Update Partner
async function updatePartner() {
  if (!selectedPartner.value) return;
  loading.value = true;
  try {
    await Submissions.update(id, { partner: selectedPartner.value });
    msg.value = 'Partner updated';
    await load();
  } catch (err) {
    console.error('Error updating partner:', err);
    msg.value = 'Failed to update partner';
  } finally {
    loading.value = false;
  }
}

// 🔹 Apply Application Add/Remove Changes
async function applyChanges() {
  loading.value = true;
  try {
    await Submissions.update(id, {
      add_application_ids: parseIds(adds.value),
      remove_application_ids: parseIds(removes.value),
    });
    msg.value = 'Applications updated';
    await load();
  } catch (err) {
    console.error('Error updating applications:', err);
    msg.value = 'Failed to update applications';
  } finally {
    loading.value = false;
  }
}

// 🔹 Submit the Submission
async function doSubmit() {
  loading.value = true;
  try {
    await Submissions.submit(id);
    msg.value = 'Submission submitted successfully';
    await load();
  } catch (err) {
    console.error('Error submitting submission:', err);
    msg.value = 'Failed to submit';
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await loadPartners();
  await load();
});
</script>

<style scoped>
.submission-container {
  max-width: 900px;
  margin: 20px auto;
  padding: 15px;
  position: relative;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* 🔄 Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
}
.spinner {
  border: 4px solid #ccc;
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

/* 🔹 Fixed Logout Button */
.logout-btn {
  position: fixed;
  top: 15px;
  right: 20px;
  padding: 8px 14px;
  background: #f44336;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  z-index: 9999;
}
.logout-btn:hover {
  background: #d32f2f;
}

h2 {
  margin-bottom: 10px;
  color: #222;
}

.submission-details p {
  margin: 6px 0;
}

.partner-select {
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.application-list {
  margin: 8px 0;
  padding: 0;
  list-style: none;
}
.application-list li {
  background: #f8f8f8;
  padding: 6px 8px;
  border-radius: 6px;
  margin-bottom: 4px;
}

.app-form {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 10px;
}

input {
  flex: 1;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

button {
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  background: #007bff;
  color: white;
  cursor: pointer;
}

button:hover {
  background: #005fcc;
}

.submit-btn {
  background: #4caf50;
}

.submit-btn:hover {
  background: #3c8e40;
}

.success-msg {
  color: green;
  margin-top: 8px;
}
</style>
