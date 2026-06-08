import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { pageService } from '../../services/pageService';
import { useToast } from '../../contexts/ToastContext';
import Button from '../../components/common/Button';
import StatusBadge from '../../components/common/StatusBadge';
import Pagination from '../../components/common/Pagination';
import SearchBar from '../../components/common/SearchBar';
import Spinner from '../../components/common/Spinner';
import EmptyState from '../../components/common/EmptyState';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { formatDate } from '../../utils/helpers';

export default function Pages() {
  const [pages, setPages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const toast = useToast();
  const navigate = useNavigate();

  useEffect(() => {
    loadPages();
  }, [page]);

  const loadPages = async () => {
    setLoading(true);
    try {
      const result = await pageService.getAll(page);
      if (result.success) {
        setPages(result.data);
        setTotal(result.meta.total);
        setTotalPages(result.meta.total_pages);
      }
    } catch (err) {
      toast.error('Failed to load pages');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!deleteId) return;
    setDeleteLoading(true);
    try {
      const result = await pageService.delete(deleteId);
      if (result.success) {
        toast.success('Page deleted');
        loadPages();
      }
    } catch (err) {
      toast.error('Failed to delete page');
    } finally {
      setDeleteLoading(false);
      setDeleteId(null);
    }
  };

  const filteredPages = pages.filter((p) =>
    p.title.toLowerCase().includes(search.toLowerCase())
  );

  if (loading && pages.length === 0) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Pages</h1>
          <p className="text-gray-500 text-sm mt-1">{total} total pages</p>
        </div>
        <Link to="/admin/pages/new">
          <Button>
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
            New Page
          </Button>
        </Link>
      </div>

      {/* Search */}
      <div className="max-w-sm">
        <SearchBar value={search} onChange={setSearch} placeholder="Search pages..." />
      </div>

      {/* Table */}
      <div className="card p-0 overflow-hidden">
        {filteredPages.length === 0 ? (
          <EmptyState
            title="No pages yet"
            description="Create your first page to get started."
            actionLabel="Create Page"
            onAction={() => navigate('/admin/pages/new')}
          />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead>
                <tr className="bg-gray-50">
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {filteredPages.map((p) => (
                  <tr key={p.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <Link to={`/admin/pages/${p.id}/edit`} className="text-sm font-medium text-primary-600 hover:text-primary-800">
                        {p.title}
                      </Link>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">/{p.slug}</td>
                    <td className="px-6 py-4"><StatusBadge status={p.status} /></td>
                    <td className="px-6 py-4 text-sm text-gray-500">{formatDate(p.updated_at)}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Link to={`/admin/pages/${p.id}/edit`} className="text-sm text-gray-400 hover:text-primary-600">Edit</Link>
                        <button onClick={() => setDeleteId(p.id)} className="text-sm text-gray-400 hover:text-red-600">Delete</button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />

      <ConfirmDialog
        isOpen={!!deleteId}
        onClose={() => setDeleteId(null)}
        onConfirm={handleDelete}
        title="Delete Page"
        message="Are you sure you want to delete this page? This action can be undone by an administrator."
        loading={deleteLoading}
      />
    </div>
  );
}
