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

use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Config;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;

use function checkdate;
use function filter_var;
use function htmlspecialchars_decode;
use function mb_convert_encoding;
use function mb_strlen;
use function strlen;
use function trim;

/**
 * When values need to be filtered
 */
class Filter
{
    /**
     * @var int MAX_BODY_SIZE max size for the body
     * ~= max size of MEDIUMTEXT in MySQL for UTF-8
     * But here it's less than that because while trying different sizes
     * I found this value to work, but not above.
     * Anyway, a few millions characters should be enough to report an experiment.
     */
    private const MAX_BODY_SIZE = 4120000;

    public static function toBinary(string|bool|int $input): int
    {
        return $input ? 1 : 0;
    }

    /**
     * Return 0 or 1 if input is on. Used for UCP.
     */
    public static function onToBinary(string $input): int
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
        $year = (int) substr($input, 0, 4);
        $month = (int) substr($input, 5, 2);
        $day = (int) substr($input, 8, 2);
        if (mb_strlen($input) !== 10 || !checkdate($month, $day, $year)) {
            return date('Y-m-d');
        }
        return $input;
    }

    /**
     * Simply sanitize email
     */
    public static function sanitizeEmail(string $input): string
    {
        $output = filter_var($input, FILTER_SANITIZE_EMAIL);
        if ($output === false) {
            return '';
        }
        return $output;
    }

    public static function email(string $input): string
    {
        // if the sent email is different from the existing one, check it's valid (not duplicate and respects domain constraint)
        $Config = Config::getConfig();
        $EmailValidator = new EmailValidator($input, $Config->configArr['email_domain']);
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
        return str_replace(array("\r\n", "\n", "\r"), ' ', $title);
    }

    /**
     * Remove all non word characters. Used for files saved on the filesystem (pdf, zip, ...)
     * This code is from https://developer.wordpress.org/reference/functions/sanitize_file_name/
     *
     * @param string $input what to sanitize
     * @return string the clean string
     */
    public static function forFilesystem(string $input): string
    {
        $specialChars = array('?', '[', ']', '/', '\\', '=', '<', '>', ':', ';', ',', "'", '"', '&', '$', '#', '*', '(', ')', '|', '~', '`', '!', '{', '}', '%', '+', chr(0));
        $input = htmlspecialchars_decode($input, ENT_QUOTES);
        $input = str_replace("#\x{00a0}#siu", ' ', $input);
        $input = str_replace($specialChars, '', $input);
        $input = str_replace(array('%20', '+'), '-', $input);
        $input = preg_replace('/[\r\n\t -]+/', '-', $input);
        return trim($input ?? 'file', '.-_');
    }

    /**
     * This exists because: The filename fallback must only contain ASCII characters. at /elabftw/vendor/symfony/http-foundation/HeaderUtils.php:173
     */
    public static function toAscii(string $input): string
    {
        // mb_convert_encoding will replace invalid characters with ?, but we want _ instead
        return str_replace('?', '_', mb_convert_encoding(self::forFilesystem($input), 'ASCII', 'UTF-8'));
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
        $config->set('HTML.Allowed', 'div[class|style],br,p[class|style],sub,img[src|class|style|width|height],sup,strong,b,em,u,a[href],s,span[style],ul[style],li[style],ol[style],dl,dt,dd,blockquote,h1[class|style],h2[class|style],h3[class|style],h4[class|style],h5[class|style],h6[class|style],hr,table[style|data-table-sort|border],tr[style],td[style|colspan|rowspan],th[style|colspan|rowspan],code,video[src|controls|style],audio[src|controls],pre[class],details,summary,caption,figure,figcaption');
        $config->set('HTML.TargetBlank', true);
        // configure the cache for htmlpurifier
        $tmpDir = FsTools::getCacheFolder('purifier');
        $config->set('Cache.SerializerPath', $tmpDir);
        // allow "display" css attribute
        $config->set('CSS.AllowTricky', true);
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
}
