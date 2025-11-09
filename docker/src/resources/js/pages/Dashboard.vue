<template>
  <div class="dashboard-container">
    <header class="dashboard-header">
      <h2>Dashboard</h2>
      <button class="logout-btn" @click="logout">Logout</button>
    </header>

    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Create User -->
    <section>
      <h3>Create User</h3>
      <form @submit.prevent="createUser" class="form-row">
        <input v-model="u.email" placeholder="email" required />
        <input v-model="u.password" placeholder="password" required type="password" />
        <button type="submit">Create</button>
      </form>
      <p v-if="uMsg" class="msg">{{ uMsg }}</p>

      <!-- 🔹 Show Users -->
      <button class="secondary-btn" @click="fetchUsers">Show Users</button>

      <div v-if="users && users.length" class="table-container">
        <table class="user-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Email</th>
              <th>Role</th>
              <th>Created At</th>
              <th>Created By</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>{{ user.id }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.role_name }}</td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td>{{ user.created_by_email || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-else-if="usersLoaded" class="msg">No users found.</p>
    </section>

    <!-- 🔹 Create Partner -->
    <section>
      <h3>Create Partner</h3>
      <form @submit.prevent="createPartner" class="form-row">
        <input v-model="p.name" placeholder="name" required />
        <input v-model="p.email" placeholder="email" />
        <input v-model="p.contact_person" placeholder="contact person" />
        <input v-model="p.phone" placeholder="phone" />
        <input v-model="p.address" placeholder="address" />
        <button type="submit">Create</button>
      </form>
      <p v-if="pMsg" class="msg">{{ pMsg }}</p>

      <button class="secondary-btn" @click="goPartnerEditor">Manage Partners</button>
    </section>

    <!-- 🔹 Create Application -->
    <section>
      <h3>Create Application</h3>
      <form @submit.prevent="createApp" class="form-row">
        <input v-model="a.name" placeholder="name" required />
        <textarea v-model="a.remark" placeholder="remark" rows="3" class="textarea"></textarea>
        <button type="submit">Create</button>
      </form>
      <p v-if="aMsg" class="msg">{{ aMsg }}</p>

      <button class="secondary-btn" @click="goApplicationManager">Manage Applications</button>
    </section>

    <!-- 🔹 Create Submission -->
    <section>
      <h3>Create Submission</h3>
      <form @submit.prevent="createSub" class="form-row">
        <select v-model.number="s.partner" required>
          <option disabled value="">-- Select Partner --</option>
          <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <button type="submit">Create</button>
      </form>
      <p v-if="sMsg" class="msg">{{ sMsg }}</p>

      <button class="secondary-btn" @click="goSubmissionManager">Manage Submissions</button>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Users, Partners, Applications, Submissions, Auth } from '../lib/api';

const router = useRouter();
const loading = ref(false);

// --- Logout ---
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// --- Navigation ---
function goPartnerEditor() {
  router.push('/partners/edit');
}
function goApplicationManager() {
  router.push('/applications/manage');
}
function goSubmissionManager() {
  router.push('/submissions/manage');
}

// --- Create User ---
const u = ref({ email: '', password: '' });
const uMsg = ref('');
async function createUser() {
  loading.value = true;
  try {
    uMsg.value = '';
    await Users.create(u.value);
    uMsg.value = 'User created';
    u.value = { email: '', password: '' };
    await fetchUsers();
  } catch (err) {
    console.error(err);
    uMsg.value = 'Failed to create user';
  } finally {
    loading.value = false;
  }
}

// --- Manage Users ---
const users = ref([]);
const usersLoaded = ref(false);
async function fetchUsers() {
  loading.value = true;
  try {
    const res = await Users.list();
    users.value = Array.isArray(res) ? res : res.data || [];
    usersLoaded.value = true;
  } catch (err) {
    console.error('Error fetching users:', err);
    users.value = [];
    usersLoaded.value = true;
  } finally {
    loading.value = false;
  }
}

function formatDate(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleString();
}

// --- Create Partner ---
const p = ref({ name: '', email: '', contact_person: '', phone: '', address: '' });
const pMsg = ref('');
async function createPartner() {
  loading.value = true;
  try {
    pMsg.value = '';
    await Partners.create(p.value);
    pMsg.value = 'Partner created';
    p.value = { name: '', email: '', contact_person: '', phone: '', address: '' };
    await loadPartners();
  } catch (err) {
    console.error(err);
    pMsg.value = 'Failed to create partner';
  } finally {
    loading.value = false;
  }
}

// --- Create Application ---
const a = ref({ name: '', remark: '' });
const aMsg = ref('');
async function createApp() {
  loading.value = true;
  try {
    aMsg.value = '';
    await Applications.create(a.value);
    aMsg.value = 'Application created';
    a.value = { name: '', remark: '' };
  } catch (err) {
    console.error(err);
    aMsg.value = 'Failed to create application';
  } finally {
    loading.value = false;
  }
}

// --- Create Submission ---
const s = ref({ partner: '' });
const sMsg = ref('');
const partners = ref([]);

async function createSub() {
  loading.value = true;
  try {
    sMsg.value = '';
    await Submissions.create(s.value);
    sMsg.value = 'Submission created';
    s.value = { partner: '' };
  } catch (err) {
    console.error(err);
    sMsg.value = 'Failed to create submission';
  } finally {
    loading.value = false;
  }
}

// --- Load Partner List for Dropdown ---
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

onMounted(loadPartners);
</script>

<style scoped>
.dashboard-container {
  max-width: 900px;
  margin: 20px auto;
  padding: 10px;
  font-family: Arial, sans-serif;
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
  align-items: flex-start;
  padding-top: 120px;
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

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  background: #fff;
  z-index: 1000;
  padding: 10px 0;
  border-bottom: 1px solid #ddd;
}

.logout-btn {
  padding: 6px 12px;
  background: #f44336;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.logout-btn:hover {
  background: #d32f2f;
}

section {
  margin-top: 25px;
  background: #fafafa;
  padding: 15px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

h3 {
  margin-bottom: 10px;
  color: #333;
}

.form-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

input, textarea, select {
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  flex: 1;
  resize: vertical;
}

button {
  padding: 8px 14px;
  border: none;
  background: #007bff;
  color: white;
  border-radius: 6px;
  cursor: pointer;
}
button:hover {
  background: #005fcc;
}
.secondary-btn {
  margin-top: 10px;
  background: #555;
}
.secondary-btn:hover {
  background: #333;
}
.msg {
  color: green;
  margin-top: 8px;
}

.table-container {
  margin-top: 12px;
  overflow-x: auto;
}
.user-table {
  width: 100%;
  border-collapse: collapse;
}
.user-table th,
.user-table td {
  border: 1px solid #ccc;
  padding: 6px 8px;
  text-align: left;
}
.user-table th {
  background-color: #f0f0f0;
}
</style>
