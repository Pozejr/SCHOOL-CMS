import axios from 'axios';

const api = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor - add CSRF token
api.interceptors.request.use(
  (config) => {
    const csrfToken = sessionStorage.getItem('csrf_token');
    if (csrfToken && ['post', 'put', 'delete', 'patch'].includes(config.method?.toLowerCase())) {
      config.headers['X-CSRF-Token'] = csrfToken;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor - handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const pathname = window.location.pathname;

    if (status === 401) {
      sessionStorage.removeItem('csrf_token');
      // Only redirect to login when the user is on a protected admin page.
      // Public pages (/ , /about, /news, /events, etc.) receive 401s from the
      // routine checkAuth() probe on every page load — that is expected and
      // must NOT trigger a redirect.
      const isProtectedPage =
        pathname.startsWith('/admin') ||
        pathname.startsWith('/dashboard') ||
        pathname.startsWith('/cms');
      if (isProtectedPage && !pathname.includes('/login')) {
        window.location.href = '/admin/login';
      }
    } else if (status === 403) {
      // Forbidden — redirect to access denied if not already there
      if (!pathname.includes('/access-denied')) {
        window.location.href = '/access-denied';
      }
    }
    return Promise.reject(error);
  }
);

export default api;
