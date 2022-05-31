<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function filter_var;
use function implode;
use function json_decode;
use League\CommonMark\Exception\UnexpectedEncodingException;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use function mb_strlen;
use function pathinfo;
use Symfony\Component\HttpFoundation\Request;
use function trim;

/**
 * Toolbelt full of useful functions
 */
class Tools
{
    /** @var int DEFAULT_UPLOAD_SIZE max size of uploaded file if we cannot find in in ini file */
    private const DEFAULT_UPLOAD_SIZE = 2;

    /**
     * For displaying messages using bootstrap alerts
     *
     * @param string $message The message to display
     * @param string $type Can be 'ok', 'ko' or 'warning'
     * @param bool $cross do we display a cross or not?
     * @return string the HTML of the message
     */
    public static function displayMessage(string $message, string $type, bool $cross = true): string
    {
        $icon = 'fa-info-circle';
        $alert = 'success';

        if ($type === 'ko') {
            $icon = 'fa-exclamation-triangle';
            $alert = 'danger';
        } elseif ($type === 'warning') {
            $icon = 'fa-chevron-right';
            $alert = $type;
        }

        $crossLink = '';

        if ($cross) {
            $crossLink = "<a href='#' class='close' data-dismiss='alert'>&times;</a>";
        }

        $begin = "<div class='alert alert-" . $alert .
            "'><i class='fas " . $icon .
            "'></i>";
        $end = '</div>';

        return $begin . $crossLink . ' ' . $message . $end;
    }

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
        return sprintf('%.2f', $bytes / 1024** $factor) . ' ' . $sizes[$factor];
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
        $ext = filter_var(pathinfo($filename, PATHINFO_EXTENSION), FILTER_SANITIZE_STRING);
        if ($ext !== null && $ext !== '' && $ext !== false) {
            return $ext;
        }

        return 'unknown';
    }

    public static function getMimeExt(string $filename): string
    {
        $ext = strtolower(self::getExt($filename));
        // special case for jpg
        if ($ext === 'jpg') {
            return 'jpeg';
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

    /**
     * Return a lang to use with fullcalendar from the pref
     *
     * @param string $lang 'pt_BR' or 'fr_FR'
     */
    public static function getCalendarLang(string $lang): string
    {
        $map = array(
            'ca_ES' => 'ca',
            'de_DE' => 'de',
            'en_GB' => 'en',
            'es_ES' => 'es',
            'fr_FR' => 'fr',
            'id_ID' => 'id',
            'it_IT' => 'it',
            'ja_JP' => 'ja',
            'ko_KR' => 'ko',
            'nl_BE' => 'nl',
            'pl_PL' => 'pl',
            'pt_BR' => 'pt-br',
            'pt_PT' => 'pt',
            'ru_RU' => 'ru',
            'sl_SI' => 'sl',
            'sk_SK' => 'sk',
            'zh_CN' => 'zh-cn',
        );
        return $map[$lang];
    }

    /**
     * Get an associative array for the langs to display in a select
     *
     * @return array<string,string>
     */
    public static function getLangsArr(): array
    {
        return array(
            'ca_ES' => 'Spanish (Catalan)',
            'de_DE' => 'German',
            'en_GB' => 'English (UK)',
            'es_ES' => 'Spanish',
            'fr_FR' => 'French',
            'id_ID' => 'Indonesian',
            'it_IT' => 'Italian',
            'ja_JP' => 'Japanese',
            'ko_KR' => 'Korean',
            'nl_BE' => 'Dutch',
            'pl_PL' => 'Polish',
            'pt_BR' => 'Portuguese (Brazilian)',
            'pt_PT' => 'Portuguese',
            'ru_RU' => 'Russian',
            'sl_SI' => 'Slovenian',
            'sk_SK' => 'Slovak',
            'zh_CN' => 'Chinese Simplified',
        );
    }

    /**
     * A better print_r()
     * Used for debugging only
     *
     * @noRector \Rector\DeadCode\Rector\ClassMethod\RemoveDeadRecursiveClassMethodRector
     * @param array<mixed> $arr
     * @return string
     */
    public static function printArr(array $arr): string
    {
        $html = '<ul>';
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $html .= '<li><span style="color:red;">' . (string) $key . '</span><b> => </b><span style="color:blue;">' . self::printArr($val) . '</span></li>';
            } else {
                $html .= '<li><span style="color:red;">' . (string) $key . '</span><b> => </b><span style="color:blue;">' . $val . '</span></li>';
            }
        }
        return $html . '</ul>';
    }

    /**
     * Display the stars rating for an entity
     *
     * @param int $rating The number of stars to display
     * @return string HTML of the stars
     */
    public static function showStars(int $rating): string
    {
        $green = "<i style='color:#54aa08' class='fas fa-star' title='☻'></i>";
        $gray = "<i style='color:gray' class='fas fa-star' title='☺'></i>";

        return str_repeat($green, $rating) . str_repeat($gray, 5 - $rating);
    }

    public static function getIdFilterSql(array $idArr): string
    {
        if (!empty($idArr)) {
            return ' AND entity.id IN (' . implode(',', $idArr) . ')';
        }
        return ' AND entity.id IN (0)';
    }

    /**
     * Process the metadata json string into a displayable array
     */
    public static function formatMetadata(string $json): string
    {
        $final = '';
        $full = json_decode($json, true);
        $extraFields = $full['extra_fields'];
        foreach ($extraFields as $key => $value) {
            $final .= '<h4>' . $key . '</h4><p>' . $value['value'] . '</p>';
        }
        return $final;
    }

    /**
     * Transform a query object in a query string
     *
     * @param array<string, mixed> $query the query array given by Request
     */
    public static function qFilter(array $query): string
    {
        $res = '';
        foreach ($query as $key => $value) {
            // tags for instance are arrays
            if ($key === 'tags') {
                foreach ($value as $tag) {
                    $res .= '&tags[]=' . $tag;
                }
            } else {
                $res .= '&' . (string) $key . '=' . $value;
            }
        }
        $output = filter_var($res, FILTER_SANITIZE_STRING);
        if ($output === false) {
            return '';
        }
        return $output;
    }
}
