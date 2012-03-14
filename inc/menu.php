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
?>
<noscript><!-- show warning if javascript is disabled -->
<ul class="err">
<li><img src="img/info.png" alt="" />
Javascript is disabled. Please enable Javascript to view this site in all it's glory. Thank You.</li>
</ul>
</noscript>
<div id="logmenu"><p>
<?php
if (isset($_SESSION['auth']) && $_SESSION['is_admin'] === '1') {
    echo "<a href='admin.php'>Admin Panel</a> | ";
    }
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1) {
    echo "Logged in as <a href='profile.php' title='Profile'>".$_SESSION['username']."</a> | 
        <a href='ucp.php'><img src='themes/".$_SESSION['prefs']['theme']."/img/pref.png' alt='Control panel' title='Control panel' /></a> | 
        <a href='logout.php'><img src='themes/".$_SESSION['prefs']['theme']."/img/logout.png' alt='' title='Logout' /></p>";
} else {
    echo "Not logged in | <a href='register.php'>Register</a></p>";
}
?>
</div>
<h1><a href="index.php"><span id='eblue'>e</span><span id='lab'>Lab</span><span id='ftw'>FTW</span></a></h1>
<nav><a href="experiments.php?mode=show">Experiments</a>
<a href="protocols.php?mode=show">Protocols</a>
<!--a href="journal-club.php">Journal Club</a-->
<a href="team.php">Team</a>
<a href="http://labcollector.curie.fr/144_Piel" target='_blank'>LabCollector</a>
<a href="http://wiki-bio6.curie.fr" target='_blank'>Wiki</a>
</nav>
<?php // print_r($_SESSION); ?>
