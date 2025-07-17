<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Override;
use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\String\UnicodeString;

/**
 * This class exists to override the default implementation of the AsciiSlugger that doesn't allow some needed characters
 */
final class FileSlugger extends AsciiSlugger
{
    #[Override]
    public function slug(string $string, string $separator = '-', ?string $locale = null): AbstractUnicodeString
    {
        return new UnicodeString($string)
            ->ascii()
            // We keep . and _ for uploaded files
            ->replaceMatches('/[^A-Za-z0-9\._]+/', $separator)
            ->trim($separator);
    }
}
