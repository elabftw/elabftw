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
$page_title = ngettext('Experiment', 'Experiments', 2);
$selected_menu = 'Experiments';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
if (isset($_SESSION) && $_SESSION['prefs']['chem_editor']) {
    ?>
    <link rel="stylesheet" href="css/chemdoodle.css" type="text/css">
    <script src="js/chemdoodle.js"></script>
    <script src="js/chemdoodle-uis.js"></script>
    <script>
        ChemDoodle.iChemLabs.useHTTPS();
    </script>
    <?php
}

// MAIN SWITCH
if (!isset($_GET['mode']) || (empty($_GET['mode'])) || ($_GET['mode'] === 'show')) {
    require_once 'inc/showXP.php';
} elseif ($_GET['mode'] === 'view') {
    require_once 'inc/viewXP.php';
} elseif ($_GET['mode'] === 'edit') {
    require_once 'inc/editXP.php';
} else {
    printf(_("There was an unexpected problem! Please %sopen an issue on GitHub%s if you think this is a bug."), "<a href='https://github.com/elabftw/elabftw/issues/'>", "</a>");
}

require_once 'inc/footer.php';
