<template>
  <div class="submission-manager">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Fixed Top Buttons (consistent layout) -->
    <button class="back-btn" @click="goBack">← Back to Dashboard</button>
    <button class="logout-btn" @click="logout">Logout</button>

    <h2>Submission Management</h2>

    <!-- 🔹 Submission List -->
    <div v-if="!editing && submissions.length" class="submission-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Partner</th>
            <th>Status</th>
            <th>Submitted At</th>
            <th>Applications</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in submissions" :key="s.id">
            <td>{{ s.id }}</td>
            <td>{{ s.partnerRel?.name || '-' }}</td>
            <td>
              <span :class="['status', s.status === 1 ? 'draft' : 'submitted']">
                {{ s.status === 1 ? 'Draft' : 'Submitted' }}
              </span>
            </td>
            <td>{{ formatDate(s.submitted_at) }}</td>
            <td>{{ s.applications?.length || 0 }}</td>
            <td>
              <button class="edit-btn" @click="editSubmission(s)">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else-if="!editing" class="no-data">No submissions found.</p>

    <!-- 🔹 Edit Submission -->
    <div v-if="editing" class="edit-form">
      <h3>Edit Submission #{{ form.id }}</h3>
      <p><b>Status:</b> {{ form.status === 1 ? 'Draft' : 'Submitted' }}</p>

      <!-- Partner (editable only if draft) -->
      <div v-if="form.status === 1" class="field">
        <label>Partner</label>
        <select v-model="selectedPartner">
          <option disabled value="">-- Select Partner --</option>
          <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
        <button class="save-btn small" @click="updatePartner">Update Partner</button>
      </div>
      <p v-else><b>Partner:</b> {{ form.partnerRel?.name || '-' }}</p>

      <!-- Applications -->
      <h4>Applications</h4>
      <ul class="app-list">
        <li v-for="a in form.applications" :key="a.id">
          #{{ a.id }} - {{ a.name }} ({{ a.status === 1 ? 'Draft' : 'Submitted' }})
        </li>
      </ul>

      <div v-if="form.status === 1" class="edit-apps">
        <h5>Modify Applications</h5>
        <form @submit.prevent="applyChanges">
          <input v-model="adds" placeholder="Add IDs (comma-separated)" />
          <input v-model="removes" placeholder="Remove IDs (comma-separated)" />
          <button type="submit" class="save-btn small">Apply</button>
        </form>
      </div>

      <!-- Submit Button -->
      <div v-if="form.status === 1">
        <button class="submit-btn" @click="submitSubmission">Submit Submission</button>
      </div>

      <div class="btn-group">
        <button class="cancel-btn" @click="cancelEdit">Back</button>
      </div>

      <p v-if="msg" class="success-msg">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Submissions, Partners, Auth } from '../lib/api';
import { useRouter } from 'vue-router';

const router = useRouter();

const submissions = ref([]);
const partners = ref([]);
const editing = ref(false);
const form = ref({});
const msg = ref('');
const adds = ref('');
const removes = ref('');
const selectedPartner = ref('');
const loading = ref(false);

// 🔹 Logout
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// 🔹 Go back
function goBack() {
  router.push('/dashboard');
}

// 🔹 Load all submissions
async function load() {
  loading.value = true;
  try {
    const res = await Submissions.list();
    submissions.value = Array.isArray(res) ? res : res.data || [];
  } catch (err) {
    console.error('❌ Error loading submissions:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Load partners
async function loadPartners() {
  try {
    partners.value = await Partners.list();
  } catch (err) {
    console.error('❌ Error loading partners:', err);
  }
}

// 🔹 Edit submission
function editSubmission(s) {
  editing.value = true;
  form.value = { ...s };
  selectedPartner.value = s.partnerRel?.id || '';
  msg.value = '';
  adds.value = '';
  removes.value = '';
}

// 🔹 Cancel edit
function cancelEdit() {
  editing.value = false;
  form.value = {};
  msg.value = '';
}

// 🔹 Update partner
async function updatePartner() {
  if (!selectedPartner.value) return;
  loading.value = true;
  try {
    await Submissions.update(form.value.id, { partner: selectedPartner.value });
    msg.value = 'Partner updated successfully';
    await reloadForm();
  } catch (err) {
    console.error('❌ Error updating partner:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Add/Remove applications
async function applyChanges() {
  loading.value = true;
  try {
    const addIds = parseIds(adds.value);
    const removeIds = parseIds(removes.value);
    await Submissions.update(form.value.id, {
      add_application_ids: addIds,
      remove_application_ids: removeIds,
    });
    msg.value = 'Applications updated successfully';
    await reloadForm();
  } catch (err) {
    console.error('❌ Error updating applications:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Submit submission
async function submitSubmission() {
  loading.value = true;
  try {
    await Submissions.submit(form.value.id);
    msg.value = 'Submission successfully submitted!';
    await reloadForm();
  } catch (err) {
    console.error('❌ Error submitting submission:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Reload updated data
async function reloadForm() {
  try {
    form.value = await Submissions.show(form.value.id);
  } catch (err) {
    console.error('❌ Error reloading form:', err);
  }
}

// 🔹 Helpers
function parseIds(s) {
  return s.split(',').map(x => x.trim()).filter(Boolean).map(Number);
}
function formatDate(dt) {
  if (!dt) return '-';
  return new Date(dt).toLocaleString('en-GB', {
    year: 'numeric', month: '2-digit', day: '2-digit',
    hour: '2-digit', minute: '2-digit'
  });
}

onMounted(async () => {
  await loadPartners();
  await load();
});
</script>

<style scoped>
.submission-manager {
  max-width: 1100px;
  margin: 20px auto;
  padding: 15px;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  position: relative;
}

/* 🔹 Fixed Buttons (consistent with PartnerEditor) */
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

/* 🔹 Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(255, 255, 255, 0.8);
  display: flex; justify-content: center; align-items: center;
  z-index: 2000;
}
.spinner {
  border: 4px solid #ccc;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  width: 50px; height: 50px;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* 🔹 Table */
.submission-table table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 60px;
}
.submission-table th,
.submission-table td {
  border: 1px solid #ddd;
  padding: 8px 10px;
}
.submission-table th {
  background-color: #f3f3f3;
  font-weight: 600;
}
.submission-table tr:hover { background-color: #fafafa; }

/* 🔹 Status */
.status {
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}
.status.draft { color: #0277bd; }
.status.submitted { color: #2e7d32; }

/* 🔹 Edit Form */
.edit-form {
  margin-top: 70px;
  padding: 15px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: #fafafa;
}
.field { margin-bottom: 10px; }
.field select {
  margin-left: 10px;
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.app-list {
  list-style: none;
  padding: 0;
  margin-top: 6px;
}
.app-list li {
  padding: 4px 8px;
  background: #f8f8f8;
  border-radius: 6px;
  margin-bottom: 5px;
}

.edit-apps input {
  margin: 4px;
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.btn-group { margin-top: 12px; }

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
.submit-btn { background: #4caf50; }
.submit-btn:hover { background: #3c8e40; }
.cancel-btn { background: #999; }
.cancel-btn:hover { background: #777; }
.edit-btn { background: #ff9800; }
.edit-btn:hover { background: #e68900; }

.success-msg {
  color: green;
  margin-top: 8px;
}
.no-data {
  color: #777;
  margin-top: 20px;
  text-align: center;
}
</style>
