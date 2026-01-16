import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- THE FIX: AXIOS INTERCEPTOR ---
// This runs before EVERY request to the server
window.axios.interceptors.request.use(config => {
    // 1. Look for the token in local storage
    const token = localStorage.getItem('auth_token');
    
    // 2. If found, attach it to the Authorization header
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    
    return config;
}, error => {
    return Promise.reject(error);
});

// Optional: Handle 401 errors globally (e.g., if token expires, force logout)
window.axios.interceptors.response.use(response => response, error => {
    if (error.response && error.response.status === 401) {
        // If server says "Unauthorized", clear token and redirect to login
        localStorage.removeItem('auth_token');
        window.location.href = '/login';
    }
    return Promise.reject(error);
});