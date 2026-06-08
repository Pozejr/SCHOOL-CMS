<?php

/**
 * School CMS — API Entry Point
 */

error_reporting(E_ALL);

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Explicitly load the router (avoids autoloader filename case issues on Windows)
require_once __DIR__ . '/../routes/ApiRouter.php';

// Load environment variables — skip if already loaded (persistent process / OPcache)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && empty($_ENV['APP_NAME'])) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (preg_match('/^"(.*)"$/', $value, $m)) { $value = $m[1]; }
        elseif (preg_match("/^'(.*)'$/", $value, $m)) { $value = $m[1]; }
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// Load configurations
$appConfig = require __DIR__ . '/../config/app.php';
$dbConfig  = require __DIR__ . '/../config/database.php';
$corsConfig = require __DIR__ . '/../config/cors.php';
$uploadConfig = require __DIR__ . '/../config/upload.php';

// Set timezone
date_default_timezone_set($appConfig['timezone']);

// Security headers — set as defaults, overridden by static file handler below
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Serve static upload files (images, documents).
// MUST run before gzip buffering and session start — static files need
// their own headers (Cache-Control, ETag, Content-Type) and skip session overhead.
// This is the highest-traffic path for public visitors viewing images.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($uri, '/uploads/') && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'])) {
    $backendRoot = dirname(__DIR__);
    $filePath = $backendRoot . $uri;

    if (is_file($filePath)) {
        $mimeTypes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
            'svg'  => 'image/svg+xml',
        ];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        // ETag-based caching — respond 304 if file unchanged
        $lastModified = filemtime($filePath);
        $etag = '"' . md5($filePath . $lastModified) . '"';
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        $clientModified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';

        // Override the JSON content-type set above
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

        if ($clientEtag === $etag || ($clientModified && strtotime($clientModified) >= $lastModified)) {
            http_response_code(304);
            exit;
        }

        header('Content-Length: ' . filesize($filePath));
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            readfile($filePath);
        }
        exit;
    }

    // File not found — fall through to API 404
}

// Enable gzip compression for API responses (3-5× smaller JSON)
// Only for API routes — static files are handled above.
if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

// Setup logging
use App\Helpers\Logger;
use App\Helpers\SessionManager;

$logFile = __DIR__ . '/../logs/app.log';
if (!is_dir(dirname($logFile))) { mkdir(dirname($logFile), 0755, true); }
Logger::setLogFile($logFile);

// Handle CORS
$corsMiddleware = new \App\Middleware\CorsMiddleware($corsConfig);
$corsMiddleware->handle();

// Start session
SessionManager::start(
    $appConfig['session_name'] ?? 'school_cms_session',
    (int)($_ENV['SESSION_LIFETIME'] ?? 7200)
);

// Database connection
try {
    $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
} catch (PDOException $e) {
    Logger::critical('Database connection failed', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'DB_ERROR',
            'message' => ($appConfig['debug'] ?? false) ? $e->getMessage() : 'Database connection failed'
        ]
    ]);
    exit;
}

// Initialize models
$userModel        = new \App\Models\User($pdo);
$pageModel        = new \App\Models\Page($pdo);
$pageSectionModel = new \App\Models\PageSection($pdo);
$pageTemplateModel = new \App\Models\PageTemplate($pdo);
$newsModel        = new \App\Models\News($pdo);
$eventModel       = new \App\Models\Event($pdo);
$fileModel        = new \App\Models\File($pdo);
$activityLogModel = new \App\Models\ActivityLog($pdo);

// Initialize services
$authService      = new \App\Services\AuthService($userModel, $activityLogModel);
$pageService      = new \App\Services\PageService($pageModel, $pageSectionModel, $activityLogModel);
$sectionService   = new \App\Services\PageSectionService($pageSectionModel, $activityLogModel);
$templateService  = new \App\Services\TemplateService($pageTemplateModel, $pageSectionModel);
$newsService      = new \App\Services\NewsService($newsModel, $activityLogModel);
$eventService     = new \App\Services\EventService($eventModel, $activityLogModel);
$fileService      = new \App\Services\FileService($fileModel, $activityLogModel, $uploadConfig);
$dashboardService = new \App\Services\DashboardService($pdo);
$userService      = new \App\Services\UserService($userModel, $activityLogModel);

// Initialize controllers
$authController      = new \App\Controllers\AuthController($authService);
$pageController      = new \App\Controllers\PageController($pageService);
$sectionController   = new \App\Controllers\PageSectionController($sectionService, $templateService);
$newsController      = new \App\Controllers\NewsController($newsService);
$eventController     = new \App\Controllers\EventController($eventService);
$fileController      = new \App\Controllers\FileController($fileService);
$dashboardController = new \App\Controllers\DashboardController($dashboardService);
$userController      = new \App\Controllers\UserController($userService);

// CSRF middleware
$csrfMiddleware = new \App\Middleware\CsrfMiddleware();
$csrfMiddleware->handle();

// Auth middleware
$authMiddleware = new \App\Middleware\AuthMiddleware((int)($_ENV['SESSION_LIFETIME'] ?? 7200));

// Router
$router = new \App\Routes\ApiRouter();
$router->setController('auth', $authController);
$router->setController('page', $pageController);
$router->setController('section', $sectionController);
$router->setController('news', $newsController);
$router->setController('event', $eventController);
$router->setController('file', $fileController);
$router->setController('dashboard', $dashboardController);
$router->setController('user', $userController);
$router->setController('_auth_middleware', $authMiddleware);

$router->loadRoutes();
$router->dispatch();
