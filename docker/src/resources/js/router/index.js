import { createRouter, createWebHistory } from 'vue-router';

/**
 * ✅ Helper: Check if user has valid token
 */
function isAuthenticated() {
  return !!localStorage.getItem('token');
}

/**
 * ✅ Application Routes
 */
const routes = [
  // 🌍 Public Partner Access (old testing page)
  {
    path: '/partner-view',
    name: 'PartnerView',
    component: () => import('../pages/PartnerView.vue'),
  },

  // 🌍 Public submissions list (e.g., /public/SrUpw69GodSU)
  {
    path: '/public/:code',
    name: 'PublicSubmission',
    component: () => import('../pages/PublicSubmission.vue'),
    props: true,
  },

  // 🌍 Public submission detail (✅ FIXED: plural `submissions` to match backend route)
  // Matches Laravel route: GET /api/public/submissions/{code}/{submissionId}
  {
    path: '/public/:code/submissions/:submissionId',
    name: 'PublicSubmissionDetail',
    component: () => import('../pages/PublicSubmissionDetail.vue'),
    props: true,
    beforeEnter: (to, from, next) => {
      console.log('🧭 Entering PublicSubmissionDetail route with params:', to.params);
      next();
    },
  },

  // 🔐 Login page
  {
    path: '/login',
    name: 'login',
    component: () => import('../pages/Login.vue'),
  },

  // 🏠 Dashboard (authenticated area)
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { requiresAuth: true },
  },

  // 📄 Application Management
  {
    path: '/applications/manage',
    name: 'applications.manage',
    component: () => import('../pages/ApplicationManager.vue'),
    meta: { requiresAuth: true },
  },

  // 🤝 Partner Management
  {
    path: '/partners/edit',
    name: 'partners.edit',
    component: () => import('../pages/PartnerEditor.vue'),
    meta: { requiresAuth: true },
  },

  // 📦 Submission Management (list)
  {
    path: '/submissions/manage',
    name: 'submissions.manage',
    component: () => import('../pages/SubmissionManager.vue'),
    meta: { requiresAuth: true },
    beforeEnter: (to, from, next) => {
      console.log('🧭 Entering SubmissionManager route...');
      next();
    },
  },

  // ✏️ Submission Editor (edit details)
  {
    path: '/submissions/edit/:id',
    name: 'submissions.edit',
    component: () => import('../pages/SubmissionEditor.vue'),
    props: true,
    meta: { requiresAuth: true },
  },

  // ⚠️ Catch-all redirect for 404s
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
];

/**
 * ✅ Router instance
 */
const router = createRouter({
  history: createWebHistory(),
  routes,
});

/**
 * 🔒 Global navigation guard
 * Redirects unauthenticated users away from protected routes
 */
router.beforeEach((to, from, next) => {
  console.log(`🔄 Navigating to: ${to.fullPath}`, {
    from: from.fullPath,
    params: to.params,
    name: to.name,
  });

  if (to.meta.requiresAuth && !isAuthenticated()) {
    console.warn('🚫 Access denied — user not authenticated');
    next({ name: 'login' });
  } else {
    next();
  }
});

export default router;
