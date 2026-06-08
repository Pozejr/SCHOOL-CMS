import { Routes, Route } from 'react-router-dom';
import React, { lazy, Suspense } from 'react';

// Layouts — loaded eagerly (needed for every route)
import AdminLayout from '../layouts/AdminLayout';
import PublicLayout from '../layouts/PublicLayout';

// Route guards — tiny, loaded eagerly
import AdminRoute from './AdminRoute';

// Shared loading fallback for lazy-loaded pages
const PageLoader = () => (
  <div className="flex items-center justify-center min-h-[60vh]">
    <div className="text-center">
      <div className="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-3" />
      <p className="text-sm text-gray-400">Loading…</p>
    </div>
  </div>
);

// Admin Pages — lazy loaded (only loaded when admin visits that route)
const Login = lazy(() => import('../pages/admin/Login'));
const Dashboard = lazy(() => import('../pages/admin/Dashboard'));
const Pages = lazy(() => import('../pages/admin/Pages'));
const PageEditor = lazy(() => import('../pages/admin/PageEditor'));
const PageBuilder = lazy(() => import('../pages/admin/PageBuilder'));
const NewsAdmin = lazy(() => import('../pages/admin/NewsAdmin'));
const NewsEditor = lazy(() => import('../pages/admin/NewsEditor'));
const EventsAdmin = lazy(() => import('../pages/admin/EventsAdmin'));
const EventEditor = lazy(() => import('../pages/admin/EventEditor'));
const FileManager = lazy(() => import('../pages/admin/FileManager'));
const Users = lazy(() => import('../pages/admin/Users'));

// Public Pages — lazy loaded (only loaded when visitor hits that route)
const Home = lazy(() => import('../pages/public/Home'));
const About = lazy(() => import('../pages/public/About'));
const Academics = lazy(() => import('../pages/public/Academics'));
const Admissions = lazy(() => import('../pages/public/Admissions'));
const PublicNews = lazy(() => import('../pages/public/PublicNews'));
const PublicNewsArticle = lazy(() => import('../pages/public/PublicNewsArticle'));
const PublicEvents = lazy(() => import('../pages/public/PublicEvents'));
const PublicPage = lazy(() => import('../pages/public/PublicPage'));
const Contact = lazy(() => import('../pages/public/Contact'));

// Misc
const NotFound = lazy(() => import('../pages/NotFound'));
const AccessDenied = lazy(() => import('../pages/AccessDenied'));

// Wrapper to avoid repeating <Suspense> for every route
function LazyRoute({ children }) {
  return <Suspense fallback={<PageLoader />}>{children}</Suspense>;
}

export default function AppRoutes() {
  return (
    <Suspense fallback={<PageLoader />}>
      <Routes>
        {/* ===== PUBLIC ROUTES — No authentication required ===== */}
        <Route element={<PublicLayout />}>
          <Route path="/" element={<LazyRoute><Home /></LazyRoute>} />
          <Route path="/about" element={<LazyRoute><About /></LazyRoute>} />
          <Route path="/academics" element={<LazyRoute><Academics /></LazyRoute>} />
          <Route path="/admissions" element={<LazyRoute><Admissions /></LazyRoute>} />
          <Route path="/news" element={<LazyRoute><PublicNews /></LazyRoute>} />
          <Route path="/news/:slug" element={<LazyRoute><PublicNewsArticle /></LazyRoute>} />
          <Route path="/events" element={<LazyRoute><PublicEvents /></LazyRoute>} />
          <Route path="/contact" element={<LazyRoute><Contact /></LazyRoute>} />
          {/* Dynamic public pages */}
          <Route path="/page/:slug" element={<LazyRoute><PublicPage /></LazyRoute>} />
        </Route>

        {/* Login Route — accessible without authentication */}
        <Route path="/admin/login" element={<LazyRoute><Login /></LazyRoute>} />

        {/* Access Denied page */}
        <Route path="/access-denied" element={<LazyRoute><AccessDenied /></LazyRoute>} />

        {/* ===== ADMIN ROUTES — Require super_admin or admin role ===== */}
        <Route
          path="/admin"
          element={
            <AdminRoute roles={['super_admin', 'admin']}>
              <AdminLayout />
            </AdminRoute>
          }
        >
          <Route index element={<LazyRoute><Dashboard /></LazyRoute>} />
          <Route path="pages" element={<LazyRoute><Pages /></LazyRoute>} />
          <Route path="pages/new" element={<LazyRoute><PageBuilder /></LazyRoute>} />
          <Route path="pages/:id/edit" element={<LazyRoute><PageEditor /></LazyRoute>} />
          <Route path="news" element={<LazyRoute><NewsAdmin /></LazyRoute>} />
          <Route path="news/new" element={<LazyRoute><NewsEditor /></LazyRoute>} />
          <Route path="news/:id/edit" element={<LazyRoute><NewsEditor /></LazyRoute>} />
          <Route path="events" element={<LazyRoute><EventsAdmin /></LazyRoute>} />
          <Route path="events/new" element={<LazyRoute><EventEditor /></LazyRoute>} />
          <Route path="events/:id/edit" element={<LazyRoute><EventEditor /></LazyRoute>} />
          <Route path="files" element={<LazyRoute><FileManager /></LazyRoute>} />
          {/* Users — super_admin only */}
          <Route
            path="users"
            element={
              <AdminRoute roles={['super_admin']}>
                <LazyRoute><Users /></LazyRoute>
              </AdminRoute>
            }
          />
        </Route>

        {/* 404 */}
        <Route path="*" element={<LazyRoute><NotFound /></LazyRoute>} />
      </Routes>
    </Suspense>
  );
}
