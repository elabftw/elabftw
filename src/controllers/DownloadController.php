<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012, 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Services\Filter;
use function fopen;
use function in_array;
use League\Flysystem\Filesystem;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use function stream_copy_to_stream;
use function substr;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * To download uploaded files
 */
class DownloadController implements ControllerInterface
{
    // the human-friendly name that we will give to the downloaded file */
    private string $realName = 'unnamed_file';

    private string $filePath;

    private string $longName;

    public function __construct(private Filesystem $fs, string $longName, string $realName = null, private bool $forceDownload = false)
    {
        // Remove any path info to avoid hacking by adding relative path, etc.
        $this->longName = Filter::forFilesystem(basename($longName));
        // get the first two letters to get the folder
        $this->filePath = substr($this->longName, 0, 2) . '/' . $this->longName;
        $this->realName = Filter::forFilesystem($realName ?? '');
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

    public function getResponse(): Response
    {
        // we stream the response to the client
        $Response = new StreamedResponse(function () {
            $outputStream = fopen('php://output', 'wb');
            if ($outputStream === false) {
                return;
            }
            try {
                $fileStream = $this->fs->readStream($this->getFilePath());
            } catch (UnableToReadFile $e) {
                // display a thumbnail if the real thumbnail cannot be found
                $fileStream = fopen(dirname(__DIR__, 2) . '/web/app/img/fallback-thumb.png', 'rb');
                if ($fileStream === false) {
                    return;
                }
            }
            stream_copy_to_stream($fileStream, $outputStream);
        });

        // set the correct Content-Type header based on mime type
        $mime = $this->getMimeType();
        $Response->headers->set('Content-Type', $mime);

        // force the download of everything (regardless of the forceDownload parameter)
        // to avoid having html injected and interpreted as an elabftw page
        $safeMimeTypes = array(
            'application/pdf',
            'image/gif',
            'image/jpeg',
            'image/png',
            'video/mp4',
            'text/plain',
        );
        if (!in_array($mime, $safeMimeTypes, true)) {
            $this->forceDownload = true;
        }

        if ($this->forceDownload) {
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $this->realName,
            );
            $Response->headers->set('Content-Disposition', $disposition);
        }

        return $Response;
    }

    /**
     * Try and get the mime type for Content-Type header
     */
    private function getMimeType(): string
    {
        try {
            return $this->fs->mimeType($this->getFilePath());
        } catch (UnableToRetrieveMetadata $e) {
            return 'application/force-download';
        }
    }
}
