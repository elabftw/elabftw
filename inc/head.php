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
<?php echo "<title>".(isset($page_title)?$page_title:"Lab manager")." - eLabFTW</title>\n"?>
<meta name="author" content="Nicolas CARPi" />
<link rel="stylesheet" media="all" href="css/main.css" />
<?php
if (isset($_SESSION['auth']) && $_SESSION['auth'] === 1){
echo "<link id='maincss' rel='stylesheet' media='all' href='themes/".$_SESSION['prefs']['theme']."/style.css' />";
} else {
echo "<link id='maincss' rel='stylesheet' media='all' href='themes/default/style.css' />";
}
?>
<link rel="stylesheet" media="all" href="css/tagcloud.css" />
<script src="js/prototype.js" type="text/javascript"></script>
<script src="js/scriptaculous.js?load=effects" type="text/javascript"></script>
<script type='text/javascript' src='js/keymaster.js'></script>
<link rel="icon" type="image/ico" href="img/favicon.ico" />
</head>

<body>
<?php // Page generation time
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
?>
<section id="container">
