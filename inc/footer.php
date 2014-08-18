<?php
/******************************************************************************
*   Copyright 2012 Nicolas CARPi
*   This file is part of eLabFTW. 
*
*    eLabFTW is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    eLabFTW is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.
*
********************************************************************************/
?>
<footer>
<!-- konami code and unicorns -->
<script src="js/cornify.min.js"></script>

<p>
<?php
if (isset($_SESSION['auth']) && $_SESSION['is_sysadmin'] === '1') {
    ?>
    <!-- SYSADMIN MENU --> 
    <a id='check_for_updates' href='#'>Check for updates</a> | <a href='sysconfig.php'>Sysadmin panel</a> | 
    <script>
    $('#check_for_updates').click(function() {
        var jqxhr = $.post('check_for_updates.php', function(answer) {
            alert(answer);
        });
    });
    </script>
<?php
}
if (isset($_SESSION['auth']) && $_SESSION['is_admin'] === '1') {
    echo "<a href='admin.php'>Admin Panel</a>";
}
echo "<p>";
if (isset($_SESSION['auth']) && isset($_SESSION['team_id'])) {
    echo "<a href='".get_team_config('link_href')." target='_blank'>".get_team_config('link_name')."</a> | ";
    echo get_team_config('team_name')." ";
}

echo "powered by <a href='http://www.elabftw.net'>eLabFTW</a> by <a href='http://www.elabftw.net' onClick='cornify_add();return false;'>Nicolas CARPi</a></p>";
echo "Page generated in ".round((microtime(true) - $start), 5)." seconds";
// show debug info only to admins
if (isset($_SESSION['auth']) && get_config('debug') == 1 && $_SESSION['is_admin'] == 1) {
    echo "Session array : ";
    echo '<pre>'.var_dump($_SESSION).'</pre>';
    echo "<br>";
    echo "Cookie : ";
    echo '<pre>'.var_dump($_COOKIE).'</pre>';
    echo "<br>";
}
?>
<br>
<a href='https://twitter.com/elabftw'>
<img src='img/twitter.png' alt='twitter' title='Follow eLabFTW on Twitter !'>
</a>
 <a href='https://github.com/NicolasCARPi/elabftw'>
<img src='img/github.png' alt='github' title='eLabFTW on GitHub'>
</a>
</p>
</footer>
<script src="js/jquery-pageslide/jquery.pageslide.min.js"></script>
<?php
if (isset($_SESSION['auth'])) {
    // show TODOlist
    echo "<script>
    key('".$_SESSION['prefs']['shortcuts']['todo']."', function(){
        $.pageslide({href:'inc/todolist.php'});
    });
    </script>";
}
?>
</body>
</html>

