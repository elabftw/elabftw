<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Controllers;

use function dirname;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Services\Filter;
use finfo;
use function function_exists;
use function is_readable;
use function substr;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * To download uploaded files
 */
class DownloadController implements ControllerInterface
{
    /** @var string $longName the hash name of the file on disk */
    private $longName;

    /** @var string $realName the human-friendly name that we will give to the downloaded file */
    private $realName;

    /** @var bool $forceDownload do we tell the browser to force the download? */
    private $forceDownload = false;

    /** @var string $filePath the full file path */
    private $filePath;

    public function __construct(string $longName, string $realName = null, bool $forceDownload = false)
    {
        // Remove any path info to avoid hacking by adding relative path, etc.
        $longName = Filter::forFilesystem(basename($longName));
        // get the first two letters to get the folder
        $fullFilePath = substr($longName, 0, 2) . '/' . $longName;
        $basePath = dirname(__DIR__, 2) . '/uploads/';
        // maybe it's an old file that has no subfolder
        if (!is_readable($basePath . $fullFilePath)) {
            $fullFilePath = $longName;
        }
        $this->filePath = $basePath . $fullFilePath;
        $this->realName = Filter::forFilesystem($realName ?? '');
        // if the name is not sent along, just use the longName instead
        if ($this->realName === null) {
            $this->realName = $longName;
        }
        if ($this->realName === '') {
            $this->realName = 'unnamed_file';
        }
        $this->forceDownload = $forceDownload;
    }

    public function getResponse(): Response
    {
        $Response = new BinaryFileResponse($this->filePath);
        $Response->headers->set('Content-Type', $this->getMimeType());

        if ($this->forceDownload) {
            $Response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $this->realName,
            );
        }

        return $Response;
    }

    /**
     * Try and get the mime type for Content-Type header
     */
    private function getMimeType(): string
    {
        $mime = false;
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($this->filePath);
        } elseif (function_exists('finfo_file')) {
            $finfo = new finfo(FILEINFO_MIME);
            $mime = $finfo->file($this->filePath);
        }
        if ($mime === false) {
            return 'application/force-download';
        }
        return $mime;
    }
}
