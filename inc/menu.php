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
$ini_arr = parse_ini_file('admin/config.ini');
?>
<noscript><!-- show warning if javascript is disabled -->
<ul class="errors">
<li><img src="img/info.png" alt="" />
Javascript is disabled. Please enable Javascript to view this site in all its glory. Thank You.</li>
</ul>
</noscript>

<div id='logo'>
<img src='img/logo-169.png' alt='elabftw' />
</div>

<div id="logmenu"><p>
<?php
if (isset($_SESSION['auth']) && $_SESSION['is_admin'] === '1') {
    // check if a new update is available
    // but before, check if git exists on the system 
    if (check_executable('git')) {
        // get what is the latest commit on master branch
        // we use curl and not git ls-remote to be able to input proxy settings
        $ch = curl_init();
        // set url
        // for branch next
        //curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/NicolasCARPi/elabftw/git/refs/heads/next");
        // for branch master
        curl_setopt($ch, CURLOPT_URL, "https://api.github.com/repos/NicolasCARPi/elabftw/git/refs/heads/master");
        // this is to get content
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // check if the proxy settings exists
        if (!isset($ini_arr['proxy'])) {
            echo "<a class='errors' href='https://github.com/NicolasCARPi/elabftw/wiki/Troubleshooting#manual-intervention-needed'>This update needs a manual intervention. Click this message.</a>";
        } else {
            // add proxy if there is one
            if(strlen($ini_arr['proxy']) > 0) {
                curl_setopt($ch, CURLOPT_PROXY, $ini_arr['proxy']);
            }
            // options to verify the github certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAPATH, $ini_arr['path']."ca_github.com.pem");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            // set a timeout of 500 millisecond
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 500);

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
            if(strlen($latest_version) == 40 && $latest_version != $current_version) {
                echo "<a class='errors' href='https://github.com/NicolasCARPi/elabftw#updating'>Update available !</a> ";
            }
        }
    } // end if git command exists

    // show admin panel link
    echo "<a href='admin.php'>Admin Panel</a> | ";
    }
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    echo "Logged in as <a href='profile.php' title='Profile'>".$_SESSION['username']."</a> | 
        <a href='ucp.php'><img src='themes/".$_SESSION['prefs']['theme']."/img/pref.png' alt='Control panel' title='Control panel' /></a> | 
        <a href='logout.php'><img src='themes/".$_SESSION['prefs']['theme']."/img/logout.png' alt='' title='Logout' /></a></p>";
} else {
    echo "<a href='login.php'>Not logged in !</a>";
}
?>
</div>

<nav><a href="experiments.php?mode=show">Experiments</a>
<a href="database.php?mode=show">Database</a>
<a href="team.php">Team</a>
<a href="search.php">Search</a>
<a href="<?php echo $ini_arr['link_href'];?>" target='_blank'><?php echo $ini_arr['link_name'];?></a>
</nav>
<hr class='flourishes'>
<!-- TITLE -->
<div id='page_title'>
<h2><?php echo strtoupper($page_title);?></h2>
</div>
<?php
if ($ini_arr['debug'] == 1) {
    echo "Session array : ";
    print_r($_SESSION);
    echo "<br />";
}
?>
