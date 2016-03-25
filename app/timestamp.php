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
namespace Elabftw\Elabftw;

use \Exception;

require_once '../inc/common.php';

// ID
if (isset($_GET['id']) && !empty($_GET['id']) && Tools::checkId($_GET['id'])) {
    $id = $_GET['id'];
} else {
    display_message('ko', _("The id parameter is not valid!"));
    require_once '../inc/footer.php';
    exit;
}

// timestamping begins
try {
    $ts = new TrustedTimestamps($id);
    $ts->timeStamp();
} catch (Exception $e) {
    $msg_arr = array();
    $msg_arr[] = $e->getMessage();
    $_SESSION['ko'] = $msg_arr;
}

// redirect
header("Location: ../experiments.php?mode=view&id=" . $id);
