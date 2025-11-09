<template>
  <div class="public-submission">
    <!-- 🔄 Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <!-- 🔹 Partner Header -->
    <h2 v-if="data">Partner: {{ data.partner.name }}</h2>
    <h2 v-else>Loading Partner...</h2>

    <!-- 🔹 Partner & Submissions -->
    <div v-if="data" class="partner-section">
      <div v-if="data.submissions?.length">
        <h4>Submissions</h4>
        <ul class="submission-list">
          <li v-for="s in data.submissions" :key="s.id">
            <router-link
              :to="`/public/${code}/submission/${s.id}`"
              class="submission-link"
            >
              #{{ s.id }} —
              <span :class="['status', s.status === 1 ? 'draft' : 'submitted']">
                {{ s.status === 1 ? 'Draft' : 'Submitted' }}
              </span>
              <span v-if="s.submitted_at" class="date">
                — {{ formatDate(s.submitted_at) }}
              </span>
              <span v-else class="not-submitted">— Not submitted</span>
            </router-link>
          </li>
        </ul>
      </div>

      <p v-else class="no-submissions">No submissions found.</p>
    </div>

    <p v-if="err" class="error-msg">{{ err }}</p>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { PublicApi } from '../lib/api';

const route = useRoute();

const code = ref(route.params.code);
const data = ref(null);
const err = ref('');
const loading = ref(false);

// 🔹 Load partner submissions by code
async function load() {
  err.value = '';
  data.value = null;
  if (!code.value) return;

  loading.value = true;
  try {
    data.value = await PublicApi.listByCode(code.value);
  } catch (e) {
    console.error('Error fetching public submissions:', e);
    err.value = 'Partner not found or has no submissions.';
  } finally {
    loading.value = false;
  }
}

// 🔹 Watch for route param change
watch(
  () => route.params.code,
  (v) => {
    code.value = v;
    load();
  }
);

// 🔹 On mount, auto-load based on route param
onMounted(load);

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
</script>

<style scoped>
.public-submission {
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

/* Partner Section */
.partner-section {
  margin-top: 20px;
}
.submission-list {
  list-style: none;
  padding: 0;
}
.submission-list li {
  background: #fafafa;
  padding: 8px 10px;
  border-radius: 6px;
  margin-bottom: 6px;
  border: 1px solid #eee;
}
.submission-link {
  text-decoration: none;
  color: #333;
  display: block;
}
.submission-link:hover {
  background: #f5f5f5;
}

/* Status Tags */
.status {
  font-weight: 600;
}
.status.draft {
  color: #0277bd;
}
.status.submitted {
  color: #2e7d32;
}

.date {
  color: #555;
  font-size: 0.9em;
}
.not-submitted {
  color: #777;
  font-style: italic;
}
.no-submissions {
  color: #777;
  margin-top: 10px;
}
.error-msg {
  color: red;
  text-align: center;
  margin-top: 15px;
}
</style>
