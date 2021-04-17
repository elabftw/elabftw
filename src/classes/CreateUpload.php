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

use Elabftw\Interfaces\CreateUploadParamsInterface;
use Elabftw\Services\Filter;
use Symfony\Component\HttpFoundation\Request;

// todo should be called CreateUploadParams in Params namespace
final class CreateUpload implements CreateUploadParamsInterface
{
    private Request $Request;

    public function __construct(Request $request)
    {
        $this->Request = $request;
    }

    public function getFilename(): string
    {
        return Filter::forFilesystem($this->Request->files->get('file')->getClientOriginalName());
    }

    public function getPathname(): string
    {
        return $this->Request->files->get('file')->getPathname();
    }
}
