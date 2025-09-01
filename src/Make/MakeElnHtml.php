<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use Elabftw\Models\Users\Users;
use ZipStream\ZipStream;
use Override;
use Symfony\Component\HttpFoundation\Response;

/**
 * Make an ELN html file
 */
final class MakeElnHtml extends MakeEln
{
    public function __construct(protected ZipStream $Zip, protected Users $requester, protected array $entityArr)
    {
        parent::__construct($Zip, $requester, $entityArr);
    }

    #[Override]
    public function getResponse(): Response
    {
        $this->processEntityArr();
        $jsonLd = json_encode($this->dataArr, JSON_THROW_ON_ERROR, 512);
        return new Response($this->crateToHtml($jsonLd, $this->getRootNode()));
    }

    #[Override]
    /**
     * @param resource $stream
     */
    protected function addAttachedFileInZip(string $path, mixed $stream): void
    {
        return;
    }
}
