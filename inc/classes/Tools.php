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

use \Elabftw\Elabftw\Db;

class Tools
{
    private $pdo;

    public function __construct()
    {
        $db = new \Elabftw\Elabftw\Db();
        $this->pdo = $db->connect();
    }

    /**
     * Converts the php.ini upload size setting to a numeric value in MB
     * Returns 2 if no value is found (using the default setting that was in there previously)
     * @return int maximum size in MB of files allowed for upload
    */
    public function returnMaxUploadSize()
    {
        $max_size = trim(ini_get('upload_max_filesize'));

        if (!isset($max_size)) {
            return 2;
        }

        $unit = strtolower($max_size[strlen($max_size) - 1]);

        // convert to Mb
        switch ($unit) {
            case 'g':
                $max_size *= 1000;
                break;
            case 'k':
                $max_size /= 1024;
                break;
        }

        // check that post_max_size is greater than upload_max_filesize
        // if not, use this value
        $post_max_size = trim(ini_get('post_max_size'));

        if (!isset($post_max_size)) {
            return 2;
        }

        $unit = strtolower($post_max_size[strlen($post_max_size) - 1]);

        // convert to Mb
        switch ($unit) {
            case 'g':
                $post_max_size *= 1000;
                break;
            case 'k':
                $post_max_size /= 1024;
                break;
        }

        if (intval($post_max_size) > intval($max_size)) {
            return intval($max_size);
        } else {
            return intval($post_max_size);
        }
    }

    /**
     * Show the units in human format from bytes.
     *
     * @param int $a_bytes size in bytes
     * @return string
     */
    public function formatBytes($a_bytes)
    {
        // nice display of filesize
        if ($a_bytes < 1024) {
            return $a_bytes . ' B';
        } elseif ($a_bytes < 1048576) {
            return round($a_bytes / 1024, 2) . ' KiB';
        } elseif ($a_bytes < 1073741824) {
            return round($a_bytes / 1048576, 2) . ' MiB';
        } elseif ($a_bytes < 1099511627776) {
            return round($a_bytes / 1073741824, 2) . ' GiB';
        } elseif ($a_bytes < 1125899906842624) {
            return round($a_bytes / 1099511627776, 2) . ' TiB';
        } else {
            return 'That is a very big file you have there my friend.';
        }
    }

    /**
     * Get the extension of a file.
     *
     * @param string $filename path of the file
     * @return string file extension
     */
    public function getExt($filename)
    {
        // Get file extension
        $path_info = pathinfo($filename);
        // if no extension
        if (!empty($path_info['extension'])) {
            return $path_info['extension'];
        }

        return 'unknown';
    }
}
