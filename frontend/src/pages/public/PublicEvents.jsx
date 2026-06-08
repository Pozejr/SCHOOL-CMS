import { useState, useEffect } from 'react';
import { eventService } from '../../services/eventService';
import Spinner from '../../components/common/Spinner';
import Pagination from '../../components/common/Pagination';
import { formatDate } from '../../utils/helpers';

export default function PublicEvents() {
  const [events, setEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => { loadEvents(); }, [page]);

  const loadEvents = async () => {
    setLoading(true);
    try {
      const result = await eventService.getPublished(page, 12);
      if (result.success) {
        setEvents(result.data);
        setTotalPages(result.meta.total_pages);
      }
    } catch {}
    finally { setLoading(false); }
  };

  if (loading && !events.length) return <Spinner size="lg" className="py-20" />;

  return (
    <div>
      <section className="bg-primary-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold">Events</h1>
          <p className="mt-3 text-primary-200 text-lg">Upcoming school events and activities.</p>
        </div>
      </section>
      <section className="py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {!events.length ? (
            <p className="text-center text-gray-500 py-12">No upcoming events at this time.</p>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {events.map((event) => (
                <div key={event.id} className="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow">
                  <div className="flex items-start gap-4 mb-4">
                    <div className="flex-shrink-0 w-16 h-16 bg-primary-50 rounded-lg flex flex-col items-center justify-center">
                      <span className="text-xs text-primary-600 font-medium">{new Date(event.event_date).toLocaleDateString('en-US', { month: 'short' })}</span>
                      <span className="text-xl font-bold text-primary-700">{new Date(event.event_date).getDate()}</span>
                      <span className="text-xs text-primary-600">{new Date(event.event_date).getFullYear()}</span>
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">{event.title}</h3>
                      {event.venue && <p className="text-sm text-gray-500 mt-1 flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                        {event.venue}
                      </p>}
                    </div>
                  </div>
                  <div className="text-sm text-gray-600" dangerouslySetInnerHTML={{ __html: event.description?.substring(0, 150) + (event.description?.length > 150 ? '...' : '') }} />
                </div>
              ))}
            </div>
          )}
          <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />
        </div>
      </section>
    </div>
  );
}
