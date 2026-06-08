<?php

namespace App\Controllers;

use App\Services\FileService;
use App\Helpers\Response;
use App\Helpers\SessionManager;

class FileController
{
    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);

        $result = $this->fileService->getAll($page, $perPage);
        Response::paginated($result['files'], $page, $perPage, $result['total']);
    }

    public function upload(): void
    {
        $user = SessionManager::getUser();

        if (!isset($_FILES['file'])) {
            Response::validationError([['field' => 'file', 'message' => 'No file provided']]);
        }

        $entityType = $_POST['entity_type'] ?? null;
        $entityId = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : null;

        $result = $this->fileService->upload($_FILES['file'], $user['id'], $entityType, $entityId);

        if (!$result['success']) {
            Response::error('UPLOAD_FAILED', $result['error']);
        }

        Response::success($result['file'], 'File uploaded successfully', 201);
    }

    public function destroy(array $params): void
    {
        $user = SessionManager::getUser();

        $result = $this->fileService->delete((int)$params['id'], $user['id']);

        if (!$result['success']) {
            Response::error('DELETE_FAILED', $result['error']);
        }

        Response::success(null, $result['message']);
    }
}
