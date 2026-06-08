import { Link } from 'react-router-dom';

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
      <div className="text-center">
        <h1 className="text-8xl font-bold text-gray-200">404</h1>
        <h2 className="text-2xl font-bold text-gray-900 mt-4">Page Not Found</h2>
        <p className="text-gray-500 mt-2">The page you're looking for doesn't exist or has been moved.</p>
        <div className="flex items-center justify-center gap-4 mt-8">
          <Link to="/" className="btn-primary px-6 py-3">Go Home</Link>
          <Link to="/admin" className="btn-secondary px-6 py-3">Admin Dashboard</Link>
        </div>
      </div>
    </div>
  );
}
