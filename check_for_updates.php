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
// this file is called with ajax post javascript from "Check for updates" link in Admin menu in inc/menu.php

function check_executable($cmd) {
    return shell_exec("which $cmd");
}

if (isset($_POST)) {
    // check if a new update is available
    // return a string with an error/info message

    // before we do the check, we need to make sure :
    // 1. git exists on the system
    // 2. curl extension is installed
    // 3. proxy setting exists

    // check if git exists on the system 
    if (!check_executable('git')) {
        echo "Install git to check for updates.";
        exit();
    }

    // check that curl extension is installed and loaded
    if (!extension_loaded('curl')) {
        echo "You need to install the curl extension for php.";
        exit();
    }
    // check if the proxy settings exists
    $ini_arr = parse_ini_file('admin/config.ini');
    if (!isset($ini_arr['proxy'])) {
        echo "Proxy setting not present. Check the wiki on github to solve this.";
        exit();
    }

    // all is good, go !
    // get what is the latest commit on master branch
    // we use curl and not git ls-remote to be able to input proxy settings
    $ch = curl_init();
    // get what is the current branch
    $current_branch = shell_exec('git symbolic-ref --short -q HEAD');
    if ($current_branch == 'master') {
    // for branch master
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/NicolasCARPi/elabftw/git/refs/heads/master");
    } else {
    // for branch next
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/NicolasCARPi/elabftw/git/refs/heads/next");
    }
    // this is to get content
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // add proxy if there is one
    if (strlen($ini_arr['proxy']) > 0) {
        curl_setopt($ch, CURLOPT_PROXY, $ini_arr['proxy']);
    }
    // options to verify the github certificate
    // set to false by default, set it to true if you're paranoid
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CAPATH, $ini_arr['path']."ca_github.com.pem");
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // set a timeout of 500 millisecond
    //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);
    // removed because we don't actually need it and it might lead to fail

    // get the json data and put in an array
    $result = json_decode(curl_exec($ch), true);
    // free resources
    curl_close($ch);

    // sha1sum of the latest commit on branch master on github.com
    $latest_version = $result['object']['sha'];
    // get curent version from local system
    $current_version = exec("git log -1 --format='%H'");
    /*
    echo "latest : ".$latest_version."<br />";
    echo "current : ".$current_version;
     */
    // do the check and display message if both versions differ
    // we check also the size of latest version, or we get the message if it couldn't connect
    if (strlen($latest_version) != 40) { // couldn't connect
        echo "Couldn't connect to github.com to check for updates.";
        exit();
    }
    if ($latest_version != $current_version) {
        echo "A new update is available !";
        exit();
    } 
    if ($latest_version == $current_version) {
    // sha1 are the same
    echo "Congratulations ! You are running the latest stable version of eLabFTW :)";
    exit();
    }
}

