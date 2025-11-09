/**
 * Main Vue 3 entry point for Laravel + Vite
 * Handles mounting of the Vue app into the Laravel blade or SPA.
 */

import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import axios from 'axios';

/**
 * --------------------------------------------------------------
 * Axios global configuration
 * --------------------------------------------------------------
 */

// Set your API base URL (Laravel routes typically use /api prefix)
axios.defaults.baseURL = '/api';

// Required for Laravel to identify AJAX requests
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Make axios globally available
window.axios = axios;

/**
 * --------------------------------------------------------------
 * Global Axios response interceptor
 * Handles expired/invalid tokens automatically (HTTP 401)
 * --------------------------------------------------------------
 */
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            console.warn('Unauthorized (401): clearing token and redirecting...');
            // Remove saved token (if any)
            localStorage.removeItem('token');

            // Avoid infinite redirect loop if already on login page
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    }
);

/**
 * --------------------------------------------------------------
 * Initialize and mount the Vue app
 * --------------------------------------------------------------
 */
const app = createApp(App);
app.use(router);
app.mount('#app');
