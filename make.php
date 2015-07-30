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
$page_title = _('Export');
$selected_menu = null;
require_once 'inc/head.php';

try {
    switch ($_GET['what']) {
        case 'csv':
            $make = new \Elabftw\Elabftw\MakeCsv($_GET['id'], $_GET['type']);
            break;

        case 'zip':
            $make = new \Elabftw\Elabftw\MakeZip($_GET['id'], $_GET['type']);
            break;
        default:
            throw new Exception(_('Bad type!'));
    }
} catch (Exception $e) {
    display_message('error', $e->getMessage());
    require_once 'inc/footer.php';
    exit;
}

// PAGE BEGIN
echo "<div class='well' style='margin-top:20px'>";
echo "<p>" . _('Your file is ready:') . "<br>
        <a href='app/download.php?type=" . $_GET['what'] . "&f=" . $make->fileName . "&name=" . $make->getCleanName() . "' target='_blank'>
        <img src='img/download.png' alt='download' /> " . $make->getCleanName() . "</a>
        <span class='filesize'>(" . \Elabftw\Elabftw\Tools::formatBytes(filesize($make->filePath)) . ")</span></p>";
echo "</div>";
require_once 'inc/footer.php';
