import api from './api';
import { API } from '../constants/apiEndpoints';

export const pageService = {
  async getPublished(page = 1, perPage = 15) {
    const response = await api.get(API.PAGES, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getAll(page = 1, perPage = 15) {
    const response = await api.get(API.PAGES_ADMIN, { params: { page, per_page: perPage } });
    return response.data;
  },

  async getBySlug(slug) {
    const response = await api.get(API.PAGE_BY_SLUG(slug));
    return response.data;
  },

  async getById(id) {
    const response = await api.get(API.PAGE_BY_ID(id));
    return response.data;
  },

  async create(data) {
    const response = await api.post(API.PAGES, data);
    return response.data;
  },

  async update(id, data) {
    const response = await api.put(API.PAGE_BY_ID(id), data);
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(API.PAGE_BY_ID(id));
    return response.data;
  },

  // Sections
  async getSections(pageId) {
    const response = await api.get(`/pages/${pageId}/sections`);
    return response.data;
  },

  async createSection(pageId, data) {
    const response = await api.post(`/pages/${pageId}/sections`, data);
    return response.data;
  },

  async updateSection(sectionId, data) {
    const response = await api.put(`/pages/sections/${sectionId}`, data);
    return response.data;
  },

  async deleteSection(sectionId) {
    const response = await api.delete(`/pages/sections/${sectionId}`);
    return response.data;
  },

  async reorderSections(pageId, sectionIds) {
    const response = await api.put(`/pages/${pageId}/sections/reorder`, { section_ids: sectionIds });
    return response.data;
  },

  // Templates
  async getTemplates() {
    const response = await api.get('/templates');
    return response.data;
  },

  async getTemplate(slug) {
    const response = await api.get(`/templates/${slug}`);
    return response.data;
  },

  async applyTemplate(pageId, templateSlug) {
    const response = await api.post(`/pages/${pageId}/apply-template`, { template_slug: templateSlug });
    return response.data;
  },
};
