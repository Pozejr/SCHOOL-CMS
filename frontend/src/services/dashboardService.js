import api from './api';
import { API } from '../constants/apiEndpoints';

export const dashboardService = {
  async getStats() {
    const response = await api.get(API.DASHBOARD_STATS);
    return response.data;
  },

  async getRecentActivity(limit = 10) {
    const response = await api.get(API.DASHBOARD_ACTIVITY, { params: { limit } });
    return response.data;
  },
};
