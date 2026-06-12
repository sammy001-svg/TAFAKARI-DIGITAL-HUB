<?php
declare(strict_types=1);

$allowed = [72, 96, 128, 144, 152, 180, 192, 384, 512];
$size    = (int)($_GET['size'] ?? 192);
if (!in_array($size, $allowed)) $size = 192;

// Cache for 7 days
header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800, immutable');
header('Vary: Accept');

$logoPath = __DIR__ . '/public/crtp-logo.jpg';

if (!function_exists('imagecreatetruecolor') || !file_exists($logoPath)) {
    // Fallback: generate a simple branded square icon
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $bg   = imagecolorallocate($img, 117, 11, 37);    // #750B25
    $text = imagecolorallocate($img, 231, 149, 42);   // #E7952A
    imagefill($img, 0, 0, $bg);
    $fontSize = max(3, (int)($size / 6));
    $str      = 'CRTP';
    $cx       = (int)($size / 2);
    $cy       = (int)($size / 2);
    if ($size >= 128) {
        imagestring($img, $fontSize, $cx - (int)(strlen($str) * $fontSize * 0.3), $cy - $fontSize, $str, $text);
    }
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Load source JPEG
$src  = @imagecreatefromjpeg($logoPath);
if (!$src) {
    // GD couldn't read it — serve a fallback
    $img  = imagecreatetruecolor($size, $size);
    $bg   = imagecolorallocate($img, 117, 11, 37);
    imagefill($img, 0, 0, $bg);
    imagepng($img); imagedestroy($img); exit;
}

$srcW = imagesx($src);
$srcH = imagesy($src);

// Create square canvas with brand off-white background
$img = imagecreatetruecolor($size, $size);
$bg  = imagecolorallocate($img, 248, 248, 240); // #F8F8F0
imagefill($img, 0, 0, $bg);

// Scale logo to fit with 8% padding on each side
$pad   = (int)($size * 0.08);
$avail = $size - $pad * 2;
$scale = min($avail / $srcW, $avail / $srcH);
$newW  = (int)($srcW * $scale);
$newH  = (int)($srcH * $scale);
$dstX  = (int)(($size - $newW) / 2);
$dstY  = (int)(($size - $newH) / 2);

imagecopyresampled($img, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);

imagepng($img, null, 8);
imagedestroy($img);
imagedestroy($src);
