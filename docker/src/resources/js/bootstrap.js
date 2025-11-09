/**
 * Load Axios HTTP library and configure it for global use.
 */

import axios from 'axios';

window.axios = axios;

// Default header required for Laravel to identify AJAX requests
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Optional: Set the base API URL (you can adjust this to your setup)
 * This assumes your Laravel routes use /api prefix.
 */
window.axios.defaults.baseURL = '/api';

/**
 * Global Axios response interceptor
 * Automatically handles expired or invalid tokens (HTTP 401)
 */
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Remove any stored token (if used in localStorage)
            localStorage.removeItem('token');

            // Avoid redirect loop if already on login page
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    }
);
