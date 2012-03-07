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
<script type="text/javascript">
function mouseOver()
{document.php.src ="img/phpon.gif";}
function mouseOut()
{document.php.src ="img/phpoff.gif";}
function mouseOver2()
{document.mysql.src ="img/mysqlon.gif";}
function mouseOut2()
{document.mysql.src ="img/mysqloff.gif";}
function mouseOver3()
{document.css.src ="img/csson.gif";}
function mouseOut3()
{document.css.src ="img/cssoff.gif";}
</script>
<p>
<?php
$ini_arr = parse_ini_file('admin/config.ini');
echo $ini_arr['lab_name']." powered by <a href='http://www.elabftw.net'>eLabFTW</a> by <a href='http://www.elabftw.net'>Nicolas CARPi</a></p>
<figure><a href='http://www.php.net'><img id='php' onmouseover='mouseOver()' onmouseout='mouseOut()' class='img' src='img/phpoff.gif' /></a>
<a href='http://www.mysql.com'><img id='mysql' onmouseover='mouseOver2()' onmouseout='mouseOut2()' class='img' src='img/mysqloff.gif' /></a>
<a href='http://jigsaw.w3.org/css-validator/check/referer'><img id='css' onmouseover='mouseOver3()' onmouseout='mouseOut3()' class='img' src='img/cssoff.gif' /></a></figure>";
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
</section>
<script src="js/jquery.pageslide.min.js" type="text/javascript"></script>
<?php
echo "<script type='text/javascript'>
key('".$_SESSION['prefs']['shortcuts']['todo']."', function(){
    $.pageslide({href:'todolist.php'});
    });";
?>
</script>
</body>
</html>
