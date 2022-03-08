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

use function checkdate;
use Elabftw\Elabftw\FsTools;
use Elabftw\Exceptions\ImproperActionException;
use function filter_var;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;
use function htmlspecialchars_decode;
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

    public static function toBinary(string $input): int
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
     * Simply sanitize string
     */
    public static function sanitize(string $input): string
    {
        $output = filter_var($input, FILTER_SANITIZE_STRING);
        if ($output === false) {
            return '';
        }
        return $output;
    }

    /**
     * Sanitize title with a filter_var and remove the line breaks.
     *
     * @param string $input The title to sanitize
     * @return string Will return Untitled if there is no input.
     */
    public static function title(string $input): string
    {
        $title = filter_var($input, FILTER_SANITIZE_STRING);
        if (empty($title)) {
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
        $input = preg_replace("#\x{00a0}#siu", ' ', $input);
        $input = str_replace($specialChars, '', $input ?? '');
        $input = str_replace(array('%20', '+'), '-', $input);
        $input = preg_replace('/[\r\n\t -]+/', '-', $input);
        return trim($input ?? 'file', '.-_');
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
        $config->set('HTML.Allowed', 'div[class],br,p[class|style],sub,img[src|class],sup,strong,b,em,u,a[href],s,span[style],ul,li,ol,dl,dt,dd,blockquote,h1,h2,h3,h4,h5,h6,hr,table[style],tr[style],td[style],th[style],code,video,audio,pre,details,summary,figure,figcaption');
        $config->set('HTML.TargetBlank', true);
        // configure the cache for htmlpurifier
        $tmpDir = FsTools::getCacheFolder('purifier');
        $config->set('Cache.SerializerPath', $tmpDir);

        $purifier = new HTMLPurifier($config);
        return $purifier->purify($input);
    }

    /**
     * Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
     * also remove | because we use this as separator for tags in SQL
     *
     * @param string $tag the tag to sanitize
     */
    public static function tag(string $tag): string
    {
        $tag = filter_var($tag, FILTER_SANITIZE_STRING);
        if ($tag === false) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        $tag = trim(str_replace(array('\\', '|'), array('', ' '), $tag));
        // empty tags are disallowed
        if ($tag === '') {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 1));
        }
        return $tag;
    }
}
