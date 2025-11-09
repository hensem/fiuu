// ======================
// 🌍 BASE URL SETUP
// ======================
const API_BASE = window.location.origin.includes('8080') ? '/api' : '/api';

let token = localStorage.getItem('token') || null;

// ======================
// 🔑 TOKEN MANAGEMENT
// ======================
export function setToken(t) {
  token = t;
  if (t) localStorage.setItem('token', t);
  else localStorage.removeItem('token');
}

function authHeaders() {
  return token ? { Authorization: `Bearer ${token}` } : {};
}

// ======================
// 🌐 GENERIC API HELPER
// ======================
export async function api(path, opts = {}) {
  console.log('📡 API CALL:', `${API_BASE}${path}`, opts.method || 'GET');

  const res = await fetch(`${API_BASE}${path}`, {
    ...opts,
    headers: {
      Accept: 'application/json',
      ...(opts.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
      ...authHeaders(),
      ...(opts.headers || {}),
    },
  });

  const contentType = res.headers.get('Content-Type') || '';
  let data = null;

  try {
    if (contentType.includes('application/json')) data = await res.json();
    else data = await res.text();
  } catch (_) {
    data = null;
  }

  if (res.status === 401) {
    console.warn('⚠️ Unauthorized — redirecting to login');
    localStorage.removeItem('token');
    window.location.href = '/login';
    throw { status: 401, message: 'Unauthorized or expired token', data };
  }

  if (res.status === 422) {
    console.warn('⚠️ Validation failed:', data);
    throw { status: 422, message: data?.message || 'Validation error', data };
  }

  if (!res.ok) {
    const message = data?.message || res.statusText || 'Unknown error';
    console.error('❌ API ERROR:', res.status, message);
    throw { status: res.status, message, data };
  }

  return data;
}

// ======================
// 🔐 AUTH API
// ======================
export const Auth = {
  async login(email, password) {
    console.log('🔑 Logging in user...');
    const data = await api('/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    setToken(data.token);
    return data;
  },

  async me() {
    return api('/me');
  },

  async logout() {
    console.log('🚪 Logging out...');
    await api('/logout', { method: 'POST' });
    setToken(null);
  },
};

// ======================
// 👥 USERS API
// ======================
export const Users = {
  list() {
    console.log('📨 GET /users');
    return api('/users');
  },

  create(payload) {
    console.log('📨 POST /users', payload);
    return api('/users', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
};

// ======================
// 🤝 PARTNERS API
// ======================
export const Partners = {
  list() {
    console.log('📨 GET /partners?full=1');
    return api('/partners?full=1');
  },

  create(payload) {
    console.log('📨 POST /partners', payload);
    return api('/partners', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  update(id, payload) {
    console.log('📨 PUT /partners/' + id, payload);
    return api(`/partners/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  show(id) {
    console.log('📨 GET /partners/' + id);
    return api(`/partners/${id}`);
  },
};

// ======================
// 📄 APPLICATIONS API
// ======================
export const Applications = {
  list(params = {}) {
    let query = '';
    if (Object.keys(params).length) {
      query = '?' + new URLSearchParams(params).toString().replaceAll('%2C', ',');
    }
    console.log('📨 GET /applications' + query);
    return api(`/applications${query}`);
  },

  // ✅ List only draft applications (status = 1)
  listDraft() {
    console.log('📨 GET /applications?status=1');
    return api('/applications?status=1');
  },

  create(payload) {
    console.log('📨 POST /applications', payload);
    return api('/applications', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  show(id) {
    console.log('📨 GET /applications/' + id);
    return api(`/applications/${id}`);
  },

  update(id, payload) {
    console.log('📨 PUT /applications/' + id, payload);
    return api(`/applications/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  // ✅ Upload attachment
  async addAttachment(id, file) {
    console.log('📤 Uploading attachment to application', id);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('original_name', file.name);
    return api(`/applications/${id}/attachments`, { method: 'POST', body: fd });
  },

  detachAttachment(id, attachmentId) {
    console.log('🗑️ DELETE attachment', attachmentId, 'from application', id);
    return api(`/applications/${id}/attachments/${attachmentId}`, { method: 'DELETE' });
  },

  async getSignedDownload(attId) {
    console.log('📨 GET /attachments/' + attId + '/signed-download');
    const res = await api(`/attachments/${attId}/signed-download`);
    return res?.url || null;
  },

  downloadAttachmentUrl(attId) {
    return `/api/attachments/${attId}/download`;
  },
};

// ======================
// 📦 SUBMISSIONS API
// ======================
export const Submissions = {
  list() {
    console.log('📨 GET /submissions');
    return api('/submissions');
  },

  create(payload) {
    console.log('📨 POST /submissions', payload);
    return api('/submissions', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  show(id) {
    console.log('📨 GET /submissions/' + id);
    return api(`/submissions/${id}`);
  },

  update(id, payload) {
    console.log('📨 PUT /submissions/' + id, payload);
    return api(`/submissions/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
    });
  },

  submit(id) {
    console.log('📨 POST /submissions/' + id + '/submit');
    return api(`/submissions/${id}/submit`, { method: 'POST' });
  },

  destroy(id) {
    console.log('🗑️ DELETE /submissions/' + id);
    return api(`/submissions/${id}`, { method: 'DELETE' });
  },

  // ✅ Attach application(s) (status 1 → 3)
  addApplications(id, appIds = []) {
    if (!appIds.length) return Promise.resolve();
    console.log('➕ Attaching apps to submission', id, appIds);
    return this.update(id, { add_application_ids: appIds });
  },

  // ✅ Detach application(s) (status 3 → 1)
  removeApplications(id, appIds = []) {
    if (!appIds.length) return Promise.resolve();
    console.log('➖ Detaching apps from submission', id, appIds);
    return this.update(id, { remove_application_ids: appIds });
  },
};

// ======================
// 🌍 PUBLIC (NO AUTH)
// ======================
export const PublicApi = {
  listByCode(code) {
    console.log('🌍 GET /public/submissions/' + code);
    return api(`/public/submissions/${encodeURIComponent(code)}`);
  },

  showSubmission(code, sid) {
    console.log('🌍 GET /public/submissions/' + code + '/' + sid);
    return api(`/public/submissions/${encodeURIComponent(code)}/${sid}`);
  },

  downloadPublicAttachmentUrl(code, attId) {
    const c = encodeURIComponent(code);
    return `/api/public/attachments/${attId}/download?code=${c}`;
  },
};
