import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

export default function AccessDenied() {
  const { user } = useAuth();

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="text-center">
        <div className="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg className="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <h1 className="text-4xl font-bold text-gray-900">Access Denied</h1>
        <h2 className="text-lg text-gray-600 mt-3">You don't have permission to access this page</h2>
        {user && (
          <p className="text-sm text-gray-400 mt-2">
            Logged in as <span className="font-medium text-gray-600">{user.first_name} {user.last_name}</span> ({user.role?.replace('_', ' ')})
          </p>
        )}
        <div className="flex items-center justify-center gap-4 mt-8">
          <Link to="/" className="btn-primary px-6 py-3">
            Go Home
          </Link>
          <Link to="/admin" className="btn-secondary px-6 py-3">
            Dashboard
          </Link>
        </div>
      </div>
    </div>
  );
}
