<template>
  <div class="submission-manager">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Fixed Top Buttons -->
    <div class="top-buttons">
      <button class="back-btn" @click="goBack">← Back to Dashboard</button>
      <button class="logout-btn" @click="logout">Logout</button>
    </div>

    <!-- ✅ Dynamic Title -->
    <h2>{{ editing ? (form.status === 2 ? 'View Submission' : 'Edit Submission') : 'Submission Management' }}</h2>

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
              <span
                :class="[
                  'status',
                  s.status === 1 ? 'draft' : s.status === 2 ? 'submitted' : 'attached'
                ]"
              >
                {{ getStatusLabel(s.status) }}
              </span>
            </td>
            <td>{{ formatDate(s.submitted_at) }}</td>
            <td>{{ s.applications?.length || 0 }}</td>
            <td class="action-buttons">
              <button
                class="edit-btn"
                @click="editSubmission(s)"
                :title="s.status === 2 ? 'View Submission' : 'Edit Submission'"
              >
                {{ s.status === 2 ? 'View' : 'Edit' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else-if="!editing" class="no-data">No submissions found.</p>

    <!-- 🔹 Edit/View Submission -->
    <div
      v-if="editing"
      class="edit-form"
      :class="{ 'view-mode': form.status === 2 }"
    >
      <h3>
        {{ form.status === 2 ? 'View' : 'Edit' }} Submission #{{ form.id }}
      </h3>
      <p><b>Status:</b> {{ getStatusLabel(form.status) }}</p>

      <p v-if="form.status === 2" class="warning-msg">
        ⚠️ This submission has been submitted and is read-only.
      </p>

      <!-- Partner -->
      <div v-if="form.status === 1" class="field">
        <label>Partner</label>
        <div class="inline-group">
          <select v-model="selectedPartner">
            <option disabled value="">-- Select Partner --</option>
            <option
              v-for="p in partners"
              :key="p.id"
              :value="p.id"
            >
              {{ p.name }}
            </option>
          </select>
          <button class="save-btn small" @click="updatePartner">
            Update Partner
          </button>
        </div>
      </div>
      <p v-else><b>Partner:</b> {{ form.partnerRel?.name || '-' }}</p>

      <!-- Applications -->
      <div class="section">
        <h4>Applications</h4>
        <ul class="app-list">
          <li
            v-for="a in form.applications"
            :key="a.id"
            class="app-item"
          >
            <span>
              #{{ a.id }} - {{ a.name }}
              <span
                :class="['app-status', a.status === 1 ? 'draft' : a.status === 3 ? 'attached' : 'other']"
              >
                ({{ getStatusLabel(a.status) }})
              </span>
            </span>
            <button
              v-if="form.status === 1 && a.status === 3"
              class="remove-btn"
              @click="removeApplication(a)"
              :title="'Detach application ' + a.name"
            >
              Remove
            </button>
          </li>
        </ul>
        <p v-if="!form.applications?.length" class="warning-msg">
          No applications attached yet.
        </p>
      </div>

      <!-- Add new application (Draft only) -->
      <div v-if="form.status === 1" class="section">
        <h5>Add Application</h5>
        <div class="inline-group">
          <select v-model="selectedAppId">
            <option disabled value="">-- Select Draft Application --</option>
            <option
              v-for="a in availableApps"
              :key="a.id"
              :value="a.id"
            >
              {{ a.name }}
            </option>
          </select>
          <button
            class="save-btn small"
            @click="addApplication"
            :disabled="!selectedAppId"
          >
            Add
          </button>
        </div>
      </div>

      <!-- Submit + Back Buttons -->
      <div class="btn-group">
        <button
          v-if="form.status === 1"
          class="submit-btn"
          @click="submitSubmission"
          :disabled="!form.applications || form.applications.length === 0"
        >
          Submit Submission
        </button>
        <button
          class="cancel-btn"
          @click="cancelEdit"
        >
          {{ form.status === 2 ? 'Back to Manage Submission' : 'Back to Manage Submission' }}
        </button>
      </div>

      <p
        v-if="form.status === 1 && (!form.applications || form.applications.length === 0)"
        class="warning-msg"
      >
        ⚠️ You must add at least one application before submitting.
      </p>

      <p v-if="msg" class="success-msg">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Submissions, Partners, Applications, Auth } from '../lib/api';
import { useRouter } from 'vue-router';

const router = useRouter();
const submissions = ref([]);
const partners = ref([]);
const availableApps = ref([]);
const editing = ref(false);
const form = ref({});
const msg = ref('');
const selectedPartner = ref('');
const selectedAppId = ref('');
const loading = ref(false);

// 🔹 Logout
async function logout() {
  await Auth.logout();
  router.push('/login');
}

// 🔹 Go back to dashboard
function goBack() {
  router.push('/dashboard');
}

// 🔹 Load submissions
async function load() {
  loading.value = true;
  try {
    const res = await Submissions.list();
    submissions.value = Array.isArray(res) ? res : res.data || [];
  } catch (err) {
    console.error('Error loading submissions:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Load partners
async function loadPartners() {
  try {
    partners.value = await Partners.list();
  } catch (err) {
    console.error('Error loading partners:', err);
  }
}

// 🔹 Load available apps
async function loadAvailableApps() {
  try {
    const res = await Applications.listDraft();
    const arr = Array.isArray(res) ? res : res.data || [];
    availableApps.value = arr.filter(a => Number(a.status) === 1);
  } catch (err) {
    console.error('Error loading draft applications:', err);
    availableApps.value = [];
  }
}

// 🔹 Edit/View submission
function editSubmission(s) {
  editing.value = true;
  form.value = { ...s };
  selectedPartner.value = s.partnerRel?.id || '';
  msg.value = '';
  selectedAppId.value = '';
  if (s.status === 1) loadAvailableApps();
}

// 🔹 Cancel edit/view
function cancelEdit() {
  editing.value = false;
  form.value = {};
  msg.value = '';
  selectedPartner.value = '';
  selectedAppId.value = '';
  load();
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
    console.error('Error updating partner:', err);
  } finally {
    loading.value = false;
  }
}

// 🔹 Add application
async function addApplication() {
  if (!selectedAppId.value) return;
  loading.value = true;
  try {
    await Submissions.update(form.value.id, { add_application_ids: [selectedAppId.value] });
    msg.value = `Application #${selectedAppId.value} attached.`;
    selectedAppId.value = '';
    await reloadForm();
    await loadAvailableApps();
  } catch (err) {
    console.error('Error adding application:', err);
    msg.value = 'Failed to attach application.';
  } finally {
    loading.value = false;
  }
}

// 🔹 Remove application
async function removeApplication(a) {
  if (Number(a.status) !== 3) {
    msg.value = '❌ Can only detach applications with status = Attached.';
    return;
  }
  if (!confirm(`Detach application #${a.id}?`)) return;
  loading.value = true;
  try {
    await Submissions.update(form.value.id, { remove_application_ids: [a.id] });
    msg.value = `Application #${a.id} detached.`;
    await reloadForm();
    await loadAvailableApps();
  } catch (err) {
    console.error('Error removing application:', err);
    msg.value = 'Failed to detach application.';
  } finally {
    loading.value = false;
  }
}

// 🔹 Submit submission
async function submitSubmission() {
  if (!form.value.applications || form.value.applications.length === 0) {
    msg.value = 'Cannot submit an empty submission.';
    return;
  }
  loading.value = true;
  try {
    await Submissions.submit(form.value.id);
    msg.value = 'Submission successfully submitted!';
    await reloadForm();
  } catch (err) {
    console.error('Error submitting submission:', err);
    msg.value = 'Failed to submit submission.';
  } finally {
    loading.value = false;
  }
}

// 🔹 Reload
async function reloadForm() {
  try {
    form.value = await Submissions.show(form.value.id);
  } catch (err) {
    console.error('Error reloading form:', err);
  }
}

// 🔹 Helpers
function getStatusLabel(status) {
  switch (Number(status)) {
    case 1: return 'Draft';
    case 2: return 'Submitted';
    case 3: return 'Attached';
    default: return 'Unknown';
  }
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
/* ✅ your original CSS retained exactly */
.submission-manager {
  max-width: 1100px;
  margin: 20px auto;
  padding: 15px;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  position: relative;
}
.top-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.back-btn, .logout-btn {
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
.status.draft { color: #1976d2; font-weight: 600; }
.status.submitted { color: #4caf50; font-weight: 600; }
.status.attached { color: #ff9800; font-weight: 600; }
.app-status { margin-left: 8px; font-size: 12px; }
.app-status.draft { color: #1976d2; }
.app-status.attached { color: #ff9800; }
.loading-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(255,255,255,0.8);
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
.submission-table table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 60px;
}
.submission-table th, .submission-table td {
  border: 1px solid #ddd;
  padding: 8px 10px;
}
.submission-table th {
  background-color: #f3f3f3;
  font-weight: 600;
}
.submission-table tr:hover { background-color: #fafafa; }
.action-buttons { display: flex; gap: 6px; }
.edit-btn, .remove-btn, .save-btn, .submit-btn, .cancel-btn {
  border: none;
  border-radius: 6px;
  color: white;
  cursor: pointer;
  padding: 6px 12px;
  font-weight: 500;
}
.edit-btn { background: #ff9800; }
.edit-btn:hover { background: #e68900; }
.edit-btn:disabled { background: #ccc; color: #666; cursor: not-allowed; }
.remove-btn { background: #f44336; }
.remove-btn:hover { background: #d32f2f; }
.save-btn { background: #007bff; }
.save-btn:hover { background: #005fcc; }
.submit-btn { background: #4caf50; }
.submit-btn:hover { background: #3c8e40; }
.cancel-btn { background: #999; }
.cancel-btn:hover { background: #777; }
.submit-btn:disabled { background: #a5d6a7; cursor: not-allowed; }
.warning-msg { color: #b71c1c; font-weight: 600; margin-top: 10px; }
.success-msg { color: green; margin-top: 8px; }
.no-data { color: #777; margin-top: 20px; text-align: center; }
.btn-group { display: flex; gap: 10px; margin-top: 20px; align-items: center; }
.section { margin-top: 20px; }
.field { margin-top: 15px; display: flex; flex-direction: column; gap: 6px; }
.inline-group { display: flex; gap: 10px; align-items: center; }
.app-item { display: flex; justify-content: space-between; align-items: center; margin: 6px 0; }
.edit-form { margin-top: 70px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; background: #fafafa; }
/* 🔹 View mode */
.view-mode { opacity: 0.95; pointer-events: none; }
.view-mode .cancel-btn { pointer-events: all; opacity: 1; }
</style>
