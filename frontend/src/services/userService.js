import api from './api';
import { API } from '../constants/apiEndpoints';

export const userService = {
  async getAll(page = 1, perPage = 15) {
    const response = await api.get(API.USERS, { params: { page, per_page: perPage } });
    return response.data;
  },

  async create(data) {
    const response = await api.post(API.USERS, data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(API.USER_BY_ID(id), data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(API.USER_BY_ID(id));
    return response.data;
  },
};
