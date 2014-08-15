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
echo "<p>".get_team_config('team_name')." powered by <a href='http://www.elabftw.net'>eLabFTW</a> by <a href='http://www.elabftw.net' onClick='cornify_add();return false;'>Nicolas CARPi</a></p>";
?>
<figure><a href='http://www.php.net'><img id='php' onmouseover="mouseOverPhp('on')" onmouseout="mouseOverPhp('off')" class='img' src='img/phpoff.gif' /></a>
<a href='http://www.mysql.com'><img id='mysql' onmouseover="mouseOverSql('on')" onmouseout="mouseOverSql('off')" class='img' src='img/mysqloff.gif' /></a>
<a href='http://jigsaw.w3.org/css-validator/check/referer'><img id='css' onmouseover="mouseOverCss('on')" onmouseout="mouseOverCss('off')" class='img' src='img/cssoff.gif' /></a></figure>
<?php
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$finish = $time;
$total_time = round(($finish - $start), 4);
echo "Page generated in ".$total_time." seconds.<br />";
// show debug info only to admins
if (isset($_SESSION['auth']) && get_config('debug') == 1 && $_SESSION['is_admin'] == 1) {
    echo "Session array : ";
    echo '<pre>'.var_dump($_SESSION).'</pre>';
    echo "<br />";
    echo "Cookie : ";
    echo '<pre>'.var_dump($_COOKIE).'</pre>';
    echo "<br />";
}
?>
</footer>
<script src="bower_components/jquery-pageslide/jquery.pageslide.min.js"></script>
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

