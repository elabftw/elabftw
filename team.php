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
require_once('inc/auth.php');
$ini_arr = parse_ini_file('admin/config.ini');
$page_title= $ini_arr['lab_name'];
require_once('inc/head.php');
require_once('inc/menu.php');
echo "<h2>".strtoupper($ini_arr['lab_name'])."</h2>";
?>
<section class='item'>
<h3>LABMEETINGS</h3>
<p><a href='http://wiki-bio6.curie.fr/wiki/index.php/Piel_Lab_inner_working#Lab_meetings' target='_blank'>Relevant wiki link</a></p>
<p class='center'><img src='img/labmeetings-2012.png' alt='labmeetings' title='labmeetings 2012' /></p>
</section>

<?php
require_once('inc/journal_club.php');
?>
<?php
require_once('inc/footer.php');
?>
