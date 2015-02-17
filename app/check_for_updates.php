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
/* this file is called with ajax post javascript from "Check for updates" link in footer
 * It will return a string with the error/status.
 */
require_once '../inc/common.php';
require_once ELAB_ROOT.'inc/locale.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /* before we do the check, we need to make sure :
     * 1. git exists on the system
     * 2. curl extension is installed
     */
    // check if git exists on the system
    if (!check_executable('git')) {
        echo _('Install git to check for updates.');
        exit;
    }

    // check that curl extension is installed and loaded
    if (!extension_loaded('curl')) {
        echo _('You need to install the curl extension for php.');
        exit;
    }

    // all is good, go !
    // get what is the latest commit on master branch
    // we use curl and not git ls-remote to be able to input proxy settings
    $ch = curl_init();
    // get what is the current branch
    $current_branch = shell_exec('git symbolic-ref --short -q HEAD');
    // we remove the end of the line character
    $current_branch = preg_replace("/\r|\n/", "", $current_branch);

    if ($current_branch == 'master') {
        // for branch master
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/elabftw/elabftw/git/refs/heads/master");
    } elseif ($current_branch == 'next') {
        // for branch next
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/elabftw/elabftw/git/refs/heads/next");
    } else {
        echo _('Unknown branch!');
        exit();
    }
    // this is to get content
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // add proxy if there is one
    if (strlen(get_config('proxy')) > 0) {
        curl_setopt($ch, CURLOPT_PROXY, get_config('proxy'));
    }
    // options to verify the github certificate (we disable check)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // add user agent
    // http://developer.github.com/v3/#user-agent-required
    curl_setopt($ch, CURLOPT_USERAGENT, "elabftw");

    // add a timeout, because if you need proxy, but don't have it, it will mess up things
    // 5 seconds
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,5); 

    // get the json data and put in an array
    $result = json_decode(curl_exec($ch), true);
    // free resources
    curl_close($ch);

    // sha1sum of the latest commit on branch master on github.com
    $latest_version = $result['object']['sha'];
    // get curent version from local system
    $current_version = exec("git log -1 --format='%H'");
    if (get_config('debug') == 1) {
        echo "latest : ".$latest_version."\n";
        echo "current : ".$current_version."\n";
    }
    // do the check and display message if both versions differ
    // we check also the size of latest version, or we get the message if it couldn't connect
    if (strlen($latest_version) != 40) { // couldn't connect
        echo _('Install git to check for updates.');
        exit;
    }
    if ($latest_version != $current_version) {
        echo _('A new update is available!');
        exit;
    }
    if ($latest_version == $current_version) {
    // sha1 are the same
        if ($current_branch == 'master') {
            echo _('Congratulations! You are running the latest stable version of eLabFTW :');
            exit;
        } else { // for branch next
            echo _('Congratulations! You are running the latest development version of eLabFTW :');
            exit;
        }
    }
}
