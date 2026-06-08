import api from './api';
import { API } from '../constants/apiEndpoints';

export const eventService = {
  async getPublished(page = 1, perPage = 15) {
    const response = await api.get(API.EVENTS, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getAll(page = 1, perPage = 15) {
    const response = await api.get(API.EVENTS_ADMIN, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getBySlug(slug) {
    const response = await api.get(API.EVENT_BY_SLUG(slug));
    return response.data;
  },

  async getById(id) {
    const response = await api.get(API.EVENT_BY_ID(id));
    return response.data;
  },

  async create(data) {
    const response = await api.post(API.EVENTS, data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(API.EVENT_BY_ID(id), data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(API.EVENT_BY_ID(id));
    return response.data;
  },
};
