<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
namespace Elabftw\Elabftw;

use \Exception;

class Update
{
    public $version;
    const URL = 'https://get.elabftw.net/updates.ini';
    // ///////////////////////////////
    // UPDATE THIS AFTER RELEASING
    const INSTALLED_VERSION = '1.1.4-p3';
    // ///////////////////////////////

    /*
     * Constructor will get what is the latest version available from URL
     *
     */
    public function __construct()
    {
        $this->getUpdatesIni();
        if (!$this->validateVersion()) {
            throw new Exception('Error getting latest version information from server!');
        }
    }
    /*
     * Make a get request with cURL, using proxy setting if any
     * @param string $url URL to hit
     * @return string|boolean Return true if the download succeeded, else false
     */
    private function get($url)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('Please install php5-curl package.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // this is to get content
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // add proxy if there is one
        if (strlen(get_config('proxy')) > 0) {
            curl_setopt($ch, CURLOPT_PROXY, get_config('proxy'));
        }
        // disable certificate check
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // add user agent
        // http://developer.github.com/v3/#user-agent-required
        curl_setopt($ch, CURLOPT_USERAGENT, "Elabftw/" . self::INSTALLED_VERSION);

        // add a timeout, because if you need proxy, but don't have it, it will mess up things
        // 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        // we don't want the header
        curl_setopt($ch, CURLOPT_HEADER, 0);

        // DO IT!
        return curl_exec($ch);
    }


    /*
     * Return the latest version of elabftw
     * Will fetch updates.ini file from elabftw.net
     *
     * @return string|bool|null latest version or false if error
     */
    private function getUpdatesIni()
    {
        $ini = self::get(self::URL);
        // convert ini into array. The `true` is for process_sections: to get multidimensionnal array.
        $versions = parse_ini_string($ini, true);
        // get the latest version (first item in array, an array itself with url and checksum)
        $this->version = array_keys($versions)[0];
    }

    /*
     * Check if the version string actually looks like a version
     *
     * @return int 1 if version match
     */
    private function validateVersion()
    {
        return preg_match('/[0-99]+\.[0-99]+\.[0-99]+.*/', $this->version);
    }

    /*
     * Return true if there is a new version out there
     *
     * @return bool
     */
    public function updateIsAvailable()
    {
        return self::INSTALLED_VERSION != $this->version;
    }

    /*
     * Return the latest version string
     *
     * @return string|int 1.1.4
     */
    public function getLatestVersion()
    {
        return $this->version;
    }

    /*
     * Return the latest version of elabftw using GitHub API
     * This function is unused but let's keep it.
     *
     * @return string|bool latest version or false if error
    private function getLatestVersionFromGitHub()
    {
        $url = 'https://api.github.com/repos/elabftw/elabftw/releases/latest';
        $res = get($url);
        $latest_arr = json_decode($res, true);
        return $latest_arr['tag_name'];
    }
     */
}
