<?php

return [
    'max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
    'image_max_size' => (int)($_ENV['UPLOAD_IMAGE_MAX_SIZE'] ?? 5242880),
    'path' => $_ENV['UPLOAD_PATH'] ?? 'uploads',
    'allowed_image_types' => explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'image/jpeg,image/png,image/gif,image/webp'),
    'allowed_document_types' => explode(',', $_ENV['ALLOWED_DOCUMENT_TYPES'] ?? 'application/pdf'),
    'max_files_per_hour' => (int)($_ENV['MAX_FILES_PER_HOUR'] ?? 20),
    'image_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'document_extensions' => ['pdf'],
];
