<template>
  <div class="partner-view">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <h2>Partner Submission Viewer</h2>

    <!-- 🔹 Partner Code Input -->
    <div v-if="!partnerLoaded" class="input-section">
      <input
        v-model="partnerCode"
        placeholder="Enter Partner Code"
        @keyup.enter="loadSubmissions"
        class="input-box"
      />
      <button @click="loadSubmissions" class="load-btn">View Submissions</button>
      <p v-if="error" class="error-msg">{{ error }}</p>
    </div>

    <!-- 🔹 Partner Info -->
    <div v-if="partnerLoaded" class="partner-header">
      <h3>Partner: {{ partner.name }} ({{ partner.code }})</h3>
      <button class="back-btn" @click="reset">← Back</button>
    </div>

    <!-- 🔹 Submissions Table -->
    <div v-if="partnerLoaded && submissions.length" class="table-container">
      <h4>Submissions</h4>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Status</th>
            <th>Submitted At</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in submissions" :key="s.id">
            <td>{{ s.id }}</td>
            <td>
              <span :class="['status', s.status === 1 ? 'draft' : 'submitted']">
                {{ s.status === 1 ? 'Draft' : 'Submitted' }}
              </span>
            </td>
            <td>{{ formatDate(s.submitted_at) }}</td>
            <td>
              <button @click="viewSubmission(s.id)" class="view-btn">
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="partnerLoaded && !submissions.length" class="no-data">
      No submissions found for this partner.
    </p>

    <!-- 🔹 Submission Details -->
    <div v-if="selectedSubmission" class="submission-details">
      <div class="details-header">
        <h4>Submission #{{ selectedSubmission.id }}</h4>
        <button class="close-btn" @click="selectedSubmission = null">×</button>
      </div>

      <div
        v-for="app in selectedSubmission.applications"
        :key="app.id"
        class="app-card"
      >
        <h5>Application #{{ app.id }} — {{ app.name }}</h5>
        <p><b>Status:</b> {{ app.status === 1 ? 'Draft' : 'Submitted' }}</p>
        <p><b>Remark:</b> {{ app.remark || '-' }}</p>

        <!-- Attachments -->
        <div v-if="app.attachments?.length">
          <h6>Attachments</h6>
          <ul class="attachments-list">
            <li v-for="att in app.attachments" :key="att.id">
              <span>
                #{{ att.id }} — {{ att.mime }} ({{ att.size }} bytes)
              </span>
              <a
                :href="downloadPublicUrl(att.id)"
                class="download-link"
                target="_blank"
                >Download</a
              >
            </li>
          </ul>
        </div>
        <p v-else class="no-attachments">No attachments found.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { PublicApi } from '../lib/api';

const route = useRoute();

const partnerCode = ref('');
const partnerLoaded = ref(false);
const partner = ref({});
const submissions = ref([]);
const selectedSubmission = ref(null);
const error = ref('');
const loading = ref(false); // 🔄 Added loading indicator

// 🔹 Auto-load if URL has /public/:code
onMounted(async () => {
  if (route.params.code) {
    partnerCode.value = route.params.code;
    await loadSubmissions();
  }
});

// 🔹 Load submissions for partner code
async function loadSubmissions() {
  error.value = '';
  selectedSubmission.value = null;

  if (!partnerCode.value.trim()) {
    error.value = 'Please enter a valid partner code.';
    return;
  }

  loading.value = true;
  try {
    const res = await PublicApi.listByCode(partnerCode.value.trim());
    partner.value = res.partner;
    submissions.value = res.submissions || [];
    partnerLoaded.value = true;
  } catch (err) {
    console.error('Error loading submissions:', err);
    error.value = 'Invalid partner code or no submissions found.';
  } finally {
    loading.value = false;
  }
}

// 🔹 View submission (with applications + attachments)
async function viewSubmission(id) {
  loading.value = true;
  try {
    const res = await PublicApi.showSubmission(partnerCode.value.trim(), id);
    selectedSubmission.value = res; // backend returns direct submission object
  } catch (err) {
    console.error('Error loading submission:', err);
    error.value = 'Failed to load submission details.';
  } finally {
    loading.value = false;
  }
}

// 🔹 Generate public download URL
function downloadPublicUrl(attId) {
  return PublicApi.downloadPublicAttachmentUrl(partnerCode.value.trim(), attId);
}

// 🔹 Format date nicely
function formatDate(dt) {
  if (!dt) return '-';
  return new Date(dt).toLocaleString('en-GB', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

// 🔹 Reset view
function reset() {
  partnerLoaded.value = false;
  partner.value = {};
  submissions.value = [];
  partnerCode.value = '';
  selectedSubmission.value = null;
  error.value = '';
}
</script>

<style scoped>
.partner-view {
  max-width: 1100px;
  margin: 30px auto;
  padding: 20px;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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

/* Input Section */
.input-section {
  text-align: center;
  margin-top: 20px;
}
.input-box {
  padding: 8px;
  width: 250px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-right: 10px;
}
.load-btn {
  background: #007bff;
  color: #fff;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}
.load-btn:hover {
  background: #005fcc;
}
.error-msg {
  color: red;
  margin-top: 8px;
}
.no-data {
  text-align: center;
  color: #777;
  margin-top: 20px;
}

/* Partner Header */
.partner-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.back-btn {
  background: #f44336;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}
.back-btn:hover {
  background: #d32f2f;
}

/* Table */
.table-container table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
th,
td {
  border: 1px solid #ddd;
  padding: 8px 10px;
  text-align: left;
}
th {
  background: #f3f3f3;
  font-weight: 600;
}
tr:hover {
  background: #fafafa;
}
.status.draft {
  color: #0277bd;
}
.status.submitted {
  color: #2e7d32;
}
.view-btn {
  background: #2196f3;
  color: #fff;
  padding: 4px 10px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.view-btn:hover {
  background: #1976d2;
}

/* Submission Details */
.submission-details {
  margin-top: 20px;
  padding: 15px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background: #fafafa;
}
.details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.close-btn {
  background: #999;
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  cursor: pointer;
}
.close-btn:hover {
  background: #777;
}
.app-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 12px;
  margin-top: 10px;
  background: white;
}
.attachments-list {
  list-style: none;
  padding-left: 0;
}
.attachments-list li {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
}
.download-link {
  color: #007bff;
  text-decoration: none;
}
.download-link:hover {
  text-decoration: underline;
}
.no-attachments {
  color: #777;
  font-style: italic;
  margin-top: 6px;
}
</style>
