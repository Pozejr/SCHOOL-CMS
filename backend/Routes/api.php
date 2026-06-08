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

    public function loadRoutes(): void
    {
        $c = $this->controllers;

        // Auth
        $this->addRoute('POST', '/auth/login', fn() => $c['auth']->login());
        $this->addRoute('POST', '/auth/logout', fn() => $c['auth']->logout(), true);
        $this->addRoute('GET', '/auth/me', fn() => $c['auth']->me(), true);
        $this->addRoute('GET', '/auth/csrf-token', fn() => $c['auth']->csrfToken());

        // Dashboard
        $this->addRoute('GET', '/dashboard/stats', fn() => $c['dashboard']->stats(), true);
        $this->addRoute('GET', '/dashboard/activity', fn() => $c['dashboard']->activity(), true);

        // Pages (public)
        $this->addRoute('GET', '/pages', fn() => $c['page']->index());
        $this->addRoute('GET', '/pages/slug/{slug}', fn($p) => $c['page']->showBySlug($p));
        $this->addRoute('GET', '/pages/{id}', fn($p) => $c['page']->show($p));

        // Pages (admin)
        $this->addRoute('GET', '/pages/admin/all', fn() => $c['page']->adminIndex(), true);
        $this->addRoute('POST', '/pages', fn() => $c['page']->store(), true);
        $this->addRoute('PUT', '/pages/{id}', fn($p) => $c['page']->update($p), true);
        $this->addRoute('DELETE', '/pages/{id}', fn($p) => $c['page']->destroy($p), true);

        // Page Sections
        $this->addRoute('GET', '/pages/{page_id}/sections', fn($p) => $c['section']->index($p), true);
        $this->addRoute('POST', '/pages/{page_id}/sections', fn($p) => $c['section']->store($p), true);
        $this->addRoute('PUT', '/pages/sections/{id}', fn($p) => $c['section']->update($p), true);
        $this->addRoute('DELETE', '/pages/sections/{id}', fn($p) => $c['section']->destroy($p), true);
        $this->addRoute('PUT', '/pages/{page_id}/sections/reorder', fn($p) => $c['section']->reorder($p), true);

        // Templates
        $this->addRoute('GET', '/templates', fn() => $c['section']->templates(), true);
        $this->addRoute('GET', '/templates/{slug}', fn($p) => $c['section']->templateBySlug($p), true);
        $this->addRoute('POST', '/pages/{page_id}/apply-template', fn($p) => $c['section']->applyTemplate($p), true);

        // News (public)
        $this->addRoute('GET', '/news', fn() => $c['news']->index());
        $this->addRoute('GET', '/news/slug/{slug}', fn($p) => $c['news']->showBySlug($p));
        $this->addRoute('GET', '/news/{id}', fn($p) => $c['news']->show($p));

        // News (admin)
        $this->addRoute('GET', '/news/admin/all', fn() => $c['news']->adminIndex(), true);
        $this->addRoute('POST', '/news', fn() => $c['news']->store(), true);
        $this->addRoute('PUT', '/news/{id}', fn($p) => $c['news']->update($p), true);
        $this->addRoute('DELETE', '/news/{id}', fn($p) => $c['news']->destroy($p), true);

        // Events (public)
        $this->addRoute('GET', '/events', fn() => $c['event']->index());
        $this->addRoute('GET', '/events/slug/{slug}', fn($p) => $c['event']->showBySlug($p));
        $this->addRoute('GET', '/events/{id}', fn($p) => $c['event']->show($p));

        // Events (admin)
        $this->addRoute('GET', '/events/admin/all', fn() => $c['event']->adminIndex(), true);
        $this->addRoute('POST', '/events', fn() => $c['event']->store(), true);
        $this->addRoute('PUT', '/events/{id}', fn($p) => $c['event']->update($p), true);
        $this->addRoute('DELETE', '/events/{id}', fn($p) => $c['event']->destroy($p), true);

        // Files
        $this->addRoute('GET', '/files', fn() => $c['file']->index(), true);
        $this->addRoute('POST', '/files/upload', fn() => $c['file']->upload(), true);
        $this->addRoute('DELETE', '/files/{id}', fn($p) => $c['file']->destroy($p), true);

        // Users (super_admin only)
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

        // Sort routes: more specific patterns first (longer = more specific)
        $routes = $this->routes;
        usort($routes, function ($a, $b) {
            $la = strlen(str_replace('{', '', $b['pattern']));
            $lb = strlen(str_replace('{', '', $a['pattern']));
            return $la - $lb;
        });

        foreach ($routes as $route) {
            if ($route['method'] !== $method) continue;

            $params = $this->matchPattern($route['pattern'], $path);
            if ($params !== false) {
                if ($route['auth']) {
                    $authMiddleware = $this->controllers['_auth_middleware'] ?? null;
                    if ($authMiddleware) {
                        $authMiddleware->handle();
                    }
                }

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
