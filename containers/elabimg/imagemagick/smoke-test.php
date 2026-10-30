<?php

declare(strict_types=1);

/**
 * @copyright 2026 Deltablot
 * @license AGPL-3.0
 *
 * Run against runtime-base, without Composer or a database.
 */

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectRejection(callable $operation): void
{
    try {
        $operation();
    } catch (ImagickException) {
        return;
    }
    throw new RuntimeException('ImageMagick accepted a prohibited operation.');
}

/** @param resource $source */
function checkThumbnail($source): void
{
    $image = new Imagick();
    // Match MakeThumbnail's stream input and first-frame selection.
    $image->setFilename('[0]');
    $image->readImageFile($source);
    $image->setIteratorIndex(0);
    $image->thumbnailImage(100, 0);
    $image->setImageFormat('jpeg');
    $info = getimagesizefromstring($image->getImageBlob());
    check(is_array($info) && $info[0] === 100 && $info['mime'] === 'image/jpeg', 'JPEG thumbnail failed.');
    $image->clear();
}

check(extension_loaded('imagick'), 'The Imagick extension did not load.');

foreach (array('BMP', 'GIF', 'HEIC', 'HEIF', 'JPEG', 'PDF', 'PNG', 'PNM', 'PS', 'EPS', 'SVG', 'TIFF', 'WEBP') as $format) {
    check(Imagick::queryFormats($format) === array($format), 'Missing format: ' . $format);
}
foreach (array('MSL', 'MVG', 'HTTP', 'HTTPS', 'URL', 'TEXT', 'LABEL', 'CAPTION', 'XC', 'GRADIENT', 'MIFF', 'PSD', 'XPS', 'VIDEO') as $format) {
    check(Imagick::queryFormats($format) === array(), 'Unexpected parser: ' . $format);
}

// Independently encoded 4x2 RGB images: Pillow 12.3 and pillow-heif 1.8.1.
$fixtures = array(
    'BMP' => 'Qk1OAAAAAAAAADYAAAAoAAAABAAAAAIAAAABABgAAAAAABgAAADEDgAAxA4AAAAAAAAAAAAAiFUiiFUiiFUiiFUiiFUiiFUiiFUiiFUi',
    'GIF' => 'R0lGODdhBAACAIEAACJViAAAAAAAAAAAACwAAAAABAACAAAIBwABCBwoMCAAOw==',
    'TIFF' => 'SUkqAAgAAAAKAAABBAABAAAABAAAAAEBBAABAAAAAgAAAAIBAwADAAAAhgAAAAMBAwABAAAAAQAAAAYBAwABAAAAAgAAABEBBAABAAAAjAAAABUBAwABAAAAAwAAABYBBAABAAAAAgAAABcBBAABAAAAGAAAABwBAwABAAAAAQAAAAAAAAAIAAgACAAiVYgiVYgiVYgiVYgiVYgiVYgiVYgiVYg=',
    'WEBP' => 'UklGRjgAAABXRUJQVlA4ICwAAADwAQCdASoEAAIAAUAmJaACdLoB+AAETAAA/vNpl/43bjDfgu/33oDeBgAAAA==',
    'HEIC' => 'AAAAHGZ0eXBoZWljAAAAAG1pZjFoZWljbWlhZgAAAXxtZXRhAAAAAAAAACFoZGxyAAAAAAAAAABwaWN0AAAAAAAAAAAAAAAAAAAAACJpbG9jAAAAAERAAAEAAQAAAAABoAABAAAAAAAAADMAAAAjaWluZgAAAAAAAQAAABVpbmZlAgAAAAABAABodmMxAAAAAA5waXRtAAAAAAABAAAA/GlwcnAAAADcaXBjbwAAAHVodmNDAQNwAAAAkAAAAAAAHvAA/P34+AAADwNgAAEAGEABDAH//wNwAAADAJAAAAMAAAMAHroCQGEAAQApQgEBA3AAAAMAkAAAAwAAAwAeoCCBBZbqrprm4CGgwIAAAAyAAAADAIRiAAEABkQBwXPBiQAAABNjb2xybmNseAABAA0ABoAAAAAUaXNwZQAAAAAAAABAAAAAQAAAAChjbGFwAAAABAAAAAEAAAACAAAAAf///8QAAAAC////wgAAAAIAAAAQcGl4aQAAAAADCAgIAAAAGGlwbWEAAAAAAAAAAQABBYECAwWEAAAAO21kYXQAAAAvKAGvEyFkY0D4EPdn/+u8Ewdu1D/Jux6c7IR0wNIggJtASJNdUAsWEICHdqVW3Pg=',
);
foreach ($fixtures as $format => $encoded) {
    $source = fopen('php://temp', 'w+b');
    check(is_resource($source), 'Cannot create fixture stream.');
    fwrite($source, (string) base64_decode($encoded, true));
    rewind($source);
    checkThumbnail($source);
    fclose($source);
    echo $format . " thumbnail OK\n";
}

foreach (array('jpeg', 'png') as $format) {
    $image = new Imagick();
    $image->newImage(4, 2, 'white');
    $image->setImageFormat($format);
    $source = fopen('php://temp', 'w+b');
    check(is_resource($source), 'Cannot create fixture stream.');
    fwrite($source, $image->getImageBlob());
    rewind($source);
    checkThumbnail($source);
    fclose($source);
    $image->clear();
}

// Nested SVGs are also used by MathJax in PDF exports.
$image = new Imagick();
$image->readImageBlob('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="10"><svg width="20" height="10"><rect width="20" height="10" fill="red"/></svg></svg>');
$image->setImageFormat('png');
$info = getimagesizefromstring($image->getImageBlob());
check(is_array($info) && $info[0] === 20 && $info[1] === 10 && $info['mime'] === 'image/png', 'SVG rasterization failed.');
$image->clear();

foreach (array('%!PS-Adobe-3.0', '%!PS-Adobe-3.0 EPSF-3.0') as $header) {
    $source = fopen('php://temp', 'w+b');
    check(is_resource($source), 'Cannot create fixture stream.');
    fwrite($source, $header . "\n%%BoundingBox: 0 0 4 2\n1 0 0 setrgbcolor\n0 0 4 2 rectfill\nshowpage\n");
    rewind($source);
    checkThumbnail($source);
    fclose($source);
}
$source = fopen($argv[1], 'rb');
check(is_resource($source), 'Cannot read PDF fixture.');
checkThumbnail($source);
fclose($source);

// QR labels use direct font annotation, even though the LABEL coder is absent.
check(is_file($argv[2]), 'No font available for QR labels.');
$image = new Imagick();
$image->newImage(100, 40, 'white');
$draw = new ImagickDraw();
$draw->setFont($argv[2]);
$draw->setFontSize(16);
$draw->setFillColor('black');
check($image->annotateImage($draw, 2, 20, 0, 'eLabFTW'), 'QR label annotation failed.');
$image->setImageFormat('png');
check(strlen($image->getImageBlob()) > 0, 'QR label PNG failed.');
$image->clear();

foreach (array('xc:white', 'label:eLabFTW', 'caption:eLabFTW', 'gradient:black-white') as $source) {
    expectRejection(static fn() => (new Imagick())->readImage($source));
}
foreach (array('BMP', 'GIF', 'HEIC', 'WEBP', 'TIFF', 'PDF', 'PS', 'SVG', 'MIFF') as $format) {
    expectRejection(static function () use ($format): void {
        $image = new Imagick();
        $image->newImage(2, 1, 'white');
        $image->setImageFormat($format);
        $image->getImageBlob();
    });
}
expectRejection(static fn() => (new Imagick())->newImage(20000, 1, 'white'));
expectRejection(static fn() => (new Imagick())->newImage(1, 20000, 'white'));

echo "ImageMagick runtime smoke tests passed.\n";
