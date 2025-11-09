<template>
  <div class="public-submission-detail">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Header -->
    <h2 v-if="partner">
      Partner: {{ partner.name }}
      <small>({{ partner.code }})</small>
    </h2>
    <h3 v-if="submissionId">Submission #{{ submissionId }}</h3>

    <!-- 🔹 Submission Details -->
    <div v-if="sub" class="submission-section">
      <p><b>Status:</b> {{ sub.status === 1 ? 'Draft' : 'Submitted' }}</p>
      <p><b>Submitted at:</b> {{ formatDate(sub.submitted_at) }}</p>

      <h3>Applications</h3>

      <div v-for="a in sub.applications" :key="a.id" class="app-card">
        <h4>#{{ a.id }} — {{ a.name }}</h4>
        <p><b>Status:</b> {{ a.status === 1 ? 'Draft' : 'Submitted' }}</p>
        <p><b>Remark:</b> {{ a.remark || '-' }}</p>

        <!-- Attachments -->
        <div v-if="a.attachments?.length">
          <h5>Attachments</h5>
          <ul class="attachments-list">
            <li v-for="att in a.attachments" :key="att.id">
              <span>
                #{{ att.id }} — {{ att.original_name || 'Unnamed file' }}
                ({{ att.size }} bytes)
              </span>
              <a
                :href="publicDownload(att.id)"
                class="download-link"
                target="_blank"
              >Download</a>
            </li>
          </ul>
        </div>
        <p v-else class="no-attachments">No attachments found.</p>
      </div>
    </div>

    <p v-if="err" class="error-msg">{{ err }}</p>

    <!-- 🔹 Back Button -->
    <router-link
      v-if="code"
      :to="`/public/${code}`"
      class="back-btn"
    >
      ← Back to Partner Submissions
    </router-link>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { PublicApi } from '../lib/api';

console.log('🟦 PublicSubmissionDetail.vue script loaded');

const route = useRoute();
console.log('🔍 Current route params on mount:', route.params);

// ✅ Use correct params (make sure matches router)
const code = ref(route.params.code);
const submissionId = Number(route.params.submissionId);

console.log('📌 Extracted params => code:', code.value, 'submissionId:', submissionId);

const sub = ref(null);
const partner = ref(null);
const err = ref('');
const loading = ref(false);

// 🔹 Build public download link
function publicDownload(attId) {
  console.log('📦 Download attachment link requested for ID', attId);
  return PublicApi.downloadPublicAttachmentUrl(code.value, attId);
}

// 🔹 Load submission detail
async function load() {
  console.log('📡 Attempting to load submission for code', code.value, 'ID', submissionId);
  if (!code.value || !submissionId) {
    console.warn('❌ Missing route params:', route.params);
    err.value = 'Invalid URL or missing submission ID.';
    return;
  }

  loading.value = true;
  err.value = '';
  sub.value = null;
  partner.value = null;

  try {
    console.log('➡️ Calling PublicApi.showSubmission...');
    const res = await PublicApi.showSubmission(code.value, submissionId);
    console.log('✅ API response received:', res);
    sub.value = res;
    partner.value = res.partner || null;
  } catch (e) {
    console.error('💥 Error loading public submission:', e);
    err.value = 'Submission not found or inaccessible.';
  } finally {
    loading.value = false;
    console.log('⏹️ Finished load() execution');
  }
}

// 🔹 Watch route change (debugging reloads)
watch(
  () => route.params,
  (newParams) => {
    console.log('🔁 Route params changed:', newParams);
  }
);

// 🔹 Format date
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

onMounted(() => {
  console.log('🚀 PublicSubmissionDetail mounted — starting load()');
  load();
});
</script>

<style scoped>
.public-submission-detail {
  max-width: 900px;
  margin: 20px auto;
  padding: 20px;
  font-family: Arial, sans-serif;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
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

/* Submission section */
.submission-section {
  margin-top: 20px;
}
.app-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 12px;
  background: #fafafa;
}
.app-card h4 {
  margin-bottom: 6px;
  color: #333;
}

/* Attachments */
.attachments-list {
  list-style: none;
  padding: 0;
  margin-top: 6px;
}
.attachments-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 0;
}
.download-link {
  color: #007bff;
  text-decoration: none;
  font-weight: 600;
}
.download-link:hover {
  text-decoration: underline;
}
.no-attachments {
  color: #777;
  font-style: italic;
}

/* Errors */
.error-msg {
  color: red;
  text-align: center;
  margin-top: 20px;
}

/* ✅ Back Button */
.back-btn {
  display: inline-block;
  margin-top: 20px;
  background: #1976d2;
  color: white;
  padding: 8px 14px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 600;
}
.back-btn:hover {
  background: #0d47a1;
}
</style>
