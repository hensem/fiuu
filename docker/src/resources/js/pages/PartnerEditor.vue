<template>
  <div class="partner-editor">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Fixed Top Buttons -->
    <button class="back-btn" @click="goBack">← Back to Dashboard</button>
    <button class="logout-btn" @click="logout">Logout</button>

    <h2>{{ editing ? 'Edit Partner' : 'Partner Management' }}</h2>

    <!-- 🔹 Partner List -->
    <div v-if="!editing && partners.length" class="partner-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <!--th>Code</th-->
            <th>Name</th>
            <th>Email</th>
            <th>Contact Person</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in partners" :key="p.id">
            <td>{{ p.id }}</td>
            <!--td><code>{{ p.code }}</code></td-->
            <td>{{ p.name }}</td>
            <td>{{ p.email || '-' }}</td>
            <td>{{ p.contact_person || '-' }}</td>
            <td>{{ p.phone || '-' }}</td>
            <td>{{ p.address || '-' }}</td>
            <td>
              <span :class="['status', p.status === 1 ? 'active' : 'inactive']">
                {{ p.status === 1 ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="action-buttons">
              <button class="edit-btn" @click="editPartner(p)">Edit</button>
              <!--button class="copy-btn" @click="copyPartnerLink(p)">Copy Link</button-->
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else-if="!editing" class="no-data">No partners found.</p>

    <!-- 🔹 Edit Form -->
    <div v-if="editing" class="edit-form">
      <h3>Edit Partner: {{ form.name }}</h3>
      <form @submit.prevent="saveEdit">
        <input v-model="form.name" placeholder="Name" required>
        <input v-model="form.email" placeholder="Email">
        <input v-model="form.contact_person" placeholder="Contact Person">
        <input v-model="form.phone" placeholder="Phone">
        <input v-model="form.address" placeholder="Address">
        <select v-model.number="form.status">
          <option :value="1">Active</option>
          <option :value="2">Inactive</option>
        </select>

        <div class="btn-group">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" @click="cancelEdit">Cancel</button>
        </div>
      </form>

      <p v-if="msg" class="success-msg">{{ msg }}</p>
    </div>

    <!-- ✅ Copy confirmation message -->
    <div v-if="copiedMsg" class="copy-toast">{{ copiedMsg }}</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Partners, Auth } from '../lib/api';
import { useRouter } from 'vue-router';

const router = useRouter();
const partners = ref([]);
const editing = ref(false);
const form = ref({});
const msg = ref('');
const loading = ref(false);
const copiedMsg = ref('');

// 🔹 Logout
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// 🔹 Go Back to Dashboard
function goBack() {
  router.push('/dashboard');
}

// 🔹 Load Partner List
async function load() {
  loading.value = true;
  try {
    const res = await Partners.list();
    partners.value = Array.isArray(res) ? res : res.data || [];
  } catch (err) {
    console.error('Error loading partners:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Edit Partner
function editPartner(p) {
  editing.value = true;
  form.value = { ...p };
  msg.value = '';
}

// 🔹 Cancel Edit
function cancelEdit() {
  editing.value = false;
  form.value = {};
  msg.value = '';
}

// 🔹 Save Changes
async function saveEdit() {
  loading.value = true;
  try {
    await Partners.update(form.value.id, form.value);
    msg.value = 'Partner updated successfully';
    editing.value = false;
    await load();
  } catch (err) {
    console.error('Error updating partner:', err);
    msg.value = 'Failed to update partner';
  } finally {
    loading.value = false;
  }
}

// ✅ 🔹 Copy Partner Link
async function copyPartnerLink(p) {
  const link = `${window.location.origin}/public/${encodeURIComponent(p.code)}`;
  try {
    await navigator.clipboard.writeText(link);
    copiedMsg.value = `✅ Link copied: ${link}`;
  } catch (err) {
    console.error('Failed to copy link:', err);
    copiedMsg.value = '❌ Failed to copy link';
  }
  setTimeout(() => (copiedMsg.value = ''), 3000);
}

onMounted(load);
</script>

<style scoped>
/* ✅ Your full CSS preserved exactly */
.partner-editor {
  max-width: 1200px;
  margin: 20px auto;
  padding: 15px;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  position: relative;
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

/* 🔹 Fixed Buttons */
.back-btn,
.logout-btn {
  position: fixed;
  top: 15px;
  padding: 8px 14px;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  z-index: 9999;
}

/* Back button on left */
.back-btn {
  left: 20px;
  background: #1976d2;
}
.back-btn:hover {
  background: #0d47a1;
}

/* Logout button on right */
.logout-btn {
  right: 20px;
  background: #f44336;
}
.logout-btn:hover {
  background: #d32f2f;
}

/* 🔹 Table */
.partner-table table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}

.partner-table th,
.partner-table td {
  border: 1px solid #ddd;
  padding: 8px 10px;
  text-align: left;
}

.partner-table th {
  background-color: #f3f3f3;
  font-weight: 600;
}

.partner-table tr:hover {
  background-color: #fafafa;
}

/* 🔹 Action Buttons */
.action-buttons {
  display: flex;
  gap: 6px;
}

.copy-btn {
  background: #607d8b;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 5px 10px;
  cursor: pointer;
}
.copy-btn:hover {
  background: #455a64;
}

/* 🔹 Status */
.status {
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}
.status.active {
  color: #2e7d32;
}
.status.inactive {
  color: #c62828;
}

/* 🔹 Edit Form */
.edit-form {
  margin-top: 24px;
  padding: 16px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: #fafafa;
}

.edit-form form {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

input,
select {
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
  width: 100%;
  box-sizing: border-box;
}

.btn-group {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}

button {
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  color: white;
}

.save-btn {
  background: #007bff;
}
.save-btn:hover {
  background: #005fcc;
}

.cancel-btn {
  background: #999;
}
.cancel-btn:hover {
  background: #777;
}

.edit-btn {
  background: #ff9800;
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}
.edit-btn:hover {
  background: #e68900;
}

.no-data {
  margin-top: 16px;
  color: #666;
}

.success-msg {
  color: green;
  margin-top: 8px;
}

/* ✅ Copy confirmation toast (top center) */
.copy-toast {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: #323232;
  color: #fff;
  padding: 10px 18px;
  border-radius: 6px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  font-size: 14px;
  animation: fadeInOut 3s ease forwards;
  z-index: 3000;
}

@keyframes fadeInOut {
  0% { opacity: 0; transform: translate(-50%, -10px); }
  10%, 90% { opacity: 1; transform: translate(-50%, 0); }
  100% { opacity: 0; transform: translate(-50%, -10px); }
}
</style>
