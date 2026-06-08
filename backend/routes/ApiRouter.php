<?php

/**
 * API Router
 */

namespace App\Routes;

class ApiRouter
{
    private string $basePath;
    private array $controllers = [];
    private array $routes = [];

    public function __construct(string $basePath = '/api/v1')
    {
        $this->basePath = $basePath;
    }

    public function setController(string $name, object $controller): void
    {
        $this->controllers[$name] = $controller;
    }

    public function addRoute(string $method, string $pattern, callable $handler, bool $auth = false, array $roles = []): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'auth', 'roles');
    }

    /**
     * Whether routes have been compiled (sorted + regex pre-built).
     * Done once after loadRoutes() — avoids re-sorting on every request.
     */
    private bool $compiled = false;

    public function loadRoutes(): void
    {
        $c = $this->controllers;

        // ================================================================
        // PUBLIC ROUTES — No authentication required
        // These endpoints serve public-facing content to website visitors.
        // ================================================================

        // Auth — public endpoints
        $this->addRoute('POST', '/auth/login', fn() => $c['auth']->login());
        $this->addRoute('GET', '/auth/csrf-token', fn() => $c['auth']->csrfToken());

        // Pages (public read)
        $this->addRoute('GET', '/pages', fn() => $c['page']->index());
        $this->addRoute('GET', '/pages/slug/{slug}', fn($p) => $c['page']->showBySlug($p));
        $this->addRoute('GET', '/pages/{id}', fn($p) => $c['page']->show($p));

        // News (public read)
        $this->addRoute('GET', '/news', fn() => $c['news']->index());
        $this->addRoute('GET', '/news/slug/{slug}', fn($p) => $c['news']->showBySlug($p));
        $this->addRoute('GET', '/news/{id}', fn($p) => $c['news']->show($p));

        // Events (public read)
        $this->addRoute('GET', '/events', fn() => $c['event']->index());
        $this->addRoute('GET', '/events/slug/{slug}', fn($p) => $c['event']->showBySlug($p));
        $this->addRoute('GET', '/events/{id}', fn($p) => $c['event']->show($p));

        // ================================================================
        // PROTECTED ROUTES — Require authentication (super_admin or admin)
        // CMS management endpoints. All require auth + admin role.
        // ================================================================

        // Auth — authenticated endpoints
        $this->addRoute('POST', '/auth/logout', fn() => $c['auth']->logout(), true, ['super_admin', 'admin', 'editor']);
        $this->addRoute('GET', '/auth/me', fn() => $c['auth']->me(), true, ['super_admin', 'admin', 'editor']);

        // Dashboard (admin only)
        $this->addRoute('GET', '/dashboard/stats', fn() => $c['dashboard']->stats(), true, ['super_admin', 'admin']);
        $this->addRoute('GET', '/dashboard/activity', fn() => $c['dashboard']->activity(), true, ['super_admin', 'admin']);

        // Pages (admin — create, update, delete)
        $this->addRoute('GET', '/pages/admin/all', fn() => $c['page']->adminIndex(), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/pages', fn() => $c['page']->store(), true, ['super_admin', 'admin']);
        $this->addRoute('PUT', '/pages/{id}', fn($p) => $c['page']->update($p), true, ['super_admin', 'admin']);
        $this->addRoute('DELETE', '/pages/{id}', fn($p) => $c['page']->destroy($p), true, ['super_admin', 'admin']);

        // Page Sections (admin)
        $this->addRoute('GET', '/pages/{page_id}/sections', fn($p) => $c['section']->index($p), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/pages/{page_id}/sections', fn($p) => $c['section']->store($p), true, ['super_admin', 'admin']);
        $this->addRoute('PUT', '/pages/sections/{id}', fn($p) => $c['section']->update($p), true, ['super_admin', 'admin']);
        $this->addRoute('DELETE', '/pages/sections/{id}', fn($p) => $c['section']->destroy($p), true, ['super_admin', 'admin']);
        $this->addRoute('PUT', '/pages/{page_id}/sections/reorder', fn($p) => $c['section']->reorder($p), true, ['super_admin', 'admin']);

        // Templates (admin)
        $this->addRoute('GET', '/templates', fn() => $c['section']->templates(), true, ['super_admin', 'admin']);
        $this->addRoute('GET', '/templates/{slug}', fn($p) => $c['section']->templateBySlug($p), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/pages/{page_id}/apply-template', fn($p) => $c['section']->applyTemplate($p), true, ['super_admin', 'admin']);

        // News (admin — create, update, delete)
        $this->addRoute('GET', '/news/admin/all', fn() => $c['news']->adminIndex(), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/news', fn() => $c['news']->store(), true, ['super_admin', 'admin']);
        $this->addRoute('PUT', '/news/{id}', fn($p) => $c['news']->update($p), true, ['super_admin', 'admin']);
        $this->addRoute('DELETE', '/news/{id}', fn($p) => $c['news']->destroy($p), true, ['super_admin', 'admin']);

        // Events (admin — create, update, delete)
        $this->addRoute('GET', '/events/admin/all', fn() => $c['event']->adminIndex(), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/events', fn() => $c['event']->store(), true, ['super_admin', 'admin']);
        $this->addRoute('PUT', '/events/{id}', fn($p) => $c['event']->update($p), true, ['super_admin', 'admin']);
        $this->addRoute('DELETE', '/events/{id}', fn($p) => $c['event']->destroy($p), true, ['super_admin', 'admin']);

        // Files (admin)
        $this->addRoute('GET', '/files', fn() => $c['file']->index(), true, ['super_admin', 'admin']);
        $this->addRoute('POST', '/files/upload', fn() => $c['file']->upload(), true, ['super_admin', 'admin']);
        $this->addRoute('DELETE', '/files/{id}', fn($p) => $c['file']->destroy($p), true, ['super_admin', 'admin']);

        // ================================================================
        // SUPER ADMIN ONLY ROUTES — Require super_admin role
        // User management is restricted to super_admin.
        // ================================================================

        $this->addRoute('GET', '/users', fn() => $c['user']->index(), true, ['super_admin']);
        $this->addRoute('POST', '/users', fn() => $c['user']->store(), true, ['super_admin']);
        $this->addRoute('PUT', '/users/{id}', fn($p) => $c['user']->update($p), true, ['super_admin']);
        $this->addRoute('DELETE', '/users/{id}', fn($p) => $c['user']->destroy($p), true, ['super_admin']);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (str_starts_with($uri, $this->basePath)) {
            $path = substr($uri, strlen($this->basePath));
        } else {
            $this->sendNotFound();
            return;
        }

        // Compile routes once: sort by specificity + pre-build regex patterns.
        // This avoids re-sorting and re-compiling regex on every request.
        if (!$this->compiled) {
            usort($this->routes, function ($a, $b) {
                $la = strlen(str_replace('{', '', $b['pattern']));
                $lb = strlen(str_replace('{', '', $a['pattern']));
                return $la - $lb;
            });
            // Pre-compile regex for each route
            foreach ($this->routes as &$route) {
                $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['pattern']);
                $route['_regex'] = '#^' . $regex . '$#';
            }
            unset($route);
            $this->compiled = true;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['_regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Authentication check — verifies session is valid and not expired
                if ($route['auth']) {
                    $authMiddleware = $this->controllers['_auth_middleware'] ?? null;
                    if ($authMiddleware) {
                        $authMiddleware->handle();
                    }
                }

                // Authorization check — validates user role against allowed roles
                if (!empty($route['roles'])) {
                    $roleMiddleware = new \App\Middleware\RoleMiddleware();
                    $user = \App\Helpers\SessionManager::getUser();
                    $roleMiddleware->handle($user, $route['roles']);
                }

                $route['handler']($params);
                return;
            }
        }

        $this->sendNotFound();
    }

    private function matchPattern(string $pattern, string $path): array|false
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return false;
    }

    private function sendNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => ['code' => 'NOT_FOUND', 'message' => 'Endpoint not found']
        ]);
        exit;
    }
}
