<template>
  <div class="application-manager">
    <!-- 🔹 Fixed Top Buttons -->
    <button class="back-btn" @click="goBack">← Back to Dashboard</button>
    <button class="logout-btn" @click="logout">Logout</button>

    <h2>Application Management</h2>

    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Application List -->
    <div v-if="applications.length" class="application-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Submission</th>
            <th>Remark</th>
            <th>Created By</th>
            <th>Updated</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in applications" :key="a.id">
            <td>{{ a.id }}</td>
            <td>{{ a.name }}</td>
            <td>
              <span :class="['status', a.status === 1 ? 'draft' : 'submitted']">
                {{ a.status === 1 ? 'Draft' : 'Submitted' }}
              </span>
            </td>
            <td>{{ a.submission ?? '-' }}</td>
            <td>{{ a.remark || '-' }}</td>
            <td>{{ a.created_by ?? '-' }}</td>
            <td>{{ formatDate(a.updated_at) }}</td>
            <td>
              <button class="edit-btn" @click="editApplication(a)">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else class="no-data">No applications found.</p>

    <!-- 🔹 Edit Form -->
    <div v-if="editing" class="edit-form">
      <h3>Edit Application: {{ form.name }}</h3>
      <form @submit.prevent="saveEdit">
        <input v-model="form.name" placeholder="Name" required />
        <textarea
          v-model="form.remark"
          placeholder="Remarks"
          rows="3"
          style="width:100%; padding:8px; border:1px solid #ccc; border-radius:6px;"
        ></textarea>

        <input
          v-model.number="form.submission"
          placeholder="Submission ID (optional)"
          type="number"
        />

        <div class="btn-group">
          <button type="submit" class="save-btn">Save</button>
          <button type="button" class="cancel-btn" @click="cancelEdit">Cancel</button>
        </div>
      </form>

      <p v-if="msg" class="success-msg">{{ msg }}</p>

      <!-- 🔹 Attachments -->
      <h4>Attachments</h4>
      <input type="file" @change="onFile" />
      <ul class="attachments-list">
        <li v-for="att in form.attachments" :key="att.id">
          <span><b>#{{ att.id }}</b> — {{ att.mime }} ({{ att.size }} bytes)</span>
          <div>
            <a :href="downloadUrl(att.id)" target="_blank" class="download-link">Download</a>
            <button class="detach-btn" @click="detach(att.id)">Detach</button>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Applications, Auth } from '../lib/api';
import { useRouter } from 'vue-router';
import dayjs from 'dayjs';

const router = useRouter();

const applications = ref([]);
const editing = ref(false);
const form = ref({});
const msg = ref('');
const loading = ref(false); // ✅ NEW

// 🔹 Logout
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// 🔹 Back to Dashboard
function goBack() {
  router.push('/dashboard');
}

// 🔹 Date format helper
function formatDate(dt) {
  return dt ? dayjs(dt).format('YYYY-MM-DD HH:mm') : '-';
}

// 🔹 Load all applications
async function load() {
  loading.value = true;
  try {
    const res = await Applications.list();
    applications.value = res;
  } catch (err) {
    console.error('Error loading applications:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Edit Application
function editApplication(a) {
  editing.value = true;
  form.value = { ...a };
  msg.value = '';
}

// 🔹 Cancel Edit
function cancelEdit() {
  editing.value = false;
  form.value = {};
  msg.value = '';
}

// 🔹 Save Edited Application
async function saveEdit() {
  loading.value = true;
  try {
    await Applications.update(form.value.id, {
      name: form.value.name,
      remark: form.value.remark,
      submission: form.value.submission,
    });
    msg.value = 'Application updated successfully';
    editing.value = false;
    await load();
  } catch (err) {
    console.error('Error updating application:', err);
    msg.value = 'Failed to update application';
  } finally {
    loading.value = false;
  }
}

// 🔹 Upload Attachment
async function onFile(e) {
  const f = e.target.files[0];
  if (!f) return;
  loading.value = true;
  try {
    await Applications.addAttachment(form.value.id, f);
    msg.value = 'Attachment added successfully';
    await load();
    const updated = applications.value.find(a => a.id === form.value.id);
    form.value.attachments = updated?.attachments || [];
  } catch (err) {
    console.error('Error uploading attachment:', err);
    msg.value = 'Error uploading attachment';
  } finally {
    loading.value = false;
  }
}

// 🔹 Detach Attachment
async function detach(attId) {
  loading.value = true;
  try {
    await Applications.detachAttachment(form.value.id, attId);
    msg.value = 'Attachment detached';
    await load();
    const updated = applications.value.find(a => a.id === form.value.id);
    form.value.attachments = updated?.attachments || [];
  } catch (err) {
    console.error('Error detaching attachment:', err);
    msg.value = 'Error detaching attachment';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.application-manager {
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
  border: 4px solid #eee;
  border-top: 4px solid #1976d2;
  border-radius: 50%;
  width: 48px;
  height: 48px;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}

/* ✅ The rest of your CSS remains unchanged */
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
.back-btn {
  left: 20px;
  background: #1976d2;
}
.back-btn:hover {
  background: #0d47a1;
}
.logout-btn {
  right: 20px;
  background: #f44336;
}
.logout-btn:hover {
  background: #d32f2f;
}
.application-table table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 16px;
}
.application-table th,
.application-table td {
  border: 1px solid #ddd;
  padding: 8px 10px;
  text-align: left;
}
.application-table th {
  background-color: #f3f3f3;
  font-weight: 600;
}
.application-table tr:hover {
  background-color: #fafafa;
}
.status {
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}
.status.draft {
  color: #0277bd;
}
.status.submitted {
  color: #2e7d32;
}
.edit-form {
  margin-top: 24px;
  padding: 16px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: #fafafa;
}
input,
textarea {
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
.attachments-list {
  list-style: none;
  padding: 0;
  margin-top: 10px;
}
.attachments-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f9f9f9;
  padding: 6px 8px;
  border-radius: 6px;
  margin-bottom: 5px;
}
.download-link {
  color: #007bff;
  text-decoration: none;
  margin-right: 8px;
}
.download-link:hover {
  text-decoration: underline;
}
.detach-btn {
  background: #ff9800;
}
.detach-btn:hover {
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
.error {
  color: red;
}
</style>
