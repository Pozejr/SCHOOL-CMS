import { useState, useEffect } from 'react';
import { fileService } from '../../services/fileService';
import { useToast } from '../../contexts/ToastContext';
import Button from '../../components/common/Button';
import FileUpload from '../../components/common/FileUpload';
import Pagination from '../../components/common/Pagination';
import Spinner from '../../components/common/Spinner';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { formatFileSize, formatDate } from '../../utils/helpers';

export default function FileManager() {
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const toast = useToast();

  useEffect(() => { loadFiles(); }, [page]);

  const loadFiles = async () => {
    setLoading(true);
    try {
      const result = await fileService.getAll(page);
      if (result.success) {
        setFiles(result.data);
        setTotalPages(result.meta.total_pages);
      }
    } catch { toast.error('Failed to load files'); }
    finally { setLoading(false); }
  };

  const handleUploadComplete = () => { loadFiles(); };

  const handleDelete = async () => {
    setDeleteLoading(true);
    try {
      await fileService.delete(deleteId);
      toast.success('File deleted');
      loadFiles();
    } catch { toast.error('Failed to delete file'); }
    finally { setDeleteLoading(false); setDeleteId(null); }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">File Manager</h1>
        <p className="text-gray-500 text-sm mt-1">Upload and manage media files.</p>
      </div>

      <div className="card">
        <h3 className="text-sm font-semibold text-gray-700 mb-3">Upload Files</h3>
        <FileUpload onUploadComplete={handleUploadComplete} />
      </div>

      {loading ? <Spinner size="lg" className="py-10" /> : (
        <div className="card p-0 overflow-hidden">
          {!files.length ? (
            <div className="text-center py-8 text-gray-500">No files uploaded yet.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr className="bg-gray-50">
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Preview</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File Name</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uploaded</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {files.map((f) => (
                    <tr key={f.id} className="hover:bg-gray-50">
                      <td className="px-6 py-3">
                        {f.file_type === 'image' ? (
                          <img src={`${import.meta.env.VITE_BACKEND_URL}/${f.file_path}`} alt={f.original_name} className="w-12 h-12 object-cover rounded-lg" />
                        ) : (
                          <div className="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                            <svg className="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-3 text-sm text-gray-700 max-w-xs truncate">{f.original_name}</td>
                      <td className="px-6 py-3"><span className="badge-info">{f.file_type}</span></td>
                      <td className="px-6 py-3 text-sm text-gray-500">{formatFileSize(f.file_size)}</td>
                      <td className="px-6 py-3 text-sm text-gray-500">{formatDate(f.created_at)}</td>
                      <td className="px-6 py-3 text-right">
                        <button onClick={() => setDeleteId(f.id)} className="text-sm text-red-500 hover:text-red-700">Delete</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}

      <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />
      <ConfirmDialog isOpen={!!deleteId} onClose={() => setDeleteId(null)} onConfirm={handleDelete} title="Delete File" message="Are you sure? This will permanently remove the file." loading={deleteLoading} />
    </div>
  );
}
