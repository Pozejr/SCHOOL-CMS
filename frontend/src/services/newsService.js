import api from './api';
import { API } from '../constants/apiEndpoints';

export const newsService = {
  async getPublished(page = 1, perPage = 15) {
    const response = await api.get(API.NEWS, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getAll(page = 1, perPage = 15) {
    const response = await api.get(API.NEWS_ADMIN, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getBySlug(slug) {
    const response = await api.get(API.NEWS_BY_SLUG(slug));
    return response.data;
  },

  async getById(id) {
    const response = await api.get(API.NEWS_BY_ID(id));
    return response.data;
  },

  async create(data) {
    const response = await api.post(API.NEWS, data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(API.NEWS_BY_ID(id), data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(API.NEWS_BY_ID(id));
    return response.data;
  },
};
