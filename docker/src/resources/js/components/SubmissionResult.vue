<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  // For public page
  partnerCode: { type: String, required: false },
  reference:   { type: String, required: false },
  // For staff page
  submissionId:{ type: [String,Number], required: false },
  authToken:   { type: String, required: false }
})

const loading = ref(true)
const error   = ref('')
const submission = ref(null)
const applications = ref([])

async function fetchData(){
  try {
    if (props.authToken && props.submissionId) {
      const api = axios.create({ baseURL: '/api', headers:{ Authorization:`Bearer ${props.authToken}` } })
      const s = await api.get(`/submissions/${props.submissionId}`)
      submission.value = s.data
      const apps = await api.get(`/submissions/${props.submissionId}` + '/applications') // implement helper endpoint or hydrate in show()
      applications.value = apps.data
    } else if (props.partnerCode && props.reference) {
      const s = await axios.get(`/api/public/${props.partnerCode}/submissions/${props.reference}`)
      submission.value = s.data.submission
      applications.value = s.data.applications
    } else {
      throw new Error('Missing identifiers.')
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function publicDownloadUrl(attId){
  return `/api/public/${props.partnerCode}/attachments/${attId}`
}

onMounted(fetchData)
</script>

<template>
  <div class="p-6">
    <div v-if="loading">Loading…</div>
    <div v-else-if="error" class="text-red-600">{{ error }}</div>
    <div v-else>
      <h1 class="text-2xl font-semibold mb-2">Submission {{ submission.reference }}</h1>
      <div class="text-sm text-gray-600 mb-6">
        Partner: {{ submission.partner.name }} ·
        Status: <span :class="submission.status === 2 ? 'text-green-600' : 'text-yellow-700'">
          {{ submission.status === 2 ? 'Submitted' : 'Draft' }}
        </span>
        <span v-if="submission.submitted_at"> · Submitted at: {{ submission.submitted_at }}</span>
      </div>

      <div class="space-y-4">
        <div v-for="app in applications" :key="app.id" class="border rounded-xl p-4">
          <div class="font-medium">{{ app.title }}</div>
          <div class="text-sm text-gray-600" v-if="app.notes">{{ app.notes }}</div>
          <div class="mt-3">
            <div class="font-semibold text-sm mb-1">Attachments</div>
            <ul class="list-disc ml-6">
              <li v-for="att in app.attachments" :key="att.id">
                <template v-if="partnerCode && reference">
                  <a :href="publicDownloadUrl(att.id)">{{ att.original_name }}</a>
                  <span class="text-xs text-gray-500"> ({{ att.mime }}, {{ Math.round(att.size/1024) }} KB)</span>
                </template>
                <template v-else>
                  <a :href="att.internal_download_url">{{ att.original_name }}</a>
                  <span class="text-xs text-gray-500"> ({{ att.mime }}, {{ Math.round(att.size/1024) }} KB)</span>
                </template>
              </li>
              <li v-if="!app.attachments?.length" class="text-gray-500">No attachments</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* minimal styling; feel free to plug Tailwind */
</style>
