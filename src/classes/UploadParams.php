<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\UploadParamsInterface;
use Elabftw\Services\Filter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadParams extends ContentParams implements UploadParamsInterface
{
    public function __construct(string $content, string $target, private ?\Symfony\Component\HttpFoundation\File\UploadedFile $file = null)
    {
        parent::__construct($content, $target);
    }

    public function getContent(): string
    {
        if ($this->target === 'real_name') {
            return $this->getRealName();
        }
        return parent::getContent();
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    private function getRealName(): string
    {
        // don't allow php extension
        $ext = Tools::getExt($this->content);
        if ($ext === 'php') {
            throw new ImproperActionException('No php extension allowed!');
        }
        return Filter::forFilesystem($this->content);
    }
}
