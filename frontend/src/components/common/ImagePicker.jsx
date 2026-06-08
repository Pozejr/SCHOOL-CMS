import { useState } from 'react';
import MediaLibraryModal from './MediaLibraryModal';

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000';

/**
 * ImagePicker — Reusable image selector with Media Library integration.
 *
 * Provides:
 *   - Text input for manual URL entry (external URLs still supported)
 *   - "Select Image" button that opens the Media Library modal
 *   - Live image preview below the input
 *   - Clear button to remove selected image
 *
 * Props:
 *   label        — field label string
 *   value        — current image path string
 *   onChange      — callback(newValue: string)
 *   placeholder  — input placeholder text
 *
 * Usage:
 *   <ImagePicker
 *     label="Featured Image"
 *     value={form.featured_image}
 *     onChange={(val) => setForm(prev => ({ ...prev, featured_image: val }))}
 *   />
 */
export default function ImagePicker({
  label = 'Image',
  value = '',
  onChange,
  placeholder = 'uploads/images/... or paste any URL',
}) {
  const [showModal, setShowModal] = useState(false);

  // Resolve the preview URL: if the value is a relative path
  // like "uploads/images/photo.jpg", prepend the backend URL.
  // If it's already a full URL (http/https), use as-is.
  const previewUrl = value
    ? value.startsWith('http')
      ? value
      : `${BACKEND_URL}/${value}`
    : null;

  const handleSelect = (imagePath) => {
    onChange(imagePath);
  };

  const handleClear = () => {
    onChange('');
  };

  const handleInputChange = (e) => {
    onChange(e.target.value);
  };

  return (
    <div>
      <label className="label">{label}</label>

      {/* Input row with Select Image button */}
      <div className="flex gap-2">
        <input
          type="text"
          value={value || ''}
          onChange={handleInputChange}
          placeholder={placeholder}
          className="input flex-1"
        />
        <button
          type="button"
          onClick={() => setShowModal(true)}
          className="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 hover:border-primary-300 transition-colors whitespace-nowrap"
          title="Choose from uploaded images"
        >
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          Select Image
        </button>
      </div>

      {/* Live image preview */}
      {previewUrl && (
        <div className="mt-2 relative group inline-block">
          <div className="relative w-full max-w-xs h-32 rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
            <img
              src={previewUrl}
              alt="Selected preview"
              className="w-full h-full object-cover"
              onError={(e) => {
                e.target.style.display = 'none';
                e.target.nextSibling.style.display = 'flex';
              }}
            />
            <div
              className="hidden w-full h-full items-center justify-center text-gray-400 text-xs"
            >
              Image not found
            </div>
          </div>
          {/* Clear button */}
          <button
            type="button"
            onClick={handleClear}
            className="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 shadow"
            title="Remove image"
          >
            ✕
          </button>
          {/* Filename hint */}
          <p className="text-xs text-gray-400 mt-1 truncate max-w-xs">
            {value}
          </p>
        </div>
      )}

      {/* Media Library Modal */}
      <MediaLibraryModal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        onSelect={handleSelect}
      />
    </div>
  );
}
