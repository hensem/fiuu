<template>
  <div class="application-manager">
    <!-- 🔹 Fixed Top Buttons -->
    <button class="back-btn" @click="goBack">← Back to Dashboard</button>
    <button class="logout-btn" @click="logout">Logout</button>

    <h2>{{ editing ? (Number(form.status) === 2 ? 'View Application' : 'Edit Application') : 'Application Management' }}</h2>

    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Application List -->
    <div v-if="!editing && applications.length" class="application-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
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
              <span
                :class="[
                  'status',
                  Number(a.status) === 1 ? 'draft' : 'submitted'
                ]"
              >
                {{ getStatusLabel(a.status) }}
              </span>
            </td>
            <td>{{ a.remark || '-' }}</td>
            <td>{{ a.created_by_email || 'Unknown' }}</td>
            <td>{{ formatDate(a.updated_at) }}</td>
            <td>
              <button
                class="edit-btn"
                @click="editApplication(a)"
                :title="Number(a.status) === 2 ? 'View Application' : 'Edit Application'"
              >
                {{ Number(a.status) === 2 ? 'View' : 'Edit' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else-if="!editing" class="no-data">No applications found.</p>

    <!-- 🔹 Edit / View Form -->
    <div
      v-if="editing"
      class="edit-form"
      :class="{ 'view-mode': Number(form.status) === 2 }"
    >
      <h3>{{ Number(form.status) === 2 ? 'View' : 'Edit' }} Application #{{ form.id }}</h3>

      <p v-if="Number(form.status) === 2" class="warning-msg">
        ⚠️ This application has been submitted and is read-only.
      </p>

      <form @submit.prevent="saveEdit">
        <input
          v-model="form.name"
          placeholder="Name"
          :readonly="Number(form.status) === 2"
          required
        />
        <textarea
          v-model="form.remark"
          placeholder="Remarks"
          rows="3"
          class="remark-box"
          :readonly="Number(form.status) === 2"
        ></textarea>

        <div class="btn-group">
          <button
            v-if="Number(form.status) === 1"
            type="submit"
            class="save-btn"
          >
            Save
          </button>
          <button type="button" class="cancel-btn" @click="cancelEdit">
            {{ Number(form.status) === 2 ? 'Back to Application Management' : 'Cancel' }}
          </button>
        </div>
      </form>

      <p v-if="msg" class="success-msg">{{ msg }}</p>

      <!-- 🔹 Attachments -->
      <h4>Attachments</h4>

      <!-- Upload disabled for submitted -->
      <div v-if="Number(form.status) === 1">
        <input type="file" @change="onFile" />
      </div>

      <ul class="attachments-list">
        <li v-for="att in (form.attachments || [])" :key="att.id">
          <span>
            <b>#{{ att.id }}</b> —
            {{ att.original_name || 'Unnamed file' }}
            ({{ att.size }} bytes)
          </span>
          <div>
            <!-- ✅ Download button always enabled -->
            <button class="download-link" @click.prevent="downloadAttachment(att.id)">
              Download
            </button>

            <!-- 🔒 Detach only in draft -->
            <button
              v-if="Number(form.status) === 1"
              class="detach-btn"
              @click="detach(att.id)"
            >
              Detach
            </button>
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

const router = useRouter();
const applications = ref([]);
const editing = ref(false);
const form = ref({});
const msg = ref('');
const loading = ref(false);

// 🔹 Logout
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// 🔹 Back to Dashboard
function goBack() {
  router.push('/dashboard');
}

// 🔹 Format Date
function formatDate(dt) {
  if (!dt) return '-';
  const d = new Date(dt);
  return d.toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// 🔹 Status Label
function getStatusLabel(status) {
  switch (Number(status)) {
    case 1: return 'Draft';
    case 2: return 'Submitted';
    case 3: return 'Attached';
    default: return 'Unknown';
  }
}

// 🔹 Load Applications
async function load() {
  loading.value = true;
  try {
    const res = await Applications.list();
    applications.value = Array.isArray(res) ? res : (res?.data || []);
  } catch (err) {
    console.error('Error loading applications:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Edit/View
function editApplication(a) {
  editing.value = true;
  form.value = {
    ...a,
    attachments: Array.isArray(a.attachments) ? a.attachments : [],
  };
  msg.value = '';
}

// 🔹 Cancel
function cancelEdit() {
  editing.value = false;
  form.value = {};
  msg.value = '';
}

// 🔹 Save
async function saveEdit() {
  if (Number(form.value.status) !== 1) {
    msg.value = 'Cannot edit submitted application';
    return;
  }
  loading.value = true;
  try {
    await Applications.update(form.value.id, {
      name: form.value.name,
      remark: form.value.remark,
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

// 🔹 Upload
async function onFile(e) {
  const f = e.target.files[0];
  if (!f) return;
  if (Number(form.value.status) !== 1) {
    msg.value = 'Cannot add attachment to submitted application';
    return;
  }
  loading.value = true;
  try {
    await Applications.addAttachment(form.value.id, f);
    msg.value = 'Attachment added successfully';
    await load();
    const updated = applications.value.find(a => String(a.id) === String(form.value.id));
    form.value.attachments = updated?.attachments || [];
  } catch (err) {
    console.error('Error uploading attachment:', err);
    msg.value = 'Error uploading attachment';
  } finally {
    loading.value = false;
  }
}

// 🔹 Download (always allowed)
async function downloadAttachment(attId) {
  loading.value = true;
  try {
    const url = await Applications.getSignedDownload(attId);
    if (url) window.open(url, '_blank');
    else msg.value = 'Failed to generate download link';
  } catch (err) {
    console.error('Error generating signed download URL:', err);
    msg.value = 'Error generating download link';
  } finally {
    loading.value = false;
  }
}

// 🔹 Detach
async function detach(attId) {
  if (Number(form.value.status) !== 1) {
    msg.value = 'Cannot detach attachment from submitted application';
    return;
  }
  loading.value = true;
  try {
    await Applications.detachAttachment(form.value.id, attId);
    msg.value = 'Attachment detached';
    await load();
    const updated = applications.value.find(a => String(a.id) === String(form.value.id));
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
/* ✅ Your CSS preserved exactly */
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
@keyframes spin { to { transform: rotate(360deg); } }
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
.back-btn { left: 20px; background: #1976d2; }
.back-btn:hover { background: #0d47a1; }
.logout-btn { right: 20px; background: #f44336; }
.logout-btn:hover { background: #d32f2f; }
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
.application-table tr:hover { background-color: #fafafa; }
.status {
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}
.status.draft { color: #0277bd; }
.status.submitted { color: #2e7d32; }
.edit-form {
  margin-top: 24px;
  padding: 16px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: #fafafa;
}
.remark-box {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  resize: vertical;
}
.warning-msg {
  color: #b71c1c;
  font-weight: 600;
  margin-bottom: 10px;
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
.save-btn { background: #007bff; }
.save-btn:hover { background: #005fcc; }
.cancel-btn { background: #999; }
.cancel-btn:hover { background: #777; }
.edit-btn {
  background: #ff9800;
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}
.edit-btn:disabled {
  background: #ccc;
  color: #666;
  cursor: not-allowed;
}
.edit-btn:hover:not(:disabled) { background: #e68900; }
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
  background: #2196f3;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 4px 10px;
  cursor: pointer;
}
.download-link:hover { background: #1976d2; }
.detach-btn {
  background: #ff9800;
  margin-left: 6px;
}
.detach-btn:hover { background: #e68900; }
.no-data { margin-top: 16px; color: #666; }
.success-msg { color: green; margin-top: 8px; }
.error { color: red; }

/* ✅ Allow downloads in view mode */
.view-mode {
  opacity: 0.95;
  pointer-events: none;
}
.view-mode .cancel-btn,
.view-mode .download-link {
  pointer-events: all;
  opacity: 1;
}
</style>
