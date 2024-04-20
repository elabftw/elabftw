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

use League\CommonMark\Exception\UnexpectedEncodingException;
use League\CommonMark\GithubFlavoredMarkdownConverter;

use function bin2hex;
use function date;
use function htmlspecialchars;
use function implode;
use function mb_strlen;
use function pathinfo;
use function random_bytes;
use function sha1;
use function trim;

/**
 * Toolbelt full of useful functions
 */
class Tools
{
    // max size of uploaded file if we cannot find it in ini file
    private const int DEFAULT_UPLOAD_SIZE = 2;

    /**
     * Convert markdown to html
     */
    public static function md2html(string $md): string
    {
        $config = array(
            'allow_unsafe_links' => false,
            'max_nesting_level' => 42,
        );

        try {
            $converter = new GithubFlavoredMarkdownConverter($config);
            return trim($converter->convert($md)->getContent(), "\n");
        } catch (UnexpectedEncodingException) {
            // fix for incorrect utf8 encoding, just return md and hope it's html
            // so at least the thing is displayed instead of triggering a fatal error
            return $md;
        }
    }

    /**
     * Converts the php.ini upload size setting to a numeric value in MB
     * Returns DEFAULT_UPLOAD_SIZE if no value is found
     * It also checks for the post_max_size value and returns the lowest value
     *
     * @return int maximum size in MB of files allowed for upload
     */
    public static function getMaxUploadSize(): int
    {
        $max_size = trim((string) ini_get('upload_max_filesize'));
        $post_max_size = trim((string) ini_get('post_max_size'));

        if (empty($max_size) || empty($post_max_size)) {
            return self::DEFAULT_UPLOAD_SIZE;
        }

        // assume they both have same unit to compare the values
        if ((int) $post_max_size > (int) $max_size) {
            $input = $max_size;
        } else {
            $input = $post_max_size;
        }

        // get unit
        $unit = strtolower($input[mb_strlen($input) - 1]);
        $value = (int) $input;

        // convert to Mb
        switch ($unit) {
            case 'g':
                $value *= 1000;
                break;
            case 'k':
                $value /= 1024;
                break;
            case 'm':
                break;
            default:
                return self::DEFAULT_UPLOAD_SIZE;
        }

        return (int) $value;
    }

    /**
     * Show the units in human format from bytes.
     *
     * @param int $bytes size in bytes
     */
    public static function formatBytes(int $bytes): string
    {
        $sizes = array('B', 'KiB', 'MiB', 'GiB', 'TiB');
        $factor = (int) floor((strlen((string) $bytes) - 1) / 3);
        return sprintf('%.2f', $bytes / 1024 ** $factor) . ' ' . $sizes[$factor];
    }

    /**
     * Generate unique elabID
     *
     * @return string unique elabid with date in front of it
     */
    public static function generateElabid(): string
    {
        return date('Ymd') . '-' . sha1(bin2hex(random_bytes(16)));
    }

    /**
     * Get the extension of a file.
     *
     * @param string $filename path of the file
     * @return string file extension
     */
    public static function getExt(string $filename): string
    {
        // Get file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ($ext === '') {
            return 'unknown';
        }

        return $ext;
    }

    /**
     * Display a generic error message
     *
     * @param bool $permission show the out of reach message for permission message
     */
    public static function error(bool $permission = false): string
    {
        if ($permission) {
            return _('This section is out of your reach!');
        }
        return _('An error occurred!');
    }

    public static function getIdFilterSql(array $idArr): string
    {
        if (!empty($idArr)) {
            return ' AND entity.id IN (' . implode(',', $idArr) . ')';
        }
        return ' AND entity.id IN (0)';
    }

    public static function getShortElabid(string $elabid): string
    {
        if (empty($elabid)) {
            return bin2hex(random_bytes(4));
        }
        return substr(explode('-', $elabid)[1], 0, 8);
    }

    public static function printArr(array $arr): string
    {
        $html = '';
        if (empty($arr)) {
            return $html;
        }
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $html .= sprintf(
                    '<li><span class="text-muted">%s</span> <span class="font-weight-bold">⇒</span><ul>%s</ul></li>',
                    self::eLabHtmlspecialchars($key),
                    self::printArr($val),
                );
            } else {
                $html .= sprintf(
                    '<li><span class="text-muted">%s</span> <span class="font-weight-bold">→</span> %s</li>',
                    self::eLabHtmlspecialchars($key),
                    self::eLabHtmlspecialchars($val),
                );
            }
        }
        return sprintf('<ul>%s</ul>', $html);
    }

    public static function eLabHtmlspecialchars(mixed $string): string
    {
        return htmlspecialchars((string) $string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
    }
}
