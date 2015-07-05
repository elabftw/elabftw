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

class Update
{
    public $version;

    public function __construct()
    {
        $this->getUpdatesIni();
    }
    /*
     * Return the latest version of elabftw
     * Will fetch updates.ini file from elabftw.net
     *
     * @return string|bool latest version or false if error
     */
    private function getUpdatesIni()
    {
        $url = 'https://get.elabftw.net/updates.ini';
        $ini = curlDownload($url);
        // convert ini into array. The `true` is for process_sections: to get multidimensionnal array.
        $versions = parse_ini_string($ini, true);
        // get the latest version (first item in array, an array itself with url and checksum)
        $this->version = array_keys($versions)[0];
    }

    /*
     * Return true if there is a new version out there
     *
     * @return bool
     */
    public function availableUpdate()
    {
        return VERSION != $this->version;
    }

    /*
     * Return the latest version string
     *
     * @return string 1.1.4
     */
    public function getLatestVersion()
    {
        return $this->version;
    }

    /*
     * Return the latest version of elabftw using GitHub API
     *
     * @return string|bool latest version or false if error
     */
    private function getLatestVersionFromGitHub()
    {
        $url = 'https://api.github.com/repos/elabftw/elabftw/releases/latest';
        $res = curlDownload($url);
        $latest_arr = json_decode($res, true);
        return $latest_arr['tag_name'];
    }
}
