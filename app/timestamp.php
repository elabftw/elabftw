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
require_once '../inc/common.php';

$crypto = new \Elabftw\Elabftw\Crypto();

// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    display_message('error', _("The id parameter is not valid!"));
    require_once '../inc/footer.php';
    exit;
}

// timestamping begins
try {
    $ts = new Elabftw\Elabftw\TrustedTimestamps($id, $connector);
    $ts->timeStamp();
} catch (Exception $e) {
    $_SESSION['errors'][] = $e->getMessage();
}

// if there was a problem during the timestamping, an error will be inside the $_SESSION['errors'] array
// and we want to stop there if that is the case.
if (is_array($_SESSION['errors'])) {
    header("Location: ../experiments.php?mode=view&id=" . $id);
    exit;
}

// redirect
header("Location: ../experiments.php?mode=view&id=" . $id);
