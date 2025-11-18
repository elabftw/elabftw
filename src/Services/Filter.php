<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;

use function checkdate;
use function filter_var;
use function grapheme_substr;
use function grapheme_strlen;
use function mb_strlen;
use function mb_substr;
use function strlen;
use function trim;

/**
 * When values need to be filtered
 */
final class Filter
{
    /**
     * ~= max size of MEDIUMTEXT in MySQL for UTF-8
     * But here it's less than that because while trying different sizes
     * I found this value to work, but not above.
     * Anyway, a few millions characters should be enough to report an experiment.
     */
    private const int MAX_BODY_SIZE = 4120000;

    public static function toBinary(string|bool|int $input): int
    {
        // special case for uncheck checkboxes
        if ($input === 'off') {
            return 0;
        }
        return $input ? 1 : 0;
    }

    public static function toPureString(string $input): string
    {
        $config = HTMLPurifier_HTML5Config::createDefault();
        // configure the cache for htmlpurifier
        $tmpDir = FsTools::getCacheFolder('purifier');
        $config->set('Cache.SerializerPath', $tmpDir);
        $config->set('HTML.Allowed', '');
        $config->set('AutoFormat.RemoveEmpty', true);
        return new HTMLPurifier($config)->purify(trim($input));
    }

    /**
     * Return 0 or 1 if input is on. Used for UCP.
     */
    public static function onToBinary(?string $input): int
    {
        return $input === 'on' ? 1 : 0;
    }

    public static function firstLetter(string $input): string
    {
        $key = $input[0];
        if (ctype_alpha($key)) {
            return $key;
        }
        throw new ImproperActionException('Incorrect value: must be a letter.');
    }

    /**
     * Make sure the date is correct (YYYY-MM-DD)
     */
    public static function kdate(string $input): string
    {
        // Check if day/month/year are good
        $year = (int) mb_substr($input, 0, 4);
        $month = (int) mb_substr($input, 5, 2);
        $day = (int) mb_substr($input, 8, 2);
        if (mb_strlen($input) !== 10 || !checkdate($month, $day, $year)) {
            return date('Y-m-d');
        }
        return $input;
    }

    /**
     * Return the date in a readable format
     * example: 2014-01-12 -> "Sunday, January 12, 2014"
     */
    public static function formatLocalDate(DateTimeImmutable $input): string
    {
        return $input->format('l, F j, Y');
    }

    /**
     * Returns an array (key => value) containing date and time
     * example : "2024-10-16 17:12:47" -> ["date" => "2024-10-16", "time" => "17:12:47"]
     */
    public static function separateDateAndTime(string $input): array
    {
        $date = explode(' ', $input);
        return array(
            'date' => $date[0],
            'time' => $date[1] ?? '',
        );
    }

    /**
     * Simply sanitize email
     */
    public static function sanitizeEmail(string $input): string
    {
        $output = filter_var($input, FILTER_SANITIZE_EMAIL);
        /** @psalm-suppress TypeDoesNotContainType see https://github.com/vimeo/psalm/issues/10561 */
        if ($output === false) {
            return '';
        }
        return $output;
    }

    public static function email(string $input): string
    {
        // if the sent email is different from the existing one, check it's valid (not duplicate and respects domain constraint)
        $Config = Config::getConfig();
        $EmailValidator = new EmailValidator($input, (bool) $Config->configArr['admins_import_users'], $Config->configArr['email_domain']);
        return $EmailValidator->validate();
    }

    /**
     * Sanitize title with a filter_var and remove the line breaks.
     *
     * @param string $input The title to sanitize
     * @return string Will return Untitled if there is no input.
     */
    public static function title(string $input): string
    {
        $title = trim($input);
        if ($title === '') {
            return _('Untitled');
        }
        // remove linebreak to avoid problem in javascript link list generation on editXP
        $title = str_replace(array("\r\n", "\n", "\r"), ' ', $title);
        $maxCharacters = 255;
        if (grapheme_strlen($title) > $maxCharacters) {
            $title = grapheme_substr($title, 0, $maxCharacters);
            if ($title === false) {
                throw new ImproperActionException('Error reducing title size!');
            }
        }
        return $title;
    }

    public static function toAsciiSlug(string $input): string
    {
        return new FileSlugger()->slug($input)->toString();
    }

    /**
     * Remove all non ascii characters. Used for files saved on the filesystem (pdf, zip, ...)
     * FIXME: this should be improved so valid utf-8 strings are still accepted
     * see: https://github.com/elabftw/elabftw/issues/5783#issuecomment-3043949949
     */
    public static function forFilesystem(string $input): string
    {
        // need to split the extension out of it or the . will be replaced, too
        $safe = self::toAsciiSlug(pathinfo($input, PATHINFO_FILENAME));

        $ext = pathinfo($input, PATHINFO_EXTENSION);
        if ($ext) {
            // re-attach extension
            return $safe . '.' . $ext;
        }
        return $safe;
    }

    public static function intOrNull(string|int $input): ?int
    {
        $res = (int) $input;
        if ($res === 0) {
            return null;
        }
        return $res;
    }

    /**
     * An hexit is an hexadecimal digit: 0 to 9 and a to f
     */
    public static function hexits(string $input): string
    {
        $res = preg_replace('/[^[:xdigit:]]/', '', $input);
        if ($res === null) {
            return '';
        }
        return $res;
    }

    /**
     * Sanitize body with a list of allowed html tags.
     *
     * @param string $input Body to sanitize
     * @return string The sanitized body or empty string if there is no input
     */
    public static function body(?string $input = null): string
    {
        if ($input === null) {
            return '';
        }
        // use strlen() instead of mb_strlen() because we want the size in bytes
        if (strlen($input) > self::MAX_BODY_SIZE) {
            throw new ImproperActionException('Content is too big! Cannot save!');
        }
        // create base config for html5
        $config = HTMLPurifier_HTML5Config::createDefault();
        // allow only certain elements
        $config->set('HTML.Allowed', 'div[class|style],br,p[class|style],sub,img[src|class|style|width|height],sup,strong,b,em,u,a[href],s,span[style],ul[style],li[style],ol[style],dl,dt,dd,blockquote,h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style],hr,table[style|data-table-sort|border],tr[style],td[style|colspan|rowspan],th[style|colspan|rowspan],code,source[src|type],video[src|controls|style|width|height],audio[src|controls],pre[class],details,summary,caption,figure,figcaption');
        $config->set('HTML.TargetBlank', true);
        // configure the cache for htmlpurifier
        $tmpDir = FsTools::getCacheFolder('purifier');
        $config->set('Cache.SerializerPath', $tmpDir);
        // allow "display" attribute for centering images
        $config->set('CSS.AllowTricky', true);
        $config->set('Attr.AllowedClasses', array(
            'language-bash',
            'language-c',
            'language-cpp',
            'language-css',
            'language-diff',
            'language-fortran',
            'language-go',
            'language-igor',
            'language-java',
            'language-javascript',
            'language-json',
            'language-julia',
            'language-latex',
            'language-lua',
            'language-makefile',
            'language-matlab',
            'language-perl',
            'language-python',
            'language-r',
            'language-ruby',
            'language-rust',
            'language-sql',
            'language-tcl',
            'language-vhdl',
            'language-yaml',
        ));
        // note: hyphens and word-break are not supported
        $config->set('CSS.AllowedProperties', array(
            'background-color',
            'border',
            'border-color',
            'color',
            'display', // see #3368
            'font-family',
            'font-size', // see #5873
            'font-style',
            'font-weight',
            'font-variant',
            'height',
            'line-height',
            'margin-left',
            'margin-right',
            'min-width',
            'text-align',
            'text-decoration',
            'word-spacing',
            'width',
            'white-space',
        ));
        // allow any image size, see #3800
        $config->set('CSS.MaxImgLength', null);
        $config->set('HTML.MaxImgLength', null);
        // allow 'data-table-sort' attribute to indicate that a table shall be sortable by js
        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addAttribute('table', 'data-table-sort', 'Enum#true');
        }

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($input);
    }

    public static function pem(string $pem): string
    {
        // Trim outer whitespace
        $pem = trim($pem);

        // Drop the header and footer lines if present
        $pem = preg_replace('/-----BEGIN CERTIFICATE-----/', '', $pem);
        $pem = preg_replace('/-----END CERTIFICATE-----/', '', $pem ?? '');

        // Remove all whitespace (newlines, spaces, tabs)
        return str_replace(array("\r", "\n", ' ', "\t"), '', $pem ?? '');
    }
}
