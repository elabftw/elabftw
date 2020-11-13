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

use Elabftw\Exceptions\ImproperActionException;
use function filter_var;
use function htmlspecialchars_decode;
use function mb_strlen;
use function nl2br;
use function strip_tags;
use function strlen;
use function strtr;
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
     *
     * @param string $input
     * @return int
     */
    public static function onToBinary(string $input): int
    {
        return $input === 'on' ? 1 : 0;
    }

    /**
     * Return the current date as YYYYMMDD format if no input
     * return input if it is a valid date
     *
     * @param string|null $input 20160521 or 2020-03-31
     * @return string
     */
    public static function kdate(?string $input = null): string
    {
        if ($input === null) {
            return date('Ymd');
        }
        // the search page's datetime inputs will return YYYY-MM-DD
        // so strip the '-'
        $input = strtr($input, array('-' => ''));
        if (mb_strlen($input) === 8) {
            // Check if day/month are good (badly)
            $datemonth = substr($input, 4, 2);
            $dateday = substr($input, 6, 2);
            if (($datemonth <= '12')
                && ($dateday <= '31')
                && ($datemonth > '0')
                && ($dateday > '0')) {
                // SUCCESS on every test
                return $input;
            }
        }
        return date('Ymd');
    }

    /**
     * Simply sanitize string
     *
     * @param string $input
     * @return string
     */
    public static function sanitize(string $input): string
    {
        $output = filter_var($input, FILTER_SANITIZE_STRING);
        if ($output === false) {
            return '';
        }
        return $output;
    }

    public static function comment(string $input): string
    {
        $output = self::sanitize($input);
        if (mb_strlen($output) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return nl2br($output);
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
     * Sanitize body with a white list of allowed html tags.
     *
     * @param string $input Body to sanitize
     * @return string The sanitized body or empty string if there is no input
     */
    public static function body(string $input): string
    {
        $whitelist = '<div><br><br /><p><sub><img><sup><strong><b><em><u><a><s><font><span><ul><li><ol><dl><dt><dd>
            <blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><th><td><code><video><audio><pagebreak><pre>
            <details><summary><figure><figcaption>';
        $body = strip_tags($input, $whitelist);
        // use strlen() instead of mb_strlen() because we want the size in bytes
        if (strlen($body) > self::MAX_BODY_SIZE) {
            throw new ImproperActionException('Content is too big! Cannot save!');
        }
        return $body;
    }

    /**
     * Sanitize tag, we remove '\' because it fucks up the javascript if you have this in the tags
     * also remove | because we use this as separator for tags in SQL
     *
     * @param string $tag the tag to sanitize
     * @return string
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
