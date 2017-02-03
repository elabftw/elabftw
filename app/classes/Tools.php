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

use Exception;

/**
 * Toolbelt full of useful functions
 */
class Tools
{
    /**
     * Return the date as YYYYMMDD format if no input
     * return input if it is valid
     *
     * @param string|null $input 20160521
     * @return string
     */
    public static function kdate($input = null)
    {
        // Check DATE (is != null ? is 8 in length ? is int ? is valable ?)
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
     * For displaying messages using jquery ui highlight/error messages
     *
     * @param string $message The message to display
     * @param string $type Can be 'ok', 'ko' or 'warning', with or without _nocross
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
            <blockquote><h1><h2><h3><h4><h5><h6><hr><table><tr><td><code><video><audio><pagebreak>";
        return strip_tags($input, $whitelist);
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
        return $date[0] . $date[1] . $date[2] . $date[3] . $s . $date['4'] . $date['5'] . $s . $date['6'] . $date['7'];
    }

    /**
     * Put firstname lowercase and first letter uppercase
     *
     * @param string $firstname
     * @return string
     */
    public static function purifyFirstname($firstname)
    {
        return ucwords(strtolower(filter_var($firstname, FILTER_SANITIZE_STRING)));
    }

    /**
     * Put lastname in capital letters
     *
     * @param string $lastname
     * @return string
     */
    public static function purifyLastname($lastname)
    {
        return strtoupper(filter_var($lastname, FILTER_SANITIZE_STRING));
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
     * Used in login.php, login-exec.php and install/index.php
     * This is needed in the case you run an http server but people are connecting
     * through haproxy with ssl, with a http_x_forwarded_proto header.
     *
     * @return bool
     */
    public static function usingSsl()
    {
        return ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'));
    }

    /**
     * Return a string 5+3+6 when fed an array
     *
     * @param array $array
     * @param string $delim An optionnal delimiter
     * @return false|string
     */
    public static function buildStringFromArray($array, $delim = '+')
    {
        $string = "";

        if (!is_array($array)) {
            return false;
        }

        foreach ($array as $i) {
            $string .= $i . $delim;
        }
        // remove last delimiter
        return rtrim($string, $delim);
    }

    /**
     * Check ID is valid (pos int)
     *
     * @param int $id
     * @throws Exception if input is not valid
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
     * Return a value for the signification of FTW
     *
     * @return string
     */
    public static function getFtw()
    {
        $ftwArr = array();
        // Lots of 'For The World' so the other ones appear more rarely
        for ($i = 0; $i < 200; $i++) {
            $ftwArr[] = 'For The World';
        }
        // Now the fun ones
        $ftwArr[] = 'For Those Wondering';
        $ftwArr[] = 'For The Worms';
        $ftwArr[] = 'Forever Two Wheels';
        $ftwArr[] = 'Free The Wookies';
        $ftwArr[] = 'Forward The Word';
        $ftwArr[] = 'Forever Together Whenever';
        $ftwArr[] = 'Face The World';
        $ftwArr[] = 'Forget The World';
        $ftwArr[] = 'Free To Watch';
        $ftwArr[] = 'Feed The World';
        $ftwArr[] = 'Feel The Wind';
        $ftwArr[] = 'Feel The Wrath';
        $ftwArr[] = 'Fight To Win';
        $ftwArr[] = 'Find The Waldo';
        $ftwArr[] = 'Finding The Way';
        $ftwArr[] = 'Flying Training Wing';
        $ftwArr[] = 'Follow The Way';
        $ftwArr[] = 'For The Wii';
        $ftwArr[] = 'For The Win';
        $ftwArr[] = 'For The Wolf';
        $ftwArr[] = 'Free The Weed';
        $ftwArr[] = 'Free The Whales';
        $ftwArr[] = 'From The Wilderness';
        $ftwArr[] = 'Freedom To Work';
        $ftwArr[] = 'For The Warriors';
        $ftwArr[] = 'Full Time Workers';
        $ftwArr[] = 'Fabricated To Win';
        $ftwArr[] = 'Furiously Taunted Wookies';
        $ftwArr[] = 'Find The Wally';

        shuffle($ftwArr);

        return $ftwArr[0];
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
     * Used when generating options for select menus
     *
     * @param string $getParam
     * @param string $value
     * @return string|null
     */
    public static function addSelected($getParam, $value)
    {
        if ($getParam === $value) {
            return " selected";
        }
    }
}
