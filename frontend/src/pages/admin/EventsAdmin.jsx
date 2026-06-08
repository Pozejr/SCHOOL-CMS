import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { eventService } from '../../services/eventService';
import { useToast } from '../../contexts/ToastContext';
import Button from '../../components/common/Button';
import StatusBadge from '../../components/common/StatusBadge';
import Pagination from '../../components/common/Pagination';
import Spinner from '../../components/common/Spinner';
import EmptyState from '../../components/common/EmptyState';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { formatDate } from '../../utils/helpers';

export default function EventsAdmin() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const toast = useToast();
  const navigate = useNavigate();

  useEffect(() => { loadEvents(); }, [page]);

  const loadEvents = async () => {
    setLoading(true);
    try {
      const result = await eventService.getAll(page);
      if (result.success) {
        setEvents(result.data);
        setTotal(result.meta.total);
        setTotalPages(result.meta.total_pages);
      }
    } catch { toast.error('Failed to load events'); }
    finally { setLoading(false); }
  };

  const handleDelete = async () => {
    setDeleteLoading(true);
    try {
      await eventService.delete(deleteId);
      toast.success('Event deleted');
      loadEvents();
    } catch { toast.error('Failed to delete'); }
    finally { setDeleteLoading(false); setDeleteId(null); }
  };

  if (loading && !events.length) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Events</h1>
          <p className="text-gray-500 text-sm mt-1">{total} events</p>
        </div>
        <Link to="/admin/events/new"><Button><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>New Event</Button></Link>
      </div>

      <div className="card p-0 overflow-hidden">
        {!events.length ? (
          <EmptyState title="No events" description="Create your first event." actionLabel="Create Event" onAction={() => navigate('/admin/events/new')} />
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead>
                <tr className="bg-gray-50">
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Venue</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {events.map((ev) => (
                  <tr key={ev.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <Link to={`/admin/events/${ev.id}/edit`} className="text-sm font-medium text-primary-600 hover:text-primary-800">{ev.title}</Link>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-500">{formatDate(ev.event_date)}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{ev.venue || '—'}</td>
                    <td className="px-6 py-4"><StatusBadge status={ev.status} /></td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <Link to={`/admin/events/${ev.id}/edit`} className="text-sm text-gray-400 hover:text-primary-600">Edit</Link>
                        <button onClick={() => setDeleteId(ev.id)} className="text-sm text-gray-400 hover:text-red-600">Delete</button>
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
      <ConfirmDialog isOpen={!!deleteId} onClose={() => setDeleteId(null)} onConfirm={handleDelete} title="Delete Event" message="Are you sure you want to delete this event?" loading={deleteLoading} />
    </div>
  );
}
