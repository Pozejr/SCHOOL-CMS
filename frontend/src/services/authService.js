import api from './api';
import { API } from '../constants/apiEndpoints';

export const authService = {
  async login(username, password) {
    const response = await api.post(API.LOGIN, { username, password });
    if (response.data.success) {
      sessionStorage.setItem('csrf_token', response.data.data.csrf_token);
    }
    return response.data;
  },

  async logout() {
    const response = await api.post(API.LOGOUT);
    sessionStorage.removeItem('csrf_token');
    return response.data;
  },

  async getMe() {
    const response = await api.get(API.ME);
    if (response.data.success) {
      const csrf = response.data.data.csrf_token;
      if (csrf) sessionStorage.setItem('csrf_token', csrf);
    }
    return response.data;
  },

  async getCsrfToken() {
    const response = await api.get(API.CSRF_TOKEN);
    if (response.data.success) {
      sessionStorage.setItem('csrf_token', response.data.data.csrf_token);
    }
    return response.data;
  },
};
