import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { newsService } from '../../services/newsService';
import { useToast } from '../../contexts/ToastContext';
import Button from '../../components/common/Button';
import StatusBadge from '../../components/common/StatusBadge';
import Pagination from '../../components/common/Pagination';
import SearchBar from '../../components/common/SearchBar';
import Spinner from '../../components/common/Spinner';
import EmptyState from '../../components/common/EmptyState';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { formatDate, truncateText, stripHtml } from '../../utils/helpers';

export default function NewsAdmin() {
  const [news, setNews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const toast = useToast();
  const navigate = useNavigate();

  useEffect(() => { loadNews(); }, [page]);

  const loadNews = async () => {
    setLoading(true);
    try {
      const result = await newsService.getAll(page);
      if (result.success) {
        setNews(result.data);
        setTotal(result.meta.total);
        setTotalPages(result.meta.total_pages);
      }
    } catch { toast.error('Failed to load news'); }
    finally { setLoading(false); }
  };

  const handleDelete = async () => {
    setDeleteLoading(true);
    try {
      await newsService.delete(deleteId);
      toast.success('Article deleted');
      loadNews();
    } catch { toast.error('Failed to delete'); }
    finally { setDeleteLoading(false); setDeleteId(null); }
  };

  const filtered = news.filter((n) => n.title.toLowerCase().includes(search.toLowerCase()));

  if (loading && !news.length) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">News</h1>
          <p className="text-gray-500 text-sm mt-1">{total} articles</p>
        </div>
        <Link to="/admin/news/new">
          <Button><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>New Article</Button>
        </Link>
      </div>

      <div className="max-w-sm">
        <SearchBar value={search} onChange={setSearch} placeholder="Search articles..." />
      </div>

      <div className="card p-0 overflow-hidden">
        {!filtered.length ? (
          <EmptyState title="No news articles" description="Create your first news article." actionLabel="Create Article" onAction={() => navigate('/admin/news/new')} />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead>
                <tr className="bg-gray-50">
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Published</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {filtered.map((n) => (
                  <tr key={n.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <Link to={`/admin/news/${n.id}/edit`} className="text-sm font-medium text-primary-600 hover:text-primary-800">{n.title}</Link>
                      <p className="text-xs text-gray-400 mt-1">{truncateText(stripHtml(n.content), 80)}</p>
                    </td>
                    <td className="px-6 py-4"><StatusBadge status={n.status} /></td>
                    <td className="px-6 py-4 text-sm text-gray-500">{formatDate(n.published_at)}</td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Link to={`/admin/news/${n.id}/edit`} className="text-sm text-gray-400 hover:text-primary-600">Edit</Link>
                        <button onClick={() => setDeleteId(n.id)} className="text-sm text-gray-400 hover:text-red-600">Delete</button>
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

      <ConfirmDialog isOpen={!!deleteId} onClose={() => setDeleteId(null)} onConfirm={handleDelete} title="Delete Article" message="Are you sure you want to delete this article?" loading={deleteLoading} />
    </div>
  );
}
