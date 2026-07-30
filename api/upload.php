<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_auth();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = strtolower(trim($_GET['type'] ?? 'image'));
if (!in_array($type, ['image', 'audio', 'video', 'document'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid upload type.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg  = match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the server size limit.',
        UPLOAD_ERR_PARTIAL  => 'Upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE  => 'No file was received.',
        default             => 'Upload failed (error ' . $code . ').',
    };
    http_response_code(400);
    echo json_encode(['error' => $msg]);
    exit;
}

$tmp  = $_FILES['file']['tmp_name'];
$orig = basename($_FILES['file']['name']);
$size = (int) $_FILES['file']['size'];
$mime = mime_content_type($tmp) ?: '';

$allowed = match ($type) {
    'image'    => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'audio'    => ['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav', 'audio/x-wav',
                   'audio/aac', 'audio/m4a', 'audio/x-m4a', 'audio/mp4'],
    'video'    => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
                   'video/x-msvideo', 'video/avi'],
    'document' => ['application/pdf',
                   'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'application/vnd.ms-excel',
                   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                   'application/vnd.ms-powerpoint',
                   'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    default    => [],
};

if (!in_array($mime, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed (' . $mime . ').']);
    exit;
}

$maxBytes = match ($type) {
    'image'    =>   8 * 1024 * 1024,
    'audio'    => 150 * 1024 * 1024,
    'video'    => 500 * 1024 * 1024,
    'document' =>  50 * 1024 * 1024,
    default    =>   5 * 1024 * 1024,
};

if ($size > $maxBytes) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Max ' . round($maxBytes / 1048576) . ' MB for ' . $type . ' uploads.']);
    exit;
}

// Derive the extension from the validated MIME type — never from the uploaded
// filename. A polyglot (JPEG magic bytes named "x.html") would otherwise pass
// the MIME whitelist and be stored as .html, giving stored XSS when served.
$extByMime = [
    'image/jpeg' => 'jpg',  'image/png'  => 'png',  'image/webp' => 'webp', 'image/gif' => 'gif',
    'audio/mpeg' => 'mp3',  'audio/mp3'  => 'mp3',  'audio/ogg'  => 'ogg',
    'audio/wav'  => 'wav',  'audio/x-wav'=> 'wav',  'audio/aac'  => 'aac',
    'audio/m4a'  => 'm4a',  'audio/x-m4a'=> 'm4a',  'audio/mp4'  => 'm4a',
    'video/mp4'  => 'mp4',  'video/webm' => 'webm', 'video/ogg'  => 'ogv',
    'video/quicktime' => 'mov', 'video/x-msvideo' => 'avi', 'video/avi' => 'avi',
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
    'application/vnd.ms-excel' => 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
];

$ext = $extByMime[$mime] ?? null;
if ($ext === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Unsupported file type.']);
    exit;
}

$safeName = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$subDir   = $type . 's';
$dir      = dirname(__DIR__) . '/public/uploads/' . $subDir;

if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not create upload directory.']);
    exit;
}

// Write upload-directory .htaccess if not present. Blocks executable types and
// also markup types, which the browser would otherwise render inline.
$htFile = $dir . '/../.htaccess';
if (!file_exists($htFile)) {
    file_put_contents(
        $htFile,
        '<FilesMatch "\.(php[0-9]?|phtml|phar|pl|py|cgi|sh|htaccess)$">' . "\n"
        . "  Require all denied\n"
        . "</FilesMatch>\n"
        . "<IfModule mod_headers.c>\n"
        . '  <FilesMatch "\.(html?|xhtml|svg|xml)$">' . "\n"
        . "    Header set Content-Disposition \"attachment\"\n"
        . "    Header set X-Content-Type-Options \"nosniff\"\n"
        . "  </FilesMatch>\n"
        . "</IfModule>\n"
        . "Options -ExecCGI -Indexes\n"
        . "AddType text/plain .php .phtml .phar .pl .py .cgi .sh\n"
    );
}

if (!move_uploaded_file($tmp, $dir . '/' . $safeName)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save the uploaded file.']);
    exit;
}

echo json_encode([
    'url'      => '/public/uploads/' . $subDir . '/' . $safeName,
    'filename' => $orig,
    'size'     => $size,
    'type'     => $mime,
]);
