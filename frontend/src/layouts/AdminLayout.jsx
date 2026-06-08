import { Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import AdminSidebar from '../components/layout/AdminSidebar';
import AdminHeader from '../components/layout/AdminHeader';

export default function AdminLayout() {
  const { user } = useAuth();

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="flex">
        {/* Sidebar */}
        <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r border-gray-200">
          <AdminSidebar user={user} />
        </aside>

        {/* Main content area */}
        <div className="lg:pl-64 flex flex-col flex-1">
          {/* Header */}
          <AdminHeader user={user} />

          {/* Page content */}
          <main className="flex-1 px-4 sm:px-6 lg:px-8 py-8">
            <Outlet />
          </main>
        </div>
      </div>

      {/* Mobile sidebar overlay - handled in AdminHeader */}
    </div>
  );
}
