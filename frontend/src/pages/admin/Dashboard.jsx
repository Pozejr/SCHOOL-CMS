import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { dashboardService } from '../../services/dashboardService';
import Spinner from '../../components/common/Spinner';
import { formatRelativeTime } from '../../utils/helpers';

const statCards = [
  { key: 'total_pages', label: 'Pages', icon: 'document', color: 'blue', href: '/admin/pages' },
  { key: 'published_news', label: 'News', icon: 'newspaper', color: 'green', href: '/admin/news' },
  { key: 'total_events', label: 'Events', icon: 'calendar', color: 'purple', href: '/admin/events' },
  { key: 'total_files', label: 'Files', icon: 'image', color: 'orange', href: '/admin/files' },
];

const colorMap = {
  blue: { bg: 'bg-blue-50', icon: 'text-blue-600', border: 'border-blue-100' },
  green: { bg: 'bg-green-50', icon: 'text-green-600', border: 'border-green-100' },
  purple: { bg: 'bg-purple-50', icon: 'text-purple-600', border: 'border-purple-100' },
  orange: { bg: 'bg-orange-50', icon: 'text-orange-600', border: 'border-orange-100' },
};

const icons = {
  document: (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
  ),
  newspaper: (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
    </svg>
  ),
  calendar: (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
  ),
  image: (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
  ),
};

export default function Dashboard() {
  const [stats, setStats] = useState(null);
  const [activity, setActivity] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      const [statsRes, activityRes] = await Promise.all([
        dashboardService.getStats(),
        dashboardService.getRecentActivity(8),
      ]);
      if (statsRes.success) setStats(statsRes.data);
      if (activityRes.success) setActivity(activityRes.data);
    } catch (err) {
      console.error('Dashboard load error:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="space-y-8">
      {/* Welcome */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 mt-1">Welcome to your School CMS admin dashboard.</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((card) => {
          const c = colorMap[card.color];
          return (
            <Link
              key={card.key}
              to={card.href}
              className={`card flex items-center gap-4 hover:shadow-md transition-shadow border ${c.border}`}
            >
              <div className={`w-12 h-12 rounded-xl ${c.bg} ${c.icon} flex items-center justify-center`}>
                {icons[card.icon]}
              </div>
              <div>
                <p className="text-2xl font-bold text-gray-900">{stats?.[card.key] ?? 0}</p>
                <p className="text-sm text-gray-500">{card.label}</p>
              </div>
            </Link>
          );
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Activity */}
        <div className="card">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
          {activity.length === 0 ? (
            <p className="text-gray-400 text-sm py-4">No recent activity</p>
          ) : (
            <div className="space-y-3">
              {activity.map((item) => (
                <div key={item.id} className="flex items-start gap-3 py-2 border-b border-gray-50 last:border-0">
                  <div className="w-8 h-8 bg-primary-50 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span className="text-xs font-semibold text-primary-700">
                      {item.first_name?.[0]}{item.last_name?.[0]}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-gray-700">
                      <span className="font-medium">{item.first_name} {item.last_name}</span>{' '}
                      {item.description}
                    </p>
                    <p className="text-xs text-gray-400 mt-0.5">{formatRelativeTime(item.created_at)}</p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Quick Actions */}
        <div className="card">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-2 gap-3">
            {[
              { label: 'New Page', href: '/admin/pages/new', icon: '📄', color: 'bg-blue-50 text-blue-700' },
              { label: 'New Article', href: '/admin/news/new', icon: '📝', color: 'bg-green-50 text-green-700' },
              { label: 'New Event', href: '/admin/events/new', icon: '📅', color: 'bg-purple-50 text-purple-700' },
              { label: 'Upload File', href: '/admin/files', icon: '🖼️', color: 'bg-orange-50 text-orange-700' },
            ].map((action) => (
              <Link
                key={action.label}
                to={action.href}
                className="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors"
              >
                <span className="text-xl">{action.icon}</span>
                <span className="text-sm font-medium text-gray-700">{action.label}</span>
              </Link>
            ))}
          </div>

          {/* System Info */}
          <div className="mt-6 pt-5 border-t border-gray-100">
            <h4 className="text-sm font-semibold text-gray-700 mb-3">System Overview</h4>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Active Users</span>
                <span className="font-medium text-gray-900">{stats?.active_users ?? 0}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Published Pages</span>
                <span className="font-medium text-gray-900">{stats?.published_pages ?? 0}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-500">Upcoming Events</span>
                <span className="font-medium text-gray-900">{stats?.upcoming_events ?? 0}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
