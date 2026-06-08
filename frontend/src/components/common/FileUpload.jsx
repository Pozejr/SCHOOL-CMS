import { useCallback } from 'react';
import { useToast } from '../../contexts/ToastContext';
import api from '../../services/api';

export default function FileUpload({
  onUploadComplete,
  accept = 'image/jpeg,image/png,image/gif,image/webp,application/pdf',
  multiple = false,
  maxSize = 10 * 1024 * 1024,
}) {
  const toast = useToast();

  const handleChange = useCallback(async (e) => {
    const files = Array.from(e.target.files || []);
    if (files.length === 0) return;

    for (const file of files) {
      if (file.size > maxSize) {
        toast.error(`${file.name} exceeds maximum file size`);
        continue;
      }

      const formData = new FormData();
      formData.append('file', file);

      try {
        const response = await api.post('/files/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        });
        if (response.data.success) {
          toast.success(`${file.name} uploaded successfully`);
          onUploadComplete?.(response.data.data);
        }
      } catch (err) {
        toast.error(err.response?.data?.error?.message || `Failed to upload ${file.name}`);
      }
    }

    e.target.value = '';
  }, [maxSize, onUploadComplete, toast]);

  return (
    <div>
      <label className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 hover:border-primary-400 transition-colors">
        <div className="flex flex-col items-center justify-center pt-5 pb-6">
          <svg className="w-8 h-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <p className="text-sm text-gray-500">
            <span className="font-semibold text-primary-600">Click to upload</span> or drag and drop
          </p>
          <p className="text-xs text-gray-400 mt-1">PNG, JPG, GIF, WebP, PDF (max 10MB)</p>
        </div>
        <input type="file" accept={accept} multiple={multiple} onChange={handleChange} className="hidden" />
      </label>
    </div>
  );
}
