<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/media_library.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$mode = trim((string) post_input('mode', ''));

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov',
    'video/x-msvideo' => 'avi',
];

if ($mode === 'upload') {
    $files = $_FILES['asset_files'] ?? null;
    if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
        flash_set('error', 'Please choose at least one media file to upload.');
        redirect('admin/media_library.php');
    }

    $adminUser = admin_user();
    $uploadedBy = (int) ($adminUser['id'] ?? 0);

    $targetDirectory = UPLOAD_BASE_PATH . '/media';
    ensure_directory($targetDirectory);

    $successCount = 0;
    $failures = [];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        $name = (string) ($files['name'][$i] ?? '');
        $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        $tmpPath = (string) ($files['tmp_name'][$i] ?? '');
        $size = (int) ($files['size'][$i] ?? 0);

        if ($name === '' || $error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $label = $name !== '' ? $name : ('file #' . ($i + 1));

        if ($error !== UPLOAD_ERR_OK) {
            $failures[] = $label . ' (upload error ' . $error . ')';
            continue;
        }

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $failures[] = $label . ' (invalid upload)';
            continue;
        }

        $mimeType = $finfo ? (string) finfo_file($finfo, $tmpPath) : '';
        if (!isset($allowedMimeTypes[$mimeType])) {
            $failures[] = $label . ' (unsupported type)';
            continue;
        }

        $extension = $allowedMimeTypes[$mimeType];
        $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $targetPath = $targetDirectory . '/' . $fileName;

        if (!move_uploaded_file($tmpPath, $targetPath)) {
            $failures[] = $label . ' (failed to save)';
            continue;
        }

        execute_query(
            "INSERT INTO media_assets
            (file_path, file_name, mime_type, file_size, uploaded_by)
            VALUES
            (:file_path, :file_name, :mime_type, :file_size, :uploaded_by)",
            [
                'file_path' => 'media/' . $fileName,
                'file_name' => mb_substr($name, 0, 255),
                'mime_type' => $mimeType,
                'file_size' => max(0, $size),
                'uploaded_by' => $uploadedBy > 0 ? $uploadedBy : null,
            ]
        );

        $successCount++;
    }

    if ($finfo) {
        finfo_close($finfo);
    }

    if ($successCount === 0 && !$failures) {
        flash_set('error', 'No files were submitted.');
    } elseif ($successCount === 0) {
        flash_set('error', 'No files uploaded. ' . implode('; ', $failures));
    } elseif ($failures) {
        flash_set(
            'error',
            'Uploaded ' . $successCount . ' file' . ($successCount === 1 ? '' : 's')
            . '. Skipped ' . count($failures) . ': ' . implode('; ', $failures)
        );
    } else {
        flash_set(
            'success',
            'Uploaded ' . $successCount . ' file' . ($successCount === 1 ? '' : 's')
            . '. Add metadata and destination below.'
        );
    }

    redirect('admin/media_library.php');
}

if ($mode === 'update') {
    $id = (int) post_input('id', 0);
    if ($id <= 0) {
        flash_set('error', 'Invalid media asset selected.');
        redirect('admin/media_library.php');
    }

    $record = fetch_one('SELECT id FROM media_assets WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$record) {
        flash_set('error', 'Media asset not found.');
        redirect('admin/media_library.php');
    }

    $destinationId = (int) post_input('destination_id', 0);
    if ($destinationId > 0) {
        if (!db_column_exists('media_assets', 'destination_id') || !db_table_exists('destinations')) {
            flash_set('error', 'Destination tagging is not available in this database.');
            redirect('admin/media_library.php');
        }
        $destination = fetch_one('SELECT id FROM destinations WHERE id = :id LIMIT 1', ['id' => $destinationId]);
        if (!$destination) {
            flash_set('error', 'Selected destination was not found.');
            redirect('admin/media_library.php');
        }
    }

    $altText = mb_substr(trim((string) post_input('alt_text', '')), 0, 255);
    $seoTitle = mb_substr(trim((string) post_input('seo_title', '')), 0, 255);
    $seoDescription = mb_substr(trim((string) post_input('seo_description', '')), 0, 320);
    $tags = mb_substr(trim((string) post_input('tags', '')), 0, 255);

    $updateData = [
        'alt_text' => $altText !== '' ? $altText : null,
        'seo_title' => $seoTitle !== '' ? $seoTitle : null,
        'seo_description' => $seoDescription !== '' ? $seoDescription : null,
        'tags' => $tags !== '' ? $tags : null,
        'id' => $id,
    ];
    $setParts = [
        'alt_text = :alt_text',
        'seo_title = :seo_title',
        'seo_description = :seo_description',
        'tags = :tags',
    ];
    if (db_column_exists('media_assets', 'destination_id')) {
        $setParts[] = 'destination_id = :destination_id';
        $updateData['destination_id'] = $destinationId > 0 ? $destinationId : null;
    }

    execute_query('UPDATE media_assets SET ' . implode(', ', $setParts) . ' WHERE id = :id', $updateData);

    flash_set('success', 'Media metadata saved.');
    redirect('admin/media_library.php');
}

flash_set('error', 'Unknown media action.');
redirect('admin/media_library.php');
