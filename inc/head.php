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
<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<link rel="icon" type="image/ico" href="img/favicon.ico" />
<?php
// Random title
$ftw_arr = array();
// Lots of 'For The World' so the other ones appear more rarely
for($i=0; $i<200;$i++){
$ftw_arr[] = 'For The World';
}
// Now the fun ones
$ftw_arr[] = 'For Those Wondering';
$ftw_arr[] = 'For The Worms';
$ftw_arr[] = 'Forever Two Wheels';
$ftw_arr[] = 'Free The Wookies';
$ftw_arr[] = 'Forward The Word';
$ftw_arr[] = 'Forever Together Whenever';
$ftw_arr[] = 'Face The World';
$ftw_arr[] = 'Forget The World';
$ftw_arr[] = 'Free To Watch';
$ftw_arr[] = 'Feed The World';
$ftw_arr[] = 'Feel The Wind';
$ftw_arr[] = 'Feel The Wrath';
$ftw_arr[] = 'Fight To Win';
$ftw_arr[] = 'Find The Waldo';
$ftw_arr[] = 'Finding The Way';
$ftw_arr[] = 'Flying Training Wing';
$ftw_arr[] = 'Follow The Way';
$ftw_arr[] = 'For The Wii';
$ftw_arr[] = 'For The Win';
$ftw_arr[] = 'For The Wolf';
$ftw_arr[] = 'Free The Weed';
$ftw_arr[] = 'Free The Whales';
$ftw_arr[] = 'From The Wilderness';
$ftw_arr[] = 'Freedom To Work';
$ftw_arr[] = 'For The Warriors';
$ftw_arr[] = 'Full Time Workers';
$ftw_arr[] = 'Fabricated To Win';
$ftw_arr[] = 'Furiously Taunted Wookies';
$ftw_arr[] = 'Flash The Watch';
shuffle($ftw_arr);
$ftw = $ftw_arr[0]; 

echo "<title>".(isset($page_title)?$page_title:"Lab manager")." - eLab ".$ftw."</title>"?>
<meta name="author" content="Nicolas CARPi" />
<!-- CSS -->
<link rel="stylesheet" media="all" href="css/main.css" />
<?php
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1){
echo "<link id='maincss' rel='stylesheet' media='all' href='themes/".$_SESSION['prefs']['theme']."/style.css' />";
} else {
echo "<link id='maincss' rel='stylesheet' media='all' href='themes/default/style.css' />";
}
?>
<link rel="stylesheet" media="all" href="css/jquery-ui.css" />
<link rel="stylesheet" media="all" href="css/tagcloud.css" />
<link rel="stylesheet" media="all" href="css/pageslide.css" />
<link rel="stylesheet" media="all" href="css/jquery.rating.css" />
<link rel="stylesheet" media="all" href="css/jquery.lightbox-0.5.css" />
<!-- JAVASCRIPT -->
<script src="js/jquery-1.7.2.min.js"></script>
<script src="js/jquery-ui-1.8.18.custom.min.js"></script>
<!-- for editable comments -->
<script src="js/jquery.jeditable.min.js"></script>
<!-- for keyboard shortcuts -->
<script src='js/keymaster.min.js'></script>
<!-- for stars rating -->
<script src='js/jquery.rating.js'></script>
</head>

<body>
<?php // Page generation time
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
?>
<section id="container">
