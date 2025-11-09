import { createRouter, createWebHistory } from 'vue-router';

// ✅ Helper — check if user is authenticated
function isAuthenticated() {
  return !!localStorage.getItem('token');
}

const routes = [
  // 🚪 Default route → redirect to login
  { path: '/', redirect: '/login' },

  // 🌍 Public Partner View (legacy optional)
  {
    path: '/partner-view',
    name: 'PartnerView',
    component: () => import('./pages/PartnerView.vue'),
  },

  // 🌍 Public submissions list
  // Example: /public/SrUpw69GodSU
  {
    path: '/public/:code',
    name: 'PublicSubmission',
    component: () => import('./pages/PublicSubmission.vue'),
    props: true,
  },

  // 🌍 Public submission detail
  // ✅ Matches Laravel: GET /api/public/submissions/{code}/{submissionId}
  {
    path: '/public/:code/submission/:submissionId',
    name: 'PublicSubmissionDetail',
    component: () => import('./pages/PublicSubmissionDetail.vue'),
    props: true,
  },

  // 🔐 Login
  {
    path: '/login',
    name: 'login',
    component: () => import('./pages/Login.vue'),
    meta: { guestOnly: true },
  },

  // 🏠 Dashboard
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('./pages/Dashboard.vue'),
    meta: { requiresAuth: true },
  },

  // 📦 Submission Management
  {
    path: '/submissions/manage',
    name: 'submissions.manage',
    component: () => import('./pages/SubmissionManager.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/submissions/edit/:id',
    name: 'submissions.edit',
    component: () => import('./pages/SubmissionEditor.vue'),
    props: true,
    meta: { requiresAuth: true },
  },

  // 📄 Applications
  {
    path: '/applications/manage',
    name: 'applications.manage',
    component: () => import('./pages/ApplicationManager.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/applications/:id',
    name: 'applications.edit',
    component: () => import('./pages/ApplicationEditor.vue'),
    props: true,
    meta: { requiresAuth: true },
  },

  // 🤝 Partner Management
  {
    path: '/partners/edit',
    name: 'partners.edit',
    component: () => import('./pages/PartnerEditor.vue'),
    meta: { requiresAuth: true },
  },

  // ⚠️ Catch-all 404 fallback
  {
    path: '/:pathMatch(.*)*',
    component: {
      template: `
        <div style="padding:40px;text-align:center;font-family:Arial;">
          <h2>404 - Page Not Found</h2>
          <p>The page you are looking for does not exist.</p>
          <router-link to="/dashboard" style="color:#1976d2;text-decoration:underline;">
            Go to Dashboard
          </router-link>
        </div>
      `,
    },
  },
];

// 🧭 Create router
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 🔒 Global navigation guard with detailed logging
router.beforeEach((to, from, next) => {
  console.groupCollapsed(`🔄 Route Navigation`);
  console.log('From:', from.fullPath || '(initial load)');
  console.log('To:', to.fullPath);
  console.log('Params:', to.params);
  console.log('Meta:', to.meta);
  console.groupEnd();

  const token = localStorage.getItem('token');

  // 🔐 Auth-protected routes
  if (to.meta.requiresAuth && !token) {
    console.warn('🚫 Unauthenticated — redirecting to /login');
    return next('/login');
  }

  // 🚫 Prevent logged-in users from visiting login/register
  if (to.meta.guestOnly && token) {
    console.log('✅ Already logged in — redirecting to /dashboard');
    return next('/dashboard');
  }

  next();
});

export default router;
