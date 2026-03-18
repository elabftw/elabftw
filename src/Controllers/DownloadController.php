<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Services\Filter;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Override;

use function basename;
use function fopen;
use function fread;
use function fseek;
use function in_array;
use function str_starts_with;
use function stream_copy_to_stream;
use function strlen;
use function mb_substr;

/**
 * To download uploaded files
 */
final class DownloadController implements ControllerInterface
{
    // the human-friendly name that we will give to the downloaded file */
    private string $realName = 'unnamed_file';

    // ascii only
    private string $realNameFallback = 'unnamed_file';

    private string $filePath;

    private string $longName;

    public function __construct(private Filesystem $fs, string $longName, ?string $realName = null, private bool $forceDownload = false)
    {
        // Remove any path info to avoid hacking by adding relative path, etc.
        $this->longName = Filter::forFilesystem(basename($longName));
        // get the first two letters to get the folder
        $this->filePath = mb_substr($this->longName, 0, 2) . '/' . $this->longName;
        $this->realName = $realName ?? $this->realName;
        $this->realNameFallback = Filter::toAsciiSlug($realName ?? '');
        if (empty($this->realName)) {
            $this->realName = 'unnamed_file';
        }
    }

    public function getFilePath(): string
    {
        // maybe it's an old file that has no subfolder
        if (!$this->fs->fileExists($this->filePath)) {
            return $this->longName;
        }
        return $this->filePath;
    }

    #[Override]
    public function getResponse(?Request $request = null): Response
    {
        // this will disable output buffering and prevent issues when downloading big files
        if (ob_get_level()) {
            ob_end_clean();
        }

        $mime = $this->getMimeType();
        $filePath = $this->getFilePath();

        // Handle Range requests for video files to enable seeking
        if ($request !== null && str_starts_with($mime, 'video/')) {
            try {
                $fileSize = $this->fs->fileSize($filePath);
            } catch (UnableToRetrieveMetadata) {
                $fileSize = 0;
            }

            if ($fileSize > 0) {
                return $this->buildRangeResponse($request, $filePath, $mime, $fileSize);
            }
        }

        // we stream the response to the client
        $Response = new StreamedResponse(function () use ($filePath) {
            $outputStream = fopen('php://output', 'wb');
            if ($outputStream === false) {
                return;
            }
            try {
                $fileStream = $this->fs->readStream($filePath);
            } catch (UnableToReadFile) {
                // display a thumbnail if the real thumbnail cannot be found
                $fileStream = fopen(dirname(__DIR__, 2) . '/web/assets/images/fallback-thumb.png', 'rb');
                if ($fileStream === false) {
                    return;
                }
            }
            stream_copy_to_stream($fileStream, $outputStream);
        });
        // set the correct Content-Type header based on mime type
        $Response->headers->set('Content-Type', $mime);

        // force the download of everything (regardless of the forceDownload parameter)
        // to avoid having html injected and interpreted as an elabftw page
        $safeMimeTypes = array(
            'application/pdf',
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/bmp',
            'image/x-bmp',
            'video/mp4',
            'text/plain',
        );
        if (!in_array($mime, $safeMimeTypes, true)) {
            $this->forceDownload = true;
        }

        $disposition = HeaderUtils::DISPOSITION_INLINE;
        // change the disposition to attachment
        if ($this->forceDownload) {
            $disposition = HeaderUtils::DISPOSITION_ATTACHMENT;
        }
        $dispositionHeader = HeaderUtils::makeDisposition(
            $disposition,
            $this->realName,
            $this->realNameFallback,
        );
        $Response->headers->set('Content-Disposition', $dispositionHeader);

        return $Response;
    }

    /**
     * Build a response that supports HTTP Range requests for video seeking.
     */
    private function buildRangeResponse(Request $request, string $filePath, string $mime, int $fileSize): Response
    {
        $start = 0;
        $end = $fileSize - 1;
        $statusCode = Response::HTTP_OK;

        $rangeHeader = $request->headers->get('Range');
        if ($rangeHeader !== null && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

            if ($end >= $fileSize) {
                $end = $fileSize - 1;
            }

            if ($start > $end || $start >= $fileSize) {
                $response = new Response('', Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
                $response->headers->set('Content-Range', sprintf('bytes */%d', $fileSize));
                return $response;
            }

            $statusCode = Response::HTTP_PARTIAL_CONTENT;
        }

        $length = $end - $start + 1;

        $Response = new StreamedResponse(function () use ($filePath, $start, $length) {
            $outputStream = fopen('php://output', 'wb');
            if ($outputStream === false) {
                return;
            }
            try {
                $fileStream = $this->fs->readStream($filePath);
            } catch (UnableToReadFile) {
                return;
            }
            if ($start > 0) {
                // Use fseek if the stream supports it, otherwise read and discard bytes.
                // S3 streams (s3:// wrapper) may not support fseek.
                if (fseek($fileStream, $start) !== 0) {
                    $remaining = $start;
                    while ($remaining > 0) {
                        $chunk = fread($fileStream, min(8192, $remaining));
                        if ($chunk === false || $chunk === '') {
                            return;
                        }
                        $remaining -= strlen($chunk);
                    }
                }
            }
            stream_copy_to_stream($fileStream, $outputStream, $length);
        }, $statusCode);

        $Response->headers->set('Content-Type', $mime);
        $Response->headers->set('Content-Length', (string) $length);
        $Response->headers->set('Accept-Ranges', 'bytes');
        $Response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $this->realName,
            $this->realNameFallback,
        ));
        if ($statusCode === Response::HTTP_PARTIAL_CONTENT) {
            $Response->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $fileSize));
        }

        return $Response;
    }

    /**
     * Return the MIME type, but replace the type of user-uploaded
     * JavaScript that is served from the eLabFTW system.
     *
     * Used in conjunction with the "X-Content-Type-Options: nosniff"
     * header as an extra layer of XSS protection. See
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
     *
     **/
    private function getMimeType(): string
    {
        try {
            $mimeType = $this->fs->mimeType($this->getFilePath());
            if ($this->isJavaScriptMimeType($mimeType)) {
                return 'application/octet-stream';
            }
            return $mimeType;
        } catch (UnableToRetrieveMetadata) {
            return 'application/force-download';
        }
    }

    /**
     * Return true if $mimeType is a JavaScript MIME type essence match, false otherwise.
     *
     * @param string $mimeType A MIME type of the form type/subtype, assumed correctly formatted and in a comparable encoding.
     *
     */
    private function isJavaScriptMimeType(string $mimeType): bool
    {
        // https://mimesniff.spec.whatwg.org/#javascript-mime-type
        $javaScriptLikeMimeTypesLower = array(
            'application/ecmascript', 'application/javascript',
            'application/x-ecmascript', 'application/x-javascript',
            'text/ecmascript', 'text/javascript',
            'text/javascript1.0', 'text/javascript1.1',
            'text/javascript1.2', 'text/javascript1.3',
            'text/javascript1.4', 'text/javascript1.5',
            'text/jscript', 'text/livescript',
            'text/x-ecmascript', 'text/x-javascript',
        );

        // JavaScript MIME type essence matches are case-insensitive.
        $mimeTypeLower = strtolower($mimeType);
        return in_array($mimeTypeLower, $javaScriptLikeMimeTypesLower, strict: false);
    }
}
