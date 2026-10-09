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
use ValueError;

use function preg_match;

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

    public function unicodeSlug(string $string): AbstractUnicodeString
    {
        return new UnicodeString($string)
            ->normalize()
            ->replaceMatches('/[<>:"\/\\\\|?*\p{Cc}]+/u', '-')
            // Remove user-controlled directional and invisible characters.
            // U+200C and U+200D are intentionally preserved for languages
            // and composed emojis such as 👩‍🔬.
            ->replaceMatches('/[\x{061C}\x{200B}\x{200E}\x{200F}\x{202A}-\x{202E}\x{2060}\x{2066}-\x{2069}\x{FEFF}]/u', '')
            ->replaceMatches('/[\p{Z}\s]+/u', '-')
            ->trim(' _-');
    }

    public function nonEmptyUnicodeSlug(string $input): AbstractUnicodeString
    {
        $safe = $this->unicodeSlug($input)->toString();
        // check if string is "empty": no letters, numbers, symbols or emojis
        if ($safe === '' || preg_match('/[\p{L}\p{N}\p{S}]/u', $safe) !== 1) {
            throw new ValueError('No letters, numbers, symbols or emojis found after cleanup.');
        }
        return new UnicodeString($safe);
    }
}
