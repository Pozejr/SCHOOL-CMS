import { useState, useEffect, useCallback } from 'react';
import { fileService } from '../../services/fileService';
import Modal from './Modal';
import Spinner from './Spinner';
import Pagination from './Pagination';
import { formatFileSize, formatDate } from '../../utils/helpers';

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';

/**
 * MediaLibraryModal — Browse and select uploaded images.
 *
 * Props:
 *   isOpen   — boolean, controls modal visibility
 *   onClose  — callback when modal is dismissed
 *   onSelect — callback(imagePath: string) when an image is chosen
 *
 * Fetches files from GET /api/v1/files?page=N&per_page=20
 * Filters to only show file_type='image'.
 * Displays a responsive grid with thumbnails.
 */
export default function MediaLibraryModal({ isOpen, onClose, onSelect }) {
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [selectedId, setSelectedId] = useState(null);

  useEffect(() => {
    if (isOpen) loadImages(page);
  }, [isOpen, page]);

  const loadImages = async (pg = 1) => {
    setLoading(true);
    try {
      // Fetch a generous page of files; the API returns all types,
      // we filter client-side to images only.
      const result = await fileService.getAll(pg, 40);
      if (result.success) {
        const imageFiles = (result.data || []).filter(
          (f) => f.file_type === 'image'
        );
        setFiles(imageFiles);
        setTotal(result.meta?.total || 0);
        setTotalPages(result.meta?.total_pages || 1);
      }
    } catch {
      /* silent */
    } finally {
      setLoading(false);
    }
  };

  const handleSelect = (file) => {
    setSelectedId(file.id);
    // Pass the relative path stored in DB: "uploads/images/filename.jpg"
    onSelect(file.file_path);
    onClose();
  };

  const handleUploadAndSelect = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    try {
      const result = await fileService.upload(file);
      if (result.success && result.data?.file_type === 'image') {
        onSelect(result.data.file_path);
        onClose();
      } else if (result.success) {
        // Uploaded but not an image type
        loadImages(page); // refresh list
      }
    } catch {
      /* upload error handled by fileService */
    }
    e.target.value = '';
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Media Library"
      size="xl"
      footer={
        <div className="flex items-center justify-between w-full">
          <span className="text-sm text-gray-500">
            {total} file{total !== 1 ? 's' : ''} uploaded
          </span>
          <label className="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            Upload New
            <input
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              onChange={handleUploadAndSelect}
              className="hidden"
            />
          </label>
        </div>
      }
    >
      {loading && !files.length ? (
        <Spinner size="lg" className="py-16" />
      ) : files.length === 0 ? (
        <div className="text-center py-16">
          <svg
            className="w-16 h-16 text-gray-300 mx-auto mb-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={1}
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <p className="text-gray-500 mb-2">No images uploaded yet.</p>
          <p className="text-sm text-gray-400">
            Upload your first image using the button below.
          </p>
        </div>
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
            {files.map((file) => {
              const isSelected = selectedId === file.id;
              const imgSrc = file.file_path?.startsWith('http')
                ? file.file_path
                : `${BACKEND_URL}/${file.file_path}`;

              return (
                <button
                  key={file.id}
                  type="button"
                  onClick={() => handleSelect(file)}
                  className={`group relative aspect-square rounded-xl overflow-hidden border-2 transition-all text-left focus:outline-none focus:ring-2 focus:ring-primary-500 ${
                    isSelected
                      ? 'border-primary-500 ring-2 ring-primary-200'
                      : 'border-gray-200 hover:border-primary-300 hover:shadow-md'
                  }`}
                  title={`${file.original_name}\n${formatFileSize(file.file_size)}\n${formatDate(file.created_at)}`}
                >
                  <img
                    src={imgSrc}
                    alt={file.original_name}
                    className="w-full h-full object-cover"
                    loading="lazy"
                  />
                  {/* Hover overlay */}
                  <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors" />
                  {/* Selection check */}
                  {isSelected && (
                    <div className="absolute top-1.5 right-1.5 w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center">
                      <svg className="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                  )}
                  {/* Filename bar */}
                  <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-2 py-1.5">
                    <p className="text-white text-xs truncate font-medium">
                      {file.original_name}
                    </p>
                  </div>
                </button>
              );
            })}
          </div>

          {totalPages > 1 && (
            <div className="mt-4">
              <Pagination
                currentPage={page}
                totalPages={totalPages}
                onPageChange={setPage}
              />
            </div>
          )}
        </>
      )}
    </Modal>
  );
}
