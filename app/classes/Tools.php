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
namespace Elabftw\Elabftw;

use Symfony\Component\HttpFoundation\Request;

/**
 * Toolbelt full of useful functions
 */
class Tools
{
    /**
     * Return the current date as YYYYMMDD format if no input
     * return input if it is a valid date
     *
     * @param string|null $input 20160521
     * @return string
     */
    public static function kdate($input = null)
    {
        if (!is_null($input)
            && ((strlen($input) == '8'))
            && self::checkId($input)) {
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
    public static function displayMessage($message, $type, $cross = true)
    {
        $glyphicon = 'info-sign';
        $alert = 'success';

        if ($type === 'ko') {
            $glyphicon = 'exclamation-sign';
            $alert = 'danger';
        } elseif ($type === 'warning') {
            $glyphicon = 'chevron-right';
            $alert = $type;
        }

        $crossLink = '';

        if ($cross) {
            $crossLink = "<a href='#' class='close' data-dismiss='alert'>&times</a>";
        }

        $begin = "<div class='alert alert-" . $alert .
            "'><span class='glyphicon glyphicon-" . $glyphicon .
            "' aria-hidden='true'></span>";
        $end = "</div>";

        return $begin . $crossLink . ' ' . $message . $end;
    }

    /**
     * Sanitize title with a filter_var and remove the line breaks.
     *
     * @param string $input The title to sanitize
     * @return string Will return Untitled if there is no input.
     */
    public static function checkTitle($input)
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
    public static function checkBody($input)
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
    public static function md2html($md)
    {
        return \Michelf\Markdown::defaultTransform($md);
    }

    /**
     * Converts the php.ini upload size setting to a numeric value in MB
     * Returns 2 if no value is found (using the default setting that was in there previously)
     * It also checks for the post_max_size value and return the lowest value
     *
     * @return int maximum size in MB of files allowed for upload
     */
    public static function returnMaxUploadSize()
    {
        $max_size = trim(ini_get('upload_max_filesize'));
        $post_max_size = trim(ini_get('post_max_size'));

        if (empty($max_size) || empty($post_max_size)) {
            return 2;
        }

        // assume they both have same unit to compare the values
        if (intval($post_max_size) > intval($max_size)) {
            $input = $max_size;
        } else {
            $input = $post_max_size;
        }

        // get unit
        $unit = strtolower($input[strlen($input) - 1]);

        // convert to Mb
        switch ($unit) {
            case 'g':
                $input *= 1000;
                break;
            case 'k':
                $input /= 1024;
                break;
            case 'm':
                break;
            default:
                return 2;
        }

        return intval($input);
    }

    /**
     * Show the units in human format from bytes.
     *
     * @param int $bytes size in bytes
     * @return string
     */
    public static function formatBytes($bytes)
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
        } else {
            return 'That is a very big file you have there my friend.';
        }
    }

    /**
     * Take a 8 digits input and output 2014.08.16
     *
     * @param string $date Input date '20140302'
     * @param string $s an optionnal param to specify the separator
     * @return false|string The formatted string
     */
    public static function formatDate($date, $s = '.')
    {
        if (strlen($date) != 8) {
            return false;
        }
        return $date[0] . $date[1] . $date[2] . $date[3] . $s . $date[4] . $date[5] . $s . $date[6] . $date[7];
    }

    /**
     * Get the extension of a file.
     *
     * @param string $filename path of the file
     * @return string file extension
     */
    public static function getExt($filename)
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
     * @return int $id if pos int
     */
    public static function checkId($id)
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
    public static function error($permission = false)
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
    public static function getCalendarLang($lang)
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
            'zh_CN' => 'zh-cn'
        );
        return $map[$lang];
    }

    /**
     * Get an associative array for the langs to display in a select
     *
     * @return array<string,string>
     */
    public static function getLangsArr()
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
    public static function printArr($arr)
    {
        $html = '<ul>';
        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                if (is_array($val)) {
                    $html .= '<li><span style="color:red;">' . $key . '</span><b> => </b><span style="color:blue;">' . self::printArr($val) . '</span></li>';
                } else {
                    $html .= '<li><span style="color:red;">' . $key . '</span><b> => </b><span style="color:blue;">' . $val . '</span></li>';
                }
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
    public static function showStars($rating)
    {
        $green = "<img src='app/img/star-green.png' alt='☻' />";
        $gray = "<img src='app/img/star-gray.png' alt='☺' />";

        return str_repeat($green, $rating) . str_repeat($gray, (5 - $rating));
    }

    /**
     * This is used to include the title in the page name (see #324)
     * It removes #, ' and " and appends "- eLabFTW"
     *
     * @param $title string
     * @return string
     */
    public static function getCleanTitle($title)
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
    public static function getUrl($Request)
    {
        $Config = new Config();
        if (strlen($Config->configArr['url']) > 10) {
            return $Config->configArr['url'];
        }
        return self::getUrlFromRequest($Request);
    }

    /**
     * Get the URL from the Request
     *
     * @param Request $Request
     * @return string the url
     */
    public static function getUrlFromRequest($Request)
    {
        return 'https://' . $Request->getHost() . ':' . $Request->getPort() . $Request->getBasePath();
    }

}
