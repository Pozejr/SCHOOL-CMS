import api from './api';
import { API } from '../constants/apiEndpoints';

export const fileService = {
  async getAll(page = 1, perPage = 20) {
    const response = await api.get(API.FILES, { params: { page, per_page: perPage } });
    return response.data;
  },

  async upload(file, entityType = null, entityId = null) {
    const formData = new FormData();
    formData.append('file', file);
    if (entityType) formData.append('entity_type', entityType);
    if (entityId) formData.append('entity_id', entityId);

    const response = await api.post(API.FILE_UPLOAD, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    return response.data;
  },

  async delete(id) {
    const response = await api.delete(API.FILE_BY_ID(id));
    return response.data;
  },
};
