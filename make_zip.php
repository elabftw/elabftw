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
require_once 'inc/common.php';
$page_title = _('Make zip archive');
$selected_menu = null;
require_once ELAB_ROOT . 'inc/head.php';
require_once ELAB_ROOT . 'inc/info_box.php';

try {
    $makezip = new \Elabftw\Elabftw\MakeZip($_GET['id'], $_GET['type'], $connector);
} catch (Exception $e) {
    display_message('error', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}

// PAGE BEGIN
echo "<div class='well' style='margin-top:20px'>";
// Display download link (with attribute type=zip for download.php)
echo "<p>" . _('Your ZIP archive is ready:') . "<br>
    <img src='img/download.png' alt='download' /> 
    <a href='app/download.php?f=". basename($makezip->getZipRelativePath()) . "&name=" . $makezip->getZipName() . "&type=zip' target='_blank'>" . $makezip->getZipName() . "</a>
    <span class='filesize'>(". (new \Elabftw\Elabftw\Tools)->formatBytes(filesize($makezip->getZipRelativePath())) . ")</span></p>";
echo "</div>";

require_once 'inc/footer.php';
