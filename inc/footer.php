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
<script>
function mouseOverPhp(action){
if (action == 'on') {
    document.php.src ="img/phpon.gif";
} else {
document.php.src ="img/phpoff.gif";}
}
function mouseOverSql(action){
if (action == 'on') {
    document.mysql.src ="img/mysqlon.gif";
} else {
document.mysql.src ="img/mysqloff.gif";}
}
function mouseOverCss(action){
if (action == 'on') {
    document.css.src ="img/csson.gif";
} else {
document.css.src ="img/cssoff.gif";}
}
</script>
<script src="js/cornify.js"></script>

<p>
<?php
// because inc/common.php is not here whene not logged in
if (!isset($_SESSION['auth'])) {
$ini_arr = parse_ini_file('admin/config.ini');
}
echo $ini_arr['lab_name']." powered by <a href='http://www.elabftw.net'>eLabFTW</a> by <a href='http://www.elabftw.net' onClick='cornify_add();return false;'>Nicolas CARPi</a></p>";
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
?>
<section class='align_right'>
<?php
$version = parse_ini_file('admin/version.ini');
echo "Version : ".$version['version'];
?>
</section>
</footer>
<script src="js/jquery.pageslide.min.js"></script>
<?php
if (isset($_SESSION['auth'])){
echo "<script>
key('".$_SESSION['prefs']['shortcuts']['todo']."', function(){
    $.pageslide({href:'inc/todolist.php'});
});
</script>";
}
?>
</body>
</html>
