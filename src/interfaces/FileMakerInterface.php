<?php

/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

declare(strict_types=1);

namespace Elabftw\Interfaces;

use Symfony\Component\HttpFoundation\Response;

/**
 * For classes that can make a file
 */
interface FileMakerInterface
{
    public function getContentType(): string;

    public function getFileName(): string;

    public function getContentSize(): int;

    public function getResponse(): Response;

    public function getFileContent(): string;
}
