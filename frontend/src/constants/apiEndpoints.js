const API_BASE = '/api/v1';

export const API = {
  // Auth
  LOGIN: `${API_BASE}/auth/login`,
  LOGOUT: `${API_BASE}/auth/logout`,
  ME: `${API_BASE}/auth/me`,
  CSRF_TOKEN: `${API_BASE}/auth/csrf-token`,

  // Dashboard
  DASHBOARD_STATS: `${API_BASE}/dashboard/stats`,
  DASHBOARD_ACTIVITY: `${API_BASE}/dashboard/activity`,

  // Pages
  PAGES: `${API_BASE}/pages`,
  PAGES_ADMIN: `${API_BASE}/pages/admin/all`,
  PAGE_BY_SLUG: (slug) => `${API_BASE}/pages/slug/${slug}`,
  PAGE_BY_ID: (id) => `${API_BASE}/pages/${id}`,

  // News
  NEWS: `${API_BASE}/news`,
  NEWS_ADMIN: `${API_BASE}/news/admin/all`,
  NEWS_BY_SLUG: (slug) => `${API_BASE}/news/slug/${slug}`,
  NEWS_BY_ID: (id) => `${API_BASE}/news/${id}`,

  // Events
  EVENTS: `${API_BASE}/events`,
  EVENTS_ADMIN: `${API_BASE}/events/admin/all`,
  EVENT_BY_SLUG: (slug) => `${API_BASE}/events/slug/${slug}`,
  EVENT_BY_ID: (id) => `${API_BASE}/events/${id}`,

  // Files
  FILES: `${API_BASE}/files`,
  FILE_UPLOAD: `${API_BASE}/files/upload`,
  FILE_BY_ID: (id) => `${API_BASE}/files/${id}`,

  // Users
  USERS: `${API_BASE}/users`,
  USER_BY_ID: (id) => `${API_BASE}/users/${id}`,
};
