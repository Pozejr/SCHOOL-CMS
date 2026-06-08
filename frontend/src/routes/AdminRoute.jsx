import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

/**
 * AdminRoute — guards admin routes.
 *
 * - Unauthenticated → redirect to /admin/login
 * - Authenticated but not admin/super_admin → redirect to /access-denied
 * - Authenticated with valid role → render children
 */
export default function AdminRoute({ children, roles }) {
  const { isAuthenticated, loading, user } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/admin/login" replace />;
  }

  // Default allowed roles for admin routes: super_admin and admin
  const allowedRoles = roles || ['super_admin', 'admin'];
  if (!allowedRoles.includes(user?.role)) {
    return <Navigate to="/access-denied" replace />;
  }

  return children;
}
