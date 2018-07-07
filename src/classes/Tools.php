<?php
/**
 * \Elabftw\Elabftw\Tools
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Toolbelt full of useful functions
 */
class Tools
{
    /** @var int DEFAULT_UPLOAD_SIZE max size of uploaded file if we cannot find in in ini file */
    private const DEFAULT_UPLOAD_SIZE = 2;

    /**
     * Return the current date as YYYYMMDD format if no input
     * return input if it is a valid date
     *
     * @param string|null $input 20160521
     * @return string
     */
    public static function kdate($input = null): string
    {
        if ($input !== null
            && \mb_strlen($input) == '8') {
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
            $crossLink = "<a href='#' class='close' data-dismiss='alert'>&times</a>";
        }

        $begin = "<div class='alert alert-" . $alert .
            "'><i class='fas " . $icon .
            "'></i>";
        $end = "</div>";

        return $begin . $crossLink . ' ' . $message . $end;
    }

    /**
     * Sanitize title with a filter_var and remove the line breaks.
     *
     * @param string $input The title to sanitize
     * @return string Will return Untitled if there is no input.
     */
    public static function checkTitle(string $input): string
    {
        if (empty($input)) {
            return _('Untitled');
        }
        $title = filter_var($input, FILTER_SANITIZE_STRING);
        // remove linebreak to avoid problem in javascript link list generation on editXP
        return str_replace(array("\r\n", "\n", "\r"), ' ', $title);
    }

    /**
     * Sanitize body with a white list of allowed html tags.
     *
     * @param string $input Body to sanitize
     * @return string The sanitized body or empty string if there is no input
     */
    public static function checkBody(string $input): string
    {
        $whitelist = "<div><br><br /><p><sub><img><sup><strong><b><em><u><a><s><font><span><ul><li><ol>
            <blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><td><code><video><audio><pagebreak><pre>
            <details><summary>";
        return strip_tags($input, $whitelist);
    }

    /**
     * Convert markdown to html
     *
     * @param string $md Markdown code
     * @return string HTML code
     */
    public static function md2html(string $md): string
    {
        return \Michelf\Markdown::defaultTransform($md);
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
        $max_size = trim(ini_get('upload_max_filesize'));
        $post_max_size = trim(ini_get('post_max_size'));

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
        $unit = strtolower($input[\mb_strlen($input) - 1]);
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

        return $value;
    }

    /**
     * Show the units in human format from bytes.
     *
     * @param int $bytes size in bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        // nice display of filesize
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KiB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MiB';
        } elseif ($bytes < 1099511627776) {
            return round($bytes / 1073741824, 2) . ' GiB';
        } elseif ($bytes < 1125899906842624) {
            return round($bytes / 1099511627776, 2) . ' TiB';
        }
        return 'That is a very big file you have there my friend.';
    }

    /**
     * Take a 8 digits input and output 2014.08.16
     *
     * @param string $date Input date '20140302'
     * @param string $s an optionnal param to specify the separator
     * @throws InvalidArgumentException
     * @return string The formatted string
     */
    public static function formatDate(string $date, string $s = '.'): string
    {
        if (\mb_strlen($date) != 8) {
            throw new InvalidArgumentException('Date has wrong size!');
        }
        return $date[0] . $date[1] . $date[2] . $date[3] . $s . $date[4] . $date[5] . $s . $date[6] . $date[7];
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
        $path_info = pathinfo($filename);
        // if no extension
        if (!empty($path_info['extension'])) {
            return $path_info['extension'];
        }

        return 'unknown';
    }

    /**
     * Check ID is valid (pos int)
     *
     * @param int $id
     * @return int|false $id if pos int
     */
    public static function checkId(int $id)
    {
        $filter_options = array(
            'options' => array(
                'min_range' => 1
            ));
        return filter_var($id, FILTER_VALIDATE_INT, $filter_options);
    }

    /**
     * Display a generic error message
     *
     * @param bool $permission show the out of reach message for permission message
     * @return string
     */
    public static function error(bool $permission = false): string
    {
        if ($permission) {
            return _("This section is out of your reach!");
        }
        return _("An error occured!");
    }

    /**
     * Return a lang to use with fullcalendar from the pref
     *
     * @param string $lang 'pt_BR' or 'fr_FR'
     * @return string
     */
    public static function getCalendarLang(string $lang): string
    {
        $map = array(
            'ca_ES' => 'ca',
            'de_DE' => 'de',
            'en_GB' => 'en',
            'es_ES' => 'es',
            'fr_FR' => 'fr',
            'it_IT' => 'it',
            'pl_PL' => 'pl',
            'pt_BR' => 'pt-br',
            'pt_PT' => 'pt',
            'ru_RU' => 'ru',
            'sl_SI' => 'sl',
            'sk_SK' => 'sk',
            'zh_CN' => 'zh-cn'
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
        $langs = array(
            'ca_ES' => 'Spanish (Catalan)',
            'de_DE' => 'German',
            'en_GB' => 'English (UK)',
            'es_ES' => 'Spanish',
            'fr_FR' => 'French',
            'it_IT' => 'Italian',
            'pl_PL' => 'Polish',
            'pt_BR' => 'Portuguese (Brazilian)',
            'pt_PT' => 'Portuguese',
            'ru_RU' => 'Russian',
            'sl_SI' => 'Slovenian',
            'sk_SK' => 'Slovak',
            'zh_CN' => 'Chinese Simplified'
        );

        return $langs;
    }

    /**
     * A better print_r()
     * Used for debugging only
     *
     * @param array $arr
     * @return string
     */
    public static function printArr(array $arr): string
    {
        $html = '<ul>';
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $html .= '<li><span style="color:red;">' . $key . '</span><b> => </b><span style="color:blue;">' . self::printArr($val) . '</span></li>';
            } else {
                $html .= '<li><span style="color:red;">' . $key . '</span><b> => </b><span style="color:blue;">' . $val . '</span></li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Display the stars rating for a DB item
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

    /**
     * This is used to include the title in the page name (see #324)
     * It removes #, ' and " and appends "- eLabFTW"
     *
     * @param string $title
     * @return string
     */
    public static function getCleanTitle(string $title): string
    {
        return str_replace(array('#', "&39;", "&34;"), '', $title) . " - eLabFTW";
    }

    /**
     * Return a full URL of the elabftw install.
     * Will first check for config value of 'url' or try to guess from Request
     *
     * @param Request $Request
     * @return string the url
     */
    public static function getUrl(Request $Request): string
    {
        $Config = new Config();

        return $Config->configArr['url'] ?? self::getUrlFromRequest($Request);
    }

    /**
     * Get the URL from the Request
     *
     * @param Request $Request
     * @return string the url
     */
    public static function getUrlFromRequest(Request $Request): string
    {
        return $Request->getScheme() . '://' . $Request->getHost() . ':' . $Request->getPort() . $Request->getBasePath();
    }

    /**
     * Get the correct class for icon from the extension
     *
     * @param string $ext Extension of the file
     * @return string Class of the fa icon
     */
    public static function getIconFromExtension(string $ext): string
    {
        switch ($ext) {
            // ARCHIVE
            case 'zip':
            case 'rar':
            case 'xz':
            case 'gz':
            case 'tgz':
            case '7z':
            case 'bz2':
            case 'tar':
                return 'fa-file-archive';

            // CODE
            case 'py':
            case 'jupyter':
            case 'js':
            case 'm':
            case 'r':
            case 'R':
                return 'fa-file-code';

            // EXCEL
            case 'xls':
            case 'xlsx':
            case 'ods':
            case 'csv':
                return 'fa-file-excel';

            // POWERPOINT
            case 'ppt':
            case 'pptx':
            case 'pps':
            case 'ppsx':
            case 'odp':
                return 'fa-file-powerpoint';

            // VIDEO
            case 'mov':
            case 'avi':
            case 'mp4':
            case 'wmv':
            case 'mpeg':
            case 'flv':
                return 'fa-file-video';

            // WORD
            case 'doc':
            case 'docx':
            case 'odt':
                return 'fa-file-word';

            default:
                return 'fa-file';
        }
    }
}
